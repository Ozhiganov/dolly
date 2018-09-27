<?php
trait TPages
{
	use \TEvents;

	final protected function Delete($tbl_name, $id, $on_delete)
	 {
		$this->DispatchEvent('page:before_delete', false, ['id' => $id]);
		if($on_delete) call_user_func($on_delete, $id);
		$count = 0;
		$children = \DB::Select($tbl_name, 'id', '`parent_id` = ?', [$id]);
		if(count($children)) foreach($children as $p) $count += $this->Delete($tbl_name, $p->id, $on_delete);
		return $count += \DB::Delete($tbl_name, '`id` = ?', [$id]);
	 }

	final protected function MoveToTrash($tbl_name, $id)
	 {
		$this->DispatchEvent('page:before_remove', false, ['id' => $id]);
		$count = 0;
		$children = \DB::Select($tbl_name, 'id', '`parent_id` = ?', [$id]);
		if(count($children)) foreach($children as $child) $count += $this->MoveToTrash($tbl_name, $child->id);
		return $count += \DB::Update($tbl_name, ['hidden' => 2, '~id' => $id], '`id` = :id');
	 }

	final protected function Restore($tbl_name, $id)
	 {
		$this->DispatchEvent('page:before_restore', false, ['id' => $id]);
		return \DB::Update($tbl_name, ['hidden' => 0, '~id' => $id], '(`id` = :id) AND (`hidden` >= 2)');
	 }

	final protected function SetOrder($tbl_name, array $order)
	 {
		foreach($order as $ids)
		 {
			$ids = array_filter(explode('|', $ids), 'is_numeric');
			$position = count($ids);
			foreach($ids as $id) \DB::UpdateById($tbl_name, ['position' => $position--], $id);
		 }
		self::SendJSON(null);
	 }

	final protected function GetPUrlFragment(array $data, $tbl_name)
	 {
		if($data['parent_id'])
		 {
			$p = \DB::GetRowById($tbl_name, $data['parent_id'], '`sid`');
			if($p && $p->sid) return "$p->sid/";
		 }
	 }

	final protected function MakePageSID(array $data, $tbl_name, \stdClass $status)
	 {
		$status->error = false;
		$sid = '' === $data['url_part'] ? null : $this->GetPUrlFragment($data, $tbl_name).$data['url_part'];
		if(null === $sid) $status->error = 'no_sid';
		elseif(\DB::ValueExists($tbl_name, 'sid', $sid)) $status->error = 'sid_exists';
		if($status->error)
		 {
			$str = '';
			foreach($data as $k => $v) $str .= $k.mt_rand(10, 9999999).$v;
			return md5($str.time());
		 }
		else return $sid;
	 }

	final protected function RegenerateSID($sid, $id, $tbl_name, &$suffix)
	 {
		$suffix = '';
		$i = 1;
		$new_sid = $sid;
		while(\DB::ValueExists($tbl_name, 'sid', $new_sid, $id ? '`id` <> :id' : false, $id ? ['id' => $id] : null))
		 {
			$suffix = '-'.$i++;
			$new_sid = "$sid$suffix";
		 }
		return $new_sid;
	 }

	final protected function ConfigFormForAdding(Form $form)
	 {
		$status = new \stdClass();
		$form->BindToEvent('before_insert', function(\EventData $d) use($status){
			$tbl_name = $d->form->GetTblName();
			if($status->set_homepage = !empty($d->all_data['set_homepage']))
			 {
				$this->RemoveHomepage($tbl_name);
				$d->data['sid'] = $d->data['url_part'] = '';
			 }
			else $d->data['sid'] = $this->MakePageSID($d->data, $tbl_name, $status);
			if(!$d->form->FieldExists('created_at')) $d->data['created_at'] = \DB::Now();
		});
		$form->BindToEvent('after_insert', function(\EventData $d) use($status){
			$base = \MSConfig::GetMSSMDir().'/pages';
			if($status->set_homepage) $d->status_msg = "<a href='$base/?page_id=$d->id'>Главная страница</a> создана.";
			else
			 {
				if($status->error)
				 {
					$sid = $this->GetPUrlFragment($d->data, $d->form->GetTblName()).$d->id;
					$new_sid = $this->RegenerateSID($sid, $d->id, $d->form->GetTblName(), $suffix);
					$data = ['sid' => $new_sid, 'url_part' => $d->id, '~id' => $d->id];
					if($sid !== $new_sid) $data['url_part'] .= $suffix;
					\DB::Update($d->form->GetTblName(), $data, '`id` = :id');
					$msg = ", новый URL присвоен автоматически (<a href='$base/settings-1/?page_id=$d->id'>редактировать</a>).";
					switch($status->error)
					 {
						case 'sid_exists': $this->AddWarningMsg("Указанный вами URL уже существует$msg"); break;
						case 'no_sid': $this->AddWarningMsg("Не указан URL$msg"); break;
					 }
				 }
				$d->status_msg = "<a href='$base/?page_id=$d->id'>Страница</a> создана.";
			 }
		});
	 }

	final protected function ConfigFormForUpdating(Form $form)
	 {
		$status = new \stdClass;
		$form->BindToEvent('before_update', function(\EventData $d) use($status){
			$tbl_name = $d->form->GetTblName();
			$page = \DB::GetRowById($tbl_name, $d->id, '`id`, `sid`, `parent_id`, `url_part`');
			if(!$page) throw new \Exception('Page not found!');
			if(!empty($d->all_data['set_homepage']))
			 {
				$this->RemoveHomepage($tbl_name);
				$d->data['parent_id'] = null;
				$d->data['url_part'] = '';
				if($page->sid)
				 {
					$d->data['sid'] = '';
					$status->changed = true;
					return;
				 }
			 }
			$status->changed = !($d->data['parent_id'] == $page->parent_id && $d->data['url_part'] === $page->url_part);
		});
		$form->BindToEvent('after_update', function(\EventData $d) use($status){
			if($status->changed)
			 {
				$update_row = function($id, \stdClass $row, \stdClass $parent = null, $tbl_name) use(&$update_row){
					if(null === $parent && isset($row->sid) && '' === $row->sid) ;// нужна ли здесь проверка???
					else $row->sid = ($parent && $parent->sid ? "$parent->sid/" : '').($row->url_part ?: $row->id);
					$new_sid = $this->RegenerateSID($row->sid, $row->id, $tbl_name, $suffix);
					$data = ['sid' => $new_sid, '~id' => $id];
					if($row->sid !== $new_sid)
					 {
						$data['url_part'] = "$row->url_part$suffix";
						$this->AddWarningMsg("URL уже существует, новый URL присвоен автоматически (<a href='".\MSConfig::GetMSSMDir()."/pages/settings-1/?page_id=$id'>редактировать</a>).");
					 }
					\DB::Update($tbl_name, $data, '`id` = :id');
					$res = \DB::Select($tbl_name, '`id`, `url_part`, `parent_id`', '`parent_id` = ?', [$id]);
					if(count($res)) foreach($res as $ch) $update_row($ch->id, $ch, $row, $tbl_name);
				};
				$row = (object)$d->data;
				$row->id = $d->id;
				$tbl_name = $d->form->GetTblName();
				$update_row($d->id, $row, $row->parent_id && ($p = \DB::GetRowById($tbl_name, $row->parent_id, '`sid`')) ? $p : null, $tbl_name);
			 }
		});
	 }

	final protected function RemoveHomepage($tbl_name)
	 {
		if($homepage = \DB::GetRowByKeyLJ(['page' => [$tbl_name, 'id'], 'parent' => [$tbl_name, 'sid', '`page`.`parent_id` = `parent`.`id`']], 'sid', ''))
		 {
			$sid = $url_part = "$homepage->id";
			if($homepage->parent__sid) $sid = "$homepage->parent__sid/$sid";
			$sid = $this->RegenerateSID($sid, $homepage->id, $tbl_name, $suffix);
			\DB::Update($tbl_name, ['sid' => $sid, 'url_part' => "$url_part$suffix", '~id' => $homepage->id], '`id` = :id');
		 }
	 }
}
?>
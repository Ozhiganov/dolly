<?php
class MSContactInfo extends MSDocument
{
	final public function __construct($tbl_name, $group_tbl_name = false, $junction_tbl_name = false)
	 {
		$this->tbl_name = $tbl_name;
		$this->group_tbl_name = $group_tbl_name;
		$this->junction_tbl_name = $junction_tbl_name;
	 }

	final public function Show()
	 {
		$this->AddJS('lib.msdndmanager', 'lib.mscontactinfo', 'lib.multiselect3')->AddCSS('lib.mscontactinfo', 'lib.multiselect3');
		if($this->UseGroups())
		 {
			$fc = 'groups';
			$g_res = DB::SelectLJ([
						'group' => [$this->group_tbl_name, '*'],
						'joint' => [$this->junction_tbl_name, '', '`joint`.`group_id` = `group`.`id`']
					], 'COUNT(`joint`.`info_id`) AS `num_of_items`', false, null, '`group`.`title` ASC', ['group_by' => '`group`.`id`'])->SetCallback(function(stdClass $row){ $row->title .= " ($row->num_of_items)"; });
			$hdr = (new Select($g_res, 'id', 'title', null, ['class' => 'msui_select mscontacts_show_group']))->SetDefaultOption('', 'Показывать все группы')->Make();
			$g_res->SetCallback(null);
		 }
		else
		 {
			$fc = 'default';
			$g_res = null;
			$hdr = '';
		 }
		$html = '';
		$dbc = clone DB();
		foreach($dbc->Select($this->tbl_name, '*', false, null, SQLExpr::MSContactInfoOrderBy()) as $row) $html .= $this->MakeRow($row, $g_res);
		echo $hdr.ui::Form('class', "form mscontacts _$fc")->SetMiddle("<div class='mscontacts__list'>$html</div>".html::Button('class', 'msui_small_button _icon _add mscontacts__add _field', 'value', 'Добавить поле').html::Button('class', 'msui_small_button _icon _add mscontacts__add _header', 'value', 'Добавить заголовок'))->SetBottom('<input type="hidden" name="__mssm_action" value="save_all" />'.ui::Submit('value', 'Сохранить'));
		$row = (object)['id' => 0, 'title' => '', 'value' => null, 'type_id' => 'title', 'position' => 0];
?><div class="mscontacts__prototype"><?php
		echo $this->MakeRow($row, $g_res);
		$row->type_id = null;
		echo $this->MakeRow($row, $g_res);
?></div><?php
	 }

	final public function Handle()
	 {
		switch($this->ActionPOST())
		 {
			case 'save_all':
				$stats = ['ins' => 0, 'upd' => 0, 'del' => 0];
				$pos = 0;
				if(empty($_POST['id'])) $stats['del'] = DB::Delete($this->tbl_name, false);
				elseif(is_array($_POST['id']))
				 {
					if($ids = array_filter($_POST['id'], 'is_numeric')) $stats['del'] = DB::Delete($this->tbl_name, '`id` NOT IN ('.implode(', ', $ids).')');
					MSConfig::RequireFile('msdb.sql');
					foreach($_POST['id'] as $key => $id)
					 {
						$data = ['title' => trim($_POST['title'][$key]), 'position' => ++$pos];
						if(isset($_POST['value'][$key]))
						 {
							$v = trim($_POST['value'][$key]);
							switch($type_id = Filter::InEnum(@$_POST['type_id'][$key], 'value', 'title', 'email', 'phone_num', 'url', 'skype', 'icq'))
							 {
								case 'url': $v = Format::AsUrl($v, ['field' => 'href']); break;
								case 'skype': $v = Format::AsSkype($v, ['field' => 'href']); break;
							 }
						 }
						else
						 {
							$type_id = 'title';
							$v = null;
						 }
						$data['value'] = $v;
						$data['type_id'] = $type_id;
						if($update = ($id && is_numeric($id)))
						 {
							$data['~id'] = $id;
							if(DB::Update($this->tbl_name, $data, '`id` = :id'))
							 {
								++$stats['upd'];
								$updated = true;
							 }
							else $updated = false;
						 }
						else
						 {
							$id = DB::Insert($this->tbl_name, $data);
							++$stats['ins'];
						 }
						if(!empty($_POST['group_id']))
						 {
							$g_updated = false;
							$p = ['info_id' => $id];
							if(empty($_POST['group_id'][$key]))
							 {
								if(DB::Delete($this->junction_tbl_name, '`info_id` = :info_id', $p)) $g_updated = true;
							 }
							else
							 {
								$ids = [];
								foreach($_POST['group_id'][$key] as $g_id => $g_st)
								 {
									if($g_st)
									 {
										if(DB::Replace($this->junction_tbl_name, ['info_id' => $id, 'group_id' => $g_id])) $g_updated = true;
									 }
									else $ids[$g_id] = $g_id;
								 }
								if($ids)
								 {
									if(DB::Delete($this->junction_tbl_name, new \MSDB\SQL\IN($ids, ['indexes' => 'to_string', 'expr' => '`info_id` = :info_id AND `group_id`'], $p), $p)) $g_updated = true;
								 }
							 }
							if($update && !$updated && $g_updated) ++$stats['upd'];
						 }
					 }
				 }
				$this->AddSuccessMsgStats($stats);
				break;
			case '':
				
				break;
			default:
		 }
	 }

	final protected function MakeRow(stdClass $row, IDBResult $g_res = null)
	 {
		if(null === $g_res) $s_groups = '';
		else
		 {
			MSConfig::RequireFile('multiselect3');
			$i_groups = new MultiSelect3($g_res, 'id', 'title');
			$res = DB::Select($this->junction_tbl_name, '`group_id`', '`info_id` = ?', [$row->id]);
			if($ids = $res->FetchAllFields()) $i_groups->SetSelected($ids);
			$s_groups = $i_groups->SetClassName('mscontacts__groups')->SetName("group_id[$row->position]")->Make();
			$s_groups = "<div class='multiselect3_wr'>$s_groups</div>";
		 }
		$i_title = html::text('name', "title[$row->position]", 'value', $row->title, 'class', 'msui_input mscontacts__title', 'maxlength', 255, 'required', 'title' === $row->type_id);
		if('title' === $row->type_id)
		 {
			$rc = 'header';
			$html = '';
		 }
		else
		 {
			$rc = 'value';
			$i_value = html::text('name', "value[$row->position]", 'value', $row->value, 'class', 'msui_input mscontacts__value', 'maxlength', 255, 'required', true);
			if($row->type_id) $i_value->SetData('type_id', $row->type_id);
			$html = "$i_value<input type='hidden' name='type_id[$row->position]' value='$row->type_id' />";
		 }
		return "<div class='form__row _to_delete _$rc'>$i_title$html$s_groups{$this->MakeActions($row)}</div>";
	 }

	final protected function MakeActions(stdClass $row) { return "<input type='hidden' name='id[$row->position]' value='$row->id' />".ui::DeleteBlock('_mscontacts').ui::DragRow('mscontacts__action _move'); }
	final protected function UseGroups() { return (bool)$this->group_tbl_name; }

	private $tbl_name;
	private $group_tbl_name;
	private $junction_tbl_name;
}
?>
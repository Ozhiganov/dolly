<?php
MSConfig::RequireFile('events');

class MSEditPairs extends MSDocument
{
	use TEvents;

	final public function __construct($tbl_name)
	 {
		$this->tbl_name = $tbl_name;
	 }

	final public function Show()
	 {
		$this->AddCSS('lib.mseditpairs')->AddJS('lib.mseditpairs');
		$res = DB::Select($this->tbl_name, '*', false, null, '`id` ASC');
		$cols = DB::GetColMeta($this->tbl_name);
		$html = '<div class="edit_pairs__row"><span class="edit_pairs__header _id">Идентификатор</span><span class="edit_pairs__header _title">Название</span></div>';
		$flds = ['id' => ['pattern' => "^[a-z0-9_-]{1,{$cols['id']->size}}$"], 'title' => ['pattern' => null]];
		$mk_name = function($f, $row){return "{$f}[$row->id]";};
		if(!$res->GetNumRows())
		 {
			$res = [(object)['id' => '', 'title' => '']];
			$mk_name = function($f){return "{$f}_new[]";};
		 }
		foreach($res as $row)
		 {
			$html .= '<div class="edit_pairs__row _to_delete">';
			foreach($flds as $f => $d) $html .= ui::Text('name', $mk_name($f, $row), 'value', $row->$f, 'required', true, 'maxlength', $cols[$f]->size, 'pattern', $d['pattern']);
			$edt = $this->DispatchEvent('row:on_create', false, ['row' => $row, 'html' => ''], ['html' => ['set' => true]]);
			$html .= $edt->html.ui::DeleteBlock().'</div>';
		 }
		$html .= '<div class="edit_pairs__row">'.html::Button('class', 'msui_small_button _icon _add smlinks__add', 'value', 'Добавить поле').'</div>';
		echo ui::Form()->SetMiddle($html)->SetBottom(ui::FAction('save_list').ui::Submit('value', 'Сохранить'));
	 }

	final public function Handle()
	 {
		switch($this->ActionPOST())
		 {
			case 'save_list': $this->HandleData(); break;
		 }
	 }

	final protected function HandleData()
	 {
		$stats = ['ins' => 0, 'upd' => 0, 'del' => 0];
		if(empty($_POST['id'])) $stats['del'] = DB::Delete($this->tbl_name, false);
		else
		 {
			MSConfig::RequireFile('msdb.sql');
			$stats['del'] = DB::Delete($this->tbl_name, new \MSDB\SQL\IN($_POST['id'], ['use_keys' => true, 'indexes' => 'to_string', 'expr' => '`id` NOT'], $p), $p);
			foreach($_POST['id'] as $id => $new_id)
			 {
				$data = ['title' => trim($_POST['title'][$id])];
				if(($new_id = trim($new_id)) && $id !== $new_id) $data['id'] = $new_id;
				if(DB::UpdateById($this->tbl_name, $data, $id)) ++$stats['upd'];
			 }
		 }
		if(!empty($_POST['id_new']))
		 {
			$values = [];
			foreach($_POST['id_new'] as $i => $id) if($id = trim($id)) $values[] = ['id' => $id, 'title' => trim($_POST['title_new'][$i])];
			if($values) $stats['ins'] += count(DB::Insert($this->tbl_name, $values));
		 }
		if(array_filter($stats))
		 {
			$labels = ['ins' => 'добавлено', 'upd' => 'обновлено', 'del' => 'удалено'];
			$msg = '';
			foreach($stats as $key => $value) if($value) $msg .= ($msg ? ', ' : '')."$labels[$key]: $value";
			$this->AddSuccessMsg("Изменения сохранены ($msg).");
		 }
	 }

	private $tbl_name;
}
?>
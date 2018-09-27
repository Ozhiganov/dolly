<?php
class DBTable extends MSDBTable
{
	final public static function Handle($rel_name, $before_delete = null, $after_delete = null)
	 {
		if(isset($_POST['delete'][$rel_name]) && !empty($_POST['ids']))
		 {
			if($ids = array_filter($_POST['ids'], 'is_numeric'))
			 {
				$key = DB::GetPrimaryKey($rel_name);
				if($before_delete) call_user_func($before_delete, $ids);
				$result = DB::Delete($rel_name, "$key ".(1 < count($ids) ? 'IN ('.implode(', ', $ids).')' : '= "'.reset($ids).'"'));
				if($after_delete) call_user_func($after_delete, $ids, $result);
				MSDocument::AddSuccessMsg('Информация удалена.');
			 }
		 }
	 }

	final public function EnableDeleting(array $options = null)
	 {
		$input_name = "delete[{$this->GetTblName()}]";
		$this->AddCol(empty($options['fld']) ? 'id' : $options['fld'], '<input type="checkbox" class="select_all" />', 3)->SetType('IdCheckBoxTCell')->SetInputName('ids[]')->SetClass('del');
		$this->ConfigInputGroup('ids[]', $input_name, 'MSDBTable.MarkForDeletion');
		$this->AddBtn(empty($options['btn_caption']) ? 'Удалить' : $options['btn_caption'], true)->SetClass(!empty($options['btn_class']) && ':remove' === $options['btn_class'] ? 'msui_small_button _icon _remove' : 'msui_small_button _icon _delete')->SetName($input_name)->SetClick('function(){return confirm("Удалить отмеченные элементы?");}');
		$this->SetAction('core.php');
		return $this;
	 }

	final public function EnableOrdering($add_col = true)
	 {
		ResourceManager::AddJS('lib.msdndmanager');
		if($add_col) $this->AddCol('move', '', 3)->SetClick('function(){}')->SetClass('msui_drag_row');
		$this->AddBtn('Сохранить порядок')->SetId($this->GetId().'_save_order')->SetClass('msui_small_button _order _icon _save')->SetName('save_order')->SetClick('MSDBTable.SaveOrder');
		$this->enable_ordering = true;
		return $this;
	 }

	final public function Make(MSDBTableStatus &$status_obj = null)
	 {
		$msg_empty = $this->GetEmptyContent();
		if(null === $msg_empty) $msg_empty .= ui::WarningMsg('В этот раздел ничего не загружено.');
		elseif(false === $msg_empty) $msg_empty = '';
		elseif(!$msg_empty) $msg_empty = null;
		else $msg_empty = ui::WarningMsg($msg_empty);
		$msg = '';
		if($this->GetTotalColWidth() != 100)
		 {
			$str = '';
			$sum = $col_count = 0;
			foreach($this->GetCols() as $col)
			 {
				$str .= ($str === '' ? '' : ' + ').$col->GetWidth();
				$sum += $col->GetWidth();
				++$col_count;
			 }
			$msg .= ui::WarningMsg('Суммарная ширина колонок не равна 100% ('.($col_count == 1 ? '' : $str.' = ').$sum.').');
		 }
		$this->SetEmptyContent($msg_empty);
		return parent::Make($status_obj).$msg;
	 }

	final public function SetRowClick($action = '""') { return $this->SetRowAction(null, $action); }

	final protected function OnShow()
	 {
		foreach($this->GetCols() as $col) if($w = $col->GetWidth()) MSDocument::AddCSSString("#{$this->GetId()} col.col_{$col->GetName()}{width:$w%;}");
	 }

	final protected function OnConfigJS(&$js_params)
	 {
		if($this->enable_ordering) $js_params[] = 'true';
	 }

	private $enable_ordering = false;
}
?>
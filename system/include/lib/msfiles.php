<?php
MSConfig::RequireFile('mstable', 'msdocs');

class FileTypeTCell extends MSTableCell
{
	public function Make($value, stdClass $row) { return "<td class='file_type' data-ext='$value'>$value</td>"; }
}

class MSFiles extends MSFilesDocument
{
	final public function __construct($tbl_name, $dir, array $options = null)
	 {
		$form = new Form($tbl_name, ['class' => '_msfiles', 'status_msgs' => ['inserted' => 'Файл загружен.', 'updated' => 'Данные обновлены.'], 'use_transaction' => true]);
		parent::__construct($tbl_name, $form, $dir, empty($options['order_by']) ? SQLExpr::MSFilesOrderBy() : $options['order_by'], !empty($options['order_by']), 'file', $options);
		$form->AddField('ext', 'Файл для загрузки <span class="max_upload_size">до '.Uploader::GetUploadMaxFileSize().'</span>', ['type' => 'File', 'host' => $this->GetHost(), 'dir' => $this->GetDir(), 'root' => $this->GetRoot()]);
		$form->AddField('title', 'Название');
		$this->AfterCreate($form);
		if($this->GetParentTblName()) $form->AddField('parent_id', $this->GetOption('pid_title'), $this->GetOption('pid_field'));
	 }

	final public function SetAccepted($list, $explode = false)
	 {
		$this->exts = $explode ? explode($explode, $list) : $list;
		return $this;
	 }

	final public function Handle()
	 {
		ms::UpdatePos($this->GetTblName(), (bool)$this->GetParentTblName(), null, $this->GetOrderBy());
		Form::Handle();
		DBTable::Handle($this->GetTblName(), [$this, 'DeleteFiles']);
	 }

	final protected function GetExts() { return $this->exts; }

	final protected function ShowPage(stdClass $parent = null)
	 {
		$form = $this->GetForm();
		$tbl = new DBTable('list', $this->GetTblName(), false, $parent ? "`parent_id` = :parent_id" : false, $parent ? ['parent_id' => $parent->id]: null, $this->GetOrderBy());
		if($parent)
		 {
			if(!Filter::NumFromGET('id')) $form->GetField('parent_id')->SetOption('value', $parent->id);
			$back = MSLoader::GetUrl(false).'?'.($this->GetOption('GET') ?: 'pid')."=$parent->id";
			$form->SetRedirectAndBack($back);
			$tbl->SetRedirect($back);
		 }
		print($form->Make());
		$this->AfterFormShow();
		$this->AddJS('lib.msfiles')->AddCSS('lib.msfiles');
		$tbl->EnableDeleting();
		$tbl->AddCol('title', 'Название', $this->HasCustomOrderBy() ? 79 : 76);
		$tbl->AddCol('ext', 'Тип', 8)->SetType('FileTypeTCell');
		$tbl->AddCol('size', 'Размер', 10)->SetCallback([$this, 'ShowFileSizeF']);
		if(!$this->HasCustomOrderBy()) $tbl->EnableOrdering();
		$tbl->SetRowClick();
		$tbl->SetPageLength(50);
		print($tbl->Make());
	 }

	private $exts = [];
}
?>
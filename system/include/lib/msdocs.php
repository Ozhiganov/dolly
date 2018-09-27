<?php
MSConfig::RequireFile('traits');

abstract class MSDocumentWithParent extends MSDocument
{
	use TOptions, TCallbacks;

	public function __construct($tbl_name, array $options = null)
	 {
		$this->tbl_name = $tbl_name;
		$this->AddOptionsMeta(['GET' => [], 'parent' => [], 'invalid_pid_msg' => []]);
		$this->SetOptionsData($options);
			if($p = $this->GetOption('parent'))
			 {
				if(is_array($p))
				 {
					$this->parent_tbl_name = $p[0];
					$this->parent_title_fld = $p[1];
					if(!empty($p[2])) $this->parent_attrs = $p[2];
				 }
				else
				 {
					$this->parent_tbl_name = $p;
					$this->parent_title_fld = '`title`';
				 }
			 }
	 }

	final public function GetParentTblName() { return $this->parent_tbl_name; }
	final public function GetTblName() { return $this->tbl_name; }
	final public function GetRow() { return $this->row; }

	public function Show()
	 {
		$parent = null;
		if($p_tbl_name = $this->GetParentTblName())
		 {
			$p_key = DB::GetPrimaryKey($p_tbl_name);
			$this->row = ($id = Filter::GetValidPageId('id')) ? DB::GetRowById($this->GetTblName(), $id) : false;
			$attrs = "$p_key AS `id`, {$this->GetParentTitleFld()} AS `title`";
			if($this->parent_attrs) $attrs .= ", $this->parent_attrs";
			$parent_id = $this->row ? $this->row->parent_id : Filter::GetValidPageId($this->GetOption('GET') ?: 'pid');
			if($nav = $this->GetNav()) $parent = $nav->GetItem($this);
			elseif($parent = DB::GetRowById($p_tbl_name, $parent_id, $attrs))
			 {
				// if($c = $this->GetOption('onshowtitle')) $parent['title'] = call_user_func($c, $parent);
				if(count(array_filter($this->GetDocTitle()))) $this->InsertTitleItem($parent->title, -1);
				else $this->SetTitle($parent->title);
			 }
			if(empty($parent)) throw new EDocument404($this->GetOption('invalid_pid_msg') ?: 'Неправильный идентификатор родительского раздела.');
		 }
		$this->BeforeShow($parent);
		$this->ShowPage($parent);
	 }

	abstract protected function ShowPage(stdClass $parent = null);

	protected function BeforeShow(stdClass $parent = null) {}

	final protected function GetParentTitleFld()
	 {
		// if(null === $this->parent_title_fld) $this->GetParentTblName();
		return $this->parent_title_fld;
	 }

	final protected function SetRow(stdClass $row)
	 {
		if(null === $this->row)
		 {
			$this->row = $row;
			return $this;
		 }
		else throw new Exception('Can not set the row!');
	 }

	private $row = null;
	private $tbl_name;
	private $parent_tbl_name = null;
	private $parent_title_fld = null;
	private $parent_attrs;
}

abstract class MSFilesDocument extends MSDocumentWithParent
{
	public function __construct($tbl_name, Form $form, $dir, $order_by, $custom_ordered, $prefix, array $options = null)
	 {
		$this->AddOptionsMeta(['after_create' => [], 'after_form_show' => [], 'on_delete' => [], 'on_delete_file' => [], 'on_handle' => [], 'pid_field' => ['type' => 'array', 'value' => ['type' => 'Hidden']], 'pid_title' => ['type' => 'string', 'value' => '']]);
		parent::__construct($tbl_name, $options);
		$this->form = $form;
		if(is_array($dir))
		 {
			$this->dir = $dir['dir'];
			$this->root = $dir['root'];
			$this->host = $dir['host'];
		 }
		else
		 {
			$this->dir = $dir;
			$this->root = $_SERVER['DOCUMENT_ROOT'];
		 }
		$this->order_by = $order_by;
		$this->custom_ordered = $custom_ordered;
		$this->prefix = $prefix;
	 }

	final public function GetForm() { return $this->form; }
	final public function ShowFileSizeF($v, $row) { return ms::GetFileSize($this->MakeFilePath($row->id, $row->ext)) ?: '---'; }
	final public function DeleteFiles(array $ids) { foreach($ids as $id) $this->DeleteFile($id); }

	final public function AddField(...$args)
	 {
		$this->GetForm()->AddField(...$args);
		return $this;
	 }

	final public function DeleteFile($id)
	 {
		$this->OnDeleteFile($this->MakeFilePath($id, false, false), $this->GetRoot());
		Uploader::UnlinkIgnoringExt($this->MakeFilePath($id));
	 }

	final public function DeleteByParentId($parent_id)
	 {
		$sql_cond = '`parent_id` = :parent_id';
		$params = ['parent_id' => $parent_id];
		$result = DB::Select($this->GetTblName(), '*', $sql_cond, $params);
		foreach($result as $row)
		 {
			$this->OnDelete($row->id, $row);
			if($row->ext) unlink($this->MakeFilePath($row->id, $row->ext));
		 }
		DB::Delete($this->GetTblName(), $sql_cond, $params);
	 }

	protected function OnDeleteFile($file_name, $root) { if($func = $this->GetOption('on_delete_file')) call_user_func($func, $file_name, $root); }
	protected function OnDelete($id, $item = null) { if($func = $this->GetOption('on_delete')) call_user_func($func, $id, $item); }
	protected function AfterFormShow() { if($func = $this->GetOption('after_form_show')) call_user_func($func, $this); }
	protected function AfterCreate(MSFieldSet $form) { if($func = $this->GetOption('after_create')) call_user_func($func, $this, $form); }
	protected function OnHandle() { if($func = $this->GetOption('on_handle')) if(false === call_user_func($func, $this)) return false; }

	final protected function GetFullPath(&$dir = null)
	 {
		$dir = $this->GetDir();
		return $this->GetRoot().$dir;
	 }

	final protected function MakeFilePath($id, $ext = false, $full = true, &$src = null)
	 {
		$n = '/'.$this->MakeFileName($id).'.'.($ext ?: '*');
		if($full)
		 {
			$p = $this->GetFullPath($dir);
			$src = $dir.$n;
			return $p.$n;
		 }
		else return ($src = $this->GetDir().$n);
	 }

	final protected function GetDir() { return $this->dir; }
	final protected function GetOrderBy() { return $this->order_by; }
	final protected function GetRoot() { return $this->root; }
	final protected function GetHost() { return $this->host; }
	final protected function HasCustomOrderBy() { return $this->custom_ordered; }
	final protected function MakeFileName($id) { return $this->prefix.'_'.$id; }

	private $dir;
	private $order_by;
	private $custom_ordered;
	private $root;
	private $host;
	private $prefix;
	private $form;
}
?>
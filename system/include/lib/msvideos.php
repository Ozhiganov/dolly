<?php
MSConfig::RequireFile('msdocs', 'form');

class MSVideoPreview extends \MSFieldSet\POSTField
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['has_preview' => []]);
		parent::__construct($owner, $name, $title, $options);
	 }

	public function MakeInput()
	 {
		return ($this->GetOption('has_preview') ? '<div class="_image_preview _main _hidden">
	<img alt="" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" />
	<input type="checkbox" name="i_src" id="video_i_src" />&nbsp;<label for="video_i_src">использовать это изображение</label>
</div>' : '').'<div class="_video_preview _main _hidden"></div>';
	 }

	public function PreProcess($value) { return ''; }
}

class MSVideos extends MSFilesDocument
{
	final public static function FixAttrs($code, &$count = null) { return preg_replace('/\b(allowfullscreen|webkitallowfullscreen|mozallowfullscreen)\b([^="])/i', '$1="true"$2', Filter::Tags($code, ['iframe', 'embed', 'object']), -1, $count); }

	final public function __construct($tbl_name = 'video', $image_dir = null, array $options = null)
	 {
		$form = new Form($tbl_name, ['class' => '_msvideo', 'status_msgs' => ['inserted' => 'Видео добавлено.', 'updated' => 'Данные обновлены.']]);
		parent::__construct($tbl_name, $form, $image_dir, empty($options['order_by']) ? SQLExpr::MSVideosOrderBy() : $options['order_by'], !empty($options['order_by']), 'image', $options);
		$form->AddField('_src', 'HTML-код для вставки (&lt;iframe&gt;, &lt;embed&gt;)', ['type' => 'Textarea', '__field' => 'code', 'required' => true]);
		$form->AddField('_video_preview', '', ['type' => '\MSVideoPreview', 'has_preview' => $this->HasPreview()]);
		if($this->HasPreview()) $form->AddField('ext', 'Изображение', ['type' => 'Image', 'host' => $this->GetHost(), 'dir' => $this->GetDir(), 'root' => $this->GetRoot(), 'max_size' => 800, 'set_size' => false]);
		$form->AddField('title', 'Заголовок', ['required' => true]);
		$form->AddField('date', 'Дата добавления');
		$form->AddField('code', '');
		$form->BindToEvent('after_insert', [$this, 'Load']);
		$form->BindToEvent('after_update', [$this, 'Load']);
		$this->AfterCreate($form);
		if($this->GetParentTblName()) $form->AddField('parent_id', $this->GetOption('pid_title'), $this->GetOption('pid_field'));
	 }

	final public function ShowPage(stdClass $parent = null)
	 {
		$this->AddJS('lib.msvideo', 'lib.msvideo_init')->AddCSS('lib.msvideo');
		$form = $this->GetForm();
		$back = MSLoader::GetUrl(false);
		if($parent)
		 {
			$back .= '?'.($this->GetOption('GET') ?: 'pid')."=$parent->id";
			if(!Filter::NumFromGET('id')) $form->GetField('parent_id')->SetOption('value', $parent->id);
			$cond = '`parent_id` = :parent_id';
			$params = ['parent_id' => $parent->id];
		 }
		else $cond = $params = null;
		$form->SetRedirectAndBack($back);
		print($form->Make());
		$this->AfterFormShow();
		$tbl = new DBTable('video_list', $this->GetTblName(), false, $cond, $params, $this->GetOrderBy());
		$tbl->EnableDeleting();
		$tbl->AddCol('title', 'Название', $this->HasCustomOrderBy() ? 97 : 94);
		$tbl->SetRowClick();
		if(!$this->HasCustomOrderBy()) $tbl->EnableOrdering();
		$tbl->SetPageLength(50);
		$tbl->SetRedirect($back);
		print($tbl->Make());
	 }

	final public function Handle()
	 {
		ms::UpdatePos($this->GetTblName(), (bool)$this->GetParentTblName(), null, $this->GetOrderBy());
		if(!empty($_POST['get_xml_file']))
		 {
			header('Content-Type: text/plain; charset=utf-8');
			try
			 {
				die(file_get_contents($_POST['get_xml_file']));
			 }
			catch(Exception $e)
			 {
				die('<data status="error"><message><![CDATA['.$e->GetMessage().']]></message></data>');
			 }
		 }
		else
		 {
			if($this->HasPreview()) DBTable::Handle($this->GetTblName(), [$this, 'DeleteFiles']);
			Form::Handle();
		 }
	 }

	final public function Load(EventData $evt)
	 {
		$attrs = ['code' => $this->FixAttrs($evt->data['code'], $count)];
		if(!empty($_POST['i_src']))
		 {
			$ext = ms::GetFileExt($_POST['i_src']);
			$dest = $this->MakeFilePath($evt->id, $ext);
			if(@copy($_POST['i_src'], $dest)) $attrs['ext'] = $ext;
			else $this->AddErrorMsg('Не удалось загрузить изображение.');
		 }
		DB::UpdateById($this->GetTblName(), $attrs, $evt->id);
	 }

	protected function OnDeleteFile($file_name, $root) { MSIcons::ClearCache($file_name, $root); }

	final protected function HasPreview() { return null !== $this->GetDir(); }
}
?>
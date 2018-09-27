<?php
class MSBannersPreview extends \MSForm\Info
{
	public function MakeInput()
	 {
		if(!($row = $this->GetFieldSet()->GetDBRow($id))) return null;
		$func = $this->GetOption('value');
		return $func($row);
	 }
}

class MSBannersType extends \MSFieldSet\POSTField
{
	public function MakeInput()
	 {
		$row = $this->GetFieldSet()->GetDBRow($id);
		return '<div class="msbanners_type"><label>'.html::Radio('name', $this->GetInputName(), 'value', 'flash', 'checked', !$row || !$row->ext || 'swf' === $row->ext).' Флэш</label> <label>'.html::Radio('name', $this->GetInputName(), 'value', 'image', 'checked', $row && $row->ext && 'swf' != $row->ext).' Изображение</label></div>';
	 }
}

class MSBanners extends MSDocument
{
	final public function __construct($tbl_name, $dir, array $options = null)
	 {
		$this->tbl_name = $tbl_name;
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
		$this->file_name = 'image_';
		if(empty($options['order_by'])) $this->order_by = SQLExpr::MSBannersOrderBy();
		else
		 {
			$this->order_by = $options['order_by'];
			$this->custom_order = true;
		 }
		$form = new Form($this->GetTblName());
		$show_bnr = function($row){
			$src = $this->GetFullFileName($row, false);
			switch($row->ext)
			 {
				case 'swf': $r = "<embed src='{$this->GetHost()}$src' width='352'></embed>";
							if($row->alt_ext) $r .= $this->MakeImgPreview($this->GetDir().'/'.$this->GetFileName().'alt_'.$row->id.'.'.$row->alt_ext, $row->url);
							return $r;
				case 'png':
				case 'gif':
				case 'jpg':
				case 'jpeg': return $this->MakeImgPreview($src, $row->url);
				default: return 'Не удалось создать превью.';
			 }
		};
		$form->AddField('preview', '', ['type' => '\MSBannersPreview', 'value' => $show_bnr]);
		$form->AddField('title', 'Название', ['required' => true]);
		$form->AddField('url', 'Ссылка');
		$form->AddField('type', '', ['type' => '\MSBannersType']);
		$form->AddField('banner', 'Баннер', ['type' => 'File']);
		$form->AddField('alt_image', 'Заглушка', ['type' => 'File']);
		$form->AddField('hidden', 'Не показывать');
		$form->BindToEvent('after_insert', function(EventData $d){
			try
			 {
				$this->LoadFiles($d, true);
			 }
			catch(EUploader $e)
			 {
				DB::Delete($this->GetTblName(), '`id` = ?', [$d->id]);
				DB::Exec('ALTER TABLE '.DB::TName($this->GetTblName())." AUTO_INCREMENT = $d->id");
				throw new EFSAction($e->GetMessage(), $e->GetCode(), $e->GetPrevious());
			 }
		});
		$form->BindToEvent('after_update', function(EventData $d){$this->LoadFiles($d, false);});
	 }

	final public function Show()
	 {
		$this->AddCSS('lib.msbanners')->AddJS('lib.msbanners');
		echo Form::Get($this->GetTblName())->Make(['Загрузить баннер', 'Редактировать баннер']);
		$tbl = new DBTable('list', $this->GetTblName(), false, false, null, $this->GetOrderBy());
		$tbl->EnableDeleting();
		$tbl->AddCol('title', 'Название', 40);
		$tbl->AddCol('hidden', '', 4)->SetCallback(function($val){if(!$val) return '<img src="/system/img/visible.png" width="16" height="16" alt="" />';});
		$tbl->AddCol('ext', '', 6);
		$tbl->AddCol('size', 'Размер', 11)->SetCallback(function($val, $row){ return ($img = @GetImageSize($this->GetFullFileName($row, true))) ? $img[0].' &#215; '.$img[1] : '&mdash;'; });
		$tbl->AddCol('url', 'Ссылка', $this->IsCustomOrdered() ? 36 : 33)->SetCallback(function($val){
			if($val)
			 {
				$val = Format::AsUrl($val);
				return "<a href='$val[href]'>$val[value]</a>";
			 }
		})->SetClick('EmptyFunc');
		if(!$this->IsCustomOrdered()) $tbl->EnableOrdering();
		$tbl->SetRedirect(MSLoader::GetUrl(false));
		$tbl->SetRowClick();
		print($tbl->Make());
	 }

	final public function Handle()
	 {
		ms::UpdatePos($this->GetTblName());
		DBTable::Handle($this->GetTblName(), function($ids){foreach($ids as $id) $this->DeleteBanner($id);});
		Form::Handle();
	 }

	final public function AddField(...$args)
	 {
		Form::Get($this->GetTblName())->AddField(...$args);
		return $this;
	 }

	final public function DeleteBanner($id)
	 {
		MSIcons::DeleteImage($this->GetDir().'/'.$this->GetFileName().$id.'.*', $this->GetRoot());
		$this->DeleteAlt($id);
	 }

	final protected function LoadFiles(EventData $d, $required)
	 {
		$c = ($is_flash = ('flash' === $d->all_data['type'])) ? 'FileUploader' : 'ImageUploader';
		$upl = new $c('banner', $this->GetDir(), $this->GetRoot());
		if($required) $upl->Required();
		if($is_flash)
		 {
			$attrs = [];
			$upl->SetAccepted('swf');
			$upl->EnableRewriting(true);
			if($file_name = $upl->LoadFile($this->GetFileName().$d->id))
			 {
				$wh = GetImageSize($file_name);
				$attrs['ext'] = ms::GetFileExt($file_name);
				$attrs['width'] = $wh[0];
				$attrs['height'] = $wh[1];
			 }
			$upl = new ImageUploader('alt_image', $this->GetDir(), $this->GetRoot());
			if($file_name = $upl->DBLoadFile($this->GetFileName().'alt_'.$d->id, $this->GetTblName(), $d->id, ['prefix' => 'alt_', 'data' => $attrs]));
		 }
		elseif($upl->DBLoadFile($this->GetFileName().$d->id, $this->GetTblName(), $d->id, ['data' => ['alt_ext' => null, 'alt_width' => null, 'alt_height' => null]])) $this->DeleteAlt($d->id);
	 }

	final protected function DeleteAlt($id) { Uploader::UnlinkIgnoringExt($this->GetFullPath().'/'.$this->GetFileName().'alt_'.$id); }
	final protected function GetTblName() { return $this->tbl_name; }
	final protected function GetDir() { return $this->dir; }
	final protected function GetFileName() { return $this->file_name; }
	final protected function GetRoot() { return $this->root; }
	final protected function GetHost() { return $this->host; }
	final protected function GetFullPath() { return $this->GetRoot().$this->GetDir(); }
	final protected function GetFullFileName(stdClass $row, $abs) { return ($abs ? $this->GetFullPath() : $this->GetDir()).'/'.$this->GetFileName().$row->id.'.'.$row->ext; }
	final protected function IsCustomOrdered() { return $this->custom_order; }
	final protected function GetOrderBy() { return $this->order_by; }

	final protected function MakeImgPreview($src, $url)
	 {
		$img = '<img alt="" src="'.$this->GetHost().'/f/w252/h250'.$src.'" />';
		return '<div class="msbanners_preview">'.($url ? '<a href="'.$url.'">'.$img.'</a>' : $img).'</div>';
	 }

	private $tbl_name;
	private $file_name;
	private $dir;
	private $root;
	private $host;
	private $order_by;
	private $custom_order = false;
}
?>
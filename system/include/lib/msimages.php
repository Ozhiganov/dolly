<?php
MSConfig::RequireFile('msdocs');
// options: order_by, has_main_image = false, can_check_image = false
class MSImages extends MSFilesDocument
{
	final public function __construct($rel_name, $dir, array $options = null)
	 {
		$this->AddOptionsMeta(['crop_size' => [], 'watermark' => [], 'max_size' => [], 'has_main_image' => [], 'fast_load' => ['type' => 'bool', 'value' => false], 'can_check_image' => []]);
		$form = new Form($rel_name, ['status_msgs' => ['inserted' => 'Фотография загружена.'], 'class' => '_load_image']);
		parent::__construct($rel_name, $form, $dir, empty($options['order_by']) ? SQLExpr::MSImagesOrderBy() : $options['order_by'], !empty($options['order_by']), 'image', $options);
		$f = ['type' => 'Image', 'host' => $this->GetHost(), 'dir' => $this->GetDir(), 'root' => $this->GetRoot(), 'required' => true];
		if(($this->watermark_dir = $this->GetOption('watermark')) && ($files = glob($this->GetRoot().$this->watermark_dir.'/watermark.*')))
		 {
			$gr = 'watermark_'.hash('crc32', $this->watermark_dir);
			$this->watermark = new Watermark($files[0], 100 - (int)Registry::GetValue($gr, 'transparency'), (int)Registry::GetValue($gr, 'hoffset'), (int)Registry::GetValue($gr, 'voffset'), Registry::GetValue($gr, 'halign'), Registry::GetValue($gr, 'valign'));
			$f['callback'] = [$this->watermark, 'Create'];
		 }
		if($opt = $this->GetOption('max_size')) list($this->max_width, $this->max_height) = $opt;
		if($opt = $this->GetOption('crop_size'))
		 {
			if(is_array($opt)) list($this->crop_width, $this->crop_height) = $opt;
			else $this->crop_width = $opt;
		 }
		$form->AddField('ext', 'Фотография <span class="max_upload_size">до '.Uploader::GetUploadMaxFileSize().'</span>', $f);
		$this->AfterCreate($form);
		if($this->GetParentTblName()) $form->AddField('parent_id', $this->GetOption('pid_title'), $this->GetOption('pid_field'));
	 }

	public function Show()
	 {
		$this->AddCSS('lib.ui', 'lib.msimages', 'lib.mscrop')->AddJS('lib.msimages', 'lib.mscrop', 'lib.msgallery');
		// $this->AddJS('lib.msdndmanager');
		// if($c = $this->GetOption('before_show')) call_user_func($c, $this);
		parent::Show();
	 }

	final public function Handle()
	 {
		if(false === $this->OnHandle()) return;
		switch($this->ActionPOST())
		 {
			case 'fast_load':
				$attrs = [];
				if($parent_id = Filter::GetValidPageId('parent_id', true)) $attrs['parent_id'] = $parent_id;
				$this->GetForm()->GetField('ext')->Handler(new EventData(['id' => DB::Insert($this->GetTblName(), $attrs, ['date_time' => 'NOW()'])]), 'fast_load');
				self::SendXML(true, '');
			case 'delete':
				if(is_array(@$_POST['items']))
				 {
					foreach($_POST['items'] as $id => $dummy)
					 if(is_numeric($id))
					  {
						$this->OnDelete($id);
						MSIcons::DeleteImage($this->GetDir()."/image_$id.*", $this->GetRoot());
						DB::DeleteById($this->GetTblName(), $id);
					  }
					$this->AddSuccessMsg('Фотографии удалены.');
				 }
				else $this->AddErrorMsg('Не указаны элементы для удаления.');
				if(Filter::GetValidPageId('parent_id', true)) ms::Redirect((empty($_GET['__mssm_id']) ? dirname($_SERVER['PHP_SELF']) : MSLoader::GetUrl()).'?'.($this->GetOption('GET') ?: 'pid').'='.$_POST['parent_id']);
				break;
			case 'set_order': ms::UpdatePos($this->GetTblName(), (bool)$this->GetParentTblName(), null, $this->GetOrderBy()); break;
			case 'check_items':
				$ids = array_filter(explode('|', @$_POST['checklist']), 'is_numeric');
				$rel = Relation::Get($this->GetTblName());
				if($ids)
				 {
					$cond = ' `id` IN ('.implode(',', $ids).')';
					try
					 {
						$rel->Update(array('checked' => 0), $this->GetParentTblName() ? ' `parent_id` IN ('.implode(',', Relation::Get($this->GetTblName())->GetFieldsByCond('parent_id', $cond, null, null, true)).')' : null);
						$rel->Update(array('checked' => 1), $cond);
						self::SendJSON(null, 'Изменения сохранены.');
					 }
					catch(Exception $e)
					 {
						die($e->GetMessage());
					 }
				 }
				elseif($this->GetParentTblName())
				 {
					if($parent_id = Filter::NumFromPOST('parent_id'))
					 {
						$rel->Update(array('checked' => 0), '`parent_id` = "'.$parent_id.'"');
						self::SendJSON(null, 'Изменения сохранены.');
					 }
				 }
				else
				 {
					$rel->Update(array('checked' => 0));
					self::SendJSON(null, 'Изменения сохранены.');
				 }
				break;
			case 'set_cover_id':
				if($id = Filter::NumFromPOST('id'))
				 {
					if($this->GetParentTblName() && ($parent_id = Filter::NumFromPOST('parent_id'))) DB::UpdateById($this->GetParentTblName(), ['main_image_id' => $id], $parent_id);
					else Registry::SetValue($this->GetTblName(), 'main_image_id', $id);
					self::SendJSON(null, 'Изменения сохранены.');
				 }
				break;
			case 'save_crop':
				if(!($id = Filter::NumFromPOST('id'))) throw new Exception('Не указан идентификатор изображения.');
				DB::UpdateById($this->GetTblName(), ['crop_left' => Filter::NumFromPOST('left'), 'crop_top' => Filter::NumFromPOST('top'), 'crop_ratio' => Filter::NumFromPOST('ratio'), 'icon_type' => $_POST['type']], $id);
				$attrs = '`id`, `ext`, `crop_left`, `crop_top`, `crop_ratio`, `icon_type`';
				if($this->GetParentTblName()) $attrs .= ', `parent_id`';
				$row = DB::GetRowById($this->GetTblName(), $id, $attrs);
				if(!$row) throw new Exception('Изображение с таким идентификатором не существует.');
				$this->SetRow($row);
				self::SendJSON(['src' => ImageProcessor::IUrl($row, Page::GetStaticHost(), $this->GetDir(), $this->GetCropWidth(), $this->GetCropHeight())], 'Изменения сохранены.');
			case 'show_crop':
				if(!($id = Filter::NumFromPOST('id'))) throw new Exception('Не указан идентификатор изображения.');
				$attrs = '`ext`, `crop_left`, `crop_top`, `crop_ratio`, `icon_type`';
				if($this->GetParentTblName()) $attrs .= ', `parent_id`';
				$row = DB::GetRowById($this->GetTblName(), $id, $attrs);
				if(!$row) throw new Exception('Изображение с таким идентификатором не существует.');
				$this->SetRow($row);
				if(!$this->GetCropWidth() || !$this->GetCropHeight()) throw new Exception('Не указаны размеры кадра.');
				$name = $this->MakeFilePath($id, $row->ext, true, $src);
				if(!file_exists($name)) throw new Exception('Файл не существует.');
				if(!($img = GetImageSize($name))) throw new Exception('Файл повреждён или не является изображением.');
				self::SendJSON(['host' => \Page::GetStaticHost(''), 'width' => $img[0], 'height' => $img[1], 'src' => $src, 'preview_width' => $this->GetCropWidth(), 'preview_height' => $this->GetCropHeight(), 'left' => $row->crop_left, 'top' => $row->crop_top, 'ratio' => $row->crop_ratio, 'icon_type' => $row->icon_type]);
			default:
				MSFieldSet::Handle();
		 }
	 }

	final public function InsertUrl($url, array $data = null)
	 {
		$data['date_time'] = DB::GetCurrTimestamp();
		$id = $this->GetRel()->Insert($data);
		try
		 {
			$upl = new ImageUploaderUrl($this->GetDir(), $this->GetRoot());
			if($this->max_width || $this->max_height) $upl->SetMaxSize($this->max_width, $this->max_height);
			if($this->watermark) $upl->SetCallback([$this->watermark, 'Create']);
			$data = [];
			$name = $upl->Load($url, 'image_'.$id, $data);
		 }
		catch(EUploader $e)
		 {
			DB::DeleteById($this->GetTblName(), $id);
			Relation::Query('ALTER TABLE '.DB::TName($this->GetTblName()).' AUTO_INCREMENT = '.$id);
			throw $e;
		 }
		DB::UpdateById($this->GetTblName(), $data, $id);
		return ['id' => $id, 'file_name' => $name, 'data' => $data];
	 }

	final protected function ConfigParentAttrs(&$attrs)
	 {
		parent::ConfigParentAttrs($attrs);
		if($this->GetOption('has_main_image')) $attrs[] = 'main_image_id';
	 }

	final protected function OnDeleteFile($file_name, $root) { MSIcons::ClearCache($file_name, $root); }

	final protected function GetCropWidth()
	 {
		if(null !== $this->crop_width)
		 {
			if(is_callable($this->crop_width))
			 {
				if(is_callable($this->crop_height))
				 {
					$this->crop_width = call_user_func($this->crop_width, $this);
					$this->crop_height = call_user_func($this->crop_height, $this);
				 }
				else list($this->crop_width, $this->crop_height) = call_user_func($this->crop_width, $this);
				if($this->crop_width <= 0) throw new Exception('Не указана ширина области обрезки фото.');
				if($this->crop_height <= 0) throw new Exception('Не указана высота области обрезки фото.');
			 }
			return $this->crop_width;
		 }
	 }

	final protected function GetCropHeight() { return $this->crop_height; }

	final protected function ShowPage(stdClass $parent = null)
	 {
		if($parent) $this->ShowImageList($this->GetOption('has_main_image') ? $parent->main_image_id : null, $parent->id);
		else $this->ShowImageList($this->GetOption('has_main_image') ? $this->GetMainImageId() : null);
	 }

	final private function UpdateNavigation($postfix, $title)
	 {
		$page_id = MSLoader::GetId();
		$new_page_id = $page_id.'_'.$postfix;
		MSLoader::SetId($new_page_id);
		if(!MainMenu::ItemExists($new_page_id)) MainMenu::AddItem($new_page_id, $title, null, MainMenu::ItemExists($page_id) ? $page_id : null, false);
		$this->ResetTitle();
	 }

	final private function GetMainImageId() { return Registry::GetValue($this->GetTblName(), 'main_image_id'); }

	final private function ShowImageList($main_image_id, $parent_id = null)
	 {
		$form = $this->GetForm();
		if($parent_id)
		 {
			$form->SetRedirectAndBack(MSLoader::GetUrl(false).'?'.($this->GetOption('GET') ?: 'pid').'='.$parent_id);
			$form->GetField('parent_id')->SetOption('value', $parent_id);
		 }
		if(Filter::NumFromGET('id'))
		 {
			print($form->Make('Редактировать фотографию'));
			$this->AfterFormShow();
		 }
		else
		 {
			echo ui::Form('id', 'fast_load_form')->SetData('hidden', $this->GetOption('fast_load') ? 0 : 1)->SetCaption('Загрузить несколько фотографий или <span class="pseudolink" id="load_one_btn">одну фотографию</span>')->SetMiddle(ui::FilesInput($form->GetField('ext')->GetInputName(), 'mfiles'))->SetBottom(ui::Button('value', 'Добавить', 'id', 'fast_load_btn').($parent_id ? '<input type="hidden" name="parent_id" value="'.$parent_id.'" />' : '')).$form->Make('Загрузить фотографию<span id="load_many_wrapper"> или <span class="pseudolink" id="load_many_btn">несколько фотографий</span></span>', null, true);
			$this->AfterFormShow();
			$result = DB::Select($this->GetTblName(), '*', $parent_id ? '`parent_id` = ?' : false, $parent_id ? [$parent_id] : null, $this->GetOrderBy());
			if($count = count($result))
			 {
				$names = array('order');
				if($this->GetOption('can_check_image')) $names[] = 'check';
				if($this->GetOption('has_main_image')) $names[] = 'cover';
				$buttons = '<input type="submit" name="delete" class="msui_small_button _icon _delete" value="Удалить" disabled="disabled" /><input type="button" name="'.implode(' ', $names).'" class="msui_small_button _icon _save" value="Сохранить изменения" disabled="disabled" /><input type="button" name="select_all" class="msui_small_button _icon select" value="Отметить все" />';
?><form method="post" action="core.php" onsubmit="return confirm('Удалить?');" class="msimages"><?php
				$info = ($count > 5 ? '<span class="total">Всего <span>'.$count.'</span> фотографи'.Format::GetAmountStr($count, 'я', 'и', 'й').'</span>' : '').($this->watermark ? '<span class="wm_preview" title="Используется водяной знак"><img src="'.$this->GetHost().'/f/w100/h100/'.$this->watermark_dir.'/'.basename($this->watermark->GetFileName()).'" alt="Водяной знак" /></span>' : '');
				$show_top_buttons = $count >= self::$num_img_for_top_btns;
				if($info || $show_top_buttons) print('<div class="buttons">'.($info ? '<span class="info">'.$info.'</span>' : '').($show_top_buttons ? $buttons : '').'</div>');
?><div id="sortalbum"><?php
				foreach($result as $img) $this->PrintImgBlock($img, $main_image_id);
?></div><div class="buttons bottom"><?=$buttons?><input type="hidden" name="parent_id" value="<?=$parent_id?>" /><input type="hidden" name="__mssm_action" value="delete" /></div></form><?php
			 }
			else print(ui::WarningMsg('Здесь нет фотографий.'));
		 }
	 }

	final private function PrintImgBlock($img, $main_image_id)
	 {
		$has_main_image = $this->GetOption('has_main_image');
		$can_check_image = $this->GetOption('can_check_image');
?><div id="thumb<?=$img->id?>" class="outer"><div class="frame"><p style="background-image:url('<?=$this->GetHost()?>/f/w130/h115<?=$this->GetDir()?>/image_<?=$img->id?>.<?=$img->ext?>');"<?=$this->image_title_fld ? ' title="'.htmlspecialchars($img[$this->image_title_fld]).'"' : ''?>></p><div nosorthandle="1"><p nosorthandle="1" class="edit"><?=($this->GetCropWidth() && $this->GetCropHeight() ? '<i nosorthandle="1" title="Кадрировать"></i>' : '')?><a href="?id=<?=$img->id?>" nosorthandle="1"></a><label for="lbl<?=$img->id?>" nosorthandle="1" title="Отметить для удаления"></label><input type="checkbox" name="items[<?=$img->id?>]" id="lbl<?=$img->id?>" nosorthandle="1" /></p><?=($has_main_image ? '<p nosorthandle="1"><input type="radio" name="cover" value="'.$img->id.'" id="r'.$img->id.'"'.($main_image_id == $img->id ? ' checked="checked"' : '').' /><label for="r'.$img->id.'" nosorthandle="1">'.(is_bool($has_main_image) ? 'Обложка' : $has_main_image).'</label></p>' : '').($can_check_image ? '<p nosorthandle="1"><input type="checkbox" name="checked" value="'.$img->id.'" id="ch'.$img->id.'"'.($img->checked ? ' checked="checked"' : '').' /><label for="ch'.$img->id.'" nosorthandle="1">'.(is_bool($can_check_image) ? 'Избранное' : $can_check_image).'</label></p>' : '')?></div><em></em></div></div><?php
	 }

	private $image_title_fld = null;
	private $max_width = 1000;
	private $max_height = 1000;
	private $crop_width = null;
	private $crop_height = null;
	private $on_handle;
	private $watermark = null;
	private $watermark_dir = null;
	private static $num_img_for_top_btns = 37;
}
?>
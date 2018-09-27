<?php
class MSWatermark extends MSDocument
{
	final public function __construct($dir = '', $root = null, $host = '')
	 {
		$this->dir = $dir;
		$this->root = null === $root ? $_SERVER['DOCUMENT_ROOT'] : $root;
		$this->host = $host;
		if($files = glob($this->root.$this->dir.'/watermark.*')) $this->watermark = $files[0];
		$this->reg_group_id = 'watermark_'.hash('crc32', $this->dir);
	 }

	final public function Show()
	 {
		$this->AddCSS('lib.ui', 'lib.mswatermark');
		$view_height = 400;
		$view_width = 700;
		$this->AddCSSString('.preview{width:'.$view_width.'px;height:'.$view_height.'px;}');
		$sel_valign = (new Select(['top' => 'Вверху', 'middle' => 'Посередине', 'bottom' => 'Внизу']))->SetId('wm_valign');
		$sel_halign = (new Select(['left' => 'Слева', 'center' => 'Посередине', 'right' => 'Справа']))->SetId('wm_halign');
		if($this->watermark)
		 {
			$ext = ms::GetFileExt($this->watermark);
			$wh = GetImageSize($this->watermark);
			$size = ms::GetFileSize($this->watermark);
			$valign = Registry::GetValue($this->reg_group_id, 'valign');
			if(!$valign) $valign = 'top';
			$sel_valign->SetSelected($valign)->SetClassName('msui_select');
			$halign = Registry::GetValue($this->reg_group_id, 'halign');
			if(!$halign) $halign = 'left';
			$sel_halign->SetSelected($halign)->SetClassName('msui_select');
			$transparency = (int)Registry::GetValue($this->reg_group_id, 'transparency');
			$voffset = (int)Registry::GetValue($this->reg_group_id, 'voffset');
			$hoffset = (int)Registry::GetValue($this->reg_group_id, 'hoffset');
			$this->AddCSSString('.preview__watermark{background-image:url("'.$this->host.$this->dir.'/'.basename($this->watermark).'");width:'.$wh[0].'px;height:'.$wh[1].'px;opacity:'.((100 - $transparency) / 100).';}');
			if('middle' == $valign)
			 {
				$vpos = 'top';
				$voff = round(($view_height - $wh[1]) / 2) + $voffset;
			 }
			else
			 {
				$vpos = $valign;
				$voff = $voffset;
			 }
			if('center' == $halign)
			 {
				$hpos = 'left';
				$hoff = round(($view_width - $wh[0]) / 2) + $hoffset;
			 }
			else
			 {
				$hpos = $halign;
				$hoff = $hoffset;
			 }
			$style = $vpos.':'.$voff.'px;'.$hpos.':'.$hoff.'px;';
			$this->AddJS('lib.mswatermarkoptions');
			$loaded = true;
		 }
		else $loaded = false;
		print(ui::Form('enctype', 'multipart/form-data', 'class', 'form watermark')->SetCaption('Водяной знак')->SetMiddle('<div class="form__row"><label class="form__label">Файл (jpg, png, gif)</label>'.ui::FileInput('watermark').'</div>')->SetBottom(ui::Submit('value', 'Загрузить').'<input type="hidden" name="__mssm_action" value="load_watermark" />'));
		if($loaded)
		 {
			print("<div class='watermark_info'>Водяной знак загружен: $ext, $wh[0]&times;$wh[1], $size <input type='button' id='delete_watermark' value='Удалить' class='msui_small_button _icon _delete' /></div>".ui::Form('id', 'wm_options')->SetCaption('Положение знака на фотографии')->SetMiddle("<div class='form__row'><label class='form__label'>Прозрачность</label>".ui::Number('id', 'wm_transparency', 'max', 9999, 'value', $transparency)."%</div>
<div class='form__row'><label class='form__label'>Выравнивание</label>{$sel_valign->Make()} {$sel_halign->Make()}</div>
<div class='form__row'><label class='form__label'>Горизонтальный отступ</label>".ui::Number('id', 'wm_hoffset', 'max', 9999, 'value', $hoffset)."пикселей</div>
<div class='form__row'><label class='form__label'>Вертикальный отступ</label>".ui::Number('id', 'wm_voffset', 'max', 9999, 'value', $voffset)."пикселей</div>
<div class='form__row'><label class='form__label'>Цвет фона</label><a href='#!preview-white' class='select_colour _white _selected'>белый</a><a href='#!preview-black' class='select_colour _black'>чёрный</a></div>
<div class='preview_wr'><div class='preview'><div class='preview__watermark' style='$style'></div></div></div>")->SetBottom(ui::Button('value', 'Сохранить', 'disabled', true, 'id', 'wm_save')));
		 }
	 }

	final public function Handle()
	 {
		switch(@$_POST['__mssm_action'])
		 {
			case 'load_watermark':
				$upl = new ImageUploader('watermark', $this->dir, $this->root);
				$upl->Required();
				$upl->LoadFile('watermark');
				$this->AddSuccessMsg('Водяной знак загружен.');
				break;
			case 'delete_watermark':
				if($this->watermark && unlink($this->watermark)) self::SendJSON(null, 'Водяной знак удалён.');
				break;
			case 'set_wm_options':
				if(!is_array($_POST['options'])) break;
				$keys = array('transparency', 'valign', 'halign', 'hoffset', 'voffset');
				$values = array('top', 'middle', 'bottom', 'left', 'center', 'right');
				foreach($_POST['options'] as $key => $option) if(in_array($key, $keys) && (is_numeric($option) || in_array($option, $values))) Registry::SetValue($this->reg_group_id, $key, $option);
				self::SendJSON(null, 'Изменения сохранены.');
		 }
	 }

	private $dir;
	private $root;
	private $host;
	private $watermark = null;
	private $reg_group_id;
}
?>
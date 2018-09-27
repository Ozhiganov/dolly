<?php
namespace MSForm;

\MSConfig::RequireFile('fsfield', 'traits', 'tsystemmessages');

class SetPageUrl extends \MSFieldSet\POSTField
{
	public function MakeInput()
	 {
		$i = \html::text('class', 'msui_input _set_page_url'.(($c = $this->GetOption('class')) ? " $c" : ''), 'id', $this->GetId(), 'readonly', true);
		if($row = $this->GetFieldSet()->GetDBRow($row_id)) $i->SetAttr('value', \MSConfig::GetProtocol().$_SERVER['HTTP_HOST'].Engine()->GetHref($row));
		return "$i";
	 }

	public function PreProcess($value) { return ''; }
}

class UrlPart extends \MSFieldSet\POSTField implements \MSFieldSet\IFieldAsync
{
	use \TSystemMessages;

	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['base' => [], 'maxlength' => [], 'parent_sid' => ['set' => true], 'pattern' => [], 'url_filter' => []]);
		parent::__construct($owner, $name, $title, $options);
	 }

	public function MakeInput()
	 {
		$i = \html::text('class', 'msui_input'.(($c = $this->GetOption('class')) ? " $c" : ''), 'id', $this->GetId(), 'name', $this->GetInputName(), 'value', $this->GetInputValue(), 'autocomplete', false)->SetData('fs_id', $this->GetFieldSet()->GetId(), 'name', $this->GetName());
		if(($a = $this->GetOption('maxlength')) > 0) $i->SetAttr('maxlength', $a);
		if($s = $this->GetOption('pattern')) $i->SetAttr('pattern', $s);
		if($s = $this->GetOption('url_filter')) if(is_object($s)) $i->SetData('url_filter', get_class($s));
		if($s = $this->GetOption('parent_sid')) $i->SetData('parent_sid', $s);
		if($s = $this->GetOption('base')) $i->SetData('base', $s);
		// else throw Exception();// ???
		return "$i";
	 }

	public function GetData()
	 {
		if(isset($_GET[$this->GetName()]))
		 {
			$filter = $this->GetOption('url_filter');
			if(is_string($filter)) $filter = function($val) use($filter){return call_user_func($filter, $val);};
			if($url_part = $filter($_GET[$this->GetName()]))
			 {
				$t_name = $this->GetFieldSet()->GetTblName();
				$sid = $url_part;
				if($parent_id = \Filter::NumFromGET('parent_id'))
				 {
					if($p = \DB::GetRowById($t_name, $parent_id))
					 {
						if($p->sid) $sid = "$p->sid/$sid";
					 }
					else throw new \Exception('Invalid parent id!');
				 }
				$row = \DB::GetRowByKey($t_name, 'sid', $sid, '`id`, `sid`, `parent_id`, `type`, `title`, `hidden`');
				if($row) $row->href = \MSConfig::GetProtocol().$_SERVER['HTTP_HOST'].\MSConfig::GetMSSMDir()."/pages/?page_id=$row->id";
				self::SendJSON(['page' => $row]);
			 }
			else throw new \EDocumentHandle('Фрагмент URL содержит недопустимые символы!');
		 }
		else throw new \EDocumentHandle('Не указан фрагмент URL!');
	 }

	public function PreProcess($value) { return trim($value); }
}

class TextInput extends \MSFieldSet\TextInput
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->ChangeOptionsMeta('class', ['value' => '']);
		parent::__construct($owner, $name, $title, $options);
	 }

	protected $required__classes = ['msui_input' => 'msui_input'];
}

class Email extends \MSFieldSet\Email
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->ChangeOptionsMeta('class', ['value' => '']);
		parent::__construct($owner, $name, $title, $options);
	 }

	protected $required__classes = ['msui_input' => 'msui_input'];
}

class TextInputUnique extends TextInput implements \MSFieldSet\IFieldAsync
{
	use \TSystemMessages;

	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		if(!isset($options['data_x']))
		$options['data_x']['fs_id'] = $owner->GetId();
		// $this->AddOptionsMeta(['list' => [], 'maxlength' => []]);
		$this->required__classes['msui_unique'] = 'msui_unique';
		parent::__construct($owner, $name, $title, $options);
		$this->SetCheck('IsUniqueText', ['required' => $this->GetOption('required')]);
	 }

	public function MakeInput()
	 {
		if($this->GetFieldSet()->GetDBRow($id))
		 {
			$d = $this->GetOption('data_x');
			$d['curr_id'] = $id;
			$this->SetOption('data_x', $d);
		 }
		return parent::MakeInput();
	 }

	public function GetData()
	 {
		if($v = isset($_GET['value']) ? trim($_GET['value']) : false) self::SendJSON($this->GetCheck()->ValidateField($v, \Filter::NumFromGET('curr_id')));
		else throw new \EDocumentHandle('Не указано значение!');
	 }
}

class Textarea extends \MSFieldSet\RenderableInput
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->ChangeOptionsMeta('class', ['value' => '']);
		parent::__construct($owner, $name, $title, $options);
	 }

	public function MakeInput() { return \html::textarea('rows', 10, 'cols', 40, ...$this->GetAttrLine())->SetHTML(htmlspecialchars($this->GetInputValue())); }
	public function PreProcess($value) { return trim($value); }
	
	protected $required__classes = ['msui_textarea' => 'msui_textarea'];
}

class TextEditor extends \MSFieldSet\RenderableInput
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['height' => []]);
		parent::__construct($owner, $name, $title, $options);
	 }

	public function MakeInput()
	 {
		\ResourceManager::RequireScript('htmleditor');
		\ResourceManager::AddJSLink('editor');
		if('lazy' === $this->GetOption('init')) $attr = 'lazy';
		else
		 {
			$this->SetOption('__label_class', '_htmleditor');
			$attr = 'true';
		 }
		return \ui::Textarea('id', $this->GetId(), 'name', $this->GetInputName(), 'class', 'msui_textarea')->SetData('htmleditor', $attr, 'editor-height', $this->GetOption('height') ?: 350)->SetHTML(htmlspecialchars($this->GetInputValue()));
	 }

	public function PreProcess($value) { return trim($value); }
}

class Decimal extends \MSFieldSet\POSTField
{
	public function MakeInput() { return \html::text('id', $this->GetId(), 'name', $this->GetInputName(), 'value', $this->GetInputValue(), 'class', 'msui_input', 'autocomplete', $this->GetOption('autocomplete')); }
	public function PreProcess($value) { return $this->GetOption('null') ? \Filter::GetFloatOrNull($value) : \Filter::GetFloatOrZero($value); }
}

class Number extends \MSFieldSet\POSTField
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['max' => [], 'min' => []]);
		parent::__construct($owner, $name, $title, $options);
	 }

	public function MakeInput() { return \ui::Number('name', $this->GetInputName(), 'id', $this->GetId(), 'value', $this->GetInputValue(), 'min', $this->GetOption('min'), 'max', $this->GetOption('max')); }
	public function PreProcess($value) { return is_numeric($value) ? $value : ($this->GetOption('null') ? null : 0); }
}

class CheckBox extends \MSFieldSet\RenderableInput
{
	public function MakeInput()
	 {
		if($on_show = $this->GetOption('on_show')) $show = call_user_func($on_show, $this->GetFieldSet()->GetDBRow($row_id), $row_id, $this) !== false;
		else $show = true;
		$this->SetOption('__no_label', true);
		if($show)
		 {
			$name = $this->GetInputName();
			$value = $this->GetInputValue();
			return "<input type='hidden' name='$name' value='0' /><label>".\html::CheckBox('id', $this->GetId(), 'name', $name, 'value', 1, 'checked', $value || (null === $value && $this->GetOption('default')), 'disabled', $this->GetOption('disabled'))->SetData('name', $this->GetName())."&nbsp;{$this->GetTitle()}</label>";
		 }
	 }

	public function PreProcess($value) { return empty($value) ? 0 : 1; }
}

class Year extends \MSFieldSet\POSTField
{
	public function MakeInput() { return \ui::Year($this->GetOption('start'), $this->GetOption('end'), 'default', $this->GetOption('null'), 'name', $this->GetInputName(), 'id', $this->GetId(), 'value', $this->GetInputValue()); }
	public function PreProcess($value) { return \Filter::GetIntOrNull($value); }
}

class ForeignKey extends \MSFieldSet\POSTField
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['title_fld' => ['type' => 'string', 'value' => 'title'], 'value_fld' => ['type' => 'string', 'value' => ''], 'tbl_2_name' => [], 'where' => ['type' => 'array,callback,null'], 'order_by' => ['type' => 'string', 'value' => '']]);
		parent::__construct($owner, $name, $title, $options);
	 }

	public function MakeInput()
	 {
		$f = $this->GetOption('title_fld');
		$k = $this->GetOption('value_fld') ?: \DB::GetPrimaryKey($this->GetOption('tbl_2_name'));
		list($cnd, $prm) = ($opt = $this->GetOption('where')) ? (is_callable($opt) ? call_user_func($opt, $this) : $opt) : [false, null];
		$s = new \Select(\DB::Select($this->GetOption('tbl_2_name'), "$k, $f AS `_title`", $cnd, $prm, $this->GetOption('order_by')), $k->name, '_title');
		if($this->GetOption('null')) $s->SetDefaultOption('', '&#151;');
		return $s->SetClassName('msui_select')->SetId($this->GetId())->SetName($this->GetInputName())->SetSelected($this->GetInputValue())->Make();
	 }

	public function PreProcess($value)
	 {
		switch($this->GetOption('type'))
		 {
			case 'varchar': return '' === $value ? null : $value;
			case 'int': return is_numeric($value) ? $value : null;
			default: throw new \EFSAction('Unsupported type!');
		 }
	 }

	final public function Omitted() { return $this->GetOption('null') ? false : parent::Omitted(); }
}

class DatePicker extends \MSFieldSet\POSTField
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['max' => [], 'min' => []]);
		parent::__construct($owner, $name, $title, $options);
	 }

	public function MakeInput()
	 {
		\ResourceManager::RequireScript('datepicker');
		\ResourceManager::AddJS('lib.datepicker');
		$id = $this->GetId();
		return \ui::Date('value', $this->GetInputValue(), 'name', $this->GetInputName(), 'id', $id, 'is_null', $this->GetOption('null'), 'null_name', $id.'_null', 'min', $this->GetOption('min'), 'max', $this->GetOption('max'));
	 }

	public function PreProcess($val) { return isset($_POST[$this->GetId().'_null']) ? null : $val; }
}

class DateTimePicker extends \MSFieldSet\POSTField
{
	public function MakeInput()
	 {
		\ResourceManager::RequireScript('datepicker');
		\ResourceManager::AddJS('lib.datepicker');
		$id = $this->GetId();
		return \ui::Date('name', $this->GetInputName(), 'value', $this->GetInputValue(), 'id', $id, 'time', true, 'is_null', $this->GetOption('null'), 'null_name', $id.'_null');
	 }

	public function PreProcess($val) { return isset($_POST[$this->GetId().'_null']) ? null : $val['date'].' '.\Filter::GetNumOrNull($val['h']).':'.\Filter::GetNumOrNull($val['m']).':'.\Filter::GetNumOrNull($val['s']); }
}

class TimePicker extends \MSFieldSet\POSTField
{
	public function MakeInput()
	 {
		$time = ($value = $this->GetInputValue()) && preg_match('/^[0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2}$/', $value) ? explode(':', $value) : ['00', '00', '00'];
		$step = (int)$this->GetOption('step');
		if($step <= 0 || $step > 30) $step = 15;
		$i_name = $this->GetInputName();
		$input = '<input type="number" min="0" max="23" name="'.$i_name.'[hours]" value="'.$time[0].'" class="msui_input _time _hours" />'.self::DIV.'<input type="number" min="0" max="59" name="'.$i_name.'[minutes]" value="'.$time[1].'" class="msui_input _time _minutes" step="'.$step.'" />';//<input type="number" min="0" max="23" name="'.$i_name.'[seconds]" />';
		if($this->GetOption('null')) $input .= '<label class="set_date_null"><input type="checkbox" name="'.$i_name.'" value="null" '.($value ? '' : ' checked="checked"').' /> '.($this->GetOption('label') ?: 'Не указывать время').'</label>';
		return '<span class="timepicker">'.$input.'</span>';
	 }

	public function PreProcess($value) { return (is_array($value) && ($h = \Filter::GetIntOrNull($value['hours'])) !== null && ($m = \Filter::GetIntOrNull($value['minutes'])) !== null) ? "$h:$m:".\Filter::GetIntOrZero(@$value['seconds']) : null; }

	const DIV = ':';
}

abstract class FileField extends \MSFieldSet\Field implements \MSFieldSet\IFile, \MSFieldSet\IIgnoreValue
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['default' => ['set' => true], 'null' => ['type' => 'bool', 'value' => false], 'host' => [], 'dir' => [], 'root' => ['type' => 'string', 'value' => $_SERVER['DOCUMENT_ROOT']], 'iname' => ['value' => 'image_']]);
		parent::__construct($owner, $name, $title, $options);
	 }

	public function GetValue() {}
	public function GetInputValue() {}

	final public function Omitted() { return false; }// это заглушка! можно определить, отправлялся ли файл.

	final protected function GetExtFld() { return $this->GetName(); }
	final protected function GetExt(\stdClass $row) { return $row->{$this->GetExtFld()}; }
	final protected function GetPath() { return ['host' => $this->GetOption('host'), 'dir' => $this->GetOption('dir'), 'root' => $this->GetOption('root')]; }
	final protected function GetBaseName($id, ...$args) { return is_string($opt = $this->GetOption('iname')) ? "$opt$id" : call_user_func($opt, $id, $this, ...$args); }

	final protected function GetFName(array &$path = null, $id, $ext)
	 {
		if(null === $path) $path = $this->GetPath();
		return "$path[dir]/{$this->GetBaseName($id)}.$ext";
	 }
}

class File extends FileField
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		if(!isset($options['iname'])) $options['iname'] = 'file_';
		$this->AddOptionsMeta(['accept' => [], 'use_db' => ['type' => 'bool', 'value' => true]]);
		parent::__construct($owner, $name, $title, $options);
		$owner->BindToEvent('after_insert', function(\EventData $d){ return $this->LoadFile($d, $this->GetOption('required')); });
		$owner->BindToEvent('after_update', function(\EventData $d){ return $this->LoadFile($d, false); });
	 }

	public function MakeInput()
	 {
		$this->GetFieldSet()->SetMultipart();
		return \ui::FileInput($this->GetName(), $this->GetId());
	 }

	protected function LoadFile(\EventData $d, $required)
	 {
		$upl = new \FileUploader($this->GetName(), $this->GetOption('dir'), $this->GetOption('root'));
		$upl->EnableRewriting(true);
		if($opt = $this->GetOption('accept')) $upl->SetAccepted($opt);
		if($required) $upl->Required();
		try
		 {
			if(($f_name = $upl->LoadFile($this->GetBaseName($d->id, $d))) && $this->GetOption('use_db')) \DB::UpdateById($this->GetFieldSet()->GetTblName(), [$this->GetExtFld() => \ms::GetFileExt($f_name)], $d->id);
		 }
		catch(\EUploader $e)
		 {
			throw new \EFSAction($e->GetMessage());
		 }
	 }
}

class Image extends FileField implements \MSFieldSet\IFieldAsync
{
	use \TSystemMessages;

	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['max_size' => [], 'set_size' => ['type' => 'array,false', 'value' => ['width', 'height']], 'callback' => ['type' => 'callback,null']]);
		parent::__construct($owner, $name, $title, $options);
		$owner->BindToEvent('after_insert', [$this, 'Handler']);
		$owner->BindToEvent('after_update', [$this, 'Handler']);
	 }

	public function GetData()
	 {
		if($id = \Filter::NumFromPOST('delete_image'))
		 {
			if($this->GetOption('required')) self::SendJSON(['id' => $id], 'Изображение нельзя удалить.', false);
			\MSIcons::DeleteImage($this->GetOption('dir')."/{$this->GetBaseName($id)}.*", $this->GetOption('root'));
			$data = [$this->GetExtFld() => null];
			if(list($w, $h) = $this->GetOption('set_size')) $data[$w] = $data[$h] = null;
			\DB::UpdateById($this->GetFieldSet()->GetTblName(), $data, $id);
			self::SendJSON(['id' => $id], 'Изображение удалено.');
		 }
	 }

	public function MakeInput()
	 {
		$fs = $this->GetFieldSet()->SetMultipart();
		$html = \ui::ImageInput($this->GetInputName(), $this->GetId());
			// if($has_icon_type = $this->relation->FieldExists('icon_type')/*  && $this->relation->FieldExists('top_x') && $this->relation->FieldExists('top_y') && $this->relation->FieldExists('bottom_x') && $this->relation->FieldExists('bottom_y') */)
			 // {
				/* \ResourceManager::RequireCSSFile('mscrop');
				$html .= '<div class="cropper select_type"><div class="options">';
				$cfg = array(array('value' => 'crop', 'label' => 'Установка видимой области вручную'),
							 array('value' => 'f', 'label' => 'Масштабирование изображения'),
							 array('value' => 'fc', 'label' => 'Автоматическая обрезка по центру изображения'),
							 array('value' => 'fctop', 'label' => 'Автоматическая обрезка по верху изображения'));
				foreach($cfg as $item) $html .= '<p><input type="radio" name="'.self::MakeInputName($this->relation->GetName(), 'icon_type').'" value="'.$item['value'].'" id="type_'.$item['value'].'"'.($row['icon_type'] == $item['value'] || !$row && 'fc' == $item['value'] ? ' checked="checked"' : '').' /><label class="'.$item['value'].'" title="'.$item['label'].'" for="type_'.$item['value'].'"><span></span><em></em></label></p>';
				$html .= '<div class="clear"></div></div></div>'; */
			 // }
		if($row = $fs->GetDBRow($id))
		 {
			// \ResourceManager::RequireJSFile('msdndmanager');
			// \ResourceManager::RequireJSFile('mscrop');
			// \ResourceManager::RequireJSFile('initcrop');
			// \ResourceManager::RequireCSSFile('mscrop');
			if($ext = $this->GetExt($row))
			 {
				$path = $this->GetPath();
				$src = $this->GetFName($path, $id, $ext);
				\ResourceManager::AddCSS('lib.image_box');
				$html .= '<div><span class="form__image _preview"><img src="'.$path['host'].'/f/w130/h250'.$src.'" data-src="'.$path['host'].$src.'" alt="Фото" class="image_with_icon__preview" /></span>';
				/* if($has_icon_type)
				 {
					$info = GetImageSize($path['root'].$src);
					$new_size = ImageProcessor::GetFittedImageSize($info[0], $info[1], 500, 500);
					$html .= '<input type="button" value="Кадрировать" class="small image crop" name="crop_'.$name.'" /><input type="hidden" name="_src" value="'.$src.'" /><input type="hidden" name="_width" value="'.$info[0].'" /><input type="hidden" name="_height" value="'.$info[1].'" /><input type="hidden" name="_new_width" value="'.$new_size['width'].'" /><input type="hidden" name="_new_height" value="'.$new_size['height'].'" /><input type="hidden" name="_crop_width" value="'.$field['args'][3].'" /><input type="hidden" name="_crop_height" value="'.$field['args'][4].'" /><input type="hidden" name="'.self::MakeInputName($this->relation->GetName(), 'icon_type').'" value="'.$row['icon_type'].'" /> ';
					$names = array('top_x', 'top_y', 'bottom_x', 'bottom_y');
					foreach($names as $n) $html .= '<input type="hidden" name="'.self::MakeInputName($this->relation->GetName(), $n).'" value="'.$row[$n].'" />';
				 } */
				if(!$this->GetOption('required')) $html .= "<input type='button' title='Удалить изображение' value='Удалить' class='msui_small_button _icon _delete' data-id='$id' data-name='{$this->GetName()}' data-fs_id='{$fs->GetId()}' onclick='DelImg(this);' />";
				$html .= '</div>';
			 }
		 }
		$this->SetOption('__label_class', '_multirow');
		return "<div class='form__input_wr image_with_icon'>$html</div>";
	 }

	public function Handler(\EventData $d, $evt_name)
	 {
		$root = $this->GetOption('root');
		$dir = $this->GetOption('dir');
		if($url = trim(@$_POST[$this->GetInputName()])) $upl = new \ImageUploaderUrl($dir, $root);
		else $upl = new \ImageUploader($this->GetInputName(), $dir, $root);
		// if($this->GetOption('required') && 'after_insert' === $evt_name) $upl->Required();// нужно продумывать дополнительно, поскольку сейчас required не имеет смысла - запись можно всё равно внести
		if($max_size = $this->GetOption('max_size'))
		 {
			if(is_array($max_size)) $upl->SetMaxSize(...$max_size);
			else $upl->SetMaxSize($max_size, $max_size);
		 }
		if($c = $this->GetOption('callback')) $upl->SetCallback($c);
		$no_size = !($f = $this->GetOption('set_size'));
		$extf = $this->GetExtFld();
		if($f) array_unshift($f, $extf);
		else $f = [$extf];
		$basename = $this->GetBaseName($d->id, $d);
		$tbl_name = $this->GetFieldSet()->GetTblName();
		$o = ['no_size' => $no_size, 'fields' => $f];
		if($url)
		 {
			$data = [];
			if('after_update' === $evt_name) $o['before_update'] = function($data, $file_name, $tbl_name, $id, $options) use($extf, $dir, $root, $basename){
				$r = \DB::GetRowById($tbl_name, $id, $extf);
				if($r->$extf && $r->$extf !== $data[$extf]) unlink("$root$dir/$basename.".$r->$extf);
			};
			$upl->DBLoad($url, $basename, $tbl_name, $d->id, $data, $o);
		 }
		else $upl->DBLoadFile($basename, $tbl_name, $d->id, $o);
	 }
}

class Hidden extends \MSFieldSet\Hidden
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['filter' => []]);
		parent::__construct($owner, $name, $title, $options);
		if(!($this->filter = $this->GetOption('filter')))
		 {
			if(\DB::ColExists($this->GetFieldSet()->GetTblName(), $name, $col))
			 {
				switch($col->type)
				 {
					case 'int': $this->filter = $col->null ? '\Filter::GetIntOrNull' : '\Filter::GetIntOrZero'; break;
					case 'float':
					case 'decimal': $this->filter = $col->null ? '\Filter::GetFloatOrNull' : '\Filter::GetFloatOrZero'; break;
				 }
			 }
		 }
	 }

	public function MakeInput()
	 {
		$this->SetOption('__row_class', false);
		return parent::MakeInput();
	 }

	public function PreProcess($value) { return $this->filter ? call_user_func($this->filter, $value) : $value; }

	private $filter;
}

class Info extends \MSFieldSet\Field
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['on_show' => []]);
		$this->ChangeOptionsMeta('class', ['type' => 'string,false', 'value' => '']);
		parent::__construct($owner, $name, $title, $options);
	 }

	public function MakeInput()
	 {
		$t = $this->GetOption('value');
		if($c = $this->GetOption('on_show')) $t = call_user_func($c, $t, $this->GetRow($id), $id, $this);
		if(null === $t || false === ($c = $this->GetOption('class'))) return $t;
		if($c) $c = " $c";
		return "<div class='form__info$c'>$t</div>";
	 }

	public function GetValue() {}
	public function GetInputValue() {}

	final public function Omitted() { return false; }

	protected function GetRow(&$id) { return $this->GetFieldSet()->GetDBRow($id); }
}

class MLSelect extends \MSFieldSet\POSTField implements \MSFieldSet\IFieldAsync
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['attrs' => [], 'callback' => ['type' => 'callback,null'], 'col_name' => ['type' => 'string', 'value' => $name], 'condition' => ['type' => 'string', 'value' => ''], 'data_attrs' => [], 'dropdown' => [], 'exclude' => ['type' => 'bool', 'value' => false], 'null_data' => [], 'null_text' => ['type' => 'string', 'value' => '—'], 'order_by' => [], 'recursive' => [], 'ref_tbl' => ['type' => 'string', 'value' => ''], 'title' => ['type' => 'string', 'value' => '`title`']]);
		parent::__construct($owner, $name, $title, $options);
	 }

	final public function MakeInput()
	 {
		if($this->GetOption('dropdown'))// доделать dropdown js!!!
		 {
			\ResourceManager::AddJS('lib.dropdownlist');
			$c = 'DropDownList';
		 }
		else
		 {
			\ResourceManager::AddJS('lib.mlselect');
			$c = 'Select';
		 }
		$this->GetOpts($tbl_name, $col, $ref_tbl_name, $key, $attrs, $recursive, $order_by, $null, $exclude, $condition, $callback);
		if($exclude) $this->GetFieldSet()->GetDBRow($row_id);
		else $row_id = null;
		if($selected = $this->GetValue())
		 {
			$ret_val = '';
			do
			 {
				if($selected)
				 {
					if($row = \DB::GetRowById($ref_tbl_name, $selected, $recursive)) $parent_id = \DB::GetRowById($ref_tbl_name, $selected, $recursive)->$recursive;
					else
					 {
						$ret_val = $this->MakeUnselected($c, $col, $ref_tbl_name, $attrs, $recursive, $order_by, $null, $exclude, $condition, $callback);
						break;
					 }
				 }
				else $parent_id = false;
				if($parent_id)
				 {
					$cnd = "`$recursive` = :parent_id";
					$prm = ['parent_id' => $parent_id];
				 }
				else
				 {
					$cnd = "`$recursive` IS NULL";
					$prm = [];
				 }
				if($exclude)
				 {
					$cnd .= " AND $key <> :curr_id";
					$prm['curr_id'] = $row_id;
				 }
				if($condition) $cnd = "($cnd) AND ($condition)";
				$db_res = \DB::Select($ref_tbl_name, $attrs, $cnd, $prm, $order_by);
				if($callback) $db_res->SetCallback($callback);
				$select = new $c($db_res, 'id', 'title', $this->GetOption('data_attrs'));
				$select->SetClassName('msui_select');
				if($parent_id) $select->SetDefaultOption('', '&#151;');
				elseif($col->null) $select->SetDefaultOption('', $null, $this->GetOption('null_data'));
				if(!$ret_val)
				 {
					$select->SetName($this->GetInputName());// name for the last select
					if($selected)// если что-то выбрано, и если это первая итерация, то добавить вниз группы инпутов select с дочерними узлами относительно выбранного.
					 {
						$cnd = "`$recursive` = :selected";
						$prm = ['selected' => $selected];
						if($exclude)
						 {
							$cnd .= " AND $key <> :curr_id";
							$prm['curr_id'] = $row_id;
						 }
						if($condition) $cnd = "($cnd) AND ($condition)";
						$res = \DB::Select($ref_tbl_name, $attrs, $cnd, $prm, $order_by);
						if(count($res))
						 {
							if($callback) $res->SetCallback($callback);
							$s = new $c($res, 'id', 'title', $this->GetOption('data_attrs'));
							$ret_val = $s->SetClassName('msui_select')->SetDefaultOption('', '&#151;')->Make();
						 }
					 }
				 }
				$ret_val = $select->Disable()->SetSelected($selected)->Make().$ret_val;
				$selected = $parent_id;
			 }
			while($selected);
		 }
		else $ret_val = $this->MakeUnselected($c, $col, $ref_tbl_name, $attrs, $recursive, $order_by, $null, $exclude, $condition, $callback);
		$this->SetOption('__label_class', '_multirow');
		return "<div class='form__input_wr _multilevel' id='{$this->GetId()}' data-fs_id='{$this->GetFieldSet()->GetId()}' data-name='{$this->GetName()}' data-curr_id='$row_id'>$ret_val</div><script type='text/javascript'>new MLSelect('{$this->GetId()}');</script>";
	 }

	final public function GetData()
	 {
		$this->GetOpts($tbl_name, $col, $ref_tbl_name, $key, $attrs, $recursive, $order_by, $null, $exclude, $condition, $callback);
		$xml = '';
		$cnd = "`$recursive` = :parent_id";
		$prm = ['parent_id' => \Filter::NumFromGET('parent_id')];
		if($exclude)
		 {
			$cnd .= " AND $key <> :curr_id";
			$prm['curr_id'] = \Filter::NumFromGET('curr_id');
		 }
		if($condition) $cnd = "($cnd) AND ($condition)";
		$result = \DB::Select($ref_tbl_name, $attrs, $cnd, $prm, $order_by);
		if($callback) $result->SetCallback($callback);
		if($da = $this->GetOption('data_attrs'))
		 {
			foreach($da as $k => $a) if(true === $a) $da[$k] = $k;
			foreach($result as $row)
			 {
				$xa = '';
				foreach($da as $k => $a) $xa .= " data-$a='{$row->$k}'";
				$xml .= "<item value='$row->id'$xa><![CDATA[$row->title]]></item>";
			 }
		 }
		else foreach($result as $row) $xml .= "<item value='$row->id'><![CDATA[$row->title]]></item>";
		\MSDocument::SendTextXML($xml);
	 }

	final public function PreProcess($value)
	 {
		$tbl_name = $this->GetFieldSet()->GetTblName();
		$col_name = $this->GetOption('col_name');
		$col = \DB::GetColMeta($tbl_name, $col_name);
		if('int' === $col->type) return is_numeric($value) ? $value : ($col->null ? null : 0);
		else return $value;
	 }

	final private function MakeUnselected($c, $col, $ref_tbl_name, $attrs, $recursive, $order_by, $null, $exclude, $condition, $callback)
	 {
		$cnd = "`$recursive` IS NULL";
		$prm = null;
		if($condition) $cnd = "($cnd) AND ($condition)";
		if($exclude)
		 {
			$cnd .= " AND $key <> :curr_id";
			$prm = ['curr_id' => $row_id];
		 }
		$db_res = \DB::Select($ref_tbl_name, $attrs, $cnd, $prm, $order_by);
		if($callback) $db_res->SetCallback($callback);
		$select = new $c($db_res, 'id', 'title', $this->GetOption('data_attrs'));
		if($col->null) $select->SetDefaultOption('', $null, $this->GetOption('null_data'));
		return $select->Disable()->SetName($this->GetInputName())->SetClassName('msui_select')->Make();
	 }

	final private function GetOpts(&$tbl_name, &$col, &$ref_tbl_name, &$key, &$attrs, &$recursive, &$order_by, &$null, &$exclude, &$condition, &$callback)
	 {
		$tbl_name = $this->GetFieldSet()->GetTblName();// имя таблицы №1, которая редактируется сейчас формой.
		$col_name = $this->GetOption('col_name');// поле таблицы №1, внешний ключ, ссылается на таблицу №2, которая либо определяется автоматически, либо указывается явно опцией 'ref_tbl'.
		if(!($ref_tbl_name = $this->GetOption('ref_tbl')))// таблица №2, на которую ссылается внешний ключ $col_name таблицы №1.
		 {
			if(!\DB::IsForeignKey($tbl_name, $col_name, $fk)) throw new \Exception("Поле `$col_name` таблицы `$tbl_name` не является внешним ключом!");
			$ref_tbl_name = $fk['table'];
		 }
		$key = \DB::GetPrimaryKey($ref_tbl_name);// первичный ключ таблицы №2, на него ссылается $col_name (и $col).
		$attrs = "$key AS `id`, {$this->GetOption('title')} AS `title`";// атрибуты, извлекаемые из таблицы №2 и подставляемые в селекты.
		if($tmp = $this->GetOption('attrs')) $attrs .= ", $tmp";
		$recursive = $this->GetOption('recursive') ?: 'parent_id';// рекурсивный внешний ключ таблицы №2.
		$col = \DB::GetColMeta($tbl_name, $col_name);
		$order_by = $this->GetOption('order_by');// порядок сортировки значений из таблицы №2.
		$null = $this->GetOption('null_text');
		$exclude = $this->GetOption('exclude');
		$condition = $this->GetOption('condition');// дополнительное условие, налагаемое на извлекаемые элементы; объединяется посредством AND
		$callback = $this->GetOption('callback');
	 }
}

class SelectPagePID extends MLSelect
{
	final public function __construct(\MSFieldSet $owner, $name, $title, array $o = [])
	 {
		if(empty($o['null_text'])) $o['null_text'] = '— Корень сайта —';
		$o['type'] = 'MLSelect';
		if(empty($o['order_by'])) $o['order_by'] = '`title` ASC';
		$o['attrs'] = (empty($o['attrs']) ? '' : "$o[attrs], ").'`sid`, `hidden`';
		$o['data_attrs'] = ['sid' => true];
		if(isset($o['callback']))
		 {
			$callback = $o['callback'];
			$o['callback'] = function(\stdClass $row) use($callback){
				$this->UpdateTitle($row);
				call_user_func($callback, $row);
			};
		 }
		else $o['callback'] = [$this, 'UpdateTitle'];
		$o['null_data'] = ['sid' => ''];
		parent::__construct($owner, $name, $title, $o);
	 }

	final public function Omitted() { return false; }

	final public static function UpdateTitle(\stdClass $row)
	 {
		if('' === $row->title) $row->title = $row->id;
		if($row->hidden > 1) $row->title = self::MARK_DELETED.$row->title;
	 }

	const MARK_DELETED = '&Oslash;&nbsp;&nbsp;';
}

trait TMultiSelect
{
	final protected function GetFKeysMeta() { return $this->fkeys_meta; }

	final protected function InitFKeysMeta($tbl_name, array $options)
	 {
		if(isset($options['data'])) throw new \Exception('Table and data both are specified!');
		$fkeys = \DB::GetForeignKeys($options['joint']);
		if(!$fkeys) throw new \Exception("Table `$options[joint]` does not have foreign keys!");
		$t1 = $t2 = false;
		if(count($fkeys) > 2)
		 {
			if(empty($options['tbl_2_name'])) throw new \Exception('The second table is not defined! Please, specify option `tbl_2_name` directly.');
			else throw new \Exception('Not implemented yet...');
		 }
		else
		 {
			foreach($fkeys as $k => $f)
			 {
				$n = $f['table'] === $tbl_name ? 't1' : 't2';
				if(false === $$n) $$n = $f;
				else throw new \Exception('Not implemented yet...');
			 }
		 }
		if(!$t1) throw new \Exception("No references found between tables `$tbl_name` and `$options[joint]`!");
		if(!$t2) throw new \Exception("No references found between tables `$options[tbl_2_name]` and `$options[joint]`!");
		$this->fkeys_meta = [1 => null, 2 => null];
		foreach($this->fkeys_meta as $k => &$v) $v = \Filter::CopyFields(null, ${"t$k"}, 'key', 'table', 'field', 'on_delete', 'on_update', 'references');
		return $this->fkeys_meta;
	 }

	private $fkeys_meta = null;
}

class MultiSelect extends \MSFieldSet\RenderableInput
{
	use TMultiSelect;

	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['joint' => ['type' => 'string'], 'title_fld' => ['type' => 'string', 'value' => 'title'], 'tbl_2_cols' => ['type' => 'string', 'value' => ''], 'clear' => []]);
		$fk = $this->InitFKeysMeta($owner->GetTblName(), $options);
		parent::__construct($owner, $name, $title, $options);
		$handler = function(\EventData $d, $evt_name) use($fk){
			$n = $this->GetName();
			$stats = ['ins' => 0, 'upd' => 0, 'del' => 0];
			if(isset($d->all_data[$n]))
			 {
				$t = $this->GetOption('joint');
				if(!empty($d->all_data[$n]['d']) && ($del = array_filter($d->all_data[$n]['d'])))
				 {
					\MSConfig::RequireFile('msdb.sql');
					$p = ['_row_id' => $d->id];
					$stats['del'] += \DB::Delete($t, new \MSDB\SQL\IN($del, ['indexes' => 'to_string', 'expr' => "(`$t`.`{$fk[1]->key}` = :_row_id) AND `$t`.`{$fk[2]->key}`"], $p), $p);
				 }
				foreach($d->all_data[$n]['v'] as $key => $id)
				 {
					if(!$id) continue;
					$row = [$fk[1]->key => $d->id, $fk[2]->key => $id];
					if('' === $d->all_data[$n]['i'][$key])
					 {
						$r = \DB::Replace($t, $row);
						if(1 === $r) ++$stats['ins'];
						elseif(2 === $r) ++$stats['upd'];
					 }
					elseif($id === $d->all_data[$n]['i'][$key]);
					else
					 {
						$f1 = "__db__ckey1_{$fk[1]->key}_";
						$f2 = "__db__ckey2_{$fk[2]->key}_";
						$row["~$f1"] = $d->id;
						$row["~$f2"] = $d->all_data[$n]['i'][$key];
						$stats['upd'] += \DB::Update($t, $row, "(`{$fk[1]->key}` = :$f1) AND (`{$fk[2]->key}` = :$f2)");
					 }
				 }
			 }
		};
		if($opt = $this->GetOption('clear'))
		 {
			if(is_callable($opt))
			 {
				$owner->BindToEvent('after_insert', function(\EventData $d, $evt_name) use($handler, $opt){
					if(!call_user_func($opt, $d, $evt_name, $this)) $handler($d, $evt_name);
				});
				$owner->BindToEvent('after_update', function(\EventData $d, $evt_name) use($handler, $opt, $fk){
					if(call_user_func($opt, $d, $evt_name, $this))
					 {
						$t = $this->GetOption('joint');
						\DB::Delete($t, "(`$t`.`{$fk[1]->key}` = :_row_id)", ['_row_id' => $d->id]);
					 }
					else $handler($d, $evt_name);
				});
			 }
			else throw new \Exception('not implemented yet...');
		 }
		else
		 {
			$owner->BindToEvent('after_insert', $handler);
			$owner->BindToEvent('after_update', $handler);
		 }
	 }

	public function MakeInput()
	 {
		\ResourceManager::AddJS('lib.multiselect');
		\ResourceManager::AddCSS('lib.multiselect');
		$this->SetOption('__label_class', '_multirow');
		$fk = $this->GetFKeysMeta();
		$tree = new \SimplePageTree($fk[2]->table, ['init' => 'multiselect', 'collapse' => true, 'popup' => true, 'trigger' => "#{$this->GetId()} .imultiselect__show_tree", 'columns' => $this->GetOption('tbl_2_cols'), 'callback' => ['\ATLCfg', 'SimplePageTreeCallback'], 'where' => ['`hidden` < :hidden', ['hidden' => 2]]]);
		\MSConfig::RequireFile('multiselect');
		$html = $tree->Make();
		if($this->GetFieldSet()->GetDBRow($row_id))
		 {
			$dbc = clone \DB();
			$res = $dbc->SelectLJ([
						'jtbl' => [$this->GetOption('joint'), ''],
						'2tbl' => [$fk[2]->table, '', "`jtbl`.`{$fk[2]->key}` = `2tbl`.`{$fk[2]->field}`"],
					], "`2tbl`.`{$fk[2]->field}` AS `id`, `2tbl`.`{$this->GetOption('title_fld')}` AS `title`, `2tbl`.`parent_id`", "(`jtbl`.`{$fk[1]->key}` = ?)", [$row_id]);
			foreach($res as $row) $html .= $this->MakeRow($tree->LeafExists($row->id, $branch) ? $branch : $this->GetBranch($row, $fk[2]->table, "`{$fk[2]->field}` AS `id`, `{$this->GetOption('title_fld')}` AS `title`, `parent_id`"));
		 }
		$html .= $this->MakeRow([(object)['id' => '', 'title' => '']]);
		return "<div class='multiselect form__input_wr' id='{$this->GetId()}'>$html</div>";
	 }

	final private function MakeRow($value)
	 {
		return (new \iMultiSelect(['name' => $this->GetInputName(), 'value' => $value]))->Make();
	 }

	final private function GetBranch(\stdClass $row, $tbl_name, $cols)
	 {
		$v = [$row];
		while($row->parent_id)
		 {
			if($row = \DB::GetRowById($tbl_name, $row->parent_id, $cols)) array_unshift($v, $row);
		 }
		return $v;
	 }
}

class ImageSize extends \MSFieldSet\RenderableInput
{
	final public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['auto_width' => [], 'default_size' => [], 'no_col_meta' => [], 'unsigned' => []]);
		if(empty($options['no_col_meta']))
		 {
			if(\DB::ColExists($owner->GetTblName(), $name, $col)) \Filter::CopyValues($options, $col, 'default', 'null', 'unsigned');
			else ;// а если такого поля нет? что делать?
		 }
		// ---
		parent::__construct($owner, $name, $title, $options);
		$this->r_name = preg_replace('/(.+)_width$/', '$1_ratio', $this->GetName(), -1, $count);
		if(!$count) throw new \Exception("Invalid name `{$this->GetName()}`!");
		$this->r_id = preg_replace('/(.+)_width$/', '$1_ratio', $this->GetId(), -1, $count);
		if(!self::$sizes_res) self::$sizes_res = \DB::Select('sys_image_size', '*', false, null, '`value` DESC');
		$this->GetFieldSet()->AddField($this->r_name, '', ['type' => 'Hidden']);
	 }

	final public function MakeInput()
	 {// ratio = width / height
		$w = $this->GetInputValue();// 0 - use default values, null - auto values.
		\ResourceManager::AddCSS('lib.image_size');
		\ResourceManager::AddJS('lib.image_size');
		$selected = $da = $whiva = '';
		$frame_class = 'msimage_size__frame';
		$default_size = $this->GetOption('default_size');
		if($w) $whiva = " value='$w'";
		else
		 {
			if(null === $w && $this->GetOption('auto_width')) $selected = 'auto';
			else
			 {
				$whiva = " value='$w'";
				$w = $default_size[0];
				$selected = 'default';
				$da = ' disabled="disabled"';
				$frame_class .= ' _disabled';
			 }
		 }
		$r = (float)$this->GetFieldSet()->GetField($this->r_name)->GetInputValue();
		if($r <= 0) $r = $default_size[1];
		$sdata = ['manually' => 'задать вручную', 'default' => 'по умолчанию'];
		$ret_val = '<div class="msimage_size _'.$this->GetName().('auto' === $this->GetOption('init') ? ' _autoinit' : '').'" data-default-width="'.$default_size[0].'" data-default-ratio="'.$default_size[1].'" data-width="'.$w.'" data-ratio="'.$r.'">
	<input type="hidden" name="'.$this->GetInputName().'" class="msimage_size__width"'.$whiva.' data-rid="'.$this->r_id.'" />
	<div class="'.$frame_class.'"><div class="msimage_size__size"></div><a class="msimage_size__resizer" href="#!resizer"></a><div class="msimage_size__resizer_bg"></div></div>
	<div class="msimage_size__controls">';
		if($o = $this->GetOption('auto_width')) $sdata['auto'] = $o;
		$select = (new \Select($sdata))->SetClassName('msui_select msimage_size__select');
		if($selected) $select->SetSelected($selected);
		$ret_val .= $select->Make().' <div class="msimage_size__lock">
			<select class="msui_select msimage_size__lock_value"'.$da.'>
				<option value="">нет</option><option value="custom">произвольно</option><option value="1">1:1</option>';
		foreach(self::$sizes_res as $s) $ret_val .= "<option value='$s->value'>$s->title</option>";
		return $ret_val.'</select></div></div></div>';
	 }

	final public function PreProcess($value) { return \Filter::GetIntOrNull($value) /* is_numeric($value) ? $value : ($this->GetOption('null') ? null : 0) */; }

	private static $sizes_res = null;

	private $r_name;
	private $r_id;
}

class Select extends \MSFieldSet\Select
{
	protected $default_css_class = 'msui_select';
}

class SetPassword extends \MSFieldSet\POSTField
{
	final public function MakeInput()
	 {
		$i = \ui::Text('autocomplete', false, 'name', $this->GetInputName());
		if(null !== ($p = $this->GetInputValue())) $i->SetAttr('placeholder', $p ? '••••••••' : 'пароль не задан');
		return $i;
	 }
}

class Tel extends \MSFieldSet\POSTField
{
	public function MakeInput() { return '<span class="msui_tel_country_code">+7</span>&nbsp;'.\html::Tel('value', $this->GetInputValue(), 'class', 'msui_input', 'id', $this->GetId(), 'name', $this->GetInputName()); }
}

class MultiSelect2 extends \MSFieldSet\POSTField
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['joint' => [], 'title_fld' => [], 'tbl_2_name' => [], 'where' => ['type' => 'array,callback,null', 'set' => true], 'order_by' => []]);
		parent::__construct($owner, $name, $title, $options);
		if(!($joint_tbl = $this->GetOption('joint'))) throw new \Exception('The joint table is not specified!');
		$tbl_name = $this->GetFieldSet()->GetTblName();
		if($this->slave_tbl = $this->GetOption('tbl_2_name'))//если задана подчинённая таблица
		 {
			throw new \Exception('Not implemented yet...');$slave_tbl_i = 1;
			$attrs = ['`'.Relation::Get($field['args'][1])->GetKeyName().'` AS `id`', empty($field['args'][5]) ? '`title`' : $field['args'][5].(empty($field['args'][6]) ? ' AS `title`' : '')];
		 }
		else// если не указана подчинённая таблица (slave_tbl), то это означает, что её вообще нет.
		 {
			$fkeys = \DB::GetForeignKeys($joint_tbl);
			if(!$fkeys) throw new \Exception("Table `$joint_tbl` does not have foreign keys!");
			$found = [];
			foreach($fkeys as $k => $f) if($f['table'] === $tbl_name) $found[] = $f;
			if($found)
			 {
				if(count($found) > 1) throw new \Exception('Not implemented yet...');
				else
				 {
					$pkey = \DB::GetPrimaryKey($joint_tbl);
					if(2 === count($pkey) && isset($pkey[$found[0]['key']]))
					 {
						foreach($pkey as $k => $v)
						 if($k === $found[0]['key']) $this->attrs['ins'][0] = $k;
						 else
						  {
							$this->attrs['sel'] = "`$k` AS `id`, `$k` AS `title`";
							$this->attrs['ins'][1] = $k;
						  }
						$this->slave_tbl = $joint_tbl;
						$this->distinct = true;
					 }
					else throw new \Exception('Not implemented yet...');
				 }
			 }
			else throw new \Exception("No references found between tables `$tbl_name` and `$joint_tbl`!");
		 }
		$handler = function(\EventData $evt, $evt_name){
			$c = "`{$this->attrs['ins'][0]}` = :_parent_id";
			$p = ['_parent_id' => $evt->id];
			if(!empty($evt->all_data[$this->GetName()]))
			 {
				$allowed = [];
				foreach($evt->all_data[$this->GetName()] as $v) if(null !== $this->ReplaceIfNotEmpty($evt->id, $v)) $allowed[$v] = $v;
				if($allowed)
				 {
					\MSConfig::RequireFile('msdb.sql');
					$c = new \MSDB\SQL\IN($allowed, ['indexes' => 'to_string', 'expr' => "($c) AND `{$this->attrs['ins'][1]}` NOT"], $p);
				 }
			 }
			\DB::Delete($this->slave_tbl, $c, $p);
		};
		$owner->BindToEvent('after_insert', function(\EventData $evt, $evt_name){if(!empty($evt->all_data[$this->GetName()])) foreach($evt->all_data[$this->GetName()] as $v) $this->ReplaceIfNotEmpty($evt->id, $v);});
		$owner->BindToEvent('after_update', $handler);
	 }

	public function MakeInput()
	 {
		\ResourceManager::AddJS('lib.multiselect2');
		\ResourceManager::AddCSS('lib.multiselect2');
		list($cnd, $prm) = ($opt = $this->GetOption('where')) ? (is_callable($opt) ? call_user_func($opt, $this) : $opt) : [false, null];
		$res = \DB::Select($this->slave_tbl, $this->attrs['sel'], $cnd, $prm, $this->GetOption('order_by'), ['distinct' => $this->distinct]);
		$html = (new \Select($res, 'id', 'title'))->Make();
		$i_name = $this->GetInputName().'[]';
		if($row = $this->GetFieldSet()->GetDBRow())
		 {
			$res_s = \DB::Select($this->slave_tbl, $this->attrs['sel'], "`{$this->attrs['ins'][0]}` = ?", [$row->id], $this->GetOption('order_by'));
			foreach($res_s as $row_s) $html .= $this->MakeMultiSelectBlock(\ui::Text('name', $i_name, 'value', $row_s->title));
		 }
		$html .= $this->MakeMultiSelectBlock(\ui::Text('name', $i_name));
		$this->SetOption('__label_class', '_multirow');
		return "<div class='form__input_wr multiselect2 _ex' id='{$this->GetId()}'>$html</div>";
	 }

	public function Omitted() { return false; }

	final private function ReplaceIfNotEmpty($id, $v)
	 {
		if('' !== ($v = trim($v))) return \DB::Replace($this->slave_tbl, [$this->attrs['ins'][0] => $id, $this->attrs['ins'][1] => $v]);
	 }

	final private function MakeMultiSelectBlock($html, $hidden = false) { return '<div class="multiselect2__block _to_delete">'.$html.\ui::DeleteBlock($hidden ? '_hidden' : '').'</div>'; }

	private $slave_tbl;
	private $attrs = ['ins' => [], 'sel' => null];
	private $distinct;
}

class MultiSelect3 extends \MSFieldSet\RenderableInput
{
	use TMultiSelect;

	final public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['collapse' => ['type' => 'bool,null'], 'joint' => [], 'title_fld' => [], 'value_fld' => ['type' => 'string', 'value' => ''], 'data_attrs' => [], 'tbl_2_name' => []]);
		if(isset($options['joint']))
		 {
			$fk = $this->InitFKeysMeta($owner->GetTblName(), $options);
			$handler = function(\EventData $evt, $evt_name) use($fk){
				$n = $this->GetName();
				if(!empty($evt->all_data[$n]))
				 {
					$ins = $del = [];
					foreach($evt->all_data[$n] as $id => $v) ${empty($v) ? 'del' : 'ins'}[] = $id;
					\MSConfig::RequireFile('msdb.sql');
					$t = $this->GetOption('joint');
					if($del)
					 {
						$p = ['_row_id' => $evt->id];
						$c = new \MSDB\SQL\IN($del, ['indexes' => 'to_string', 'expr' => "(`$t`.`{$fk[1]->key}` = :_row_id) AND `$t`.`{$fk[2]->key}`"], $p);
						\DB::Delete($t, $c, $p);
					 }
					if($ins) foreach($ins as $id) \DB::Replace($t, [$fk[1]->key => $evt->id, $fk[2]->key => $id]);
				 }
			};
			$owner->BindToEvent('after_insert', $handler);
			$owner->BindToEvent('after_update', $handler);
		 }
		parent::__construct($owner, $name, $title, $options);
	 }

	public function MakeInput()
	 {
		\MSConfig::RequireFile('multiselect3');
		\ResourceManager::AddJS('lib.multiselect3');
		\ResourceManager::AddCSS('lib.multiselect3');
		$this->SetOption('__label_class', '_multirow');
		$o = ['init' => $this->GetOption('init'), 'collapse' => $this->GetOption('collapse'), 'class' => 'form__input_wr _multilevel'];
		if(null === $o['collapse']) $o['collapse'] = 'uncheck';
		if($c = $this->GetOption('class')) $o['class'] .= " $c";
		if(null === ($fk = $this->GetFKeysMeta()))
		 {
			$d = $this->GetOption('data');
			$t_fld = $this->GetOption('title_fld') ?: (\MSFieldSet\Select::IsDataCallable($d) ? 'title' : null);
		 }
		else
		 {
			$t_fld = $this->GetOption('title_fld') ?: 'title';
			$order_by = '`title` ASC';
			if($row = $this->GetFieldSet()->GetDBRow($row_id))
			 {
				$d = \DB::SelectLJ([
						't2' => [$fk[2]->table, ''],
						'joint' => [$this->GetOption('joint'), '', "`joint`.`{$fk[2]->key}` = `t2`.`{$fk[2]->field}` AND `joint`.`{$fk[1]->key}` = ?"],
					], "`joint`.`{$fk[1]->key}` AS `__checked`, `t2`.`{$fk[2]->field}` AS `id`, `t2`.`$t_fld` AS `title`", false, [$row_id], $order_by);
			 }
			else $d = \DB::Select($fk[2]->table, "`{$fk[2]->table}`.`{$fk[2]->field}` AS `id`, `{$fk[2]->table}`.`$t_fld` AS `title`", false, null, $order_by);
			$t_fld = 'title';
			$o['check_fld'] = '__checked';
		 }
		$obj = new \MultiSelect3($d, $this->GetOption('value_fld') ?: (\MSFieldSet\Select::IsDataCallable($d) ? 'id' : null), $t_fld, $this->GetOption('data_attrs'), $o);
		return $obj->SetId($this->GetId())->SetName($this->GetInputName())->Make();
	 }
}
?>
<?php
namespace MSFieldSet;

interface IField
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = []);
	public function MakeInput();
	public function GetValue();
	public function GetInputValue();
	public function Omitted();
	public function SetOption($name, $value);
}

interface IFieldAsync
{
	public function GetData();
}

interface IFile {}
interface IIgnoreValue {}

abstract class Field implements IField
{
	use \TOptions;

	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->owner = $owner;
		$this->name = $name;
		$this->title = $title;
		$this->AddOptionsMeta([
						'__data' => ['set' => true],
						'__field' => [],
						'__label_class' => ['set' => true],
						'__no_label' => ['set' => true, 'type' => 'bool', 'value' => false],
						'__row_class' => ['set' => true, 'type' => 'string,false', 'value' => ''],
						'class' => ['type' => 'string', 'value' => ''],
						'disabled' => ['type' => 'bool', 'value' => false],
						'readonly' => ['type' => 'bool', 'value' => false],
						'required' => ['type' => 'bool', 'value' => false],
						'type' => [],
						'value' => ['set' => true],
					]);
		$this->SetOptionsData($options);
	 }

	final public function GetErrMsg()
	 {
		if($this->HasErrMsg($val))
		 {
			unset($_SESSION[$this->GetFieldSet()->GetSessionId()]['error'][$this->GetName()]);
			return $val;
		 }
	 }

	final public function HasErrMsg(&$msg = null)
	 {
		$sess_id = $this->GetFieldSet()->GetSessionId();
		$msg = ($r = isset($_SESSION[$sess_id]['error'][$this->GetName()])) ? $_SESSION[$sess_id]['error'][$this->GetName()] : null;
		return $r;
	 }

	final public function SetCheck($ch_name, array $options = [])
	 {
		if($this->check) throw new \EFSCheckIsSet('Проверка для поля `'.$this->GetFieldSet()->GetId().'`.`'.$this->GetName().'` уже установлена ('.get_class($this->check).').');
		if('\\' !== $ch_name[0]) $ch_name = "\MSFieldSet\\$ch_name";
		return $this->InitCheck(new $ch_name($this, $options));
	 }

	final public function GetType($long = true)
	 {
		if(null === $this->type)
		 {
			$c = get_class($this);
			$this->type = (false === ($pos = strrpos($c, '\\'))) ? ['long' => $c, 'short' => $c] : ['long' => str_replace('\\', '-', $c), 'short' => substr($c, $pos + 1)];
		 }
		return $this->type[$long ? 'long' : 'short'];
	 }

	final public function GetFieldSet() { return $this->owner; }
	final public function GetName() { return $this->name; }
	final public function GetTitle() { return $this->title; }
	final public function GetInputName() { return $this->GetFieldSet()->GetId().'_'.$this->GetName(); }
	final public function GetId() { return $this->GetFieldSet()->GetId().'_'.$this->GetName(); }

	final public function GetCheck()
	 {
		if(null === $this->check)
		 {
			if($this->GetOption('required')) $this->InitCheck(new $this->default_check($this));
			else $this->check = false;
		 }
		return $this->check;
	 }

	final protected function SetErrMsg($val) { $_SESSION[$this->GetFieldSet()->GetSessionId()]['error'][$this->GetName()] = $val; }

	final protected function Validate($val)
	 {
		if($check = $this->GetCheck())
		 {
			$status = $check->Validate($val);
			if(true === $status);
			else
			 {
				$e = is_array($status) ? new \EFSCheckFailed(...$status) : new \EFSCheckFailed("$status");
				$this->SetErrMsg($e->GetMessage());
				throw $e;
			 }
		 }
	 }

	final private function InitCheck(\MSFieldSet\FSCheck $check) { return $this->check = $check; }

	protected $default_check = '\MSFieldSet\IsNotEmpty';

	private $owner;
	private $name;
	private $title;
	private $check = null;
	private $type = null;
}

abstract class POSTField extends Field
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['default' => ['set' => true], 'null' => ['type' => 'bool', 'value' => false], 'post_process' => [], 'pre_process' => []]);
		parent::__construct($owner, $name, $title, $options);
	 }

	final public function __destruct()
	 {
		$fs = $this->GetFieldSet();
		if(!$fs->Crashed() || $fs->IsAsync()) unset($_SESSION[$fs->GetSessionId()]['data'][$this->GetName()]);
	 }

	final public function GetValue()
	 {
		$val = $this->OptionExists('value') ? $this->GetOption('value') : $this->PreProcess($this->GetRawValue());
		$fs = $this->GetFieldSet();
		$_SESSION[$fs->GetSessionId()]['data'][$this->GetName()] = $val;
		if(null === $val)
		 {
			if($this->OptionExists('value'));//переходим к проверке валидности данных: $this->Validate($val);
			elseif($this->Omitted())
			 {
				if($fs->FieldOmissionAllowed($this->GetName())) return new \FSVoidValue();
				elseif($fs->IsRunning())
				 {
					$e = new \ЕFSFieldOmitted("Для поля `{$this->GetName()}` нет данных!");
					$this->SetErrMsg($e->GetMessage());
					throw $e;
				 }
				elseif($this->OptionExists('default')) $val = $this->GetOption('default');
			 }
		 }
		$this->Validate($val);
		return $this->PostProcess($val);
	 }

	final public function GetInputValue()
	 {
		if($this->OptionExists('value')) return $this->GetOption('value');// разве его не нужно обрабатывать (PreProcess)? разве оно отличается от raw value?
		$fs = $this->GetFieldSet();
		if($fs->IsRunning()) return $this->PreProcess($this->GetRawValue());
		return isset($_SESSION[$fs->GetSessionId()]['data'][$this->GetName()]) ? $_SESSION[$fs->GetSessionId()]['data'][$this->GetName()] : $this->GetOption('default');
	 }

	public function Omitted() { return !array_key_exists($this->GetInputName(), $_POST); }

	protected function PreProcess($value) { return ($callback = $this->GetOption('pre_process')) ? call_user_func($callback, $value, $this) : $value; }
	protected function PostProcess($value) { return ($callback = $this->GetOption('post_process')) ? call_user_func($callback, $value, $this) : $value; }

	final protected function GetRawValue() { return @$_POST[$this->GetInputName()]; }
}

abstract class RenderableInput extends POSTField
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['autocomplete' => ['type' => 'bool,string', 'value' => ''], 'data_x' => ['set' => true, 'type' => 'array', 'value' => []], 'init' => ['set' => true, 'value' => 'auto'], 'placeholder' => ['type' => 'string,true', 'value' => ''], 'on_create' => ['type' => 'callback,null'], 'on_show' => ['type' => 'callback,null']]);
		parent::__construct($owner, $name, $title, $options);
		if($callback = $this->GetOption('on_create')) return call_user_func($callback, $this);
	 }

	protected function GetAttrStr()
	 {
		$r = '';
		$this->WalkAttrs(function($k, $v) use(&$r){$r .= " $k='$v'";});
		return $r;
	 }

	protected function GetAttrArr()
	 {
		$r = [];
		$this->WalkAttrs(function($k, $v) use(&$r){$r[$k] = $v;});
		return $r;
	 }

	protected function GetAttrLine()
	 {
		$r = [];
		$this->WalkAttrs(function($k, $v) use(&$r){ $r[] = $k; $r[] = $v; });
		return $r;
	 }

	protected function GetDataAttrStr()
	 {
		$r = '';
		$this->WalkDataAttrs(function($k, $v) use(&$r){$r .= " data-$k='$v'";});
		return $r;
	 }

	protected function GetDataAttrArr()
	 {
		$r = [];
		$this->WalkDataAttrs(function($k, $v) use(&$r){$r[$k] = $v;});
		return $r;
	 }

	protected function GetDataAttrLine()
	 {
		$r = [];
		$this->WalkDataAttrs(function($k, $v) use(&$r){ $r[] = $k; $r[] = $v; });
		return $r;
	 }

	final private function WalkAttrs($func)
	 {
		$func('id', $this->GetId());
		$func('name', $this->GetInputName());
		$n = 'autocomplete';
		$v = $this->GetOption($n);
		if(false === $v || 'off' == $v) $func($n, 'off');
		elseif(true === $v || 'on' == $v) $func($n, 'on');
		foreach([
				'placeholder' => function(&$v, $n){if(true === $v) $v = $this->GetTitle();},
			] as $n => $f)
		 {
			if('' !== ($v = $this->GetOption($n)))
			 {
				if($f) $f($v, $n);
				if($v) $v = \Filter::TextAttribute($v);
				$func($n, $v);
			 }
		 }
		foreach([
				'class' => function(&$v, $n){
					if($this->required__classes)
					 {
						$c = $this->required__classes;
						if('' !== $v)
						 {
							if(false === strpos($v, ' ')) $c[$v] = $v;
							else foreach(array_filter(explode(' ', $v)) as $k) $c[$k] = $k;
						 }
						$v = implode(' ', $c);
					 }
				},
			] as $n => $f)
		 {
			$v = $this->GetOption($n);
			$f($v, $n);
			if('' !== $v) $func($n, $v);
		 }
		foreach(['readonly', 'disabled', 'required'] as $n) if($this->GetOption($n)) $func($n, $n);
	 }

	final private function WalkDataAttrs($func)
	 {
		if($opt = $this->GetOption('data_x')) foreach($opt as $n => $v) if(null !== $v) $func($n, $v ? \Filter::TextAttribute($v) : $v);
	 }

	protected $required__classes = [];
}

class AdapterInput extends RenderableInput
{
	public function MakeInput() { if($callback = $this->GetOption('on_show')) return call_user_func($callback, $this); }
}

class TextInput extends RenderableInput
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['list' => [], 'maxlength' => []]);
		$this->ChangeOptionsMeta('class', ['value' => 'form__input_text']);
		parent::__construct($owner, $name, $title, $options);
	 }

	public function MakeInput() { return $this->MakeCode('text'); }

	protected function GetAttrs(array $a = [])
	 {
		foreach(['list' => 'strval', 'maxlength' => 'intval'] as $k => $v)
		 if(isset($a[$k])) throw new \Exception("Attribue '$k' already exists!");
		 else $a[$k] = $v;
		return $a;
	 }

	protected function PreProcess($value) { return parent::PreProcess(trim($value)); }

	protected function AddValueAttr(&$s)
	 {
		$v = $this->GetInputValue();
		$s .= ' value="'.(empty($v) ? $v : \Filter::TextAttribute($v)).'"';
	 }

	final protected function MakeCode($type)
	 {
		$s = $this->GetAttrStr();
		$this->AddValueAttr($s);
		foreach($this->GetAttrs() as $attr => $t) if(null !== ($v = $this->GetOption($attr))) $s .= " $attr='{$t($v)}'";
		$s .= $this->GetDataAttrStr();
		return "<input type='$type'$s />";
	 }
}

class Email extends TextInput
{
	final public function MakeInput() { return $this->MakeCode('email'); }
}

class Tel extends TextInput
{
	final public function MakeInput() { return $this->MakeCode('tel'); }
}

class Number extends TextInput
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['max' => [], 'min' => []]);
		parent::__construct($owner, $name, $title, $options);
	 }

	final public function MakeInput() { return $this->MakeCode('number'); }

	protected function GetAttrs(array $a = []) { return parent::GetAttrs(['min' => 'intval', 'max' => 'intval']); }

	protected function AddValueAttr(&$s)
	 {
		$v = $this->GetInputValue();
		$s .= ' value="'.(empty($v) ? $v : (int)$v).'"';
	 }
}

class Hidden extends POSTField
{
	public function MakeInput() { return \html::Hidden('id', $this->GetId(), 'name', $this->GetInputName(), 'value', $this->GetInputValue())->SetData('name', $this->GetName()); }
}

class HiddenIgnored extends Hidden implements IIgnoreValue {}

class Textarea extends RenderableInput
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->ChangeOptionsMeta('class', ['value' => 'form__textarea']);
		parent::__construct($owner, $name, $title, $options);
	 }

	public function MakeInput()
	 {
		return '<textarea'.$this->GetAttrStr().'>'.str_replace(['<', '>'], ['&lt;', '&gt;'], $this->GetInputValue()).'</textarea>';
	 }
}

abstract class Captcha extends Field implements IIgnoreValue
{
	final public function Omitted() { return false; }
}

class reCaptcha extends Captcha
{
	final public function __construct(\MSFieldSet $owner, $name, $title, array $o = [])
	 {
		$this->AddOptionsMeta(['secret_key' => [], 'site_key' => []]);
		foreach(['secret_key', 'site_key'] as $key)// заменить инициализирующимися опциями!
		 {
			if(empty($o[$key]))
			 {
				$o[$key] = \Registry::GetValue('recaptcha', $key);
				if(!$o[$key]) throw new \Exception("Не указан `$key`!");
			 }
		 }
		parent::__construct($owner, $name, $title, $o);
		$this->SetCheck('reCaptchaCheck', ['secret' => $o['secret_key']]);
	 }

	final public function GetValue()
	 {
		$this->Validate($_POST['g-recaptcha-response']);
	 }

	final public function MakeInput()
	 {
		\Page::AddJSLink('https://www.google.com/recaptcha/api.js');
		return "<div class='g-recaptcha' data-sitekey='{$this->GetOption('site_key')}' id='{$this->GetId()}'></div>";
	 }

	final public function GetInputValue() {}
}

class Securimage extends Captcha
{
	final public function __construct(\MSFieldSet $owner, $name, $title, array $o = array())
	 {
		parent::__construct($owner, $name, $title, $o);
		$this->SetCheck('SecurimageCheck');
	 }

	final public function MakeInput()
	 {
		$id = $this->GetId();
		if(file_exists($_SERVER['DOCUMENT_ROOT'].'/securimage/audio'))//$this->GetOption('use_audio'))
		 {
			$swf_url = '/securimage/securimage_play.swf?audio_file=/securimage/securimage_play.php&amp;bgColor1=#fff&amp;bgColor2=#fff&amp;iconColor=#777&amp;borderWidth=1&amp;borderColor=#000';
			$obj = '<object class="securimage_captcha__button _audio" type="application/x-shockwave-flash" data="'.$swf_url.'" width="24" height="24"><param name="movie" value="'.$swf_url.'" /></object>';
		 }
		else $obj = '';
		return '<div class="securimage_captcha">
	<img id="'.$id.'" src="/securimage/securimage_show.php" alt="CAPTCHA Image" class="securimage_captcha__image" width="215" height="80" />
	<input type="text" name="'.$this->GetInputName().'" maxlength="6" class="securimage_captcha__value" autocomplete="off" />
	<div class="securimage_captcha__buttons"><input type="button" class="securimage_captcha__button _reload" onclick="document.getElementById(\''.$id.'\').src = \'/securimage/securimage_show.php?\' + Math.random();" value="" title="Другое изображение" />'.$obj.'</div>
</div>';
	 }

	final public function GetValue()
	 {
		$val = $this->GetInputValue();
		$this->Validate($val);
		return $val;
	 }

	final public function GetInputValue() { return @$_POST[$this->GetInputName()]; }
}

class CheckBox extends POSTField
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->default_check = '\MSFieldSet\HasCheck';
		$this->AddOptionsMeta(['label' => ['value' => true]]);
		parent::__construct($owner, $name, $title, $options);
	 }

	public function MakeInput()
	 {
		$html = \html::Hidden('name', $this->GetInputName(), 'value', 0).\html::CheckBox('id', $this->GetId(), 'name', $this->GetInputName(), 'value', 1, 'checked', $this->GetInputValue(), 'required', $this->GetOption('required'));
		if($l = $this->GetOption('label'))
		 {
			if(true === $l) $l = $this->GetTitle();
			return "<label>$html $l</label>";
		 }
		else return "$html";
	 }
}

class CheckBoxIgnored extends CheckBox implements IIgnoreValue {}

abstract class SelectInput extends RenderableInput
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['data' => [], 'data_attrs' => ['type' => 'array,null'], 'default_option' => ['type' => 'array,string', 'value' => ''], 'title_fld' => ['type' => 'string', 'value' => ''], 'value_fld' => ['type' => 'string', 'value' => '']]);
		parent::__construct($owner, $name, $title, $options);
	 }

	protected function GetInputObject()
	 {
		$d = $this->GetOption('data');
		return new \Select($d, $this->GetOption('value_fld') ?: (self::IsDataCallable($d) ? 'id' : null), $this->GetOption('title_fld') ?: (self::IsDataCallable($d) ? 'title' : null), $this->GetOption('data_attrs'));
	 }

	final public static function IsDataCallable($d) { return is_callable($d) || ($d instanceof \Iterator); }
}

class Select extends SelectInput
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->AddOptionsMeta(['multiple' => ['type' => 'bool', 'value' => false]]);
		$this->ChangeOptionsMeta('class', ['value' => $this->default_css_class]);
		parent::__construct($owner, $name, $title, $options);
	 }

	public function MakeInput()
	 {
		if($func = $this->GetOption('on_show')) call_user_func($func);
		$obj = $this->GetInputObject();
		if($default = $this->GetOption('default_option'))
		 {
			if(!is_array($default)) $default = ['', $default];
			$obj->SetDefaultOption($default[0], $default[1]);
		 }
		$c = $this->GetOption('class');
		$name = $this->GetInputName();
		if($is_m = $this->GetOption('multiple'))
		 {
			$wr_c = ($c ? $c.'_m_wr' : 'multiple_select_wr').' _'.$this->GetName();
			if('auto' === $this->GetOption('init')) $wr_c .= ' _autoinit';
			$c .= ' _multiple';
			$name .= '[]';
		 }
		if($c) $obj->SetClassName($c);
		$obj->SetName($name);
		$html = '';
		if($is_m)
		 {
			if(($values = $this->GetInputValue()) && is_array($values))
			 foreach(array_filter($values) as $v)
			  {
				$html .= $obj->SetSelected($v)->Make();
				if($obj->IsObject())
				 {
					if($r = $this->GetOption('reset')) throw new \Exception('Not implemented yet!');
					else $obj->GetSource()->Rewind();
				 }
			  }
			$v = $default[0];
		 }
		else
		 {
			$v = $this->GetInputValue();
			$obj->SetId($this->GetId());
		 }
		$html .= $obj->SetSelected($v)->Make();
		return $is_m ? "<div id='{$this->GetId()}' class='$wr_c'>$html</div>" : $html;
	 }

	protected $default_css_class = 'form__select';
}

class DBSelect extends Select
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $options = [])
	 {
		$this->ChangeOptionsMeta('data', ['type' => 'array']);
		$this->ChangeOptionsMeta('value_fld', ['value' => 'id']);
		$this->ChangeOptionsMeta('title_fld', ['value' => 'title']);
		parent::__construct($owner, $name, $title, $options);
	 }

	protected function GetInputObject()
	 {
		$d = $this->GetOption('data');
		return new \Select(\DB::Select(...$d), $this->GetOption('value_fld'), $this->GetOption('title_fld'), $this->GetOption('data_attrs'));
	 }
}
// class SelectEmail extends Select
// {
	// final public function __construct(\MSFieldSet $owner, $name, $title, array $o = array())
	 // {
		// if(empty($o['rel_name'])) throw new \Exception('Укажите имя таблицы БД ($options[\'rel_name\']) для поля `'.$owner->GetId().'`.`'.$name.'` (SelectEmail)!');
		// $o['data'] = Relation::Get($o['rel_name'])->Select(@$o['fields'], null, empty($o['order']) ? SQLExpr::MSSimpleListOrder() : $o['order']);
		// $o['method'] = 'FetchAssoc';
		// $o['value_fld'] = 'id';
		// $o['title_fld'] = 'title';
		// parent::__construct($owner, $name, $title, $o);
	 // }

	// final protected function PostProcess($value)
	 // {
		// if($value && ($row = Relation::Get($this->GetOption('rel_name'))->GetAssocById($value)))
		 // {
			// if($row['email'])
			 // {
				// $fs = $this->GetFieldSet();
				// $fs->GetTpl(($tpl = $this->GetOption('tpl')) ? $tpl : 'mdl_'.preg_replace('/_form$/', '', $fs->GetId()))->SetTo($row['email']);
			 // }
			// $value = $row['title'];
		 // }
		// return $value;
	 // }
// }

class File extends Field implements IFile
{
	final public function __construct(\MSFieldSet $owner, $name, $title, array $o = [])
	 {
		$this->AddOptionsMeta(['accept' => [], 'extensions' => []]);
		parent::__construct($owner, $name, $title, $o);
	 }

	public function MakeInput() { return '<input type="file" id="'.$this->GetId().'" name="'.$this->GetInputName().'"'.(($accept = $this->GetOption('accept')) ? ' accept="'.$accept.'"' : '').(($ext = $this->GetOption('extensions')) ? ' data-extensions="'.$ext.'"' : '').' />'; }// расширения должны браться из проверки? как бы сделать так, чтоб accept тоже брался из проверки (тут понадобится соответствие между расширениями и mime-типом)?
	public function GetValue() {}
	public function GetInputValue() {}

	final public function Omitted() { return false; }// это заглушка! можно определить, отправлялся ли файл.
}

class NewPassword extends Field
{
	final public function __construct(\MSFieldSet $owner, $name, $title, array $o = array())
	 {
		parent::__construct($owner, $name, $title, $o);
		$this->SetCheck('PasswordCheck');
	 }

	final public function MakeInput()
	 {
		return '<div class="form__new_password">
	<input type="password" id="'.$this->GetId().'" name="'.$this->GetInputName().'[value]" autocomplete="off" required="required" class="form__input_password" maxlength="48" />
	<input type="button" value="Показать пароль" class="form__new_password_show _hidden" />
	<div class="form__new_password_bar _hidden"><div class="form__new_password_bar_area"></div></div>
	<div class="form__new_password_strength"></div>
	<input type="password" id="'.$this->GetId().'_copy" name="'.$this->GetInputName().'[copy]" autocomplete="off" class="form__input_password" maxlength="48" placeholder="повторите пароль, чтоб не ошибиться" />
	<div class="form__new_password_not_eq"></div>
</div>';
	 }

	final public function GetValue()
	 {
		$val = @$_POST[$this->GetInputName()];
		$this->Validate($val);
		return $val['value'];
	 }

	final public function GetInputValue() {}
	final public function Omitted() { return false; }
}

class Radio extends SelectInput
{
	public function MakeInput()
	 {
		$d = $this->GetOption('data');
		$obj = (new \Radio($d, $this->GetOption('value_fld') ?: (Select::IsDataCallable($d) ? 'id' : null), $this->GetOption('title_fld') ?: (Select::IsDataCallable($d) ? 'title' : null), $this->GetOption('data_attrs')))->SetId($this->GetId())->SetName($this->GetInputName());
		if($v = $this->GetOption('class')) $obj->SetClassName($v);
		return $obj->SetSelected($this->GetInputValue())->Make();
	 }
}

class Password extends POSTField
{
	public function __construct(\MSFieldSet $owner, $name, $title, array $o = array())
	 {
		parent::__construct($owner, $name, $title, $o);
		$this->SetCheck();
	 }

	public function MakeInput()
	 {
		$ac = $this->GetOption('autocomplete');
		return '<input class="form__input_password" type="password" id="'.$this->GetId().'" required="required" name="'.$this->GetInputName().'" maxlength="48"'.(false === $ac || 'off' === $ac ? ' autocomplete="off"' : (true === $ac || 'on' === $ac ? ' autocomplete="on"' : '')).' />';
	 }
}

class Date extends POSTField
{
	public function MakeInput()
	 {
		if($func = $this->GetOption('on_show')) call_user_func($func);
		return '<input id="'.$this->GetId().'" type="date" name="'.$this->GetInputName().'" value="'.$this->GetInputValue().'" />';
	 }
}
?>
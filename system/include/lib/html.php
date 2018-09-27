<?php
require_once(dirname(__FILE__).'/traits.php');

class HTMLAttribute
{
	use TOptions;

	final public function __construct($name, $value = null, array $options = null)
	 {
		$this->SetOptionsData($options);
		$this->AddOptionsMeta(['default' => [], 'allowed' => [], 'min' => []]);
		$this->name = $name;
		if(null === $value)
		 {
			if($v = $this->GetOption('default')) $this->SetValue($v);
		 }
		else $this->SetValue($value);
	 }

	final public function GetName() { return $this->name; }
	final public function GetValue() { return $this->value; }

	public function SetValue($value)
	 {
		$this->value = $value;
		return $this;
	 }

	public function __toString() { return null === $this->value ? '' : ' '.$this->name.'="'.$this->value.'"'; }

	private $name;
	private $value = null;
}

class TextAttribute extends HTMLAttribute
{
	final public static function ProcessText($val) { return str_replace(['"', '<', '>'], ['&quot;', '&lt;', '&gt;'], $val); }

	public function __toString() { return null === ($v = $this->GetValue()) ? '' : ' '.$this->GetName().'="'.($v ? $this->ProcessText($v) : $v).'"'; }
}

class EnumAttribute extends HTMLAttribute
{
	final public function SetValue($value)
	 {
		if(!($allowed = $this->GetOption('allowed'))) throw new Exception("Option `allowed` is not specified for EnumAttribute `{$this->GetName()}`!");
		if(!in_array($value, $allowed)) throw new Exception("Value '$value' is not allowed for attribute `{$this->GetName()}`!");
		return parent::SetValue($value);
	 }
}

class IntAttribute extends HTMLAttribute
{
	final public function SetValue($value)
	 {
		if(null !== ($min = $this->GetOption('min')) && null !== $value && $value < $min) throw new Exception("Value `$value` is less than minimum allowed for IntAttribute `{$this->GetName()}` ($value < $min)!");
		return parent::SetValue($value);
	 }
}

class ReadonlyAttribute extends HTMLAttribute
{
	final public function SetValue($value)
	 {
		if(null === $this->GetValue()) return parent::SetValue($value);
		throw new Exception("Attribute `{$this->GetName()}` is readonly!");
	 }
}

class BooleanAttribute extends HTMLAttribute
{
	public function __toString() { return $this->GetValue() ? ' '.$this->GetName().'="'.$this->GetName().'"' : ''; }
}

class RequiredAttribute extends HTMLAttribute
{
	final public function __toString()
	 {
		if($this->GetValue()) return parent::__toString();
		die('HTML form action attribute is not specified!');// We can not use 'throw new Exception' here. See http://php.net/manual/en/language.oop5.magic.php#object.tostring
	 }
}

class OnOffAttribute extends HTMLAttribute
{
	final public function SetValue($value)
	 {
		if('on' === $value || 'off' === $value) return parent::SetValue($value);
		if(true === $value || false === $value || 1 === $value || 0 === $value) return parent::SetValue($value ? 'on' : 'off');
		throw new Exception("Invalid value `$value` for attribute `{$this->GetName()}`! Must be true, false, 'on', 'off', 1 or 0.");
	 }
}

abstract class HTMLTag
{
	final public function __construct(...$args)
	 {
		$this->AddAttributes($this->attributes);
		$this->attributes['title'] = new TextAttribute('title');
		$this->SetAttrs($args);
	 }

	final public function GetAttr($name) { return false === $this->attributes[$name] ? '' : $this->attributes[$name]->GetValue(); }

	final public function SetAttr(...$args)
	 {
		$this->SetAttrs($args);
		return $this;
	 }

	final public function SetData(...$args)
	 {
		if(1 === count($args) && is_array($args[0])) foreach($args[0] as $k => $v) $this->data["data-$k"] = $v;
		else
		 {
			$this->CheckNumArgs($args, $length);
			for($i = 0; $i < $length; $i += 2) $this->data['data-'.$args[$i]] = $args[$i + 1];
		 }
		return $this;
	 }

	abstract public function GetName();

	protected function AddAttributes(&$attributes) {}

	final protected function GetAttributesAsString()
	 {
		$ret_val = '';
		foreach($this->attributes as $a) $ret_val .= $a;
		foreach($this->data as $a => $v) $ret_val .= " $a=\"$v\"";
		return $ret_val;
	 }

	final private function CheckNumArgs(array $args, &$length)
	 {
		$length = count($args);
		if($length % 2) throw new Exception('Number of arguments must be even (attribute - value)!');
	 }

	final private function SetAttrs(array $args)
	 {
		$this->CheckNumArgs($args, $length);
		for($i = 0; $i < $length; $i += 2)
		 if(isset($this->attributes[$args[$i]]))
		  {
			if(null !== $args[$i + 1])
			 {
				if($this->attributes[$args[$i]]) $this->attributes[$args[$i]]->SetValue($args[$i + 1]);
				else $this->attributes[$args[$i]] = new HTMLAttribute($args[$i], $args[$i + 1]);
			 }
		  }
		 else throw new Exception("Attribute `$args[$i]` is not allowed for tag `{$this->GetName()}`!");
	 }

	private $attributes = ['class' => false, 'id' => false, 'lang' => false, 'tabindex' => false];
	private $data = [];
}

abstract class VoidTag extends HTMLTag
{
	final public function __toString()
	 {
		return "<{$this->GetName()}{$this->GetAttributesAsString()} />";
	 }
}

abstract class NormalTag extends HTMLTag
{
	final public function __toString()
	 {
		if($this->remove_if_empty && !$this->html && !$this->children) return '';
		$html = '';
		foreach($this->children as $tag) $html .= $tag;
		return "<{$this->GetName()}{$this->GetAttributesAsString()}>{$this->html}$html</{$this->GetName()}>";
	 }

	final public function RemoveIfEmpty()
	 {
		$this->remove_if_empty = true;
		return $this;
	 }

	final public function SetHTML($html)
	 {
		$this->html = $html;
		$this->children = [];
		return $this;
	 }

	final public function Append(HTMLTag $tag)
	 {
		if(1 < func_num_args()) foreach(func_get_args() as $tag) $this->Append($tag);
		else $this->children[] = $tag;
		return $this;
	 }

	final public function GetHTML() { return $this->html; }

	private $html;
	private $children = [];
	private $remove_if_empty = false;
}

class HTMLDiv extends NormalTag
{
	final public function GetName() { return 'div'; }
}

class HTMLForm extends NormalTag
{
	final public function GetName() { return 'form'; }

	final protected function AddAttributes(&$attributes)
	 {
		parent::AddAttributes($attributes);
		$attributes['action'] = new RequiredAttribute('action');
		$attributes['autocomplete'] = new OnOffAttribute('autocomplete');
		$attributes['enctype'] = new EnumAttribute('enctype', null, ['allowed' => ['multipart/form-data']]);
		$attributes['method'] = new EnumAttribute('method', null, ['allowed' => ['get', 'post'], 'default' => 'post']);
		$attributes['name'] = false;
		$attributes['target'] = false;
	 }
}

abstract class HTMLInput extends VoidTag
{
	final public function GetName() { return 'input'; }

	protected function AddAttributes(&$attributes)
	 {
		parent::AddAttributes($attributes);
		$attributes['disabled'] = new BooleanAttribute('disabled');
		$attributes['name'] = false;
		$attributes['value'] = new TextAttribute('value');
	 }
}

abstract class HTMLTextInput extends HTMLInput
{
	protected function AddAttributes(&$attributes)
	 {
		parent::AddAttributes($attributes);
		$attributes['maxlength'] = new IntAttribute('maxlength', null, ['min' => 1]);
		$attributes['readonly'] = new BooleanAttribute('readonly');
		$attributes['size'] = false;
		$attributes['placeholder'] = false;
		$attributes['required'] = new BooleanAttribute('required');
		foreach(['autocorrect', 'autocomplete', 'autocapitalize'] as $a) $attributes[$a] = new OnOffAttribute($a);
	 }
}

abstract class HTMLButtonInput extends HTMLInput
{
	
}

class HTMLButton extends HTMLButtonInput
{
	final protected function AddAttributes(&$attributes)
	 {
		parent::AddAttributes($attributes);
		$attributes['type'] = new ReadonlyAttribute('type', 'button');
	 }
}

class HTMLSubmit extends HTMLButtonInput
{
	final protected function AddAttributes(&$attributes)
	 {
		parent::AddAttributes($attributes);
		$attributes['type'] = new ReadonlyAttribute('type', 'submit');
	 }
}

class HTMLCheckBox extends HTMLButtonInput
{
	final protected function AddAttributes(&$attributes)
	 {
		parent::AddAttributes($attributes);
		$attributes['type'] = new ReadonlyAttribute('type', 'checkbox');
		$attributes['checked'] = new BooleanAttribute('checked');
		$attributes['required'] = new BooleanAttribute('required');
	 }
}

class HTMLRadio extends HTMLButtonInput
{
	final protected function AddAttributes(&$attributes)
	 {
		parent::AddAttributes($attributes);
		$attributes['type'] = new ReadonlyAttribute('type', 'radio');
		$attributes['checked'] = new BooleanAttribute('checked');
	 }
}

class HTMLText extends HTMLTextInput
{
	final protected function AddAttributes(&$attributes)
	 {
		parent::AddAttributes($attributes);
		$attributes['list'] = new TextAttribute('list');
		$attributes['pattern'] = new TextAttribute('pattern');
		$attributes['type'] = new ReadonlyAttribute('type', 'text');
	 }
}

class HTMLSearch extends HTMLTextInput
{
	final protected function AddAttributes(&$attributes)
	 {
		parent::AddAttributes($attributes);
		$attributes['pattern'] = new TextAttribute('pattern');
		$attributes['type'] = new ReadonlyAttribute('type', 'search');
	 }
}

class HTMLPassword extends HTMLTextInput
{
	final protected function AddAttributes(&$attributes)
	 {
		parent::AddAttributes($attributes);
		$attributes['type'] = new ReadonlyAttribute('type', 'password');
	 }
}

class HTMLNumber extends HTMLTextInput
{
	final protected function AddAttributes(&$attributes)
	 {
		parent::AddAttributes($attributes);
		$attributes['type'] = new ReadonlyAttribute('type', 'number');
		$attributes['max'] = false;
		$attributes['min'] = false;
	 }
}

class HTMLTel extends HTMLTextInput
{
	final protected function AddAttributes(&$attributes)
	 {
		parent::AddAttributes($attributes);
		$attributes['type'] = new ReadonlyAttribute('type', 'tel');
		$attributes['placeholder'] = new TextAttribute('placeholder', '9997771234');
		$attributes['pattern'] = new TextAttribute('pattern', '[0-9]{10}');
		$attributes['maxlength']->SetValue(10);
	 }
}

class HTMLTextarea extends NormalTag
{
	final public function GetName() { return 'textarea'; }

	final protected function AddAttributes(&$attributes)
	 {
		parent::AddAttributes($attributes);
		$attributes['cols'] = false;
		$attributes['name'] = false;
		$attributes['placeholder'] = false;
		$attributes['required'] = new BooleanAttribute('required');
		$attributes['rows'] = false;
	 }
}

class HTMLSelect extends NormalTag
{
	final public function GetName() { return 'select'; }

	final protected function AddAttributes(&$attributes)
	 {
		parent::AddAttributes($attributes);
		foreach(['disabled', 'multiple'] as $a) $attributes[$a] = new BooleanAttribute($a);
		$attributes['name'] = false;
		$attributes['size'] = false;
	 }
}

class HTMLHidden extends HTMLInput
{
	protected function AddAttributes(&$attributes)
	 {
		parent::AddAttributes($attributes);
		$attributes['type'] = new ReadonlyAttribute('type', 'hidden');
	 }
}

abstract class html
{
	final public static function __callStatic($name, $arguments)
	 {
		$c = "HTML$name";
		if(class_exists($c, false))
		 {
			if(empty(self::$reflections[$c])) self::$reflections[$c] = new ReflectionClass($c);
			return self::CheckClass(self::$reflections[$c]->newInstanceArgs($arguments));
		 }
		trigger_error('Call to undefined method '.__CLASS__."::$name()", E_USER_ERROR);
	 }

	final private static function CheckClass(HTMLTag $obj) { return $obj; }

	final private function __construct() {}

	private static $reflections = [];
}
?>
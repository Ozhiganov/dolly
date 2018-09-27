<?php
abstract class DropDown
{
	use TOptions;

	final public function __construct($source, $f_value = null, $f_title = null, array $data_attrs = null, array $options = null)
	 {
		$this->AddOptionsMeta(['id' => [], 'name' => [], 'class' => [], 'on_create' => [], 'title' => [], 'reverse_selected' => ['type' => 'bool', 'value' => false]]);
		$this->SetOptionsData($options);
		if($data_attrs) foreach($data_attrs as $key => &$attr) if(true === $attr) $attr = $key;
		$this->data = ['source' => $source, 'f_value' => $f_value, 'f_title' => $f_title, 'data_attrs' => $data_attrs];
		$this->OnCreate();
	 }

	final public function SetSelected($val)
	 {
		$this->selected = is_array($val) ? array_combine($val, $val) : [$val => $val];
		return $this;
	 }

	final public function SetDefaultOption($value, $title, array $data = null)
	 {
		$this->default_option = ['value' => $value, 'title' => $title, 'data' => $data];
		return $this;
	 }

	final public function SetId($val)
	 {
		$this->id = $val;
		return $this;
	 }

	final public function SetName($val)
	 {
		$this->name = $val;
		return $this;
	 }

	final public function SetClassName($val)
	 {
		$this->class_name = $val;
		return $this;
	 }

	final public function Disable()
	 {
		$this->disabled = true;
		return $this;
	 }

	final public function GetSelected() { return $this->selected; }
	final public function GetId() { return $this->id ?: $this->GetOption('id'); }
	final public function GetName() { return $this->name ?: $this->GetOption('name'); }
	final public function GetClassName() { return $this->class_name ?: $this->GetOption('class'); }
	final public function IsDisabled() { return $this->disabled; }
	final public function GetSource() { return $this->data['source']; }
	final public function GetDataAttrs() { return $this->data['data_attrs']; }
	final public function IsObject() { return is_object($this->data['source']); }

	protected function OnCreate() { if($callback = $this->GetOption('on_create')) call_user_func($callback, $this); }

	final protected function GetData() { return $this->data; }
	final protected function GetDefaultOption() { return $this->default_option; }

	final protected function IsSelected($id)
	 {
		$r = isset($this->selected[$id]);
		return $this->GetOption('reverse_selected') ? !$r : $r;
	 }

	private $id;
	private $name;
	private $class_name;
	private $selected = null;
	private $disabled = false;
	private $default_option;
	private $data;
}
?>
<?php
class DBRegVal implements \Sunder\Content\IFieldEditable
{
	use TOptions;

	final public function __construct($section_id, $name, array $options = null)
	 {
		$this->section_id = $section_id;
		$this->name = $name;
		$this->options = $options;
	 }

	final public function GetSourceName() { return 'registry'; }
	final public function GetRowId() { return [$this->section_id, $this->name]; }
	final public function GetMeta() {}// по идее, тип нужно будет указывать вручную в опциях конструктора, поскольку поля любых типов хранятся в таблице registry_value одинаково.
	final public function GetName() { return "0:$this->section_id,1:$this->name"; }

	final public function __invoke() { return Registry::GetValue($this->section_id, $this->name); }

	private $section_id;
	private $name;
}
?>
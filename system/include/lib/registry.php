<?php
interface IRegistry
{
	function SetValue($section_id, $name, $value);
	function GetValue($section_id, $name, $clear_cache = false);
}

abstract class Registry
{
	final public static function SetValue($section_id, $name, $value)
	 {
		if(null === self::$instance) self::Init();
		return self::$instance->SetValue($section_id, $name, $value);
	 }

	final public static function GetValue($section_id, $name, $clear_cache = false)
	 {
		if(null === self::$instance) self::Init();
		return self::$instance->GetValue($section_id, $name, $clear_cache);
	 }

	final public static function GetValues($section_id, array $names, array $options = null, $clear_cache = false)
	 {
		if(null === self::$instance) self::Init();
		return self::$instance->GetValues($section_id, $names, $options, $clear_cache);
	 }

	final public static function Set($obj)
	 {
		if(null === self::$ini) return self::$ini = $obj;
		throw new Exception('Registry was initialized with instance of '.get_class($obj).'!');
	 }

	final private static function Init()
	 {
		if(null === self::$ini) self::$ini = new DBRegistry();
		elseif(self::$ini instanceof IRegistry) ;
		elseif(is_callable(self::$ini)) self::$ini = call_user_func(self::$ini);
		elseif(is_string(self::$ini)) self::$ini = new self::$ini();
		$init = function(IRegistry $obj){
			if(null === self::$instance) return self::$instance = $obj;
			throw new Exception('Registry was initialized with instance of '.get_class($obj).'!');
		};
		$init(self::$ini);
	 }

	final private function __construct() {}

	private static $instance = null;
	private static $ini = null;
}

class DBRegistry implements IRegistry
{
	final public function GetText($block_id, $as_array = false) { if($row = $this->GetRowById('text_block', $block_id, 'text')) return $as_array ? $row : $row['text']; }
	/* final public static function SetTextBlock($block_id, $text) { return Relation::Get('text_block')->Replace($block_id, $text); }*/

	final public function SetValue($section_id, $name, $value)
	 {
		DB::Replace('registry_value', ['section_id' => $section_id, 'name' => $name, 'value' => $value]);
	 }

	final public function GetValue($section_id, $name, $clear_cache = false)
	 {
		if($clear_cache || empty($this->cache__reg_values[$section_id]) || !array_key_exists($name, $this->cache__reg_values[$section_id]))
		 {
			$this->cache__reg_values[$section_id][$name] = null;
			$res = DB::Select('registry_value', '*', '`section_id` = :section_id', [':section_id' => $section_id]);
			foreach($res as $row) $this->cache__reg_values[$row->section_id][$row->name] = $row->value;
		 }
		return $this->cache__reg_values[$section_id][$name];
	 }

	final public function GetValues($section_id, array $names, array $options = null, $clear_cache = false)
	 {
		if($clear_cache) $this->cache__reg_values[$section_id] = [];
		$v = [];
		foreach($names as $n) $v[] = $this->GetValue($section_id, $n, false);
		return $v;
	 }

	private $cache__reg_values = [];
}

class FileSystemRegistry implements IRegistry
{
	final public function __construct()
	 {
		MSConfig::RequireFile('filesystemstorage');
		$this->storage = new FileSystemStorage('/registry_value.php', ['readonly' => false, 'root' => MSSE_INC_DIR.'/storage']);
	 }

	final public function SetValue($section_id, $name, $value)
	 {
		$this->storage->{$this->MakeKey($section_id, $name)} = ['section_id' => $section_id, 'name' => $name, 'value' => $value];
	 }

	final public function GetValue($section_id, $name, $clear_cache = false)
	 {
		if($clear_cache) $this->storage->Reload();
		return $this->storage->{$this->MakeKey($section_id, $name)}->value;
	 }

	final public function GetValues($section_id, array $names, array $options = null, $clear_cache = false)
	 {
		if($clear_cache) $this->storage->Reload();
		$v = [];
		foreach($names as $n) $v[] = $this->GetValue($section_id, $n, false);
		return $v;
	 }

	final private function MakeKey($section_id, $name) { return "-$section_id-:=$name="; }

	private $storage;
}
?>
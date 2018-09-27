<?php
class MSSiteManagerMeta
{
	final public static function Instance()
	 {
		if(null === self::$instance) self::$instance = new MSSiteManagerMeta();
		return self::$instance;
	 }

	final public function GetTitle($id) { return $this->data[$id]['title']; }
	final public function GetData() { return $this->data; }

	final public function Add(array $data)
	 {
		foreach($data as $k => $v) $this->AddSingle($k, $v);
		return $this;
	 }

	final public function AddSingle($k, array $v)
	 {
		if(isset($this->data[$k])) throw new Exception("Duplicate key `$k`!");
		else $this->data[$k] = $v;
		return $this;
	 }

	final public function Exists($id, $all = true)
	 {
		$p = $all ? 'data' : 'basic_data';
		return isset($this->{$p}[$id]) ? (empty($this->{$p}[$id]['type']) ?: 0) : false;
	 }

	final public function AliasExists($doc_id, &$alias = null)
	 {
		if(empty($this->data[$doc_id]['alias']))
		 {
			$alias = null;
			return false;
		 }
		else
		 {
			$alias = $this->data[$doc_id]['alias'];
			return true;
		 }
	 }

	final public function CheckPermissionsLevel($doc_id, $level)
	 {
		if(isset($this->data[$doc_id]))
		 {
			if(!empty($this->data[$doc_id]['alias'])) $doc_id = $this->data[$doc_id]['alias'];
			return isset($this->data[$doc_id]['permissions']) ? isset($this->data[$doc_id]['permissions'][$level]) : (0 == $level || 1 == $level);
		 }
	 }

	final private function __construct()
	 {
		$this->basic_data = $this->data = (require_once "$_SERVER[DOCUMENT_ROOT]/system/include/admin_meta.php");
		Events::Dispatch(strtolower(__CLASS__).':on_create', false, ['target' => $this]);
	 }

	private $basic_data;
	private $data;

	private static $instance = null;
}
?>
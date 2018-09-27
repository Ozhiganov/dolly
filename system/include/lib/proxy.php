<?php
// options:
// skip_empty: true or false

// skip_empty: null, false, null or false, any, no // use_empty - ?
// 'array of data' or 'array of callbacks'
// cache:
// passing only value or value and key? order (v - k, k - v)? use_key - ?
class ArrayProxy implements Iterator
{
	final public function __construct(array $data = null, $callback, array $options = null, ...$args)
	 {
		$this->data = $data;
		$this->callback = $callback;
		$this->args = $args;
		$options = $options ? array_merge(self::$defaults, $options) : self::$defaults;
		if(!empty($options['type'])) $this->type = $options['type'];
		if($options['skip_empty'])
		 {
			$this->current = 'GetCurrent_Empty';
			$this->valid = 'IsValid_Empty';
		 }
	 }

	final public function Current() { return $this->{$this->current}(); }

	final public function Key() { return key($this->data); }

	final public function Next() { next($this->data); }

	public function Rewind()
	 {
		// if(null === $this->rows) $this->Init();
		// elseif($this->sth) $this->FetchAllRows();
		reset($this->data);
	 }

	final public function Valid() { return $this->{$this->valid}(); }

	final private function GetCurrent()
	 {
		$v = $this->{$this->invoke}();
		if(null !== $this->type) settype($v, $this->type);
		return $v;
	 }

	final private function GetCurrent_Empty() { return $this->current_value; }

	final private function IsValid() { return null !== key($this->data); }

	final private function IsValid_Empty()
	 {
		while($this->IsValid())
		 {
			if($v = $this->GetCurrent())
			 {
				$this->current_value = $v;
				return true;
			 }
			else $this->Next();
		 }
		return false;
	 }

	final private function Invoke()
	 {
		$v = current($this->data);
		return true === $this->callback ? call_user_func($v, ...$this->args) : call_user_func($this->callback, $v, ...$this->args);
	 }

	// final private function InvokeKey0() { return ; }
	// final private function InvokeKey1() { return ; }

	private $data;
	private $callback;
	private $args;
	private $current_value;
	private $type = null;
	private $current = 'GetCurrent';
	private $valid = 'IsValid';
	private $invoke = 'Invoke';

	private static $defaults = ['skip_empty' => true];
}
?>
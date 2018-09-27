<?php
class ArrayResult implements IDBResult
{
	final public function __construct(array $rows, array $options = null)
	 {
		$this->o = new OptionsGroup($options, ['transform' => ['set' => true]]);
		$this->rows = $rows;
		if($this->o->transform)
		 {
			$this->curr = 'GetCurrent_Transform';
			if(true === $this->o->transform) $this->o->transform = function($k, $v){
					$r = new stdClass;
					$r->id = $k;
					$r->title = $v;
					return $r;
				};
		 }
	 }

	// final public function Attach(array $row)
	 // {
		// $this->rows[] = $row;
		// return $this;
	 // }

	final public function Current() { return $this->{$this->curr}($this->rows); }
	final public function Key() { return key($this->rows); }
	final public function Next() { next($this->rows); }
	final public function Rewind() { reset($this->rows); }
	final public function Valid() { return null !== key($this->rows); }

	final public function Implode($glue, $field = null, $wrap = null) { throw new Exception('not implemented yet'); }

	final public function jsonSerialize()
	 {throw new Exception('not implemented yet');
		// return $this->FetchAll();
	 }

	final public function count() {throw new Exception('not implemented yet'); return count($this->rows); }
	final public function Fetch() {throw new Exception('not implemented yet'); return ($row = each($this->rows)) ? $row['value'] : false; }

	final public function SetFilter($callback, ...$args)
	 {throw new Exception('not implemented yet');
		// if($this->filter) throw new Exception('Filter can not be changed.');
		// if(null !== $this->rows) throw new Exception('Filter can not be set after fetching data from DB.');
		// $this->filter = $this->CreateCallbackArgs($callback);
		// $this->filter_args = $args;
		// $this->next = 'FetchRow_Filter';
		return $this;
	 }

	final public function SetCallback($callback = null, ...$args)
	 {
		throw new Exception('not implemented yet');
		return $this;
	 }

	final public function LockCallback()
	 {throw new Exception('not implemented yet');
		// $this->lock_callback = true;
		return $this;
	 }

	final public function FetchField($name = null)
	 {
		throw new Exception('not implemented yet');
	 }

	final public function FetchAll()
	 {
		throw new Exception('not implemented yet');
	 }

	final public function FetchAllFields($name = null, $key = null)
	 {
		throw new Exception('not implemented yet');
	 }

	final private function GetCurrent() { return current($this->rows); }

	final private function GetCurrent_Transform() { return call_user_func($this->o->transform, key($this->rows), current($this->rows)); }

	private $o;
	private $rows;
	private $curr = 'GetCurrent';
}
?>
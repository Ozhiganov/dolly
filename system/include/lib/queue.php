<?php
class Queue
{
	final public function __construct($max_length = 0, $unique = true, array $items = array())
	 {
		$this->max_length = $max_length;
		$this->unique = $unique;
		$this->items = $items;
	 }

	final public function Push($item)
	 {
		if($this->unique && in_array($item, $this->items)) return null;
		$this->items[] = $item;
		return $this->GetMaxLength() && $this->GetLength() > $this->GetMaxLength() ? $this->Shift() : null;
	 }

	final public function Unshift($item)
	 {
		if($this->unique && in_array($item, $this->items)) return null;
		array_unshift($this->items, $item);
		return $this->GetMaxLength() && $this->GetLength() > $this->GetMaxLength() ? $this->Pop() : null;
	 }

	final public function Reset() { reset($this->items); }
	final public function Next() { return ($i = each($this->items)) ? $i['value'] : null; }
	final public function Pop() { return array_pop($this->items); }
	final public function Shift() { return array_shift($this->items); }
	final public function GetLength() { return count($this->items); }
	final public function GetMaxLength() { return $this->max_length; }
	final public function GetItem($index) { return $this->items[$index]; }
	final public function GetArray() { return $this->items; }
	final public function SearchItem($item) { return array_search($item, $this->items); }
	final public function RemoveItem($index) { array_splice($this->items, $index, 1); }
//	final public function SetMaxLength($value) { $this->max_length = $value; }

	protected $items;
	private $max_length;
	private $unique;
}
?>
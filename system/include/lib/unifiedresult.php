<?php
class EMSDBUnifiedResultAttachSelf extends EMSDB {}

class UnifiedResult implements IDBResult
{
	final public function __construct(...$results)
	 {
		foreach($results as $res) $this->Attach($res);
	 }

	final public function Attach(IDBResult $res, ...$results)
	 {
		if($this === $res) throw new EMSDBUnifiedResultAttachSelf('Can not attach self!');
		$this->results[] = $res;
		foreach($results as $res) $this->Attach($res);
		return $this;
	 }

	final public function Fetch()
	 {
		while($this->curr_i < count($this->results))
		 {
			if($row = $this->results[$this->curr_i]->Fetch()) return $row;
			++$this->curr_i;
		 }
		return null;
	 }

	final public function FetchAll() { throw new Exception('not implemented yet...'); return ; }

	final public function FetchAllFields($name = null, $key = null) { throw new Exception('not implemented yet...'); return ; }
	
	final public function Current() { return $this->results[$this->curr_i]->Current(); }

	final public function Key() { return $this->curr_i.'.'.$this->results[$this->curr_i]->Key(); }

	final public function Next()
	 {
		if($this->curr_i < count($this->results))
		 {
			$this->results[$this->curr_i]->Next();
			if($this->results[$this->curr_i]->Valid()) return true;
			else
			 {
				++$this->curr_i;
				return false;
			 }
		 }
		return null;
	 }

	public function Rewind()
	 {
		foreach($this->results as $res) $res->Rewind();
		$this->curr_i = 0;
	 }

	final public function Valid()
	 {
		while($this->curr_i < count($this->results))
		 {
			if($this->results[$this->curr_i]->Valid()) return true;
			else ++$this->curr_i;
		 }
		return false;
	 }

	final public function count()
	 {
		$ret_val = 0;
		foreach($this->results as $res) $ret_val += $res->count();
		return $ret_val;
	 }

	final public function FetchField($name = null)
	 {
		return ($row = $this->Fetch()) ? $row->{null === $name ? $this->field_0_name : $name} : null;
	 }

	final public function Implode($glue, $field = null, $wrap = null) { throw new Exception('not implemented yet'); }

	final public function jsonSerialize()
	 {
		return $this->FetchAll();
	 }

	public function SetFilter($callback, ...$args)
	 {
		foreach($this->results as $res) $res->SetFilter($callback, ...$args);
		return $this;
	 }

	final public function SetCallback($callback, ...$args)
	 {
		foreach($this->results as $res) $res->SetCallback($callback, ...$args);
		return $this;
	 }

	public function LockCallback()
	 {
		foreach($this->results as $res) $res->LockCallback();
		return $this;
	 }

	private $results = [];
	private $curr_i = 0;
}
?>
<?php
class TimeMeter
{
	final public function __construct()
	 {
		$this->Start();
	 }

	final public function Start()
	 {
		$this->start = microtime(true);
		return $this;
	 }

	final public function __toString()
	 {
		$v0 = $v = $this->GetResult();
		$p = '';
		foreach(['', 'm', 'µ'] as $p)
		 if($v >= 1) break;
		 else $v *= 1000;
		return "$v {$p}sec ($v0)";
	 }

	final public function GetResult()
	 {
		$val = microtime(true) - $this->start;
		return $val;
	 }

	private $start;
}
?>
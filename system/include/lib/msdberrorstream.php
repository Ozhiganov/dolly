<?php
class MSDBErrorStream implements IMSErrorStream
{
	final public function __construct($on_init = false)
	 {
		$this->on_init = $on_init;
	 }

	final public function InsertException(Exception $e)
	 {
		$this->Init();
		MSConfig::LogException($e);
	 }

	final public function InsertError(array $error)
	 {
		$this->Init();
		MSConfig::LogError($error);
	 }

	final public function GetExceptionById($id)
	 {
		$this->Init();
		return Relation::Get('sys_exception')->GetAssocById($id);
	 }

	final public function GetErrorById($id)
	 {
		$this->Init();
		return Relation::Get('sys_error')->GetAssocById($id);
	 }

	final private function Init() { if($this->on_init) call_user_func($this->on_init); }

	private $on_init;
}
?>
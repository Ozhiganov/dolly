<?php
class msmq_message extends stdClass
{
	final public function __construct($text, $type)
	 {
		$this->text = $text;
		$this->type = $type;
	 }

	final public function __toString() { return $this->text; }
	final public function __isset($name) { return ('text' === $name || 'type' === $name); }
	final public function __get($name) { if('text' === $name || 'type' === $name) return $this->$name; }
	final public function __set($name, $value) { if('text' === $name || 'type' === $name) throw new Exception('`text` and `type` are read only!'); }

	private $text;
	private $type;
}

class MsgQueue
{
	final public static function Get($id)
	 {
		if(empty(self::$instances[$id])) self::$instances[$id] = new MsgQueue($id);
		return self::$instances[$id];
	 }

	final public function __construct($id)
	 {
		if(self::$sess_start && !session_id())
		 {
			try
			 {
				session_start();
			 }
			catch(Exception $e)
			 {
				unset($_COOKIE[session_name()]);
				$_SESSION = array();
				session_destroy();
				session_start();
			 }
		 }
		if(isset(self::$instances[$id])) throw new Exception("Message queue with the ID `$id` already exists.");
		$this->id = $id;
		self::$instances[$id] = $this;
	 }

	final public function Add($text, $type = null)
	 {
		$_SESSION[$this->GetSessionId()][] = serialize(new msmq_message($text, $type));
		return $this;
	 }

	final public function GetAll($glue = '<br/>')
	 {
		if(!empty($_SESSION[$this->GetSessionId()]))
		 {
			$msgs = $_SESSION[$this->GetSessionId()];
			unset($_SESSION[$this->GetSessionId()]);
			if(is_callable($glue))
			 {
				$str = '';
				foreach($msgs as $m) $str .= call_user_func($glue, unserialize($m));
				return $str;
			 }
			else return implode($glue, $msgs);
		 }
	 }

	final public function HasErrors()
	 {
		if(!empty($_SESSION[$this->GetSessionId()])) foreach($_SESSION[$this->GetSessionId()] as $m) if('error' === unserialize($m)->type) return true;
		return false;
	 }

	final public function AddError($text) { return $this->Add($text, 'error'); }
	final public function AddWarning($text) { return $this->Add($text, 'warning'); }
	final public function AddSuccess($text) { return $this->Add($text, 'success'); }
	final public function GetId() { return $this->id; }
	final public function IsEmpty() { return empty($_SESSION[$this->GetSessionId()]); }
	final public function Fetch() { return empty($_SESSION[$this->GetSessionId()]) ? null : unserialize(array_shift($_SESSION[$this->GetSessionId()])); }

	final protected function GetSessionId() { return '__msmq_'.$this->GetId(); }

	private $id;

	private static $instances = array();
	private static $sess_start = true;
}
?>
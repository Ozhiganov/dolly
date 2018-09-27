<?php
class PageButtonsCollector extends \EventData
{
	final public function __construct(array $buttons, array $data, array $meta = [])
	 {
		parent::__construct($data, $meta);
		$this->buttons = $buttons;
	 }

	final public function Add($k, $b)
	 {
		if(isset($this->buttons[$k])) throw new \Exception("Duplicate key `$k` for page action!");
		$this->buttons[$k] = $b;
		return $this;
	 }

	final public function AddArray(array $buttons)
	 {
		foreach($buttons as $k => $b) $this->Add($k, $b);
		return $this;
	 }

	final public function GetButtons() { return $this->buttons; }

	private $buttons = [];
}

abstract class PageButtons
{
	final public static function Instance()
	 {
		if(null === self::$instance) self::$instance = new static();
		return self::$instance;
	 }

	final public function GetButtons()
	 {
		if(($obj = $this->GetEventObject($name)) && $obj->HandlerExists($name)) return $obj->DispatchEventData($name, false, new PageButtonsCollector($this->buttons, []))->GetButtons();
		else return $this->buttons;
	 }

	final public function EachButton($callback, stdClass $data)
	 {
		foreach($this->GetButtons() as $name => $b) if($d = call_user_func($b, $data)) call_user_func($callback, $d);
	 }

	abstract protected function GetEventObject(&$name);
	abstract protected function CreateButtons();

	final private function __construct()
	 {
		$this->buttons = $this->CreateButtons();
	 }

	private $buttons;

	private static $instance = null;
}
?>
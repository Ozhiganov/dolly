<?php
class MSFButtons
{
	final public function __construct($url, $save_selected = false)
	 {
		$this->url = $url;
		$this->save_selected = $save_selected;
	 }

	final public function AddGroup($name, $default = null)
	 {
		$r = new ReflectionClass('MSFButtonsGroup');
		return $this->InitGroup($r->newInstance($this, $name, $default), $name);
	 }

	final public function GetGroup($name)
	 {
		if(empty($this->groups[$name])) throw new Exception('Group with name `'.$name.'` doesn\'t exist.');
		return $this->groups[$name];
	 }

	final public function GetUrl() { return $this->url; }
	final public function SaveSelected() { return $this->save_selected; }

	final public function GetRequest(MSFButtonsGroup $caller = null)
	 {
		$r = '';
		foreach($this->groups as $group) if($group != $caller) if($value = $group->GetValue()) $r .= ($r ? '&' : '').$group->GetName().'='.$value;
		return $r;
	 }

	final private function InitGroup(MSFButtonsGroup $group, $name)
	 {
		if(isset($this->groups[$name])) throw new Exception('Group with name `'.$name.'` exists.');
		$this->groups[$name] = $group;
		return $group;
	 }

	private $groups = [];
	private $url;
	private $save_selected;
}

class MSFButtonsGroup implements Iterator
{
	final public function __construct(MSFButtons $filter, $name, $default = null)
	 {
		$this->filter = $filter;
		$this->name = $name;
		$this->default = $default;
	 }

	final public function current()
	 {
		if($btn = current($this->btns))
		 {
			$key = key($this->btns);
			$params = '';
			if($r = $this->filter->GetRequest($this)) $params .= $r.'&';
			$params .= $this->GetName();
			if($key) $params .= "=$key";
			$b = new stdClass();
			$b->index = $this->index++;
			$b->title = $btn;
			$b->href = $this->filter->GetUrl();
			if($params) $b->href .= (strpos($b->href, '?') === false ? '?' : '&').$params;
			$b->count = count($this->btns);
			$b->selected = $this->value == $key;
			$b->class = 'msui_filter__button';
			if($b->selected) $b->class .= ' _selected';
			return $b;
		 }
	 }

	final public function key() { return key($this->btns); }
	final public function next() { next($this->btns); }

	final public function rewind()
	 {
		$this->index = 0;
		reset($this->btns);
	 }

	final public function valid() { return null !== key($this->btns); }

	final public function AddBtn($title, $value)
	 {
		if(isset($this->btns[$value])) throw new Exception("Button with value '$value' exists in group `{$this->GetName()}`.");
		$this->btns[$value] = $title;
		if($this->filter->SaveSelected())
		 {
			if(isset($_GET[$this->GetName()])) $_SESSION['__msfbuttons'][$this->GetName()] = $_GET[$this->GetName()];
			elseif(isset($_SESSION['__msfbuttons'][$this->GetName()])) $_GET[$this->GetName()] = $_SESSION['__msfbuttons'][$this->GetName()];
		 }
		if(@$_GET[$this->GetName()] == $value || (empty($_GET[$this->GetName()]) && $this->GetDefaultValue() == $value)) $this->value = $value;
		return $this;
	 }

	final public function GetName() { return $this->name; }
	final public function GetValue() { return $this->value; }
	final public function GetDefaultValue() { return $this->default; }

	private $btns = [];
	private $value;
	private $index = 0;
	private $filter;
	private $name;
	private $default;
}
?>
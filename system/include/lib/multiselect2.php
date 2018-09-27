<?php
class Multiselect2
{
	use \TOptions;

	final public function __construct($name, $title, $data, $method = null, array $options = null)
	 {
		$this->options = $options;
		if(true === $name)
		 {
			$this->is_array = false;
			$this->name = function($id){return $id;};
		 }
		else
		 {
			if(is_callable($name)) $this->is_array = false;
			$this->name = $name;
		 }
		$this->title = $title;
		$this->data = $data;
		$this->method = $method;
	 }

	final public function Make()
	 {
		\ResourceManager::AddCSS('lib.multiselect2');
		\ResourceManager::AddJS('lib.multiselect2');
		$v = $this->GetOption('value');
		$html = '';
		$opt_chk = $opt_qty = 0;
		if($this->method)
		 {
			while($row = $this->data->{$this->method}()) $html .= $this->MkBlock($row[$this->value_fld], $row[$this->title_fld], $opt_chk, $opt_qty, $v);
		 }
		elseif(is_callable($this->data)) while($row = call_user_func($this->data)) $html .= $this->MkBlock($row[$this->value_fld], $row[$this->title_fld], $opt_chk, $opt_qty, $v);
		else foreach($this->data as $id => $title) $html .= $this->MkBlock($id, $title, $opt_chk, $opt_qty, $v);
		return "<div class='msmultiselect2'><button type='button' class='msmultiselect2__toggle'>{$this->title}: <span class='msmultiselect2__n'>$opt_chk</span> из $opt_qty</button>$html</div>";
	 }

	final private function MkBlock($id, $title, &$opt_chk, &$opt_qty, array $values = null)
	 {
		++$opt_qty;
		if($this->Checked($id, $values))
		 {
			$ch = ' checked="checked"';
			$cl = ' _checked';
			++$opt_chk;
		 }
		else $ch = $cl = '';
		return "<label class='msmultiselect2__option$cl'><input type='checkbox' name='{$this->GetInputName($id)}'$ch value='$id' />$title</label>";
	 }

	final private function Checked($id, $values) { return $values && ($this->is_array ? in_array($id, $values) : !empty($values[$id])); }
	final private function GetInputName($id) { return $this->is_array ? $this->name.'[]' : call_user_func($this->name, $id); }

	private $name;
	private $title;
	private $data;
	private $method;
	private $value_fld = 'id';
	private $title_fld = 'title';
	private $is_array = true;
}
?>
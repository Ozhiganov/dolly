<?php
class SearchSelect 
{
	use TOptions;

	final public function __construct($name, $tbl_name, array $o = null)
	 {
		$this->name = $name;
		$this->tbl_name = $tbl_name;
		$this->SetOptionsData($o);
	 }

	final public function __toString() { return $this->Make(); }
	final public function GetName() { return $this->name; }
	final public function GetTblName() { return $this->tbl_name; }
	final public function GetLimit() { return $this->GetOption('limit') ?: 15; }

	final public function GetRes($condition = false, array $params = null)
	 {
		$col = $this->GetCol();
		return DB::Select($this->tbl_name, "`id`, `$col` AS `title`", $condition, $params, $this->GetOption('order_by') ?: "`$col` ASC", ['limit' => $this->GetLimit()]);
	 }

	final public function Make()
	 {
		$c = '';
		if($this->HasDefaultRes()) $c .= ' _show_latest';
		if($this->GetOption('new')) $c .= ' _new';
		$maxlength = $this->GetOption('maxlength') ?: 64;
		$search = html::Search('class', "msui_input$c", 'maxlength', $maxlength, 'autocomplete', false, 'disabled', $this->GetOption('disabled'))->SetData('name', $this->GetName());
		if($opt = $this->GetOption('params')) $search->SetData('params', is_array($opt) ? implode(',', array_keys($opt)) : $opt);
		$text = html::Text('class', 'msui_input', 'maxlength', $maxlength, 'readonly', true, 'placeholder', $this->GetOption('placeholder'));
		return "<div class='msui_search_select' data-name='{$this->GetName()}'>$text$search<input type='hidden' name='{$this->GetName()}_id' /><div class='msui_search_select__list'></div><input type='button' class='msui_search_select__clear' value='×' /></div>";
	 }

	final public function Handle()
	 {
		if(isset($_GET['search_select_name']) && $this->GetName() === $_GET['search_select_name'])
		 {
			if(isset($_GET['text']) && ($text = trim($_GET['text'])))
			 {
				$text = preg_replace('/\s{2,}/', ' ', $text);
				$text = str_replace(' ', '%', $text);
				$q = (object)['condition' => '', 'params' => []];
				$searchers = [
					function($text){return "$text%";},
					function($text){return "% $text%";},
					function($text){return "%-$text%";},
					function($text){return "%($text%";},
					function($text){return "%#$text%";},
				];
				$c = '';
				foreach($searchers as $k => $v)
				 {
					if($c) $c .= ' OR ';
					$c .= "`{$this->GetCol()}` LIKE :__search_text_$k";
				 }
				if($callback = $this->GetOption('before_search'))
				 {
					if(false === call_user_func($callback, $q, $this->GetRequestParams(), $this)) $this->ShowList([]);
					if($q->condition) $q->condition = "($q->condition) AND ($c)";
					else $q->condition = $c;
				 }
				else $q->condition = $c;
				foreach($searchers as $k => $v) $q->params["__search_text_$k"] = $v($text);
				$this->ShowList($this->GetRes($q->condition, $q->params));
			 }
			elseif($this->HasDefaultRes($res)) $this->ShowList($res);
			else MSDocument::SendXML(null, '', false);
		 }
	 }

	final protected function ProcessItemTitle(stdClass $row) { return $row->title; }
	final protected function GetCol() { return $this->GetOption('column') ?: 'title'; }

	final protected function GetRequestParams()
	 {
		$p = new stdClass;
		if($opt = $this->GetOption('params'))
		 {
			if(is_array($opt))
			 {
				foreach($opt as $k => $f)
				 {
					$v = @$_GET['r'][$k];
					$p->$k = is_callable($f) ? call_user_func($f, $v) : $v;
				 }
			 }
			else foreach(explode(',', $opt) as $k) $p->$k = @$_GET['r'][$k];
		 }
		return $p;
	 }

	final protected function HasDefaultRes(&$res = null)
	 {
		if($callback = $this->GetOption('default'))
		 {
			if(func_num_args() > 0) $res = call_user_func($callback, $this->GetRequestParams(), $this);
			return true;
		 }
	 }

	final protected function ShowList($res) 
	 {
		$xml = '';
		foreach($res as $row) $xml .= "<item id='$row->id'><![CDATA[{$this->ProcessItemTitle($row)}]]></item>";
		MSDocument::SendTextXML($xml);
	 }

	private $name;
	private $tbl_name;
}
?>
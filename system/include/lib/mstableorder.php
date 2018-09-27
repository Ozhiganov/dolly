<?php
class MSTableOrder
{
	use TOptions;

	final public function __construct($tbl_name, $default, array $options = null)
	 {
		$this->tbl_name = $tbl_name;
		$this->AddOptionsMeta(['url_params' => ['type' => 'string', 'value' => ''], 'on_get_expr' => ['type' => 'callback,null']]);
		$this->SetOptionsData($options);
		if($opt = $this->GetOption('url_params')) $this->url_params = "&$opt";
		if(!empty($_GET['msorder']) && preg_match('/^[a-z0-9_\-]{1,50}\.[a-z0-9_\-]{1,50}\.(a|de)sc$/', $_GET['msorder']))
		 {
			$this->order = explode('.', $_GET['msorder']);
			if(count($this->order) != 3 || $this->order[0] != $this->tbl_name) $this->order = null;
		 }
		else
		 {
			$this->order = explode('.', $default);
			array_unshift($this->order, $this->tbl_name);
		 }
	 }

	final public function GetCaption($id, $title, $asc = true)
	 {
		$order = $this->order[1] == $id ? $this->order : null;
		switch($order[2])
		 {
			case 'asc': $order = 'desc';
						$class = ' _asc';
						break;
			case 'desc': $order = 'asc';
						 $class = ' _desc';
						 break;
			default: $order = $asc ? 'asc' : 'desc';
					 $class = '';
		 }
		return "<a href='?msorder={$this->tbl_name}.$id.$order{$this->url_params}' class='mstableorder_header$class'>$title</a>";
	 }

	final public function GetExpr()
	 {
		if($this->order)
		 {
			$expr = "`{$this->order[1]}` {$this->order[2]}";
			return ($opt = $this->GetOption('on_get_expr')) ? call_user_func($opt, $expr, $this->order[2], $this->order[1], $this->order[0]) : $expr;
		 }
	 }

	final public function __toString() { return $this->GetExpr(); }

	private $tbl_name;
	private $url_params = '';
	private $order = null;
}
?>
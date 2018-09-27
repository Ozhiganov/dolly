<?php
MSConfig::RequireFile('mstable');

class MSDBColData
{
	final public function __construct($name, $caption, $width, $colspan)
	 {
		$this->name = $name;
		$this->caption = $caption;
		$this->width = $width;
		$this->colspan = $colspan;
	 }

	final public function SetCallback($val)
	 {
		$this->callback = func_num_args() > 1 ? func_get_args() : $val;
		return $this;
	 }

	final public function SetType($val, array $args = null)
	 {
		$this->type = $val ?: 'TableCell';
		$this->args = $args;
		return $this;
	 }

	final public function SetClick($val)
	 {
		$this->click = $val;
		return $this;
	 }

	final public function SetInputName($val)
	 {
		$this->input_name = $val;
		return $this;
	 }

	final public function SetClass($val)
	 {
		$this->class = $val;
		return $this;
	 }

	final public function SetExpression($val)
	 {
		$this->expression = $val;
		return $this;
	 }

	final public function IsVisible() { return !is_a($this->type, 'IMSTableCellInvisible', true); }
	final public function GetName() { return $this->name; }
	final public function GetCaption() { return $this->caption; }
	final public function GetWidth() { return $this->width; }
	final public function GetColspan() { return $this->colspan; }
	final public function GetCallback() { return $this->callback; }
	final public function GetExpression() { return $this->expression; }
	final public function GetType() { return $this->type; }
	final public function GetArgs() { return $this->args; }
	final public function GetClick() { return $this->click; }
	final public function GetInputName() { return $this->input_name; }
	final public function GetClass() { return $this->class; }

	private $name;
	private $caption;
	private $width;
	private $colspan;
	private $callback;
	private $expression;
	private $type = 'TableCell';
	private $args;
	private $click;
	private $input_name;
	private $class;
}

class MSDBTableStatus implements Countable
{
	final public function __construct($num_rows, $num_pages, $page_num)
	 {
		$this->num_rows = $num_rows;
		$this->num_pages = $num_pages;
		$this->page_num = $page_num;
	 }

	final public function Count() { return $this->num_rows; }
	final public function GetNumPages() { return $this->num_pages; }
	final public function GetPageNum() { return $this->page_num; }

	private $num_rows;
	private $num_pages;
	private $page_num;
}

class MSDBTable
{
	final public function __construct($id, $tbl_name, $columns = false, $condition = false, array $params = null, $order_by = false, array $clauses = null)
	 {
		$this->id = $id;
		if(is_array($tbl_name))
		 {
			$this->tbl_names = $tbl_name;
			$this->tbl_name = reset($this->tbl_names)[0];
		 }
		else
		 {
			$this->tbl_name = $tbl_name;
			$this->tbl_names = [$tbl_name => $tbl_name];
		 }
		$this->columns = $columns;
		$this->condition = $condition;
		$this->params = $params;
		$this->order_by = $order_by;
		$this->clauses = $clauses;
		$this->OnCreate();
	 }

	final public function GetTblName() { return $this->tbl_name; }
	final public function GetTblNames() { return $this->tbl_names; }
	final public function GetAction() { return $this->action; }
	final public function GetCol($name) { return $this->cols[$name]; }
	final public function GetId() { return $this->id; }
	final public function GetRedirect() { return $this->redirect; }

	final public function AddCol($name, $caption = '', $width = 0, $colspan = 0)
	 {
		if(is_array($name)) list($name, $expr) = $name;
		else $expr = false;
		if(isset($this->cols[$name])) throw new EMSTableDuplicateColName("Duplicate column name '$name' in a table [id='{$this->GetId()}'].");
		$this->total_col_width += $width;
		$col = new MSDBColData($name, $caption, $width, $colspan);
		if($expr) $col->SetExpression($expr);
		$this->cols[$name] = $col;
		return $col;
	 }

	final public function SetCaption($val)
	 {
		$this->caption = $val;
		return $this;
	 }

	final public function SetRowAction($inputs, $click = null)
	 {
		$this->row_inputs = $inputs;
		$this->row_click = $click;
	 }

	final public function ConfigInputGroup($name, $button_name, $click = null)
	 {
		if(isset($this->input_groups[$name]))
		 {
			$result = 0;
			if(is_array($this->input_groups[$name]['button'])) $result += 2;
			if(is_array($button_name)) ++$result;
			switch($result)
			 {
				case 1: $names = $button_name; $names[] = $this->input_groups[$name]['button']; break;
				case 2: $names = $this->input_groups[$name]['button']; $names[] = $button_name; break;
				case 3: $names = array_merge($this->input_groups[$name]['button'], $button_name); break;
				default: $names = array($this->input_groups[$name]['button'], $button_name);
			 }
			$this->input_groups[$name]['button'] = $names;
			if($click) $this->input_groups[$name]['click'] = $click;
		 }
		else $this->input_groups[$name] = array('button' => $button_name, 'click' => $click);
	 }

	final public function SetAction($val)
	 {
		$this->action = $val;
		return $this;
	 }

	final public function SetRedirect($val)
	 {
		$this->redirect = $val;
		return $this;
	 }

	final public function AddBtn($title, $submit = false, $position = null)
	 {
		$btn = new MSTableButton($title, $submit, $position);
		$this->btns[] = $btn;
		return $btn;
	 }

	final public function SetPageLength($val)
	 {
		$this->page_length = $val;
		return $this;
	 }

	final public function HideHeader()
	 {
		$this->hide_header = true;
		return $this;
	 }

	final public function SetEmptyContent($val)
	 {
		$this->empty_content = $val;
		return $this;
	 }

	final public function GetTotalColWidth() { return $this->total_col_width; }

	public function Make(MSDBTableStatus &$status_obj = null)
	 {
		$this->OnShow();
		$table = new MSTable($this->id, $this->row_prototype);
		if($this->hide_header) $table->HideHeader();
		$table->SetClass(implode(' ', $this->css_classes));
		$table->SetCaption($this->caption);
		$this->callbacks = [];
		$btn_show_all = '';
		if($lj = count($this->tbl_names) > 1)
		 {
			$alias = key($this->tbl_names);
			if(is_int($alias)) $alias = $this->tbl_name;
			$alias = "`$alias`.";
		 }
		else $alias = '';
		$cols = $this->columns;
		$num = 0;
		foreach($this->cols as $col)
		 {
			$this->AddColToTable($table, $col, $col->IsVisible() ? $num++ : null);
			$name = $col->GetName();
			if($expr = $col->GetExpression()) $cols = "($expr) AS `$name`".($cols ? ', ' : '').$cols;
			elseif(DB::ColExists($this->tbl_name, $name)) $cols = "$alias`$name`".($cols ? ', ' : '').$cols;
		 }
		$clauses = $this->clauses;
		if($this->page_length)
		 {
			$show_all = false;
			if(is_numeric(@$_GET['__mstable_page'][$this->GetId()])) $_SESSION['__mstable_page'][$this->GetId()] = $page_num = $_GET['__mstable_page'][$this->GetId()];
			elseif(is_numeric(@$_SESSION['__mstable_page'][$this->GetId()])) $page_num = $_SESSION['__mstable_page'][$this->GetId()];
			else $page_num = 1;
			if(!$page_num) $show_all = true;
			if(!$lj && empty($clauses['group_by'])) $count = DB::Count($this->tbl_name, $this->condition, $this->params);
			else
			 {
				$c = $clauses;
				$c['return_string'] = true;
				$sql = DB::{$lj ? 'SelectLJ' : 'Select'}($lj ? $this->tbl_names : $this->tbl_name, $cols, $this->condition, $this->params, $this->order_by, $c)->GetQueryString();
				$count = DB::Query("SELECT COUNT(*) FROM ($sql) AS `tmp`")->FetchField();
			 }
			$num_pages = ceil($count / $this->page_length);
			if($page_num < 1 || $page_num > $num_pages) $page_num = 1;
			$table->SetPageLength($num_pages);
			if(!$show_all)
			 {
				$clauses['limit'] = (($page_num - 1) * $this->page_length).', '.$this->page_length;
				$table->SetPageNum($page_num);
				if($num_pages > 1) $btn_show_all = '<div class="mstable_show_all"><a href="'.$table->GetProcessedURIForPageNavigator(0).'">Показать всю таблицу ('.Format::AsInt($count).' строк'.Format::GetAmountStr($count, 'а', 'и', '').')</a></div>';
			 }
		 }
		else $num_pages = $page_num = 1;
		if(!$cols) throw new Exception('No columns defined!');
		$result = DB::{$lj ? 'SelectLJ' : 'Select'}($lj ? $this->tbl_names : $this->tbl_name, $cols, $this->condition, $this->params, $this->order_by, $clauses);
		if($this->callbacks)
		 {
			$result->SetCallback(function($row){
				foreach($this->callbacks as $id => $callback) $row->$id = call_user_func($callback, isset($row->$id) ? $row->$id : null, $row);
				return $row;
			});
		 }
		$table->SetData($result);
		$row_config = $this->row_inputs || $this->row_click ? '{'.($this->row_inputs ? 'inputs:"'.$this->row_inputs.'"' : '').($this->row_inputs && $this->row_click ? ', ' : '').($this->row_click ? 'click:'.$this->row_click : '').'}' : 'null';
		$btns_config = [];
		foreach($this->btns as $btn)
		 {
			$table->AddBtnObj($btn);
			if($btn->GetName() && $btn->GetClick()) $btns_config[] = '"'.$btn->GetName().'":'.$btn->GetClick();
		 }
		$js_params = ["'{$this->GetId()}'"];
		$js_params[] = $row_config;
		$js_params[] = $this->cells_config ? '{'.implode(',', $this->cells_config).'}' : 'null';
		if($this->input_groups)
		 {
			$params = [];
			foreach($this->input_groups as $name => $conf) $params[] = '"'.$name.'":{'.($conf['button'] ? 'button:["'.(is_array($conf['button']) ? implode('", "', $conf['button']) : $conf['button']).'"]' : '').($conf['button'] && $conf['click'] ? ',' : '').($conf['click'] ? 'click:'.$conf['click'] : '').'}';
			$js_params[] = '{'.implode(',', $params).'}';
		 }
		else $js_params[] = 'null';
		if($btns_config) $js_params[] = '{'.implode(',', $btns_config).'}';
		$this->OnConfigJS($js_params);
		$ret_val = '<div class="table_wr">'.$table->Make().'</div>'.$btn_show_all.'<script type="text/javascript">/* <![CDATA[ */new MSDBTable('.implode(',', $js_params).');/* ]]> */</script>';
		$status_obj = new MSDBTableStatus(count($table), $num_pages, $page_num);
		return !count($status_obj) && null !== $this->empty_content ? $this->empty_content : ($this->action ? '<form method="post" action="'.$this->action.'">'.$ret_val.'<div><input type="hidden" value="'.($this->redirect ?: substr($_SERVER['REQUEST_URI'], mb_strlen(MSConfig::GetMSSMDir(), 'utf-8'))).'" name="__redirect" /></div></form>' : $ret_val);
	 }

	protected function OnShow() {}
	protected function OnConfigJS(&$js_params) {}

	protected function OnCreate()
	 {
		ResourceManager::AddCSS('lib.table');
		ResourceManager::AddJS('lib.msdbtable');
	 }

	final protected function GetEmptyContent() { return $this->empty_content; }
	final protected function GetCols() { return $this->cols; }

	final private function AddColToTable(MSTable $table, MSDBColData $col, $num)
	 {
		$name = $col->GetName();
		$tcol = $table->AddCol($name, $col->GetCaption(), $col->GetWidth() ? $col->GetWidth().'%' : 0, $col->GetColspan(), ($args = $col->GetArgs()) ? [$col->GetType(), $args] : $col->GetType());
		$tcol->SetClass($col->GetClass());
		$callback = $col->GetCallback();
		if($callback) $this->callbacks[$name] = $callback;
		if(null !== $num)
		 {
			$js_params = [];
			if($click = $col->GetClick()) $js_params[] = 'click:'.$click;
			if($input_name = $col->GetInputName()) $js_params[] = 'inputs:"'.$input_name.'"';
			if($js_params) $this->cells_config[] = $num.':{'.implode(',', $js_params).'}';
		 }
	 }

	private $id;
	private $tbl_name;
	private $tbl_names;
	private $columns;
	private $condition;
	private $params;
	private $order_by;
	private $clauses;
	private $cols = [];
	private $row_prototype = 'TableRow';
	private $caption;
	private $row_inputs;
	private $row_click;
	private $table;
	private $css_classes = ['table' => 'table', 'hover' => 'hover'];
	private $action;
	private $redirect;
	private $cells_config = [];
	private $input_groups = [];
	private $btns = [];
	private $page_length = 0;
	private $empty_content = null;
	private $hide_header = false;
	private $total_col_width = 0;
	private $callbacks = [];
}
?>
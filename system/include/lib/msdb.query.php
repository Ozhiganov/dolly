<?php
namespace MSDB\Query;

abstract class SelectQuery implements \IDBResult
{
	use \TOptions;

	protected function __construct(array $args, array $options = null)
	 {
		$this->AddOptionsMeta(['db' => ['type' => 'int,string', 'value' => 0]]);
		$this->SetOptionsData($options);
		$this->arguments = $args;
		$this->method = str_replace('MSDB\\Query\\', '', get_class($this));
		foreach($this->callbacks as $k => $v)
		 if(isset($this->arguments[$k]) && is_callable($this->arguments[$k]))
		  {
			$this->callbacks[$k] = $this->arguments[$k];
			$this->arguments[$k] = [];
		  }
	 }

	final public function rewind() { return $this->CreateResult()->rewind(); }
	final public function current() { return $this->result->current(); }
	final public function key() { return $this->result->key(); }
	final public function next() { return $this->result->next(); }
	final public function valid() { return $this->result->valid(); }

	final public function SetParam($name, $value)
	 {
		$this->arguments['params'][$name] = $value;
		$this->changed = true;
		return $this;
	 }

	final public function SetParams(array $params = null)
	 {
		$this->arguments['params'] = $params;
		$this->changed = true;
		return $this;
	 }

	final public function Fetch() { return $this->result->Fetch(); }
	final public function FetchAll() { return $this->result->FetchAll(); }
	final public function Count() { return $this->result->Count(); }
	final public function FetchField($fld_name = null) { return $this->result->FetchField($fld_name); }
	final public function FetchAllFields($name = null, $key = null) { return $this->result->FetchAllFields($name, $key); }
	final public function SetFilter($callback, ...$args) { return $this->result->SetFilter($callback, ...$args); }
	final public function SetCallback($callback, ...$args) { return $this->result->SetCallback($callback, ...$args); }
	final public function LockCallback() { return $this->result->LockCallback(); }

	final public function Implode($glue, $field = null, $wrap = null) { throw new Exception('not implemented yet'); }

	public function jsonSerialize() { return []; }

	final private function CreateResult()
	 {
		if(true === $this->changed)
		 {
			$this->changed = false;
			$args = [];
			foreach($this->arguments as $key => $arg)
			 {
				if(isset($this->callbacks[$key]))
				 {
					$this->changed = true;
					$arg = call_user_func($this->callbacks[$key], $arg);
					if('condition' === $key && null === $arg) return ($this->result = new \EmptyDBResult());
				 }
				$args[] = $arg;
			 }
			$i = $this->GetOption('db');
			if(!\MSDB::InstanceExists($i, $inst)) $inst = \MSDB::Instance()->CloneConnection($i);
			$this->result = $inst->{$this->method}(...$args);
		 }
		return $this->result;
	 }

	protected $method;

	private $arguments;
	private $result = null;
	private $changed = true;
	private $callbacks = ['condition' => null, 'params' => null];
}

class Select extends SelectQuery
{
	final public function __construct($tbl_name, $columns = '*', $condition = false, /* array */ $params = null, $order_by = false, array $clauses = null, array $options = null)
	 {
		parent::__construct(['tbl_name' => $tbl_name, 'columns' => $columns, 'condition' => $condition, 'params' => $params, 'order_by' => $order_by, 'clauses' => $clauses], $options);
	 }
}

class SelectLJ extends SelectQuery
{
	final public function __construct(array $tbl_names, $columns = false, $condition = false, /* array */ $params = null, $order_by = false, array $clauses = null, array $options = null)
	 {
		parent::__construct(['tbl_names' => $tbl_names, 'columns' => $columns, 'condition' => $condition, 'params' => $params, 'order_by' => $order_by, 'clauses' => $clauses], $options);
	 }
}

abstract class MSLayoutQuery
{
	use \TOptions, \TEvents;

	abstract protected function CreateDataSet();

	final public function GetName() { return $this->owner->GetName().':'.$this->GetLName(); }

	protected function __construct(\MSLayout $l, $lname, $fsrc, array $rule_set = null, array $parameters = null, array $options = null)
	 {
		$this->RegisterEvents('layout:after_create', ...self::$layout_events);
		$this->AddOptionsMeta(['data_set' => ['type' => 'array', 'value' => []]]);
		$this->SetOptionsData($options);
		$this->owner = $l;
		$this->lname = $lname;
		$this->fsrc = $fsrc;
		$this->rule_set = $rule_set;
		$this->parameters = $parameters;
		$l->BindToEvent('before_run', function(\EventData $d){
			if(0 === $d->build_number)
			 {
				$this->data_set = $this->CreateDataSet();
				$this->layout = new \Layout($d->layout->GetName().':'.$this->GetLName(), $this->data_set, $this->fsrc, $this->rule_set, $this->parameters);
				$this->DispatchEvent('layout:after_create', false, ['layout' => $this->layout]);
				foreach(self::$layout_events as $n) if($this->HandlerExists($n)) $this->layout->BindToEvent($n, function(\EventData $d, $name){ $this->DispatchEventData($name, false, $d); });
			 }
		});
		$l->BindToEvent('on_fetch', function(\EventData $d){
			$this->data_set->SetParam($this->prm_index, $d->data->id);
		});
	 }

	final protected function GetDataSet() { return $this->data_set; }
	final protected function GetLName() { return $this->lname; }
	final protected function GetFSrc() { return $this->fsrc; }

	private $owner;
	private $layout = null;
	private $data_set;
	private $lname;
	private $fsrc;
	private $rule_set;
	private $parameters;
	private $prm_index = 0;

	private static $layout_events = ['before_run', 'on_fetch', 'empty', 'after_run'];
}

abstract class MSLayoutSelect extends MSLayoutQuery
{
	protected function __construct(\MSLayout $l, $lname, $fr_src, array $rule_set = null, array $parameters = null, $t_name, $columns, $condition, /* array */ $params = null, $order_by, array $clauses = null, array $options = null)
	 {
		parent::__construct($l, $lname, $fr_src, $rule_set, $parameters, $options);
		$this->t_name = $t_name;
		$this->columns = $columns;
		$this->condition = $condition;
		$this->params = $params;
		$this->order_by = $order_by;
		$this->clauses = $clauses;
	 }

	protected function CreateDataSet()
	 {
		$class = "\\MSDB\\Query\\$this->method";
		return new $class($this->GetTName(), $this->GetColumns(), $this->GetCondition(), $this->GetParams(), $this->GetOrderBy(), $this->GetClauses(), $this->GetOption('data_set'));
	 }

	final protected function GetTName() { return $this->t_name; }
	final protected function GetColumns() { return $this->columns; }
	final protected function GetCondition() { return $this->condition; }
	final protected function GetParams() { return $this->params; }
	final protected function GetOrderBy() { return $this->order_by; }
	final protected function GetClauses() { return $this->clauses; }

	protected $method;

	private $t_name;
	private $columns;
	private $condition;
	private $params;
	private $order_by;
	private $clauses;
}

class LayoutSelect extends MSLayoutSelect
{
	public function __construct(\MSLayout $l, $lname, $fr_src, array $rule_set = null, array $parameters = null, $tbl_name, $columns = '*', $condition = false, /* array */ $params = null, $order_by = false, array $clauses = null, array $options = null)
	 {
		$this->method = 'Select';
		parent::__construct($l, $lname, $fr_src, $rule_set, $parameters, $tbl_name, $columns, $condition, $params, $order_by, $clauses, $options);
	 }
}

class LayoutSelectLJ extends MSLayoutSelect
{
	public function __construct(\MSLayout $l, $lname, $fr_src, array $rule_set = null, array $parameters = null, array $tbl_names, $columns = false, $condition = false, /* array */ $params = null, $order_by = false, array $clauses = null, array $options = null)
	 {
		$this->method = 'SelectLJ';
		parent::__construct($l, $lname, $fr_src, $rule_set, $parameters, $tbl_names, $columns, $condition, $params, $order_by, $clauses, $options);
	 }
}
?>
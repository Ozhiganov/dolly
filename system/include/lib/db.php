<?php
require_once(MSSE_LIB_DIR.'/traits.php');

class EMSDB extends Exception {}
	class EMSDBInvalidArgument extends EMSDB {}
	class EMSDBQuery extends EMSDB {}

class MSDBColMeta
{
	final public function __construct($name, $type, $null, $default, $auto_increment)
	 {
		$this->data = ['name' => $name, 'sql_type' => $type, 'null' => $null, 'default' => $default, 'auto_inc' => $auto_increment];
	 }

	final public function __set($name, $value) { throw new Exception('All properties are read only!'); }

	final public function __get($name)
	 {
		if(array_key_exists($name, $this->data)) return $this->data[$name];
		elseif('type' === $name || 'size' === $name || 'unsigned' === $name)
		 {
			if(preg_match('/^(int|varchar|decimal)\(([0-9,]+)\)( unsigned)?$/', $this->data['sql_type'], $m))
			 {
				$this->data['type'] = $m[1];
				$this->data['size'] = $m[2];
				$this->data['unsigned'] = 'int' === $this->data['type'] || 'decimal' === $this->data['type'] ? !empty($m[3]) : null;
			 }
			else
			 {
				$this->data['type'] = $this->data['sql_type'];
				$this->data['size'] = $this->data['unsigned'] = null;
			 }
			return $this->data[$name];
		 }
        throw new Exception('Undefined property: '. __CLASS__ ."::$$name");
	 }

	final public function ToArray()
	 {
		if(null === $this->_as_array)
		 {
			$this->_as_array = [];
			foreach(self::$properties as $key) $this->_as_array[$key] = $this->$key;
		 }
		return $this->_as_array;
	 }

	final public function __toString() { return "`$this->name`"; }

	final public function __debugInfo() { return $this->data; }

	private static $properties = ['name', 'sql_type', 'null', 'default', 'auto_inc', 'type', 'size', 'unsigned'];

	private $data;
	private $_as_array = null;
}

abstract class MSDBTableMetadata
{
	abstract protected function GetColumnData();
	abstract protected function GetKeysData();

	final public function __construct($tbl_name, MSDB $db, ...$args)
	 {
		$this->tbl_name = $tbl_name;
		$this->db = $db;
		$this->args = $args;
	 }

	final public function GetColumn($name)
	 {
		if(null === $this->column_meta) $this->column_meta = $this->GetColumnData();
		return $this->column_meta[$name];
	 }

	final public function ColumnExists($name, MSDBColMeta &$col = null)
	 {
		if(null === $this->column_meta) $this->column_meta = $this->GetColumnData();
		if(isset($this->column_meta[$name]))
		 {
			$col = $this->column_meta[$name];
			return true;
		 }
		else
		 {
			$col = null;
			return false;
		 }
	 }

	final public function KeyExists($name, array &$key = null)
	 {
		if(null === $this->keys) $this->keys = $this->GetKeysData();
		if(isset($this->keys[$name]))
		 {
			$key = $this->keys[$name];
			return true;
		 }
		else
		 {
			$key = null;
			return false;
		 }
	 }

	final public function GetColumns()
	 {
		if(null === $this->column_meta) $this->column_meta = $this->GetColumnData();
		return $this->column_meta;
	 }

	final public function GetPrimaryKey()
	 {
		if(null === $this->primary_key)
		 {
			if(null === $this->column_meta) $this->column_meta = $this->GetColumnData();
			if(null === $this->keys) $this->keys = $this->GetKeysData();
			$this->primary_key = false;
			if(isset($this->keys['PRIMARY']))
			 {
				if(1 === count($this->keys['PRIMARY'])) $this->primary_key = $this->column_meta[$this->keys['PRIMARY'][1]->Column_name];
				else
				 {
					$this->primary_key = [];
					foreach($this->keys['PRIMARY'] as $k => $v) $this->primary_key[$v->Column_name] = $this->column_meta[$v->Column_name];
				 }
			 }
		 }
		return $this->primary_key;
	 }

	final public function GetKey($name)
	 {
		if(null === $this->keys) $this->keys = $this->GetKeysData();
		return $this->keys[$name];
	 }

	final public function GetKeys()
	 {
		if(null === $this->keys) $this->keys = $this->GetKeysData();
		return $this->keys;
	 }

	final protected function GetTableName() { return $this->tbl_name; }
	final protected function GetDB() { return $this->db; }
	final protected function GetArgs() { return $this->args; }

	private $column_meta = null;
	private $primary_key = null;
	private $keys = null;
	private $tbl_name;
	private $db;
	private $args;
}

abstract class MSDBStatic
{
	final public static function CurDate($offset = false)
	 {
		$f = 'Y-m-d';
		return $offset ? date($f, time() + $offset * 86400) : date($f);
	 }

	final public static function Now($offset = false)
	 {
		$f = 'Y-m-d H:i:s';
		return $offset ? date($f, time() + $offset) : date($f);
	 }

	final public static function Microtime()
	 {
		$t = microtime(true);
		return (new DateTime(date('Y-m-d H:i:s.'.sprintf("%06d", ($t - floor($t)) * 1000000), $t)))->format("Y-m-d H:i:s.u");
	 }

	final public static function AddLazyConnection($callback, $index = 0)
	 {
		if(isset(self::$lazy_connections[$index])) throw new EMSDB("Connection with index [$index] already exists.");
		self::$lazy_connections[$index] = $callback;
	 }

	final protected static function GetLazyConnection($index) { if(isset(self::$lazy_connections[$index])) return call_user_func(self::$lazy_connections[$index], $index); }

	private static $lazy_connections = [];
}

abstract class MSDB extends MSDBStatic
{
	use TInstances;

	public function __construct($prefix, $index)
	 {
		$this->prefix = $prefix;
		$this->index = $index;
		self::SetInstance($index, $this);
	 }

	public function __clone()
	 {
		if(is_int($this->index))
		 {
			$index = $this->index;
			while(self::InstanceExists(++$index));
		 }
		else
		 {
			$i = 1;
			while(self::InstanceExists($index = "$this->index-".$i++));
		 }
		$this->index = $index;
		self::SetInstance($index, $this);
	 }

	public function TName($n) { return $this->GetPrefix().$n; }
	public function __debugInfo() { return ['index' => $this->GetIndex()]; }

	final public function GetPrefix() { return $this->prefix; }
	final public function GetIndex() { return $this->index; }

	abstract public function BeginTransaction();
	abstract public function ColExists($tbl_name, $col_name, MSDBColMeta &$col = null);
	abstract public function Commit();
	abstract public function Count($tbl_name, $condition = false, array $params = null, $distinct = false);
	abstract public function Delete($tbl_name, $condition, array $params = null);
	abstract public function DeleteByID($tbl_name, $id);
	abstract public function Exists($tbl_name, $condition, array $params = null);
	abstract public function GetColMeta($tbl_name, $col_name = false);
	abstract public function GetFirstRow($tbl_name, $columns = '*', $condition = false, array $params = null, $order_by = false);
	abstract public function GetKeys($tbl_name);
	abstract public function GetPrimaryKey($tbl_name);
	abstract public function GetRowByCKey($tbl_name, array $key, $columns = '*', $condition = false);
	abstract public function GetRowByCondition($tbl_name, $columns, $condition, array $params = null, $order_by = false, array $options = null);
	abstract public function GetRowByID($tbl_name, $id, $columns = '*', array $options = null);
	abstract public function GetRowByKey($tbl_name, $key, $value, $columns = '*');
	abstract public function GetRowByKeyLJ(array $tbl_names, $key, $value, $columns = false, array $options = null);
	abstract public function Insert($tbl_name, array $values, array $fld_expr = []);
	abstract public function InsertUpdate($tbl_name, array $data, array $duplicate);
	abstract public function KeyExists($tbl_name, $key_name, array &$key = null);
	abstract public function KeysExist($tbl_name, $col_name, array &$keys = null);
	abstract public function Replace($tbl_name, array $data);
	abstract public function RollBack();
	abstract public function Select($tbl_name, $columns = '*', $condition = false, array $params = null, $order_by = false, array $clauses = null);
	abstract public function SelectLJ(array $tbl_names, $columns = false, $condition = false, array $params = null, $order_by = false, array $clauses = null);
	abstract public function TableExists($tbl_name, array &$cols = null);
	/* Note that an INSERT ... ON DUPLICATE KEY UPDATE statement is not an INSERT statement, rowCount won't return the number or rows inserted or updated for such a statement. For MySQL, it will return 1 if the row is inserted, and 2 if it is updated, but that may not apply to other databases. */
	abstract public function Update($tbl_name, array $values, $condition, $order_by = false);
	abstract public function UpdateByID($tbl_name, array $values, $id);
	abstract public function ValueExists($tbl_name, $col_name, $value, $condition = false, array $params = null);

	// final public function ClearCache()
	// final public function IsForeignKey($tbl_name, $key_name, &$key = null)
	// final public function GetForeignKeys($tbl_name)
	// final public function GetPrevNextRows($tbl_name, $type, $id, $columns = '*', $condition = false, array $params = null, $order_by = false)

	private $prefix = '';
	private $index;
}

trait TMSDB
{
	final public function ColExists($tbl_name, $col_name, MSDBColMeta &$col = null)
	 {
		if(empty($this->cache__table_meta[$tbl_name])) $this->SetTableMetadata($tbl_name);
		return $this->cache__table_meta[$tbl_name]->ColumnExists($col_name, $col);
	 }

	final public function KeyExists($tbl_name, $key_name, array &$key = null)
	 {
		if(empty($this->cache__table_meta[$tbl_name])) $this->SetTableMetadata($tbl_name);
		return $this->cache__table_meta[$tbl_name]->KeyExists($key_name, $key);
	 }

	final public function KeysExist($tbl_name, $col_name, array &$keys = null)
	 {
		if(empty($this->cache__table_meta[$tbl_name])) $this->SetTableMetadata($tbl_name);
		$keys = [];
		foreach($this->GetKeys($tbl_name) as $key_name => $k)
		 {
			foreach($k as $key)
			 if($col_name === $key->Column_name)
			  {
				$keys[$key_name] = $k;
				break;
			  }
		 }
		return count($keys) > 0;
	 }

	final public function GetColMeta($tbl_name, $col_name = false)
	 {
		if(empty($this->cache__table_meta[$tbl_name])) $this->SetTableMetadata($tbl_name);
		return $col_name ? $this->cache__table_meta[$tbl_name]->GetColumn($col_name) : $this->cache__table_meta[$tbl_name]->GetColumns();
	 }

	final public function GetKeys($tbl_name)
	 {
		if(empty($this->cache__table_meta[$tbl_name])) $this->SetTableMetadata($tbl_name);
		return $this->cache__table_meta[$tbl_name]->GetKeys();
	 }

	final public function GetPrimaryKey($tbl_name)
	 {
		if(empty($this->cache__table_meta[$tbl_name])) $this->SetTableMetadata($tbl_name);
		return $this->cache__table_meta[$tbl_name]->GetPrimaryKey();
	 }

	protected function GetTableRawMetadata($tbl_name) { return []; }

	final private function SetTableMetadata($tbl_name)
	 {
		$class = get_class($this).'TableMetadata';
		$this->cache__table_meta[$tbl_name] = new $class($this->TName($tbl_name), $this, ...$this->GetTableRawMetadata($tbl_name));
	 }

	private $cache__table_meta = [];
}

function DB($index = 0) { return MSDB::Instance($index); }

class MSDBRow extends stdClass
{
	public function __construct(array $args)
	 {
		if(null === $this->__data) $this->__data = [];
		else $this->__data = ['__data' => $this->__data];
		foreach($args as $name => $value) $this->__data[$name] = $value;
	 }

	public function __isset($name) { return array_key_exists($name, $this->__data); }

	public function __get($name) { return '__data' === $name ? $this->__data[$name] : call_user_func($this->__data[$name], $this, $name); }

	public function __set($name, $value)
	 {
		if('__data' === $name) $this->__data[$name] = $value;
		else $this->$name = $value;
	 }

	private $__data = null;
}

interface IDBResult extends Iterator, JsonSerializable, Countable
{
	public function Fetch();
	public function FetchAll();
	public function FetchField($fld_name = null);
	public function FetchAllFields($name = null, $key = null);
	public function SetFilter($callback, ...$args);
	public function SetCallback($callback, ...$args);
	public function LockCallback();
	public function Implode($glue, $field = null, $wrap = null);
}

abstract class MSDBResult implements IDBResult
{
	use TCallbacks;

	abstract protected function FetchRow();

	public function __clone()
	 {
		throw new Exception('Can not clone instance of '.get_class($this));
	 }

	public function __debugInfo() { return []; }

	final public function FetchAll()
	 {
		if(null === $this->callback)
		 {
			if(null === $this->rows) $this->Open();
			if('NextRow' !== $this->next) $this->FetchAllRows();
			return $this->rows;
		 }
		$this->Rewind();
		$rows = [];
		while($this->Valid())
		 {
			$rows[] = $this->Current();
			$this->Next();
		 }
		return $rows;
	 }

	final public function count()
	 {
		if(null === $this->rows) $this->Open();
		if('NextRow' !== $this->next) $this->FetchAllRows();
		return count($this->rows);
	 }

	final public function Delete()
	 {
		if('NextRow' !== $this->next) $this->Close();
		$this->rows = [];
		return $this;
	 }

	final public function Fetch()
	 {
		if(null === $this->rows) $this->Rewind();
		if($this->Valid())
		 {
			$r = $this->Current();
			$this->Next();
			return $r;
		 }
		else return null;
	 }

	final public function FetchAllFields($name = null, $key = null)
	 {
		$this->Rewind();
		if(null === $name) $name = $this->field_0_name;
		$rows = [];
		if(null === $key)
		 {
			while($this->Valid())
			 {
				$rows[] = $this->Current()->$name;
				$this->Next();
			 }
		 }
		else
		 {
			while($this->Valid())
			 {
				$row = $this->Current();
				$rows[$row->$key] = $row->$name;
				$this->Next();
			 }
		 }
		return $rows;
	 }

	final public function FetchField($name = null) { return ($row = $this->Fetch()) ? $row->{null === $name ? $this->field_0_name : $name} : null; }

	final public function Implode($glue, $field = null, $wrap = null)
	 {
		$s = '';
		$this->Rewind();
		if($wrap)
		 {
			while($row = $this->Fetch())
			 {
				if($s) $s .= $glue;
				$s .= $wrap($row->{null === $field ? $this->field_0_name : $field});
			 }
		 }
		else
		 {
			while($row = $this->Fetch())
			 {
				if($s) $s .= $glue;
				$s .= $row->{null === $field ? $this->field_0_name : $field};
			 }
		 }
		return $s;
	 }

	final public function jsonSerialize()
	 {
		return $this->FetchAll();
	 }

	final public function Key() { return key($this->rows); }

	final public function LockCallback()
	 {
		$this->lock_callback = true;
		return $this;
	 }

	final public function Current() { return $this->{$this->current}(); }

	final public function Valid() { return $this->{$this->valid}(); }

	final public function Next()
	 {
		$this->{$this->next}();
	 }

	final public function Rewind()
	 {
		if(null === $this->rows) $this->Open();
		elseif('NextRow' !== $this->next) $this->FetchAllRows();// это не ошибка: здесь, в отличие от других подобных фрагментов, должно быть else.
		reset($this->rows);
	 }

	final public function SetCallback($callback, ...$args)
	 {
		if($this->CallbackLocked()) throw new Exception('Invocation of `SetCallback` is forbidden.');
		if($callback)
		 {
			$this->callback = $this->CreateCallbackArgs($callback);
			$this->callback_args = $args;
			$this->valid = 'IsValid_Callback';
			$this->current = 'GetCurrent_Callback';
		 }
		else
		 {
			$this->callback = $this->callback_args = null;
			$this->valid = 'IsValid';
			$this->current = 'GetCurrent';
		 }
		return $this;
	 }

	final public function SetFilter($callback, ...$args)
	 {
		if($this->filter) throw new Exception('Filter can not be changed.');
		if(null !== $this->rows) throw new Exception('Filter can not be set after fetching data from DB.');
		$this->filter = $this->CreateCallbackArgs($callback);
		$this->filter_args = $args;
		$this->next = 'AddNextRow_Filter';
		return $this;
	 }

	protected function Close()
	 {
		$this->next = 'NextRow';
	 }

	protected function Open()
	 {
		$this->rows = [];
		if($this->filter)
		 {
			while($row = $this->FetchRow())
			 {
				$r = $this->filter->__invoke($row);
				if(!$row) break;
				if(false === $r) continue;
				$this->rows[] = $row;
				foreach($row as $this->field_0_name => $dummy) break;
				return;
			 }
			$this->Close();
		 }
		elseif($row = $this->FetchRow())
		 {
			$this->rows[] = $row;
			foreach($row as $this->field_0_name => $dummy) break;
		 }
		else $this->Close();
	 }

	final protected function CallbackLocked() { return $this->lock_callback; }

	final private function FetchAllRows()
	 {
		if($this->filter)
		 {
			while($row = $this->FetchRow())
			 {
				$r = $this->filter->__invoke($row);
				if($row)
				 {
					if(false !== $r) $this->rows[] = $row;
				 }
				else break;
			 }
		 }
		else while($row = $this->FetchRow()) $this->rows[] = $row;
		$this->Close();
	 }

	final private function AddRow($row)
	 {
		if($row) $this->rows[] = $row;
		else $this->Close();
		$this->NextRow();
	 }

	final private function AddNextRow()
	 {
		$row = $this->FetchRow();
		$this->AddRow($row);
	 }

	final private function AddNextRow_Filter()
	 {
		while($row = $this->FetchRow())
		 {
			$r = $this->filter->__invoke($row);
			if($row && false === $r) continue;
			break;
		 }
		$this->AddRow($row);
	 }

	final private function NextRow() { next($this->rows); }

	final private function GetCurrent() { return current($this->rows); }

	final private function IsValid() { return null !== key($this->rows); }

	final private function GetCurrent_Callback() { return $this->current_row; }

	final private function IsValid_Callback()
	 {
		while($this->IsValid())
		 {
			$row = clone $this->GetCurrent();
			$r = $this->callback->__invoke($row, ...$this->callback_args);
			if($row)
			 {
				if(false === $r) $this->Next();
				else
				 {
					$this->current_row = $row;
					return true;
				 }
			 }
			else
			 {
				do $this->Next();
				while($this->IsValid());
				return false;
			 }
		 }
		return false;
	 }

	private $field_0_name = null;
	private $filter = null;
	private $filter_args = null;
	private $lock_callback = false;
	private $next = 'AddNextRow';
	private $valid = 'IsValid';
	private $current = 'GetCurrent';
	private $callback = null;
	private $callback_args = null;
	private $current_row = null;
	private $rows = null;
}

class EmptyDBResult implements IDBResult
{
	final public function Fetch() { return false; }
	final public function FetchAll() { return []; }
	final public function count() { return 0; }
	final public function FetchField($fld_name = null) { return false; }
	final public function FetchAllFields($name = null, $key = null) { return false; }
	final public function SetFilter($callback, ...$args) { return $this; }
	final public function SetCallback($callback, ...$args) { return $this; }
	final public function LockCallback() { return $this; }
	final public function rewind() {}
	final public function current() { return false; }
	final public function key() {}
	final public function next() {}
	final public function valid() { return false; }
	final public function jsonSerialize() { return []; }
	final public function Implode($glue, $field = null, $wrap = null) { return ''; }
	final public function __debugInfo() { return []; }
}

abstract class DB extends MSDBStatic
{
	// duplicate interface of MSDB class to use this shortened notation: DB::SomeStaticMethod()
	final public static function InstanceExists($index, MSDB &$inst = null) { return MSDB::InstanceExists($index, $inst); }
	final public static function GetInstancesIDs() { return MSDB::GetInstancesIDs(); }
	final public static function Count($tbl_name, $condition = false, array $params = null, $distinct = false) { return MSDB::Instance(self::$index)->Count($tbl_name, $condition, $params, $distinct); }
	final public static function BeginTransaction() { return MSDB::Instance(self::$index)->BeginTransaction(); }
	final public static function CloneConnection($new_index, array $params = null) { return MSDB::Instance(self::$index)->CloneConnection($new_index, $params); }
	final public static function Commit() { return MSDB::Instance(self::$index)->Commit(); }
	final public static function RollBack() { return MSDB::Instance(self::$index)->RollBack(); }
	final public static function ClearCache() { return MSDB::Instance(self::$index)->ClearCache(); }
	final public static function ColExists($tbl_name, $col_name, MSDBColMeta &$col = null) { return MSDB::Instance(self::$index)->ColExists($tbl_name, $col_name, $col); }
	final public static function Exec($statement) { return MSDB::Instance(self::$index)->Exec($statement); }
	final public static function Exists($tbl_name, $condition, array $params = null) { return MSDB::Instance(self::$index)->Exists($tbl_name, $condition, $params); }
	final public static function Query($statement, ...$params) { return MSDB::Instance(self::$index)->Query($statement, ...$params); }
	final public static function Delete($tbl_name, $condition, array $params = null) { return MSDB::Instance(self::$index)->Delete($tbl_name, $condition, $params); }
	final public static function DeleteByID($tbl_name, $id) { return MSDB::Instance(self::$index)->DeleteByID($tbl_name, $id); }
	final public static function GetColMeta($tbl_name, $col_name = false) { return MSDB::Instance(self::$index)->GetColMeta($tbl_name, $col_name); }
	final public static function GetCreateTable($tbl_name) { return MSDB::Instance(self::$index)->GetCreateTable($tbl_name); }
	final public static function GetForeignKeys($tbl_name) { return MSDB::Instance(self::$index)->GetForeignKeys($tbl_name); }
	final public static function IsForeignKey($tbl_name, $key_name, &$key = null) { return MSDB::Instance(self::$index)->IsForeignKey($tbl_name, $key_name, $key); }
	final public static function GetInformationSchema($tbl_name, $field = false) { return MSDB::Instance(self::$index)->GetInformationSchema($tbl_name, $field); }
	final public static function GetKeys($tbl_name) { return MSDB::Instance(self::$index)->GetKeys($tbl_name); }
	final public static function GetPrefix() { return MSDB::Instance(self::$index)->GetPrefix(); }
	final public static function GetPrevNextRows($tbl_name, $type, $id, $columns = '*', $condition = false, array $params = null, $order_by = false) { return MSDB::Instance(self::$index)->GetPrevNextRows($tbl_name, $type, $id, $columns, $condition, $params, $order_by); }
	final public static function GetPrimaryKey($tbl_name) { return MSDB::Instance(self::$index)->GetPrimaryKey($tbl_name); }
	final public static function GetRowByCondition($tbl_name, $columns, $condition, array $params = null, $order_by = false) { return MSDB::Instance(self::$index)->GetRowByCondition($tbl_name, $columns, $condition, $params, $order_by); }
	final public static function GetRowByID($tbl_name, $id, $columns = '*', array $options = null) { return MSDB::Instance(self::$index)->GetRowByID($tbl_name, $id, $columns, $options); }
	final public static function GetRowByKey($tbl_name, $key, $value, $columns = '*') { return MSDB::Instance(self::$index)->GetRowByKey($tbl_name, $key, $value, $columns); }
	final public static function GetRowByKeyLJ(array $tbl_names, $key, $value, $columns = false, array $options = null) { return MSDB::Instance(self::$index)->GetRowByKeyLJ($tbl_names, $key, $value, $columns, $options); }
	final public static function GetFirstRow($tbl_name, $columns = '*', $condition = false, array $params = null, $order_by = false) { return MSDB::Instance(self::$index)->GetFirstRow($tbl_name, $columns, $condition, $params, $order_by); }
	final public static function Insert($tbl_name, array $values, array $fld_expr = []) { return MSDB::Instance(self::$index)->Insert($tbl_name, $values, $fld_expr); }
	final public static function InsertUpdate($tbl_name, array $data, array $duplicate) { return MSDB::Instance(self::$index)->InsertUpdate($tbl_name, $data, $duplicate); }
	final public static function KeyExists($tbl_name, $key_name, array &$key = null) { return MSDB::Instance(self::$index)->KeyExists($tbl_name, $key_name, $key); }
	final public static function KeysExist($tbl_name, $col_name, array &$keys = null) { return MSDB::Instance(self::$index)->KeysExist($tbl_name, $col_name, $keys); }
	final public static function Replace($tbl_name, array $data) { return MSDB::Instance(self::$index)->Replace($tbl_name, $data); }
	final public static function Select($tbl_name, $columns = '*', $condition = false, array $params = null, $order_by = false, array $clauses = null) { return MSDB::Instance(self::$index)->Select($tbl_name, $columns, $condition, $params, $order_by, $clauses); }
	final public static function SelectLJ(array $tbl_names, $columns = false, $condition = false, array $params = null, $order_by = false, array $clauses = null) { return MSDB::Instance(self::$index)->SelectLJ($tbl_names, $columns, $condition, $params, $order_by, $clauses); }
	final public static function TableExists($tbl_name, array &$cols = null) { return MSDB::Instance(self::$index)->TableExists($tbl_name, $cols); }
	final public static function TName($n) { return MSDB::Instance(self::$index)->TName($n); }
	final public static function Update($tbl_name, array $values, $condition, $order_by = false) { return MSDB::Instance(self::$index)->Update($tbl_name, $values, $condition, $order_by); }
	final public static function UpdateByID($tbl_name, array $values, $id) { return MSDB::Instance(self::$index)->UpdateByID($tbl_name, $values, $id); }
	final public static function ValueExists($tbl_name, $col_name, $value, $condition = false, array $params = null) { return MSDB::Instance(self::$index)->ValueExists($tbl_name, $col_name, $value, $condition, $params); }
	// -- end of duplication
	final public static function GetIndex() { return self::$index; }

	final public static function SetIndex($val)
	 {
		$i = self::$index;
		self::$index = $val;
		return $i;
	 }
		 
	final private function __construct() {}

	private static $index = 0;
}
?>
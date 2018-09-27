<?php
require_once(MSSE_LIB_DIR.'/db.php');

class SQLDBResult extends MSDBResult
{
	public function __construct(PDOStatement $sth, array $options = null)
	 {
		$this->sth = $sth;
		self::$instances[] = $this;
		$this->ConfigPDOStatement($this->sth, $options);
	 }

	final public static function ConfigPDOStatement(PDOStatement $sth, array $options = null)
	 {
		if(!empty($options['properties'])) $sth->setFetchMode(PDO::FETCH_CLASS, 'MSDBRow', [$options['properties']]);
		elseif(!empty($options['fetch_mode'])) $sth->setFetchMode(...$options['fetch_mode']);
	 }

	final public static function CloseAll()
	 {
		foreach(self::$instances as $inst) if($inst->sth) $inst->sth->closeCursor();
	 }

	final public function GetQueryString()
	 {
		if(null === $this->query_string) $this->query_string = $this->sth->queryString;
		return $this->query_string;
	 }

	final protected function Open()
	 {
		$this->sth->execute();
		parent::Open();
	 }

	final protected function Close()
	 {
		$this->query_string = $this->sth->queryString;
		$this->sth->closeCursor();
		$this->sth = null;
		parent::Close();
	 }

	final protected function FetchRow()
	 {
		return $this->sth->fetch();
	 }

	private static $instances = [];

	private $sth;
	private $query_string = null;
}

abstract class SQLDB extends MSDB
{
	use TMSDB;

	public function __construct(array $params, $username, $password, array $driver_options = [], $index = 0)
	 {
		$this->CheckConstructParams($params, self::$p_1, self::$p_2);
		$params = array_merge(self::$p_1, $params);
		if(!$params['dbname']) die(__CLASS__ ."::__construct: 'dbname' can not be empty!");
		parent::__construct(isset($params['prefix']) ? "$params[prefix]" : '', $index);
		$this->Init($params, $username, $password, $driver_options);
		$this->dbname = $params['dbname'];
		$this->connection = [$params, $username, $password, $driver_options];
	 }

	final private function Init(array $params, $username, $password, array $driver_options = [])
	 {
		$dsn = '';
		foreach(self::$p_1 as $k => $dummy) if('driver' != $k && $params[$k]) $dsn .= ($dsn ? ';' : '')."$k=$params[$k]";
		$this->pdo = new PDO("$params[driver]:$dsn", $username, $password, $driver_options);
		$this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
	 }

	public function TName($n) { return '`'.parent::TName($n).'`'; }

	public function __clone()
	 {
		parent::__clone();
		$this->Init(...$this->connection);
	 }

	final public function Exec($statement) { return $this->pdo->exec($statement); }

	final public function Query($statement, ...$params)
	 {
		if($params)
		 {
			$sth = $this->pdo->prepare($statement);
			$this->BindValues($sth, $params);
			$sth->execute();
		 }
		else $sth = $this->pdo->query($statement);
		return new SQLDBResult($sth);
	 }

	final public function CloneConnection($new_index, array $params = null)
	 {
		$this->CheckConstructParams($params, self::$p_2);
		$class = get_class($this);
		return new $class($params ? array_merge($this->connection[0], $params) : $this->connection[0], $this->connection[1], $this->connection[2], $this->connection[3], $new_index);
	 }

	final public function Count($tbl_name, $condition = false, array $params = null, $distinct = false)
	 {
		$q = 'SELECT COUNT('.($distinct ? "DISTINCT $distinct" : '*').') FROM '.$this->TName($tbl_name);
		if($condition) $q .= " WHERE $condition";
		if($params)
		 {
			$sth = $this->pdo->prepare($q);
			$this->BindValues($sth, $params);
			$sth->execute();
		 }
		else $sth = $this->pdo->query($q);
		$v = $sth->fetchColumn();
		$sth->closeCursor();
		return $v;
	 }

	final public function Exists($tbl_name, $condition, array $params = null)
	 {
		$q = "SELECT EXISTS(SELECT * FROM {$this->TName($tbl_name)} WHERE $condition)";
		$sth = $this->pdo->prepare($q);
		if($params) $this->BindValues($sth, $params);
		$sth->execute();
		$v = $sth->fetchColumn();
		$sth->closeCursor();
		return $v;
	 }

	final public function BeginTransaction() { return $this->pdo->beginTransaction(); }
	final public function Commit() { return $this->pdo->commit(); }
	final public function RollBack() { return $this->pdo->rollBack(); }

	final public function ClearCache()
	 {
		$this->cache__table_meta = [];
		$this->cache__foreign_keys = [];
		$this->cache__create_table = [];
	 }

	final public function TableExists($tbl_name, array &$cols = null)
	 {
		if(empty($this->cache__table_meta[$tbl_name]))
		 {
			if($this->GetInformationSchema($tbl_name)) $this->SetTableMetadata($tbl_name);
			else
			 {
				$cols = null;
				return false;
			 }
		 }
		$cols = $this->cache__table_meta[$tbl_name]->GetColumns();
		return true;
	 }

	final public function GetCreateTable($tbl_name)
	 {
		if(empty($this->cache__create_table[$tbl_name]))
		 {
			$sth = $this->pdo->query('SHOW CREATE TABLE '.$this->TName($tbl_name));
			$row = $sth->fetch();
			$sth->closeCursor();
			$this->cache__create_table[$row->Table] = $row->{'Create Table'};
		 }
		return $this->cache__create_table[$tbl_name];
	 }

	final public function GetDBName() { return $this->dbname; }

	final public function Select($tbl_name, $columns = '*', $condition = false, array $params = null, $order_by = false, array $clauses = null)
	 {
		return $this->PrepareSelectQuery($this->TName($tbl_name), $columns, $condition, $params, $order_by, $clauses, $this->MakeDBResOptions(['tbl_name' => $tbl_name], $clauses));
	 }

	final public function SelectLJ(array $tbl_names, $columns = false, $condition = false, array $params = null, $order_by = false, array $clauses = null)
	 {
		$o = ['tbl_name' => []];
		foreach($tbl_names as $k => $v) $o['tbl_name'][$k] = $v[0];
		return $this->PrepareSelectQuery($this->MakeLeftJoin($tbl_names, $columns), $columns, $condition, $params, $order_by, $clauses, $this->MakeDBResOptions($o, $clauses));
	 }

	final protected function MakeLeftJoin(array $tbl_names, &$columns, &$master_alias = null)
	 {
		$q = '';
		foreach($tbl_names as $alias => $data)
		 {
			if(is_int($alias)) $alias = $data[0];
			if($q)
			 {
				$q .= " LEFT JOIN {$this->TName($data[0])} AS `$alias` ON ($data[2])";
				if('*' === $data[1]) foreach($this->GetColMeta($data[0]) as $c => $col) $columns .= ($columns ? ', ' : '')."`$alias`.`$c` AS `{$alias}__$c`";
				elseif($data[1]) foreach(is_string($data[1]) ? explode(',', $data[1]) : $data[1] as $c) $columns .= ($columns ? ', ' : '')."`$alias`.`$c` AS `{$alias}__$c`";
			 }
			else
			 {
				$master_alias = $alias;
				$q .= $this->TName($data[0])." AS `$alias`";
				if('*' === $data[1]) $columns .= ($columns ? ', ' : '')."`$alias`.*";
				elseif($data[1]) foreach(is_string($data[1]) ? explode(',', $data[1]) : $data[1] as $c) $columns .= ($columns ? ', ' : '')."`$alias`.`$c`";
			 }
		 }
		return $q;
	 }

	final protected function PrepareSelectQuery($tbl, $columns, $condition, array $params = null, $order_by, array $clauses = null, array $dbres_options = null)
	 {
		if(!empty($clauses['distinct'])) $columns = "DISTINCT $columns";
		$q = "SELECT $columns FROM $tbl";
		if($condition) $q .= " WHERE $condition";
		if(!empty($clauses['group_by'])) $q .= " GROUP BY $clauses[group_by]";
		if(!empty($clauses['having'])) $q .= " HAVING $clauses[having]";
		if($order_by) $q .= " ORDER BY $order_by";
		if(!empty($clauses['limit'])) $q .= " LIMIT $clauses[limit]";
		$sth = $this->pdo->prepare($q);
		if($params) $this->BindValues($sth, $params);
		return new SQLDBResult($sth, $dbres_options);
	 }

	final public function GetRowByCKey($tbl_name, array $key, $columns = '*', $condition = false)
	 {
		throw new Exception('not implemented yet...');
		// $data = [];
		// $where = '';
		// foreach($key as $field => $value)
		 // {
			// if($where) $where .= ' AND ';
			// $where .= "($field = :$field)";
			// $data[$field] = $value;
		 // }
		// if($condition) $where .= " AND ($condition)";
		// return $this->GetRowByCondition($tbl_name, $columns, $where, $data);
	 }

	final public function GetRowByCondition($tbl_name, $columns, $condition, array $params = null, $order_by = false, array $options = null)
	 {
		$q = "SELECT $columns FROM {$this->TName($tbl_name)} WHERE $condition";
		if($order_by) $q .= " ORDER BY $order_by";
		$options['tbl_name'] = $tbl_name;
		return $this->PrepareAndFetchOnce($q, $params, $options);
	 }

	final public function GetRowByID($tbl_name, $id, $columns = '*', array $options = null)
	 {
		$key = $this->GetPrimaryKey($tbl_name);
		if(is_array($key))
		 {
			$where = '';
			foreach($key as $k) $where .= ($where ? ' AND ' : '')."($k = :$k->name)";
			$data = $id;
		 }
		else
		 {
			$where = "$key = :$key->name";
			$data = [$key->name => $id];
		 }
		return $this->GetRowByCondition($tbl_name, $columns, $where, $data, false, $options);
	 }

	final public function GetRowByKey($tbl_name, $key, $value, $columns = '*')
	 {
		return $this->GetRowByCondition($tbl_name, $columns, "(`$key` = ?)", [$value]);
	 }

	final public function GetRowByKeyLJ(array $tbl_names, $key, $value, $columns = false, array $options = null)
	 {
		$q = $this->MakeLeftJoin($tbl_names, $columns, $master_alias);
		$where = "(`$master_alias`.`$key` = ?)";
		return $this->PrepareAndFetchOnce("SELECT $columns FROM $q WHERE $where", [$value], $options);
	 }

	final public function GetFirstRow($tbl_name, $columns = '*', $condition = false, array $params = null, $order_by = false) { return $this->GetRowByCondition($tbl_name, $columns, $condition, $params, $order_by); }

	final public function IsForeignKey($tbl_name, $key_name, &$key = null)
	 {
		$keys = $this->GetForeignKeys($tbl_name);
		if(isset($keys[$key_name]))
		 {
			$key = $keys[$key_name];
			return true;
		 }
		else
		 {
			$key = null;
			return false;
		 }
	 }

	final public function GetForeignKeys($tbl_name)
	 {
		// RESTRICT - default
		// "SELECT `rc`.TABLE_NAME AS table_name, `rc`.CONSTRAINT_NAME AS constraint_name, UPDATE_RULE AS update_rule, DELETE_RULE AS delete_rule, `rc`.REFERENCED_TABLE_NAME AS referenced_table_name, COLUMN_NAME AS column_name FROM information_schema.REFERENTIAL_CONSTRAINTS AS `rc` LEFT JOIN information_schema.KEY_COLUMN_USAGE AS `kcu` ON (`rc`.CONSTRAINT_SCHEMA = `kcu`.CONSTRAINT_SCHEMA AND `rc`.TABLE_NAME = `kcu`.TABLE_NAME AND `rc`.CONSTRAINT_NAME = `kcu`.CONSTRAINT_NAME) WHERE `rc`.CONSTRAINT_SCHEMA = '$db_name' AND `rc`.TABLE_NAME = '$name'"
		if(!isset($this->cache__foreign_keys[$tbl_name]))
		 {
			$this->cache__foreign_keys[$tbl_name] = [];
			if(preg_match_all('/CONSTRAINT `([a-z0-9_\-]+)` FOREIGN KEY \(`([a-z0-9_\-]+)`\) REFERENCES `([a-z0-9_\-]+)` \(`([a-z0-9_\-]+)`\)( ON DELETE (SET NULL|CASCADE|RESTRICT|NO ACTION))?( ON UPDATE (SET NULL|CASCADE|RESTRICT|NO ACTION))?/i',
							  $this->GetCreateTable($tbl_name),
							  $matches,
							  PREG_SET_ORDER))
			 foreach($matches as $m)
			  $this->cache__foreign_keys[$tbl_name][$m[2]] = ['key' => $m[2], 'table' => $m[3], 'field' => $m[4], 'on_delete' => empty($m[6]) ? 'RESTRICT' : $m[6], 'on_update' => empty($m[8]) ? 'RESTRICT' : $m[8], 'references' => "`$m[3]`.`$m[4]`", 'constraint' => $m[1]];
		 }
		return $this->cache__foreign_keys[$tbl_name];
	 }

	final public function GetInformationSchema($tbl_name, $field = false)
	 {
		if(null === $this->sth_cache__i_schema) $this->sth_cache__i_schema = $this->pdo->prepare("SELECT * FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA` = ? AND `TABLE_NAME` = ?");
		$this->sth_cache__i_schema->execute([$this->GetDBName(), $this->GetPrefix().$tbl_name]);
		$row = $this->sth_cache__i_schema->fetch();
		$this->sth_cache__i_schema->closeCursor();
		return $field ? $row[$field] : $row;
	 }

	final public function GetPrevNextRows($tbl_name, $type, $id, $columns = '*', $condition = false, array $params = null, $order_by = false)
	 {
		$ret_val = [];
		switch($type)
		 {
			case 'position':
				if(isset($params['position'])) ;
				elseif($row = $this->GetRowByID($tbl_name, $id, 'position')) Filter::CopyValues($params, $row, 'position', ['position' => 'position_eq']);
				else return null;
				$params['__this_id'] = $id;
				foreach(['prev' => ['(`position` < :position) OR (`position` = :position_eq AND `id` > :__this_id)', '`position` DESC, `id` ASC'],
						 'next' => ['(`position` > :position) OR (`position` = :position_eq AND `id` < :__this_id)', '`position` ASC, `id` DESC']] as $k => $v)
				 if($row = $this->GetRowByCondition($tbl_name, $columns, $condition ? "($condition) AND ($v[0])" : $v[0], $params, $v[1]))
				  {
					$ret_val[$k] = $row;
					$ret_val[$k]->__type = $k;
				  }
				break;
			default:
				if(!$order_by) throw new Exception('ORDER BY clause can not be empty! You must specify it.');
				$res = $this->Select($tbl_name, $columns, $condition, $params, $order_by);
				if(is_array($id))
				 {
					$key = $id[0];
					$id = $id[1];
				 }
				else $key = 'id';
				while($row = $res->Fetch())
				 {
					if($row[$key] == $id)
					 {
						if($next = $res->Fetch()) $ret_val['next'] = $next;
						break;
					 }
					$ret_val['prev'] = $row;
				 }
				$res->Delete();
				if(!empty($ret_val['next'])) $ret_val['next']['__type'] = 'next';
				if(!empty($ret_val['prev'])) $ret_val['prev']['__type'] = 'prev';
		 }
		return $ret_val;
	 }

	final public function Insert($tbl_name, array $values, array $fld_expr = [])
	 {
		$fields = ($is_single = empty($values[0])) ? $values : $values[0];
		$s1 = $s2 = '';
		foreach($fields as $field => $dummy)
		 {
			if($s1)
			 {
				$s1 .= ', ';
				$s2 .= ', ';
			 }
			$s1 .= "`$field`";
			$s2 .= ":$field";
		 }
		foreach($fld_expr as $field => $expr)
		 {
			if($s1)
			 {
				$s1 .= ', ';
				$s2 .= ', ';
			 }
			$s1 .= "`$field`";
			$s2 .= $expr;
		 }
		$stmt = $this->pdo->prepare("INSERT INTO {$this->TName($tbl_name)} ($s1) VALUES ($s2)");
		if($is_single)
		 {
			foreach($values as $field => $value) $stmt->bindValue($field, $value, $this->GetParamType($value));
			$stmt->execute();
			return ($id = $this->pdo->lastInsertId()) === '0' ? null : $id;
		 }
		else
		 {
			$ids = [];
			foreach($values as $row)
			 {
				foreach($row as $field => $value) $stmt->bindValue($field, $value, $this->GetParamType($value));
				$stmt->execute();
				$ids[] = ($id = $this->pdo->lastInsertId()) === '0' ? null : $id;
			 }
			return $ids;
		 }
	 }

	final public function InsertUpdate($tbl_name, array $data, array $duplicate)
	 {
		$fields = $values = $update = '';
		foreach($data as $k => $v)
		 {
			if($fields)
			 {
				$fields .= ', ';
				$values .= ', ';
			 }
			if($k[0] === '=')
			 {
				$k = substr($k, 1);
				$values .= $v;
			 }
			else $values .= ":$k";
			$fields .= "`$k`";
		 }
		foreach($duplicate as $k => $v)
		 {
			if($update) $update .= ', ';
			if($k[0] === '=')
			 {
				if(true === $v) $v = $data[$k];
				$k = substr($k, 1);
			 }
			else $v = true === $v ? "VALUES(`$k`)" : ":_$k";
			$update .= "`$k` = $v";
		 }
		$stmt = $this->pdo->prepare("INSERT INTO {$this->TName($tbl_name)} ($fields) VALUES ($values) ON DUPLICATE KEY UPDATE $update");
		foreach($data as $k => $value) if($k[0] !== '=') $stmt->bindValue($k, $value, $this->GetParamType($value));
		foreach($duplicate as $k => $value) if($k[0] !== '=' && true !== $value) $stmt->bindValue("_$k", $value, $this->GetParamType($value));
		$stmt->execute();
		return $stmt->rowCount();
	 }

	/* Note that an INSERT ... ON DUPLICATE KEY UPDATE statement is not an INSERT statement, rowCount won't return the number or rows inserted or updated for such a statement. For MySQL, it will return 1 if the row is inserted, and 2 if it is updated, but that may not apply to other databases. */
	final public function Replace($tbl_name, array $data)
	 {
		$fields = $values = $update = '';
		foreach($data as $k => $v)
		 {
			if($fields)
			 {
				$fields .= ', ';
				$values .= ', ';
				$update .= ', ';
			 }
			if($k[0] === '=')
			 {
				$k = substr($k, 1);
				$values .= $v;
			 }
			else $values .= ":$k";
			$fields .= "`$k`";
			$update .= "`$k` = VALUES(`$k`)";
		 }
		$stmt = $this->pdo->prepare("INSERT INTO {$this->TName($tbl_name)} ($fields) VALUES ($values) ON DUPLICATE KEY UPDATE $update");
		foreach($data as $k => $value) if($k[0] !== '=') $stmt->bindValue($k, $value, $this->GetParamType($value));
		$stmt->execute();
		return $stmt->rowCount();
	 }

	final public function Update($tbl_name, array $values, $condition, $order_by = false)
	 {
		$s = '';
		foreach($values as $k => $v)
		 {
			if($k[0] === '~') continue;
			if($s) $s .= ', ';
			$s .= $k[0] === '=' ? '`'.substr($k, 1)."` = $v" : "`$k` = :$k";
		 }
		if(!$s) throw new Exception('SET clause can not be empty in UPDATE statement!');
		$q = "UPDATE {$this->TName($tbl_name)} SET $s";
		if($condition) $q .= " WHERE $condition";
		if($order_by) $q .= " ORDER BY $order_by";
		$stmt = $this->pdo->prepare($q);
		foreach($values as $k => $value) if($k[0] !== '=') $stmt->bindValue($k[0] === '~' ? substr($k, 1) : $k, $value, $this->GetParamType($value));
		$stmt->execute();
		return $stmt->rowCount();
	 }

	final public function UpdateByID($tbl_name, array $values, $id)
	 {
		$key = $this->GetPrimaryKey($tbl_name);
		if(is_array($key)) throw new Exception('not implemented yet...');
		else
		 {
			$condition = "$key = :__db__row_$key->name";
			$values["~__db__row_$key->name"] = $id;
		 }
		return $this->Update($tbl_name, $values, $condition);
	 }

	final public function ValueExists($tbl_name, $col_name, $value, $condition = false, array $params = null)
	 {
		$sth = $this->pdo->prepare("SELECT EXISTS(SELECT * FROM {$this->TName($tbl_name)} WHERE `$col_name` = :__ex_value".($condition ? " AND ($condition)" : '').")");
		$params['__ex_value'] = $value;
		foreach($params as $k => $value) $sth->bindValue($k, $value, $this->GetParamType($value));
		$sth->execute();
		$v = $sth->fetchColumn();
		$sth->closeCursor();
		return $v;
	 }

	final public function Delete($tbl_name, $condition, array $params = null)
	 {
		$q = 'DELETE FROM '.$this->TName($tbl_name);
		if($condition)
		 {
			$q .= " WHERE $condition";
			if($params)
			 {
				$stmt = $this->pdo->prepare($q);
				$stmt->execute($params);
				return $stmt->rowCount();
			 }
		 }
		return $this->pdo->exec($q);
	 }

	final public function DeleteByID($tbl_name, $id)
	 {
		$key = $this->GetPrimaryKey($tbl_name);
		if(is_array($key)) throw new Exception('not implemented yet...');
		else
		 {
			$condition = "$key = :".$key->name;
			$values = [":$key->name" => $id];
		 }
		return $this->Delete($tbl_name, $condition, $values);
	 }

	/* http://php.net/manual/en/pdo.constants.php - 2016-03-19
	PDO::PARAM_BOOL	- Represents a boolean data type.
	PDO::PARAM_NULL	- Represents the SQL NULL data type.
	PDO::PARAM_INT	- Represents the SQL INTEGER data type.
	PDO::PARAM_STR	- Represents the SQL CHAR, VARCHAR, or other string data type.
	PDO::PARAM_LOB	- Represents the SQL large object data type.
	PDO::PARAM_STMT	- Represents a recordset type. Not currently supported by any drivers. */
	final public static function GetParamType($val)
	 {
		if($val === null) return PDO::PARAM_NULL;
		switch(gettype($val))
		 {
			case 'integer': return PDO::PARAM_INT;
			case 'boolean': return PDO::PARAM_BOOL;
			default: return PDO::PARAM_STR;
		 }
	 }

	final protected static function OnUndefinedInstance($index)
	 {
		return self::GetLazyConnection($index);
	 }

	final protected static function BindValues(PDOStatement $sth, array $params)
	 {
		if(0 === key($params)) foreach($params as $field => $value) $sth->bindValue(1 + $field, $value, self::GetParamType($value));
		else foreach($params as $field => $value) $sth->bindValue($field, $value, self::GetParamType($value));
	 }

	final protected static function MakeDBResOptions(array $options, array $values = null)
	 {
		if($values) foreach(['properties', 'fetch_mode'] as $f) if(array_key_exists($f, $values)) $options[$f] = $values[$f];
		return $options;
	 }

	final protected function CheckConstructParams(array $params = null, ...$args)
	 {
		if($params && ($diff = array_diff_key($params, ...$args))) throw new EMSDB('Invalid parameter name'.(count($diff) > 1 ? 's: '.implode(', ', array_keys($diff)) : ': '.array_keys($diff)[0]).'. Allowed: '.implode(', ', array_keys(count($args) > 1 ? array_merge(...$args) : $args[0])).'.');
	 }

	final protected function PrepareAndFetchOnce($q, array $params = null, array $options = null)
	 {
		$sth = $this->pdo->prepare("$q LIMIT 1");
		if($options) SQLDBResult::ConfigPDOStatement($sth, $options);
		$sth->execute($params);
		$row = $sth->fetch();
		$sth->closeCursor();
		return $row;
	 }

	private $dbname;
	private $pdo;
	private $cache__foreign_keys = [];
	private $cache__create_table = [];
	private $sth_cache__i_schema = null;
	private $connection;

	private static $p_1 = ['driver' => '', 'host' => 'localhost', 'port' => '', 'dbname' => '', 'unix_socket' => ''];
	private static $p_2 = ['prefix' => ''];
}

class MYSQLDBTableMetadata extends MSDBTableMetadata
{
	final protected function GetColumnData()
	 {
		$tmp = [];
		foreach($this->GetDB()->Query('SHOW COLUMNS FROM '.$this->GetTableName()) as $row) $tmp[$row->Field] = new MSDBColMeta($row->Field, $row->Type, 'YES' === $row->Null, $row->Default, 'auto_increment' === $row->Extra);
		return $tmp;
	 }

	final protected function GetKeysData()
	 {
		$ret_val = [];
		foreach($this->GetDB()->Query('SHOW KEYS FROM '.$this->GetTableName()) as $row)
		 {
			if(empty($ret_val[$row->Key_name])) $ret_val[$row->Key_name] = [];
			$ret_val[$row->Key_name][$row->Seq_in_index] = $row;
		 }
		return $ret_val;
	 }
}

class MYSQLDB extends SQLDB
{
	final public function __construct(array $params, $username, $password, array $driver_options = [], $index = 0)
	 {
		// if(!isset($driver_options[PDO::MYSQL_ATTR_INIT_COMMAND])) $driver_options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES "UTF8"';
		$charset = 'utf8mb4';
		$collation = 'utf8mb4_unicode_ci';
		$q = "SET NAMES '$charset'";
		if($collation) $q .= " COLLATE '$collation'";
		$driver_options[PDO::MYSQL_ATTR_INIT_COMMAND] = $q;
		$params['driver'] = 'mysql';
		$driver_options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = false;
		parent::__construct($params, $username, $password, $driver_options, $index);
	 }

	final public function GetEngine($tbl_name) { return $this->GetInformationSchema($tbl_name, 'ENGINE'); }
}
?>
<?php
require_once(MSSE_LIB_DIR.'/db.php');

class FileDBResult extends MSDBResult
{
	public function __construct(FileSystemStorageReadonly $src, array $options = null)
	 {
		$this->src = $src;
		// if(!empty($options['properties'])) $sth->setFetchMode(PDO::FETCH_CLASS, 'MSDBRow', [$options['properties']]);
	 }

	protected function FetchRow()
	 {
		if($this->start)
		 {
			$this->src->Rewind();
			$this->start = false;
		 }
		if($this->src->Valid())
		 {
			$r = $this->src->Current();
			$this->src->Next();
			return $r;
		 }
		else return null;
	 }

	private $src;
	private $start = true;
}

class FileDBTableMetadata extends MSDBTableMetadata
{
	final protected function GetColumnData()
	 {
		$tmp = [];
		foreach($this->GetArgs()[0] as $name => $row) $tmp[$name] = new MSDBColMeta($name, $row->GetSQLType(), $row->IsNullable(), $row->value, $row->auto_increment);
		return $tmp;
	 }

	final protected function GetKeysData()
	 {
		$r = [];
		foreach($this->GetArgs()[0]->GetKeys() as $name => $fields)
		 {
			if('primary' === $name) $name = 'PRIMARY';
			$r[$name] = [];
			foreach($fields as $i => $f)
			 {
				$k = new stdClass();
				$k->Table = $this->GetTableName();
				// $r[$name]->["Non_unique"]=> int(0)
				$k->Key_name = $name;
				$k->Seq_in_index = $i + 1;
				$k->Column_name = $f->name;
				// $r[$name]->["Collation"]=> string(1) "A"
				// $r[$name]->["Cardinality"]=> int(26)
				// $r[$name]->["Sub_part"]=> NULL
				// $r[$name]->["Packed"]=> NULL
				// $r[$name]->["Null"]=> string(0) ""
				// $r[$name]->["Index_type"]=> string(5) "BTREE"
				// $r[$name]->["Comment"]=> string(0) ""
				// $r[$name]->["Index_comment"]=> string(0) ""
				$r[$name][$k->Seq_in_index] = $k;
			 }
		 }
		return $r;
	 }
}

class FileDB extends MSDB
{
	use TMSDB;

	public function __construct(array $options = null, $index = 0)
	 {
		$this->options = new OptionsGroup($options, ['prefix' => ['type' => 'string', 'value' => ''], 'dir' => ['type' => 'string', 'value' => MSSE_INC_DIR.'/storage']]);
		parent::__construct($this->options->prefix, $index);
	 }

	final public function Count($tbl_name, $condition = false, array $params = null, $distinct = false)
	 {
		if(func_num_args() > 1) throw new Exception('not implemented yet...');
		return count($this->GetStorage($tbl_name));
	 }

	final public function DeleteByID($tbl_name, $id)
	 {
		throw new Exception('not implemented yet...');
		$this->ECheck_1146($tbl_name, $cols);
		var_dump(isset($this->GetStorage($tbl_name, false)->$id));
	 }

	final public function Insert($tbl_name, array $values, array $fld_expr = [])
	 {
		$this->ECheck_1146($tbl_name, $cols);
		$is_single = empty($values[0]);
		foreach($fld_expr as $field => $expr)
		 {
			throw new Exception('not implemented yet...');
		 }
		if($is_single)
		 {
			$this->ECheck_1054($values, $cols);
			return $this->GetStorage($tbl_name, false)->Push($values);
		 }
		else
		 {
			foreach($values as $row) $this->ECheck_1054($row, $cols);
			$ids = [];
			foreach($values as $row) $ids[] = $this->GetStorage($tbl_name, false)->Push($row);
			return $ids;
		 }
	 }

	final public function Select($tbl_name, $columns = '*', $condition = false, array $params = null, $order_by = false, array $clauses = null)
	 {
		if(func_num_args() > 2 || '*' !== $columns) throw new Exception('not implemented yet...');
		$this->ECheck_1146($tbl_name, $cols);
		return new FileDBResult($this->GetStorage($tbl_name));
	 }

	final public function TableExists($tbl_name, array &$cols = null)
	 {
		if(empty($this->cache__table_meta[$tbl_name]))
		 {
			if(file_exists($this->GetFilePath($tbl_name))) $this->SetTableMetadata($tbl_name);
			else
			 {
				$cols = null;
				return false;
			 }
		 }
		$cols = $this->cache__table_meta[$tbl_name]->GetColumns();
		return true;
	 }

	final public function UpdateByID($tbl_name, array $values, $id)
	 {
		$this->ECheck_1146_1054($tbl_name, $values, $cols);
		throw new Exception('not implemented yet...');
	 }
	 // {
		// $key = $this->GetPrimaryKey($tbl_name);
		// if(is_array($key)) throw new Exception('not implemented yet...');
		// else
		 // {
			// $condition = "$key = :__db__row_$key->name";
			// $values["~__db__row_$key->name"] = $id;
		 // }
		// return $this->Update($tbl_name, $values, $condition);
	 // }
	final public function ValueExists($tbl_name, $col_name, $value, $condition = false, array $params = null)
	 {
		if($condition || $params) throw new Exception('not implemented yet...');
		$this->ECheck_1146_1054($tbl_name, $col_name, $cols);
		return $this->GetStorage($tbl_name)->ValueExists($col_name, $value);
	 }

// ---
	final public function Exists($tbl_name, $condition, array $params = null)
	 {
		throw new Exception('not implemented yet...');
	 }
	 // {
		// $q = "SELECT EXISTS(SELECT * FROM {$this->TName($tbl_name)} WHERE $condition)";
		// $sth = $this->pdo->prepare($q);
		// if($params) $this->BindValues($sth, $params);
		// $sth->execute();
		// $v = $sth->fetchColumn();
		// $sth->closeCursor();
		// return $v;
	 // }

	final public function BeginTransaction()
	 {
		throw new Exception('not implemented yet...');
	 }
	final public function Commit()
	 {
		throw new Exception('not implemented yet...');
	 }
	final public function RollBack()
	 {
		throw new Exception('not implemented yet...');
	 }

	// final public function ClearCache()
	 // {
		// $this->cache__table_meta = [];
		// $this->cache__foreign_keys = [];
		// $this->cache__create_table = [];
	 // }

	// final public function KeyExists($tbl_name, $key_name, array &$key = null)
	 // {
		// throw new Exception('not implemented yet...');
	 // }
	 // {
		// if(empty($this->cache__table_meta[$tbl_name])) $this->SetTableMetadata($tbl_name);
		// return $this->cache__table_meta[$tbl_name]->KeyExists($key_name, $key);
	 // }

	// final public function KeysExist($tbl_name, $col_name, array &$keys = null)
	 // {
		// throw new Exception('not implemented yet...');
	 // }
	 // {
		// if(empty($this->cache__table_meta[$tbl_name])) $this->SetTableMetadata($tbl_name);
		// $keys = [];
		// foreach($this->GetKeys($tbl_name) as $key_name => $k)
		 // {
			// foreach($k as $key)
			 // if($col_name === $key->Column_name)
			  // {
				// $keys[$key_name] = $k;
				// break;
			  // }
		 // }
		// return count($keys) > 0;
	 // }

	// final public function GetPrimaryKey($tbl_name)
	 // {
		// throw new Exception('not implemented yet...');
	 // }
	 // {
		// if(empty($this->cache__table_meta[$tbl_name])) $this->SetTableMetadata($tbl_name);
		// return $this->cache__table_meta[$tbl_name]->GetPrimaryKey();
	 // }

	// final public function GetKeys($tbl_name)
	 // {
		// throw new Exception('not implemented yet...');
	 // }
	 // {
		// if(empty($this->cache__table_meta[$tbl_name])) $this->SetTableMetadata($tbl_name);
		// return $this->cache__table_meta[$tbl_name]->GetKeys();
	 // }

	final public function SelectLJ(array $tbl_names, $columns = false, $condition = false, array $params = null, $order_by = false, array $clauses = null)
	 {
		throw new Exception('not implemented yet...');
	 }
	final public function GetRowByCKey($tbl_name, array $key, $columns = '*', $condition = false)
	 {
		throw new Exception('not implemented yet...');
	 }

	final public function GetRowByCondition($tbl_name, $columns, $condition, array $params = null, $order_by = false, array $options = null)
	 {
		throw new Exception('not implemented yet...');
	 }
	 // {
		// $q = "SELECT $columns FROM {$this->TName($tbl_name)} WHERE $condition";
		// if($order_by) $q .= " ORDER BY $order_by";
		// $options['tbl_name'] = $tbl_name;
		// return $this->PrepareAndFetchOnce($q, $params, $options);
	 // }

	final public function GetRowByID($tbl_name, $id, $columns = '*', array $options = null)
	 {
		throw new Exception('not implemented yet...');
	 }
	 // {
		// $key = $this->GetPrimaryKey($tbl_name);
		// if(is_array($key))
		 // {
			// $where = '';
			// foreach($key as $k) $where .= ($where ? ' AND ' : '')."($k = :$k->name)";
			// $data = $id;
		 // }
		// else
		 // {
			// $where = "$key = :$key->name";
			// $data = [$key->name => $id];
		 // }
		// return $this->GetRowByCondition($tbl_name, $columns, $where, $data, false, $options);
	 // }

	final public function GetRowByKey($tbl_name, $key, $value, $columns = '*')
	 {
		throw new Exception('not implemented yet...');
	 }
	 // {
		// return $this->GetRowByCondition($tbl_name, $columns, "(`$key` = ?)", [$value]);
	 // }

	final public function GetRowByKeyLJ(array $tbl_names, $key, $value, $columns = false, array $options = null)
	 {
		throw new Exception('not implemented yet...');
	 }
	 // {
		// $q = $this->MakeLeftJoin($tbl_names, $columns, $master_alias);
		// $where = "(`$master_alias`.`$key` = ?)";
		// return $this->PrepareAndFetchOnce("SELECT $columns FROM $q WHERE $where", [$value], $options);
	 // }

	final public function GetFirstRow($tbl_name, $columns = '*', $condition = false, array $params = null, $order_by = false)// { return $this->GetRowByCondition($tbl_name, $columns, $condition, $params, $order_by); }
	 {
		throw new Exception('not implemented yet...');
	 }

	// final public function IsForeignKey($tbl_name, $key_name, &$key = null)
	 // {
		// $keys = $this->GetForeignKeys($tbl_name);
		// if(isset($keys[$key_name]))
		 // {
			// $key = $keys[$key_name];
			// return true;
		 // }
		// else
		 // {
			// $key = null;
			// return false;
		 // }
	 // }

	// final public function GetForeignKeys($tbl_name)
	 // {
		// RESTRICT - default
		// "SELECT `rc`.TABLE_NAME AS table_name, `rc`.CONSTRAINT_NAME AS constraint_name, UPDATE_RULE AS update_rule, DELETE_RULE AS delete_rule, `rc`.REFERENCED_TABLE_NAME AS referenced_table_name, COLUMN_NAME AS column_name FROM information_schema.REFERENTIAL_CONSTRAINTS AS `rc` LEFT JOIN information_schema.KEY_COLUMN_USAGE AS `kcu` ON (`rc`.CONSTRAINT_SCHEMA = `kcu`.CONSTRAINT_SCHEMA AND `rc`.TABLE_NAME = `kcu`.TABLE_NAME AND `rc`.CONSTRAINT_NAME = `kcu`.CONSTRAINT_NAME) WHERE `rc`.CONSTRAINT_SCHEMA = '$db_name' AND `rc`.TABLE_NAME = '$name'"
		// if(!isset($this->cache__foreign_keys[$tbl_name]))
		 // {
			// $this->cache__foreign_keys[$tbl_name] = [];
			// if(preg_match_all('/CONSTRAINT `([a-z0-9_\-]+)` FOREIGN KEY \(`([a-z0-9_\-]+)`\) REFERENCES `([a-z0-9_\-]+)` \(`([a-z0-9_\-]+)`\)( ON DELETE (SET NULL|CASCADE|RESTRICT|NO ACTION))?( ON UPDATE (SET NULL|CASCADE|RESTRICT|NO ACTION))?/i',
							  // $this->GetCreateTable($tbl_name),
							  // $matches,
							  // PREG_SET_ORDER))
			 // foreach($matches as $m)
			  // $this->cache__foreign_keys[$tbl_name][$m[2]] = ['key' => $m[2], 'table' => $m[3], 'field' => $m[4], 'on_delete' => empty($m[6]) ? 'RESTRICT' : $m[6], 'on_update' => empty($m[8]) ? 'RESTRICT' : $m[8], 'references' => "`$m[3]`.`$m[4]`", 'constraint' => $m[1]];
		 // }
		// return $this->cache__foreign_keys[$tbl_name];
	 // }

	// final public function GetPrevNextRows($tbl_name, $type, $id, $columns = '*', $condition = false, array $params = null, $order_by = false)
	 // {
		// $ret_val = [];
		// switch($type)
		 // {
			// case 'position':
				// if(isset($params['position'])) ;
				// elseif($row = $this->GetRowByID($tbl_name, $id, 'position')) Filter::CopyValues($params, $row, 'position', ['position' => 'position_eq']);
				// else return null;
				// $params['__this_id'] = $id;
				// foreach(['prev' => ['(`position` < :position) OR (`position` = :position_eq AND `id` > :__this_id)', '`position` DESC, `id` ASC'],
						 // 'next' => ['(`position` > :position) OR (`position` = :position_eq AND `id` < :__this_id)', '`position` ASC, `id` DESC']] as $k => $v)
				 // if($row = $this->GetRowByCondition($tbl_name, $columns, $condition ? "($condition) AND ($v[0])" : $v[0], $params, $v[1]))
				  // {
					// $ret_val[$k] = $row;
					// $ret_val[$k]->__type = $k;
				  // }
				// break;
			// default:
				// if(!$order_by) throw new Exception('ORDER BY clause can not be empty! You must specify it.');
				// $res = $this->Select($tbl_name, $columns, $condition, $params, $order_by);
				// if(is_array($id))
				 // {
					// $key = $id[0];
					// $id = $id[1];
				 // }
				// else $key = 'id';
				// while($row = $res->Fetch())
				 // {
					// if($row[$key] == $id)
					 // {
						// if($next = $res->Fetch()) $ret_val['next'] = $next;
						// break;
					 // }
					// $ret_val['prev'] = $row;
				 // }
				// $res->Delete();
				// if(!empty($ret_val['next'])) $ret_val['next']['__type'] = 'next';
				// if(!empty($ret_val['prev'])) $ret_val['prev']['__type'] = 'prev';
		 // }
		// return $ret_val;
	 // }

	final public function InsertUpdate($tbl_name, array $data, array $duplicate)
	 {
		throw new Exception('not implemented yet...');
	 }
	 // {
		// $fields = $values = $update = '';
		// foreach($data as $k => $v)
		 // {
			// if($fields)
			 // {
				// $fields .= ', ';
				// $values .= ', ';
			 // }
			// if($k[0] === '=')
			 // {
				// $k = substr($k, 1);
				// $values .= $v;
			 // }
			// else $values .= ":$k";
			// $fields .= "`$k`";
		 // }
		// foreach($duplicate as $k => $v)
		 // {
			// if($update) $update .= ', ';
			// if($k[0] === '=')
			 // {
				// if(true === $v) $v = $data[$k];
				// $k = substr($k, 1);
			 // }
			// else $v = true === $v ? "VALUES(`$k`)" : ":_$k";
			// $update .= "`$k` = $v";
		 // }
		// $stmt = $this->pdo->prepare("INSERT INTO {$this->TName($tbl_name)} ($fields) VALUES ($values) ON DUPLICATE KEY UPDATE $update");
		// foreach($data as $k => $value) if($k[0] !== '=') $stmt->bindValue($k, $value, $this->GetParamType($value));
		// foreach($duplicate as $k => $value) if($k[0] !== '=' && true !== $value) $stmt->bindValue("_$k", $value, $this->GetParamType($value));
		// $stmt->execute();
		// return $stmt->rowCount();
	 // }

	/* Note that an INSERT ... ON DUPLICATE KEY UPDATE statement is not an INSERT statement, rowCount won't return the number or rows inserted or updated for such a statement. For MySQL, it will return 1 if the row is inserted, and 2 if it is updated, but that may not apply to other databases. */
	final public function Replace($tbl_name, array $data)
	 {
		throw new Exception('not implemented yet...');
	 }
	 // {
		// $fields = $values = $update = '';
		// foreach($data as $k => $v)
		 // {
			// if($fields)
			 // {
				// $fields .= ', ';
				// $values .= ', ';
				// $update .= ', ';
			 // }
			// if($k[0] === '=')
			 // {
				// $k = substr($k, 1);
				// $values .= $v;
			 // }
			// else $values .= ":$k";
			// $fields .= "`$k`";
			// $update .= "`$k` = VALUES(`$k`)";
		 // }
		// $stmt = $this->pdo->prepare("INSERT INTO {$this->TName($tbl_name)} ($fields) VALUES ($values) ON DUPLICATE KEY UPDATE $update");
		// foreach($data as $k => $value) if($k[0] !== '=') $stmt->bindValue($k, $value, $this->GetParamType($value));
		// $stmt->execute();
		// return $stmt->rowCount();
	 // }

	final public function Update($tbl_name, array $values, $condition, $order_by = false)
	 {
		throw new Exception('not implemented yet...');
	 }
	 // {
		// $s = '';
		// foreach($values as $k => $v)
		 // {
			// if($k[0] === '~') continue;
			// if($s) $s .= ', ';
			// $s .= $k[0] === '=' ? '`'.substr($k, 1)."` = $v" : "`$k` = :$k";
		 // }
		// if(!$s) throw new Exception('SET clause can not be empty in UPDATE statement!');
		// $q = "UPDATE {$this->TName($tbl_name)} SET $s";
		// if($condition) $q .= " WHERE $condition";
		// if($order_by) $q .= " ORDER BY $order_by";
		// $stmt = $this->pdo->prepare($q);
		// foreach($values as $k => $value) if($k[0] !== '=') $stmt->bindValue($k[0] === '~' ? substr($k, 1) : $k, $value, $this->GetParamType($value));
		// $stmt->execute();
		// return $stmt->rowCount();
	 // }

	final public function Delete($tbl_name, $condition, array $params = null)
	 {
		throw new Exception('not implemented yet...');
	 }

	final protected function GetTableRawMetadata($tbl_name) { return [$this->GetStorage($tbl_name)->GetMeta()]; }

	final protected function GetFilePath($tbl_name) { return $this->options->dir."/{$this->TName($tbl_name)}.php"; }

	final protected function GetStorage($tbl_name, $readonly = true)
	 {
		$n = 'cache__storage_'.($readonly ? 'read' : 'write');
		$t_name = $this->TName($tbl_name);
		if(empty($this->{$n}[$t_name]))
		 {
			MSConfig::RequireFile('filesystemstorage');
			$this->{$n}[$t_name] = $readonly ? new FileSystemStorageReadonly("/$t_name.php", ['root' => $this->options->dir]) : new FileSystemStorage("/$t_name.php", ['readonly' => false, 'root' => $this->options->dir]);
		 }
		return $this->{$n}[$t_name];
	 }

	final private function ECheck_1054($v, array $cols)
	 {
		if(is_array($v))
		 {
			if($diff = array_diff_key($v, $cols)) $v = key($diff);
			else return;
		 }
		elseif(isset($cols[$v])) return;
		throw new EMSDBQuery("Unknown column '$v' in 'field list'", 1054);
	 }

	final private function ECheck_1146($tbl_name, array &$cols = null) { if(!$this->TableExists($tbl_name, $cols)) throw new EMSDBQuery("Table '{$this->TName($tbl_name)}' doesn't exist", 1146); }

	final private function ECheck_1146_1054($tbl_name, $v, array &$cols = null)
	 {
		$this->ECheck_1146($tbl_name, $cols);
		$this->ECheck_1054($v, $cols);
	 }

	//1048 Column 'title' cannot be null

	private $options;
	private $cache__storage_read = [];
	private $cache__storage_write = [];
}
?>
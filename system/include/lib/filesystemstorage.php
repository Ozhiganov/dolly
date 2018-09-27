<?php
class FileSystemStorageRow extends stdClass implements Iterator, JsonSerializable
{
	final public function __construct($id, FileSystemStorage $owner, stdClass $row)
	 {
		$this->id = $id;
		$this->owner = $owner;
		$this->row = $row;
	 }

	final public function rewind()
	 {
		if(null === $this->meta) $this->meta = $this->owner->GetMeta();
		$this->meta->rewind();
	 }

	final public function current() { return $this->GetValue($this->meta->key()); }
	final public function key() { return $this->meta->key(); }
	final public function next() { $this->meta->next(); }
	final public function valid() { return $this->meta->valid(); }
	final public function jsonSerialize() { return $this->__debugInfo(); }
	final public function Changed() { return $this->data; }

	final public function __set($name, $value)
	 {
		if(!$this->owner->ColExists($name)) throw new Exception($this->GetEUndefinedMsg($name));
		$this->owner->CheckReadonly();
		if(null === $this->meta) $this->meta = $this->owner->GetMeta();
		if(null === $value && !$this->meta->$name->IsNullable()) throw new Exception("Field '$name' cannot be null");
		$this->data[$name] = $this->meta->$name->CastValue($value);
		$this->row->changed = true;
	 }

	final public function __get($name)
	 {
		if(!$this->owner->ColExists($name)) throw new Exception($this->GetEUndefinedMsg($name));
		return $this->GetValue($name);
	 }

	final public function __isset($name)
	 {
		return isset($this->data[$name]) || (isset($this->row->data[$this->id]) && isset($this->row->data[$this->id][$name]));
	 }

	final public function __unset($name)
	 {
		$this->owner->CheckReadonly();
		$this->data[$name] = null;
		$this->row->changed = true;
	 }

	final public function __debugInfo()
	 {
		if(null === $this->meta) $this->meta = $this->owner->GetMeta();
		$r = [];
		foreach($this->meta as $k => $v) $r[$k] = $this->GetValue($k);
		return $r;
	 }

	final public function SetDefaults()
	 {
		if(null === $this->meta) $this->meta = $this->owner->GetMeta();
		foreach($this->meta as $k => $v) $this->data[$k] = $this->meta->$k->value;
		return $this;
	 }

	final protected function GetEUndefinedMsg($name) { return 'Undefined property: '.get_class($this).'::$'.$name; }

	final protected function GetValue($name)
	 {
		if(array_key_exists($name, $this->data)) return $this->data[$name];
		if(isset($this->row->data[$this->id]) && isset($this->row->data[$this->id][$name])) return $this->row->data[$this->id][$name];
		if(null === $this->meta) $this->meta = $this->owner->GetMeta();
		return $this->meta->$name->value;
	 }

	private $id;
	private $owner;
	private $data = [];
	private $meta = null;
	private $row;
}

class FileSystemStorageMetaElement extends OptionsGroup
{
	public function __construct($name, array $values = null)
	 {
		$values['name'] = $name;
		parent::__construct($values, [
			'name' => ['type' => 'string,len_gt0'],
			'type' => ['type' => 'string,len_gt0'],
			'length' => ['type' => 'int,gt0,null'],
			'value' => [],
			'auto_increment' => ['type' => 'bool', 'value' => false],
			'key' => ['type' => 'string', 'value' => ''],
		]);
		$t = $this->__get('type');
		$this->parsed_type = self::ParseType($t);
		if(self::TypeIsCompound($t)) throw new Exception("Invalid type '$t' for field '$name'");
		if($this->__get('auto_increment') && !$this->IsInt()) throw new Exception("Incorrect column specifier 'auto_increment' for field '$name'");
		$this->parsed_type = $this->parsed_type->types;
		unset($this->parsed_type['null']);
		$this->parsed_type = key($this->parsed_type);
		if(('int' === $this->parsed_type /* || 'float' === $this->parsed_type */) && !$this->__get('length')) throw new Exception("Length is not specified for field '$name' (type '$this->parsed_type')");
	 }

	final public function IsNullable() { return self::TypeIsNullable($this->__get('type')); }
	final public function IsUnsigned() { return self::TypeIsUnsigned($this->__get('type')); }
	final public function IsInt() { return self::TypeIsInt($this->__get('type')); }
	final public function IsFloat() { return self::TypeIsFloat($this->__get('type')); }
	final public function IsString() { return self::TypeIsString($this->__get('type')); }
	final public function IsBool() { return self::TypeIsBool($this->__get('type')); }
	final public function IsArray() { return self::TypeIsArray($this->__get('type')); }

	final public function GetSQLType()
	 {
		$len = $this->__get('length');
		if('string' === $this->parsed_type) return $len ? "varchar($len)" : 'text';
		if('int' === $this->parsed_type) return "int($len)";
		return $this->parsed_type;
	 }

	final public function CastValue($v)
	 {
		if(null === $v && $this->IsNullable()) return $v;
		switch($this->parsed_type)
		 {
			case 'float': if(!is_float($v)) return (float)$v; break;
			case 'int': if(!is_int($v)) return (int)$v; break;
			case 'bool': if(!is_bool($v)) return (bool)$v; break;
			case 'string': if(!is_string($v)) return "$v"; break;
			case 'array': if(!is_array($v)) throw new UnexpectedValueException('Value must be of the type array, '.MSConfig::GetVarType($v).' given'); break;
		 }
		return $v;
	 }

	final public function __debugInfo()
	 {
		$r = [];
		foreach($this as $k => $v) $r[$k] = $v;
		return $r;
	 }

	private $parsed_type;
}

class FileSystemStorageMeta extends stdClass implements Iterator
{
	final public function __construct(AbstractFileSystemStorage $owner, array $data)
	 {
		$this->owner = $owner;
		foreach($data as $k => $v)
		 {
			$this->data[$k] = new FileSystemStorageMetaElement($k, $v);
			if($this->data[$k]->key)
			 {
				if('primary' === $this->data[$k]->key)
				 {
					if(!isset($this->keys[$this->data[$k]->key])) $this->keys[$this->data[$k]->key] = [];
					$key = new stdClass;
					$key->name = $k;
					$this->keys[$this->data[$k]->key][] = $key;
				 }
				else throw new Exception("Invalid type '{$this->data[$k]->key}' for key '$k'");
			 }
		 }
	 }

	final public function rewind() { reset($this->data); }
	final public function current() { return current($this->data); }
	final public function key() { return key($this->data); }
	final public function next() { next($this->data); }
	final public function valid() { return null !== key($this->data); }
	final public function __isset($name) { return array_key_exists($name, $this->data); }
	final public function __set($name, $value) { throw new Exception('Read only!'); }
	final public function __unset($name) { throw new Exception('Read only!'); }
	final public function GetKeys() { return $this->keys; }

	final public function GetPrimaryKey()
	 {
		if(null === $this->primary_key)
		 {
			if(isset($this->keys['primary']))
			 {
				if(1 === count($this->keys['primary'])) $this->primary_key = $this->__get($this->keys['primary'][0]->name);
				else
				 {
					$this->primary_key = [];
					foreach($this->keys['primary'] as $k => $v) $this->primary_key[$v->name] = $this->__get($v->name);
				 }
			 }
			else $this->primary_key = false;
		 }
		return $this->primary_key;
	 }

	final public function __get($name)
	 {
		if(array_key_exists($name, $this->data)) return $this->data[$name];
		throw new Exception('Undefined property: '.get_class($this).'::$'.$name);
	 }

	public function __debugInfo()
	 {
		$r = [];
		foreach($this->data as $k => $v) $r[$k] = $v->type;
		return $r;
	 }

	private $data = [];
	private $keys = [];
	private $primary_key = null;
	private $owner;
}

abstract class AbstractFileSystemStorage implements Iterator, JsonSerializable, Countable
{
	use TOptions;

	public function __construct($file_name, array $options = null)
	 {
		$this->AddOptionsMeta(['root' => ['type' => 'string', 'value' => $_SERVER['DOCUMENT_ROOT']]]);
		$this->SetOptionsData($options);
		$this->file_name = $file_name;
		$this->root = $this->GetOption('root');
		$this->name = realpath("$this->root/$this->file_name");
		if(!isset(self::$files[$this->name]))
		 {
			$f = new stdClass();
			$f->items = $f->meta = $f->__data = [];
			$f->data = null;
			$f->changed = false;
			self::$files[$this->name] = $f;
		 }
		self::$files[$this->name]->items[] = $this;
	 }

	public function __clone()
	 {
		throw new Exception('Can not clone instance of '.get_class($this));
	 }

	final public function GetName() { return $this->name; }
	final public function __debugInfo() { return ['name' => $this->name, 'data' => $this->jsonSerialize()]; }
	final public function GetKeys() { return $this->GetMeta()->GetKeys(); }
	final public function GetPrimaryKey() { return $this->GetMeta()->GetPrimaryKey(); }

	final public function count()
	 {
		$d = $this->InitData();
		reset($d->data);
		$i = 0;
		foreach($d->data as $k => $row) if($this->RowIsNotEmpty($d, $k)) ++$i;
		return $i;
	 }

	final public function jsonSerialize()
	 {
		$this->InitData();
		$r = [];
		foreach($this as $k => $row) $r[$k] = $row;
		return $r;
	 }

	final public function ColExists($name, &$col = null)
	 {
		$meta = $this->GetMeta();
		$col = ($r = isset($meta->$name)) ? $meta->$name : null;
		return $r;
	 }

	final public function ValueExists($col_name, $value)
	 {
		if(!isset($this->GetMeta()->$col_name)) throw new Exception("Undefined property: '$col_name'");
		$values = array_column($this->InitData()->data, $col_name);
		if(null === $value || '' === $value) return false !== array_search($value, $values, true);
		$values = array_filter($values, function($v){return null !== $v;});
		return $values && false !== array_search($value, $values);
	 }

	final public function GetMeta(stdClass &$d = null)
	 {
		$d = $this->InitData();
		return self::$meta_data[$this->name];
	 }

	final public function Reload()
	 {
		$tmp = $this->Load();
		self::$files[$this->name]->meta = $tmp['meta'];
		self::$files[$this->name]->data = $tmp['data'];
		self::$files[$this->name]->keys = $tmp['keys'];
		self::$meta_data[$this->name] = new FileSystemStorageMeta($this, $tmp['meta']);
	 }

	final public function __destruct()
	 {
		$f = $this->GetFiles();
		foreach($f->items as $k => $v)
		 if($v === $this)
		  {
			unset($f->items[$k]);
			break;
		  }
		if(!$f->items && $f->changed) $this->Save($f);
	 }

	final protected function GetFiles() { return self::$files[$this->name]; }

	final protected function RowIsNotEmpty(stdClass $d, $k)
	 {
		if(array_key_exists($k, $d->__data))
		 {
			if(null !== $d->__data[$k]) return true;
		 }
		elseif(null !== $d->data[$k]) return true;
	 }

	final protected function Load()
	 {
		$tmp = (require $this->name);
		if(is_array($tmp))
		 {
			if(isset($tmp['meta']) && is_array($tmp['meta'])) return ['meta' => $tmp['meta'], 'data' => isset($tmp['data']) ? $tmp['data'] : [], 'keys' => isset($tmp['keys']) ? $tmp['keys'] : []];
		 }
		throw new Exception("$this->name: invalid file format!");
	 }

	final protected function InitData()
	 {
		if(null === self::$files[$this->name]->data) $this->Reload();
		return self::$files[$this->name];
	 }

	final protected function Save(stdClass $f)
	 {
		$h = fopen($this->GetName(), 'c');
		$t = $this->Load();
		$changed = false;
		foreach($f->__data as $row_id => $row)
		 {
			if(null === $row)
			 {
				if(isset($t['data'][$row_id]))
				 {
					unset($t['data'][$row_id]);
					$changed = true;
				 }
			 }
			elseif($d = $row->Changed())
			 {
				$changed = true;
				if(!isset($t['data'][$row_id])) $t['data'][$row_id] = [];
				foreach($row as $k => $v)
				 {
					if(null === $v)
					 {
						$m = $this->GetMeta()->$k;
						if(!$m->IsNullable() && null === $m->value)
						 {
							if(!$m->auto_increment) throw new Exception("Field '$k' cannot be null");
						 }
					 }
					$t['data'][$row_id][$k] = $v;
				 }
			 }
		 }
		if($changed)
		 {
			$code = '<?php'.PHP_EOL.'return '.var_export($t, true).';'.PHP_EOL.'?>';
			ftruncate($h, strlen($code));
			fwrite($h, $code);
		 }
		fclose($h);
	 }

	private $file_name;
	private $root;
	private $name;

	private static $meta_data = [];
	private static $files = [];
}

class FileSystemStorage extends AbstractFileSystemStorage
{
	final public function __construct($file_name, array $options = null)
	 {
		$this->AddOptionsMeta(['readonly' => ['type' => 'bool', 'value' => true]]);
		parent::__construct($file_name, $options);
	 }

	final public function rewind() { reset($this->InitData()->data); }
	final public function key() { return key($this->InitData()->data); }
	final public function next() { next($this->InitData()->data); }

	final public function current()
	 {
		$d = $this->InitData();
		return $this->GetRow($d, key($d->data));
	 }

	final public function valid()
	 {
		$d = $this->InitData();
		do
		 {
			$k = key($d->data);
			if(null === $k) return false;
			if($this->RowIsNotEmpty($d, $k)) return true;
			next($d->data);
		 }
		while(1);
	 }

	final public function __set($name, $value)
	 {
		if(null === $value) $this->__unset($name);
		else
		 {
			$this->CheckReadonly();
			if(is_array($value)) $type = 'array';
			elseif($value instanceof stdClass) $type = 'object';
			else $type = false;
			if($type)
			 {
				$m = $this->GetMeta($d);
				$row = $this->GetRow($d, $name, $new)->SetDefaults();
				if($pkey = $m->GetPrimaryKey())
				 {
					if(is_array($pkey) > 1) throw new Exception('Can not use __set() with compound keys');
					if($this->HasKeyValue($pkey->name, $value, $type, $kval))
					 {
						if(null === $kval) throw new Exception("Key '$pkey->name' cannot be null");
						if($m->{$pkey->name}->CastValue($name) !== $m->{$pkey->name}->CastValue($kval))
						 {
							if($new) throw new Exception("Key '$pkey->name' must be equal to the index");
							$d->__data[$name] = null;
							$d->changed = true;
							$d->__data[$kval] = $row;
							if(!array_key_exists($kval, $d->data)) $d->data[$kval] = null;
						 }
					 }
					else $row->{$pkey->name} = $name;
				 }
				foreach($value as $k => $v) $row->$k = $v;
			 }
			else throw new Exception('Invalid type: '.gettype($value).'!');
		 }
	 }

	final public function &__get($name)
	 {
		$d = $this->InitData();
		return $this->GetRow($d, $name);
	 }

	final public function __isset($name)
	 {
		$d = $this->InitData();
		return isset($d->data[$name]) || $d->changed;
	 }

	final public function __unset($name)
	 {
		$this->CheckReadonly();
		if(($pkey = $this->GetMeta($d)->GetPrimaryKey()) && is_array($pkey) > 1) throw new Exception('Can not use __unset() with compound keys');
		$d->__data[$name] = null;
		$d->changed = true;
	 }

	final public function __invoke($value)
	 {
		$this->CheckReadonly();
		if(is_array($value)) $type = 'array';
		elseif($value instanceof stdClass) $type = 'object';
		else $type = false;
		if($type)
		 {
			$m = $this->GetMeta($d);
			if($pkey = $m->GetPrimaryKey())
			 {
				if(is_array($pkey) > 1) throw new Exception('Can not use __set() with compound keys');
				if($this->HasKeyValue($pkey->name, $value, $type, $kval) && null !== $kval) $k = $kval;
				elseif($m->{$pkey->name}->IsInt() && $m->{$pkey->name}->auto_increment)
				 {
					$k = $this->GetAutoInc($d);
					if('array' === $type) $value[$pkey->name] = $m->{$pkey->name}->CastValue($k);
					else $value->{$pkey->name} = $m->{$pkey->name}->CastValue($k);
				 }
				else throw new Exception("Undefined key value '$pkey->name'");
			 }
			else $k = $this->GetAutoInc($d);
			$d->changed = true;
			$d->__data[$k] = new FileSystemStorageRow($k, $this, $d);
			$d->data[$k] = null;
			foreach($value as $f => $v)
			 {
				if(null === $v && !$m->$f->IsNullable()) throw new Exception("Field '$f' cannot be null");
				$d->__data[$k]->$f = $m->$f->CastValue($v);
			 }
			$this->Save($d);
			$d->changed = false;
			$this->Reload();
			return $k;
		 }
		else throw new Exception('Invalid type: '.gettype($value).'!');
	 }

	final public function Clear()
	 {
		$this->CheckReadonly();
		$d = $this->InitData();
		foreach($d->data as $k => $v) $d->__data[$k] = null;
		$d->changed = true;
	 }

	final public function CheckReadonly() { if($this->GetOption('readonly')) throw new Exception('Object is readonly! Instance of '.get_class($this).": '{$this->GetName()}'."); }

	final private function &GetRow(stdClass $d, $k, &$new = null)
	 {
		$new = false;
		if(!array_key_exists($k, $d->__data))
		 {
			$d->__data[$k] = new FileSystemStorageRow($k, $this, $this->GetFiles());
			if($new = !array_key_exists($k, $d->data)) $d->data[$k] = null;
		 }
		return $d->__data[$k];
	 }

	final private function HasKeyValue($name, $values, $type, &$kval)
	 {
		if('array' === $type)
		 {
			if($has_kval = array_key_exists($name, $values)) $kval = $values[$name];
		 }
		elseif($has_kval = (property_exists($values, $name) || isset($values->{$name}))) $kval = $values->{$name};
		return $has_kval;
	 }

	final private function GetAutoInc(stdClass $d)
	 {
		$d->data[] = true;
		end($d->data);
		return key($d->data);
	 }
}

class FileSystemStorageReadonly extends AbstractFileSystemStorage
{
	final public function rewind() { reset($this->InitData()->data); }
	final public function key() { return key($this->InitData()->data); }
	final public function next() { next($this->InitData()->data); }
	final public function valid() { return null !== key($this->InitData()->data); }
	final public function current() { if(null !== ($k = key($this->InitData()->data))) return $this->__get($k); }
	final public function __set($name, $value) { throw new Exception('Object is readonly! Instance of '.get_class($this).": '{$this->GetName()}'."); }
	final public function __get($name) { return (object)$this->InitData()->data[$name]; }
}
?>
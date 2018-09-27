<?php
namespace Sunder\Content;

abstract class Source
{
	use \TOptions;

	abstract public function FieldEditable(\stdClass $data, $name, &$row_id = null);
	abstract public function RowEditable(\stdClass $data);

	final public function __construct($id, array $options = null)
	 {
		if(!is_string($id) || '' === $id) throw new \Exception('Source ID must be a non-empty string!');
		if(isset(self::$instances[$id])) throw new \Exception("Duplicate ID `$id` for the Content Source!");
		self::$instances[$id] = $this;
		$this->id = $id;
		$this->options = $options;
		$this->OnCreate();
	 }

	final public static function Get($id) { return self::$instances[$id]; }

	final public function AttachLayouts(...$items)
	 {
		foreach($items as $i => $item)
		 {
			if($item instanceof \MSLayout) $name = $item->GetName();
			elseif(is_string($item)) $name = $item;
			else throw new \Exception('Argument '.($i + 1).' passed to '. __METHOD__ .'() must be a string or an instance of MSLayout, '.\SunderLayout::GetVarType($item).' given.');
			if($this->LayoutAttached($name, $source)) throw new \Exception("Layout `$name` is already attached to the `{$source->GetId()}` Content Source!");
			self::$l2s[$name] = $this;
			$this->layouts[$name] = $name;
		 }
		return $this;
	 }

	final public function AttachValues(\stdClass $data, ...$items)
	 {
		foreach($items as $i => $item)
		 {
			if(is_string($item));
			else throw new \Exception('Argument '.($i + 1).' passed to '. __METHOD__ .'() must be a string, '.\SunderLayout::GetVarType($item).' given.');
			if(isset(self::$values[$item])) throw new \Exception("Value `$item` is already attached to the `{$this->GetId()}` Content Source!");
			self::$values[$item] = ['source' => $this, 'data' => $data, 'name' => $item];
		 }
		return $this;
	 }

	final public static function ValueEditable($name, &$row_id = null)
	 {
		if(isset(self::$values[$name])) return self::$values[$name]['source']->FieldEditable(self::$values[$name]['data'], $name, $row_id);
		else
		 {
			$row_id = null;
			return false;
		 }
	 }

	final public static function Exists($id, Source &$source = null)
	 {
		$source = ($v = isset(self::$instances[$id])) ? self::$instances[$id] : null;
		return $v;
	 }

	final public static function LayoutAttached($name, Source &$source = null)
	 {
		$source = ($v = isset(self::$l2s[$name])) ? self::$l2s[$name] : null;
		return $v;
	 }

	final public function GetId() { return $this->id; }

	abstract protected function OnCreate();

	private static $instances = [];
	private static $l2s = [];
	private static $values = [];

	private $layouts = [];
	private $id;
}

class DBSource extends Source
{
	final public function FieldEditable(\stdClass $data, $name, &$row_id = null)
	 {
		$row_id = null;
		if(isset($this->fields[$name]))
		 {
			if(null === $this->check || call_user_func($this->check, $data, $name))
			 {
				$key = \DB::GetPrimaryKey($this->fields[$name]->tbl_name);
				$row_id = isset($data->{$key->name}) ? $data->{$key->name} : false;
				return $this->fields[$name];
			 }
		 }
	 }

	final public function RowEditable(\stdClass $data) { return null === $this->check ? true : call_user_func($this->check, $data, null); }

	final public function GetTblName() { return $this->tbl_name; }

	final public function GetFieldType($fld_name)
	 {
		if(!isset(self::$col_meta[$fld_name]))
		 {
			if(\DB::ColExists($this->tbl_name, $fld_name, $col))
			 {
				self::$col_meta[$fld_name] = new \stdClass;
				foreach(self::$properties as $key) self::$col_meta[$fld_name]->$key = $col->$key;
			 }
			else self::$col_meta[$fld_name] = false;
		 }
		return self::$col_meta[$fld_name];
	 }

	final public function AddField($name, $title, array $o = [])
	 {
		if(empty($name)) throw new \Exception('Field name can not be empty!');
		if(isset($this->fields[$name])) throw new \Exception("Duplicate field name: `$name`!");
		return ($this->fields[$name] = new DBSourceField($this, $name, $title, $o));
	 }

	final public function AddFields(...$fields)
	 {
		foreach($fields as $field) $this->AddField(...$field);
		return $this;
	 }

	final protected function OnCreate()
	 {
		$this->tbl_name = $this->GetOption('tbl_name') ?: $this->GetId();
		\DB::GetColMeta($this->tbl_name);
		if($opt = $this->GetOption('check')) $this->check = $opt;
	 }

	private static $properties = ['name', 'sql_type', 'null', 'default', 'type', 'size', 'unsigned'];
	private static $col_meta = [];

	private $tbl_name;
	private $fields = [];
	private $check = null;
}

class DBSourceField extends \stdClass
{
	final public function __construct(DBSource $owner, $name, $title = null, array $o = [])
	 {
		$this->owner = $owner;
		$this->o = $o;
		$this->data = ['name' => $name, 'title' => $title, 'tbl_name' => $this->owner->GetTblName(), 'fld_name' => empty($o['fld_name']) ? $name : $o['fld_name']];
		$this->data['meta'] = $this->owner->GetFieldType($this->data['fld_name']);
	 }

	final public function __get($name)
	 {
		if('source' === $name) return $this->owner;
		if(array_key_exists($name, $this->data)) return $this->data[$name];
		throw new \Exception('Undefined property: '. __CLASS__ .'::$'.$name.'!');
	 }

	final public function __set($name, $value) { throw new \Exception('Read only property: '. __CLASS__ .'::$'.$name.'!'); }
	final public function __isset($name) { return isset($this->data[$name]); }
	final public function __debugInfo() { return $this->data; }

	private $owner;
	private $data = [];
	private $o;
}

class Adapter
{
	final public function __invoke(\stdClass $data, $name, \MSLayout $layout = null, $is_attr, \DOMNode $node, \DOMNode $dn = null)
	 {
		if(true === $is_attr) return;
		if(null === $layout) $f = Source::ValueEditable($name, $row_id);
		elseif(Source::LayoutAttached($layout->GetName(), $source)) $f = $source->FieldEditable($data, $name, $row_id);
		else return;
		if($f)
		 {
			$l = 'sunder.content.'.$this->i++;
			$d = ['source_id' => $f->source->GetId(), 'row_id' => $row_id, 'meta' => $f->meta, 'sdn' => [], 'title' => $f->title];
			if($node->attributes) foreach($node->attributes as $key => $attr) $d['sdn'][$key] = $attr->value;
			if($d['meta'] && isset(self::$types[$d['meta']->type])) $d['value'] = "{$data->$name}";
			$doc = $node->ownerDocument;
			\n::Before($node, $doc->createComment("$l:".json_encode($d)));
			\n::After($node, $doc->createComment($l));
		 }
	 }

	private static $types = ['int' => true];

	private $i = 0;
}
?>
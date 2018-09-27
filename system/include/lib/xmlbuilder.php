<?php
namespace XMLBuilder;

abstract class Node
{
	final public static function ToXML($val, $tag_name = 'value')
	 {
		$type = gettype($val);
		$tag = new Tag($tag_name);
		$tag->type = $type;
		switch($type)
		 {
			case 'object':
				$tag->class = get_class($val);
				foreach($val as $k => $v)
				 {
					$el = new Tag('pair');
					$tag->Append($el);
					$el->Append(self::ToXML($k, 'key'));// индексы нужны не всегда, иногда они не имеют смысла
					$el->Append(self::ToXML($v));
				 }
				break;
			case 'array':
				foreach($val as $k => $v)
				 {
					$el = new Tag('element');
					$tag->Append($el);
					$el->Append(self::ToXML($k, 'key'));
					$el->Append(self::ToXML($v));
				 }
				break;
			case 'boolean':
			case 'double':
			case 'string':
			case 'integer': $tag->AppendText($val); break;
			case 'NULL': break;
			default://resource //unknown type
		 }
		return $tag;
	 }

	final protected function SetParent(Node $parent)
	 {
		$this->parent = $parent;
	 }

	final protected function GetLevel()
	 {
		return null === $this->parent ? 0 : $this->parent->GetLevel() + 1;
	 }

	private $parent = null;
}

class Tag extends Node
{
	final public function __construct($name)
	 {
		$this->name = $name;
	 }

	final public function __set($name, $value)
	 {
		if(!isset($this->attributes[$name])) $this->attributes[$name] = new \stdClass;
		$this->attributes[$name]->value = $value;
		$this->attributes[$name]->as_string = $this->EscAttrVal($value);
	 }

	final public function __get($name)
	 {
		if(isset($this->attributes[$name])) return $this->attributes[$name]->value;
	 }

	final public function __isset($name)
	 {
		return array_key_exists($name, $this->attributes);
	 }

	final public function __toString()
	 {
		if(false === $this->name)
		 {
			$s = '';
			foreach($this->child_nodes as $node) $s .= $node;
		 }
		else
		 {
			$s = "<$this->name";
			foreach($this->attributes as $n => $v) $s .= " $n='$v->as_string'";
			if($this->child_nodes)
			 {
				$s .= '>';
				foreach($this->child_nodes as $node) $s .= $node;
				$s .= "</$this->name>";
			 }
			else $s .= ' />';
		 }
		return $s;
	 }

	final public function Append(Node $node)
	 {
		$this->child_nodes[] = $node;
		$node->SetParent($this);
		return $this;
	 }

	final public function AppendText($value)
	 {
		$node = new Text($value);
		$this->child_nodes[] = $node;
		$node->SetParent($this);
		return $this;
	 }

	final protected static function EscAttrVal($value)
	 {
		switch($type = gettype($value))
		 {
			case 'boolean':
			case 'integer':
			case 'double': return "$value";
			case 'string': return \Filter::TextAttribute($value);
			case 'NULL': return '';
			default: throw new \InvalidArgumentException("Attribute value must be a scalar, $type given.");
		 }
	 }

	private $name;
	private $child_nodes = [];
	private $attributes = [];
}

class Text extends Node
{
	final public function __construct($value)
	 {
		switch($type = gettype($value))
		 {
			case 'boolean':
			case 'integer':
			case 'double':
			case 'string':
			case 'NULL':
					$this->value = $value;
					$this->type = $type;
					break;
			default: throw new \InvalidArgumentException(__METHOD__ ." expects parameter 1 to be a scalar, $type given.");
		 }
	 }

	final public function __toString()
	 {
		return $this->value && 'string' === $this->type ? "<![CDATA[$this->value]]>" : "$this->value";
	 }

	private $value;
	private $type;
}
class XMLCode extends Node
{
	final public function __construct($value)
	 {
		$this->value = $value;
	 }

	final public function __toString()
	 {
		return "$this->value";
	 }

	private $value;
}
?>
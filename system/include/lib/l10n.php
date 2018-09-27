<?php
class EL10N extends Exception {}
	class EL10NInitialized extends EL10N {}
	class EL10NNotInitialized extends EL10N {}
	class EL10NUndefinedValue extends EL10N {}

interface IMaxieSystemsL10N
{
	public function ToArray($callback = false, ...$args);
}

abstract class MaxieSystemsL10N implements Iterator, JsonSerializable, IMaxieSystemsL10N
{
	final public static function CreateCallback($callback, $caller)
	 {
		if(!is_callable($callback)) throw new Exception(get_called_class()."::$caller() requires argument 1 to be a valid callback");
		if(is_object($callback)) return $callback;
		if(is_array($callback))
		 {
			list($callback, $method) = $callback;
			if(is_object($callback)) return function($k, &$v, $is_callable, ...$args) use($callback, $method) { return $callback->{$method}($k, $v, $is_callable, ...$args); };
		 }
		elseif(false !== strpos($callback, '::')) list($callback, $method) = explode('::', $callback);
		else return $callback;
		return function($k, &$v, $is_callable, ...$args) use($callback, $method) { return $callback::$method($k, $v, $is_callable, ...$args); };
	 }

	public function ToArray($callback = false, ...$args)
	 {
		$r = [];
		if($callback)
		 {
			$callback = $this->CreateCallback($callback, __FUNCTION__);
			foreach($this->keys as $k => $v)
			 {
				$name = $this->MkName($k);
				if(isset($this->strings->$name))
				 {
					$v = $this->strings->$name;
					$c = false;
				 }
				elseif(isset($this->closures->$name))
				 {
					$v = $this->closures->$name;
					$c = true;
				 }
				else throw new EL10NUndefinedValue("Undefined value for lang item `$name`!");
				if(false === $callback($k, $v, $c, ...$args)) continue;
				$r[$k] = $v;
			 }
		 }
		else foreach($this as $k => $v) $r[$k] = $v;
		return $r;
	 }

	final public function jsonSerialize()
	 {
		return $this->ToArray([$this, 'OnJSONSerialize']);
	 }

	public function rewind() { reset($this->keys); }

	final public function current()
	 {
		$name = $this->MkName(key($this->keys));
		if(isset($this->strings->$name)) return $this->strings->$name;
		if(isset($this->closures->$name)) return $this->closures->$name;
	 }

	final public function key() { return key($this->keys); }
	final public function next() { next($this->keys); }
	final public function valid() { return null !== key($this->keys); }
	final public function __set($name, $value) { throw new Exception('All properties are read only!'); }

	final public function OnJSONSerialize($k, &$v, $c)
	 {
		if($c) $v = new stdClass;
	 }

	protected function MkName($name) { return $name; }

	protected $strings;
	protected $closures;
	protected $keys = [];
}

class L10NProxyData extends MaxieSystemsL10N
{
	final public function __construct($dir, stdClass $strings, stdClass $closures, array $keys)
	 {
		$this->dir = $dir;
		$this->strings = $strings;
		$this->closures = $closures;
		$this->keys = $keys;
	 }

	final public function __call($name, array $args)
	 {
		$name = $this->MkName($name);
		if(isset($this->closures->$name)) return $this->closures->$name->__invoke(...$args);
		if(isset($this->strings->$name)) return $this->strings->$name;
		throw new EL10NUndefinedValue("Undefined value for lang item `$name`!");
	 }

	final public function __get($name)
	 {
		$name = $this->MkName($name);
		if(isset($this->strings->$name)) return $this->strings->$name;
		if(isset($this->closures->$name)) return;// здесь выбрасывать исключение или пропускать в зависимости от отладочного режима?
		throw new EL10NUndefinedValue("Undefined value for lang item `$name`!");
	 }

	final public function __debugInfo() { return []; }

	final protected function MkName($name) { return "$this->dir/$name"; }

	private $dir;
}

class L10N extends MaxieSystemsL10N
{
	final public function __construct($lang, $default, array $options = null)
	 {
		$index = empty($options['index']) ? 0 : $options['index'];
		if(isset(self::$instances[$index])) throw new EL10NInitialized("Instance of ".get_class(self::$instances[$index])." with index [$index] already exists!");
		if(!$lang) $lang = $default;
		if(!$lang) throw new EL10NUndefinedValue('Undefined language!');
		self::$instances[$index] = $this;
		$this->lang = [$lang, $default];
		if(empty($this->lang[1])) $this->lang[1] = $this->lang[0];
		$this->strings = new stdClass();
		$this->closures = new stdClass();
		$this->root_dir = (isset($options['root']) ? $options['root'] : $_SERVER['DOCUMENT_ROOT']).(empty($options['dir']) ? '/include/lang' : $options['dir']);
	 }

	final public static function Instance($index = 0)
	 {
		if(empty(self::$instances[$index])) throw new EL10NNotInitialized(get_called_class().": instance with index [$index] is not initialized! Call constructor explicitly.");
		return self::$instances[$index];
	 }

	final public static function Exists($index, L10N &$inst = null)
	 {
		$inst = ($r = isset(self::$instances[$index])) ? self::$instances[$index] : null;
		return $r;
	 }

	final public static function GetInstancesCount() { return count(self::$instances); }

	final public function GetLang(&$default = null)
	 {
		list($lang, $default) = $this->lang;
		return $lang;
	 }

	final public function ReplaceNode(DOMNode $node)
	 {
		$text = $this->{$node->GetAttribute('name')};
		$attr = $node->GetAttribute('attr');
		if(null === $text)
		 {
			if('' === $attr || !$node->previousSibling->HasAttribute($attr)) throw new EL10NUndefinedValue('Undefined value for tag '.SunderLayout::LANG_NODE_NAME."[name='{$node->GetAttribute('name')}']!");
		 }
		elseif('' === $attr) return n::Replace($node, $node->ownerDocument->createTextNode($text));
		else $node->previousSibling->SetAttribute($attr, SunderLayout::ProcessAttributeValue($text));
		return n::Remove($node);
	 }

	final public function GetJS()
	 {
		$r = [];
		foreach(self::$export as $k => $v)
		 {
			$v = null;
			if($this->DirExists($k, $v)) $r[$k] = $v->ToArray([$this, 'OnJSONSerialize']);
		 }
		return json_encode(['current_lang' => self::GetLang($l), 'default_lang' => $l, 'items' => $r]);
	 }

	// final public static function Export(...$names)
	 // {
		// foreach($names as $name)
		 // {
			// if(false === $name) unset(self::$export['']);
			// else self::$export[$name] = true;
		 // }
		// return self::Instance();
	 // }

	final public function DirExists($dir, MaxieSystemsL10N &$data = null)
	 {
		$dir = "$dir";
		if($r = ('' === $dir)) $data = $this;
		else
		 {
			$dir = "/$dir";
			if($r = isset($this->proxy_data[$dir])) $data = $this->proxy_data[$dir];
			elseif($r = $this->StorageExists($dir)) $data = $this->InitProxyDataByDir($dir);
			else $data = null;
		 }
		return $r;
	 }

	final public function __invoke($dir)
	 {
		$dir = "/$dir";
		if(!isset($this->proxy_data[$dir])) $this->InitProxyDataByDir($dir);
		return $this->proxy_data[$dir];
	 }

	final public function __call($name, array $args)
	 {
		if(isset($this->closures->$name)) return $this->closures->$name->__invoke(...$args);
		if(isset($this->strings->$name)) return $this->strings->$name;
		$dir = $this->InitProxyDataByName($name);
		if($this->Init($dir))
		 {
			if(isset($this->closures->$name)) return $this->closures->$name->__invoke(...$args);
			if(isset($this->strings->$name)) return $this->strings->$name;
		 }
	 }

	final public function __get($name)
	 {
		if(isset($this->strings->$name)) return $this->strings->$name;
		// if(isset($this->closures->$name)) return $this->closures->$name->__invoke();// здесь выбрасывать исключение или пропускать в зависимости от отладочного режима?
		$dir = $this->InitProxyDataByName($name);
		if($this->Init($dir))
		 {
			if(isset($this->strings->$name)) return $this->strings->$name;
			// if(isset($this->closures->$name)) return $this->closures->$name->__invoke();// здесь выбрасывать исключение или пропускать в зависимости от отладочного режима?
		 }
	 }

	final public function ToArray($callback = false, ...$args)
	 {
		if($callback && !isset($this->all_keys[''])) $this->Init('');
		return parent::ToArray($callback, ...$args);
	 }

	final public function rewind()
	 {
		if(!isset($this->all_keys[''])) $this->Init('');
		parent::rewind();
	 }

	final public function __debugInfo() { return ['current_lang' => $this->lang[0], 'default_lang' => $this->lang[1]]; }

	protected function Load($dir, $lang, &$fname = null)
	 {
		$fname = "$this->root_dir$dir/$lang.php";
		$items = (require $fname);
		return ($items && $items !== 1) ? $items : [];
	 }

	protected function StorageExists($dir) { return file_exists("$this->root_dir$dir"); }

	final private function Init($dir)
	 {
		foreach($this->lang as $i => $lang)
		 if(empty($this->loaded[$dir][$lang]))
		  {
			$data = $this->Load($dir, $lang, $fname);
			if(!is_array($data) && !($data instanceof Iterator)) throw new Exception("Language storage `$fname` must return array or Iterator, ".(is_object($data) ? 'instance of '.get_class($data) : gettype($data)).' given.');
			$this->all_keys[$dir] = [];
			$init_property = function($n, $k, $s) use($dir){
				$p = is_string($s) ? 'strings' : 'closures';
				if(!isset($this->$p->$n))
				 {
					$this->$p->$n = $s;
					$this->all_keys[$dir][$k] = $k;
				 }
			};
			if('' === $dir)
			 {
				foreach($data as $k => $s) $init_property($k, $k, $s);
				$this->keys = &$this->all_keys[''];
			 }
			else foreach($data as $k => $s) $init_property("$dir/$k", $k, $s);
			return ($this->loaded[$dir][$lang] = true);
		  }
	 }

	final private function InitProxyDataByName($name)
	 {
		if(self::DELIMITER === $name[0])
		 {
			$pos = strrpos($name, self::DELIMITER);
			if(0 === $pos) throw new Exception("Invalid value '$name' for attribute lang[name]: must have at least two delimiters!");
			$dir = substr($name, 0, $pos);
			if(!isset($this->proxy_data[$dir])) $this->proxy_data[$dir] = new L10NProxyData($dir, $this->strings, $this->closures, $this->all_keys[$dir]);
		 }
		else $dir = '';
		return $dir;
	 }

	final private function InitProxyDataByDir($dir)
	 {
		$this->Init($dir);
		return $this->proxy_data[$dir] = new L10NProxyData($dir, $this->strings, $this->closures, $this->all_keys[$dir]);
	 }

	private $lang;
	private $all_keys = [];
	private $proxy_data = [];
	private $loaded = [];
	private $root_dir;

	private static $instances = [];
	private static $export = ['' => true];

	const DELIMITER = '/';
}

function l10n($dir = '') { return '' === "$dir" ? L10N::Instance() : L10N::Instance()->__invoke($dir); }
?>
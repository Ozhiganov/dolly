<?php
trait TOptions
{
	final public function GetOption($name, &$is_default = null)
	 {
		if(null === $this->options) $this->InitializeOptionsObjects();
		$is_default = $this->options->PropertyIsDefault($name);
		return $this->options->$name;
	 }

	final public function OptionExists($name, &$value = null)
	 {
		if(null === $this->options) $this->InitializeOptionsObjects();
		$value = ($ex = isset($this->options->$name)) ? $this->options->$name : null;
		return $ex;
	 }

	final public function GetOptions(...$names)
	 {
		if(null === $this->options) $this->InitializeOptionsObjects();
		if($names)
		 {
			$r = [];
			foreach($names as $n) $r[$n] = $this->options->$n;
			return $r;
		 }
		else return $this->options->__debugInfo();
	 }

	final public function SetOption($name, $value)
	 {
		if(null === $this->options) $this->InitializeOptionsObjects();
		$this->options->$name = $value;
		return $this;
	 }

	final public function Options2Fields(stdClass $dest, ...$names)
	 {
		foreach($names as $name) if($this->OptionExists($name, $value)) $dest->$name = $value;
		return $this;
	 }

	final protected function SetOptionsData(array $data = null)
	 {
		if(null === $this->options_data) $this->options_data = null === $data ? [] : $data;
		else throw new Exception(get_class($this).'::'. __FUNCTION__ .'(). Can not rewrite options: already initialized!');
		return $this;
	 }

	final protected function AddOptionsMeta(array $meta)
	 {
		foreach($meta as $k => $v)
		 if(isset($this->options_meta[$k])) throw new Exception(get_class($this).'::'. __FUNCTION__ ."(). Option `$k` exists!");
		 else $this->options_meta[$k] = $v;
		return $this;
	 }

	final protected function ChangeOptionsMeta($name, array $meta)
	 {
		if(null === $this->options)
		 {
			if(!isset($this->options_meta_changes[$name])) $this->options_meta_changes[$name] = $meta;
			return $this;
		 }
		throw new Exception(get_class($this).'::'. __FUNCTION__ ."(). Can not rewrite meta data for option `$name`: options already initialized!");
	 }

	final private function InitializeOptionsObjects()
	 {
		if(null === $this->options)
		 {
			foreach($this->options_meta_changes as $name => $meta)
			 {
				if(isset($this->options_meta[$name])) $this->options_meta[$name] = $meta ? array_merge($this->options_meta[$name], $meta) : $meta;
				else throw new Exception(get_class($this).'::'. __FUNCTION__ ."(). Undefined option `$name`!");
			 }
			if($this->options_meta) $this->options = new OptionsGroup($this->options_data, $this->options_meta);
			else throw new Exception(get_class($this).'::'. __FUNCTION__ ."(). Empty metadata for options!");
		 }
		else throw new Exception('Can not rewrite options: already initialized!');
	 }

	private $options = null;
	private $options_data = null;
	private $options_meta = null;
	private $options_meta_changes = [];
}

trait TInstances
{
	final public static function Instance($index = 0)
	 {
		if(empty(self::$instances[$index])) if(!(self::$instances[$index] = self::OnUndefinedInstance($index))) throw new Exception(get_called_class().": instance with index [$index] is undefined.");
		return self::$instances[$index];
	 }

	final public static function InstanceExists($index, &$inst = null)
	 {
		$inst = ($r = isset(self::$instances[$index])) ? self::$instances[$index] : null;
		return $r;
	 }

	final public static function GetInstancesCount() { return count(self::$instances); }

	final public static function GetInstancesIDs()
	 {
		$r = [];
		foreach(self::$instances as $k => $v) $r[$k] = $k;
		return $r;
	 }

	protected static function OnUndefinedInstance($index) {}

	final protected static function SetInstance($index, $obj)
	 {
		if(isset(self::$instances[$index])) throw new Exception(get_called_class().": Object with index [$index] already exists (".get_class(self::$instances[$index]).").");
		self::$instances[$index] = $obj;
	 }

	private static $instances = [];
}

trait TCallbacks
{
	final protected static function CreateCallbackArgs($callback, $method = false)
	 {
		if(is_array($callback)) list($callback, $method) = $callback;
		if(is_object($callback) && $method) return function(...$args) use($callback, $method) { return $callback->{$method}(...$args); };
		elseif(is_string($callback) && ($method || strpos($callback, '::') !== false))
		 {
			if(!$method) list($callback, $method) = explode('::', $callback);
			return function(...$args) use($callback, $method) { return $callback::$method(...$args); };
		 }
		else return $callback;
	 }
}
?>
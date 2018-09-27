<?php
MSConfig::RequireFile('datacontainer');

class LangSelector implements Iterator
{
	final public function __construct($data, $host, array $options = null)
	 {
		$this->o = new OptionsGroup($options, ['default_lang' => ['type' => 'string', 'value' => 'en'], 'index' => ['type' => 'int,string', 'value' => 0], 'protocol' => ['type' => 'string', 'value' => 'https://'], 'uri' => ['type' => 'callback,bool', 'value' => false], 'www' => ['type' => 'bool,null', 'value' => null]]);
		if(isset(self::$instances[$this->o->index])) throw new Exception("Instance of ".get_class(self::$instances[$this->o->index])." with index [{$this->o->index}] already exists!");
		self::$instances[$this->o->index] = $this;
		$this->host = $host;
		if($this->o->uri) $this->mk_link = 'MkLink_'.(true === $this->o->uri ? 'HostAndUri' : 'CreateUrl');
		if(is_array($data))
		 {
			MSConfig::RequireFile('arrayresult');
			$this->data = new ArrayResult($data, ['transform' => function($k, $v){
				$r = new stdClass();
				$r->id = $k;
				$r->title = $v;
				$r->class = "select_lang__item _$k";
				$r->selected = $k === $this->GetLang();
				$r->href = $this->o->protocol.$this->{$this->mk_link}($r, $k, $this->host, $_SERVER['REQUEST_URI']);
				if($r->selected) $r->class .= ' _selected';
				return $r;
			}]);
		 }
		elseif(is_string($data)) $this->data = \DB::Select($data, '*');
		elseif($data instanceof Iterator) $this->data = $data;
		else throw new Exception('Invalid data type!');
		$this->lang = $this->o->default_lang;
		foreach($this->data as $k => $v) if($v->host === $_SERVER['HTTP_HOST'])
		 {
			$this->lang = $k;
			break;
		 }
	 }

	final public static function Instance($index = 0)
	 {
		if(empty(self::$instances[$index])) throw new Exception(get_called_class().": instance with index [$index] is not initialized! Call constructor explicitly.");
		return self::$instances[$index];
	 }

	final public function rewind() { $this->data->rewind(); }
	final public function current() { return $this->data->current(); }
	final public function key() { return $this->data->key(); }
	final public function next() { $this->data->next(); }
	final public function valid() { return $this->data->valid(); }

	final public function GetLang(&$default = null)
	 {
		$default = $this->o->default_lang;
		return $this->lang;
	 }

	final private function MkLink_Host(stdClass $row, $lang, $host)
	 {
		$row->host = "$lang.";
		if($this->o->default_lang === $lang)
		 {
			if(null === $this->o->www) $row->host = '';
			elseif(true === $this->o->www) $row->host = 'www.';
		 }
		return ($row->host .= $host);
	 }

	final private function MkLink_HostAndUri(stdClass $row, $lang, $host, $uri) { return $this->MkLink_Host($row, $lang, $host).$uri; }
	final private function MkLink_CreateUrl(stdClass $row, $lang, $host, $uri) { return call_user_func($this->o->uri, $lang, $this->MkLink_Host($row, $lang, $host), $uri); }

	private $data;
	private $lang;
	private $host;
	private $o;
	private $mk_link = 'MkLink_Host';

	private static $instances = [];
}
?>
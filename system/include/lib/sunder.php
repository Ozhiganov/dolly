<?php
if(!defined('MSSE_SYS_INC_DIR')) define('MSSE_SYS_INC_DIR', dirname(__FILE__));
require_once(MSSE_SYS_INC_DIR.'/n.php');
require_once(MSSE_SYS_INC_DIR.'/traits.php');
require_once(MSSE_SYS_INC_DIR.'/events.php');

class ESunder extends Exception {}
	class ESunderInitialized extends ESunder {}
	class ESunderNotInitialized extends ESunder {}
	class ESunderDuplicateFormat extends ESunder {}
	class ESunderDuplicateLayout extends ESunder {}
	class ESunderValueIsSet extends ESunder {}
	class ESunderGlobalRuleIsSet extends ESunder {}
	class ESunderMissingLayout extends ESunder {}
	class ESunderMissingAttribute extends ESunder {}
	class ESunderMissingFragment extends ESunder {}
	class ESunderMissingParameter extends ESunder {}
	class ESunderMissingRule extends ESunder {}
	class ESunderInvalidFragment extends ESunder {}
	class ESunderInvalidDataSet extends ESunder {}
	class ESunderInvalidRule extends ESunder {}
	class ESunderInvalidHandler extends ESunder {}
	class ESunderEmptyAttribute extends ESunder {}
	class ESunderEmptyName extends ESunder {}
	class ESunderEmptyL10N extends ESunder {}

class ESunderInvalidXMLFragment extends ESunder
{
	final public function __construct($message, $code, LibXMLError $xml_error, $xml_code, $layout_name, $xml_src, array $caller)
	 {
		parent::__construct($message, $code);
		$this->xml_error = $xml_error;
		$this->xml_code = $xml_code;
		$this->layout_name = $layout_name;
		$this->xml_src = $xml_src;
		$this->caller = $caller;
	 }

	final public function GetXMLError() { return $this->xml_error; }
	final public function GetXMLCode() { return $this->xml_code; }
	final public function GetLayoutName() { return $this->layout_name; }
	final public function GetXMLSrc() { return $this->xml_src; }
	final public function GetCaller() { return $this->caller; }

	private $xml_error;
	private $xml_code;
	private $layout_name;
	private $xml_src;
	private $caller;
}

interface ILayout
{
	public function __construct($name, $data_set, $fr_src, array $rule_set = null, array $parameters = null);
}

interface IRecursiveLayout {}

interface IRecursiveDataSet
{
	public function SetParent(stdClass $data);
	public function GetDataSet();
}

abstract class SunderLayout
{
	const DATA_NODE_NAME = 'sdn';
	const LAYOUT_NODE_NAME = 'layout';
	const LANG_NODE_NAME = 'lang';

	final public static function code2utf($num)
	 {
		if($num < 128) return chr($num);
		if($num < 2048) return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
		if($num < 65536) return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
		if($num < 2097152) return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
		return '';
	 }

	final public static function HTMLEntityDecodeUTF8($string)
	 {
		static $trans_tbl = [];
		// replace numeric entities
		$string = preg_replace_callback('~&#x([0-9a-f]+);~i', function($m){ return self::code2utf(hexdec($m[1])); }, $string);
		$string = preg_replace_callback('~&#([0-9]+);~', function($m){ return self::code2utf($m[1]); }, $string);
		// replace literal entities
		if(!$trans_tbl) foreach(get_html_translation_table(HTML_ENTITIES) as $val=>$key) $trans_tbl[$key] = utf8_encode($val);
		return strtr($string, $trans_tbl);
	 }

	final public static function SetGlobalRule($name, $rule)
	 {
		if(':' !== $name[0]) $name = ":$name";
		if(isset(self::$global_rule_set[$name])) throw new ESunderGlobalRuleIsSet("Global rule `$name` is set.");
		if(is_array($rule))
		 {
			$r = ['raw' => array_shift($rule), 'is_prm' => true, 'args' => $rule, 'search' => []];
			if(!$r['args']) throw new ESunderInvalidRule("Parameterized rule `$name` must have at least 1 argument!");
			foreach($r['args'] as $key => $arg) $r['search'][] = "{\$$key}";
		 }
		else $r = ['raw' => $rule, 'func' => self::InitRule($rule, $name), 'is_prm' => false];
		self::$global_rule_set[$name] = $r;
	 }

	final public static function SetGlobalRules(array $values)
	 {
		foreach($values as $name => $value) self::SetGlobalRule($name, $value);
	 }

	final public static function ProcessAttributeValue($value) { return empty($value) || strpos($value, '&') === false ? $value : self::HTMLEntityDecodeUTF8($value); }

	final public static function GetVarType($v, $s = 'instance of ') { return 'object' === ($t = gettype($v)) ? $s.get_class($v) : $t; }

	final protected static function InitRule($rule, $name)
	 {
		if(is_string($rule)) return create_function('$d', "return $rule;");
		if(true === $rule || false === $rule) return $rule;
		if(is_object($rule) && ($rule instanceof Closure)) return $rule;
		throw new ESunderInvalidRule("Rule `$name` is invalid! String, Boolean or Closure expected; ".self::GetVarType($rule).' given.');
	 }

	final protected static function IsInsideLayout(DOMElement $node, DOMNode $root)
	 {
		while($node->parentNode && $root !== $node->parentNode)
		 {
			if(self::LAYOUT_NODE_NAME === $node->tagName) return true;
			$node = $node->parentNode;
		 }
	 }

	final protected static function GetGlobalRule($name, array &$params = null, MSLayout $l = null)
	 {
		if(isset(self::$global_rule_set[$name]))
		 {
			$r = self::$global_rule_set[$name];
			if($r['is_prm'])
			 {
				if($params)
				 {
					if(empty($params[$name]))
					 {
						$params[$name] = [];
						foreach($r['args'] as $arg)
						 {
							if(isset($params['__p'][$arg])) $params[$name][$arg] = isset($params['__r'][$name][$arg]) ? $params['__r'][$name][$arg] : $params['__p'][$arg];
							else throw new ESunderMissingParameter(($l ? get_called_class()."[name='{$l->GetName()}']: p" : 'P')."arameter `$arg` for global rule `$name` is missing!");
						 }
					 }
					$rule = str_replace($r['search'], $params[$name], $r['raw'], $count);
					if(!$count) throw new ESunderInvalidRule("Parameterized rule `$name` is invalid! No placeholders found.");
					return create_function('$d', "return $rule;");
				 }
				throw new ESunderMissingParameter(($l ? get_called_class()."[name='{$l->GetName()}']: p" : 'P')."arameters for global rule `$name` are missing!");
			 }
			else return $r['func'];
		 }
		throw new ESunderMissingRule("Global rule `$name` is missing!");
	 }

	final protected static function ReplaceLayout(DOMElement $node, MSLayout $parent = null, &$name = null)
	 {
		$name = $node->getAttribute('name');
		$recursive = false;
		if(null !== $parent)
		 {
			if(':' === $name)
			 {
				$name = $parent->GetName();
				$recursive = true;
			 }
			elseif(':' === $name[0]) $name = $parent->GetName().$name;
		 }
		if(empty(self::$layouts[$name]))
		 {
			$layout = null;
			if('true' === $node->getAttribute('default'))
			 {
				if(!$node->hasChildNodes()) throw new ESunderMissingFragment("Layout[name='$name']: default layout can not be empty, inline fragment required!");
				foreach($node->GetElementsByTagName($node->nodeName) as $node0)
				 {
					$name0 = $node0->getAttribute('name');
					if(':' === $name0);
					elseif(':' === $name0[0])
					 {
						$n = $node0;
						while($n = $n->parentNode)
						 if($n->nodeName === $node->nodeName)
						  {
							if($n === $node) $node0->setAttribute('name', $name.$name0);
							break;
						  }
					 }
				 }
				self::ReplaceIFValues($node);
				n::Replace($node);
				$status = 1;
			 }
			else
			 {
				n::Remove($node);
				$status = null;
			 }
		 }
		else
		 {
			$layout = self::$layouts[$name];
			$status = ($recursive && !(self::$layouts[$name] instanceof IRecursiveLayout)) ? null : $layout->Build($node);
		 }
		if(SUNDER_DEBUG) \Sunder\Debug\Statistics::CountLayoutTag($name, $status, $parent, $layout);
		return $status;
	 }

	final protected static function ReplaceDataNode(DOMNode $node, stdClass $data, MSLayout $layout = null, $on_replace)
	 {
		$name = $attr = $is_attr = false;
		if($node->HasAttribute('name')) $name = $node->GetAttribute('name');
		elseif($node->HasAttribute('d'))
		 {
			$name = $node->GetAttribute('d');
			$attr = "data-$name";
			$is_attr = true;
		 }
		elseif($node->HasAttribute('a'))
		 {
			$name = $node->GetAttribute('a');
			$attr = $name;
			$is_attr = true;
		 }
		if($name)
		 {
			if(isset($data->$name))
			 {
				$value = "{$data->$name}";
				if($node->HasAttribute('attr'))
				 {
					$attr = $node->GetAttribute('attr');
					$is_attr = true;
				 }
				if($is_attr)
				 {
					if('' === $value)
					 {
						$a = $node->getAttribute('ifempty');
						if('set' === $a) $node->previousSibling->SetAttribute($attr, '');
						elseif('remove' === $a) $node->previousSibling->RemoveAttribute($attr);
					 }
					else $node->previousSibling->SetAttribute($attr, self::ProcessAttributeValue(self::FormatValue($node, $value)));
				 }
				elseif('' !== $value)
				 {
					$value = self::FormatValue($node, $value);
					if(is_numeric($value)) $dn = $node->ownerDocument->createTextNode($value);
					elseif(false === strpos($value, '<')) $dn = $node->ownerDocument->createTextNode(strpos($value, '&') === false ? $value : html_entity_decode($value, ENT_XHTML | ENT_QUOTES, "UTF-8"));
					else $dn = self::FillFragment($node->ownerDocument->createDocumentFragment(), $value, 0, "Data set, value '$name'", ['class' => __CLASS__, 'type' => '::', 'function' => __FUNCTION__]);
					if(null !== $on_replace) $on_replace($data, $name, $layout, false, $node, $dn);
					n::Replace($node, $dn);
					return;
				 }
				if(null !== $on_replace) $on_replace($data, $name, $layout, $is_attr, $node, null);
			 }
			elseif(null !== $on_replace && property_exists($data, $name)) $on_replace($data, $name, $layout, $attr || $node->HasAttribute('attr'), $node, null);
		 }
		elseif($group = $node->GetAttribute('group'))
		 {
			if(!empty($data->$group)) foreach($data->$group as $attr => $value) $node->previousSibling->SetAttribute($attr, $value);
		 }
		elseif($node->HasAttribute('value')) $node->previousSibling->SetAttribute($node->GetAttribute('attr'), $node->GetAttribute('value'));
		n::Remove($node);
	 }

	final protected static function ReplaceIFValues(DOMNode $root)
	 {
		$nodes = $root->GetElementsByTagName('if');
		for($j = $nodes->length - 1; $j >= 0; --$j)
		 {
			$node = $nodes->item($j);
			if(!self::IsInsideLayout($node, $root))
			 {
				if($node->HasAttribute('static') && ($name = $node->GetAttribute('static')) !== '')
				 {
					n::ReplaceIfElseNodes(Sunder::GetIfValue($name), null, $node);
				 }
				else throw new ESunderMissingAttribute("Element `if` is missing required attribute `static`.");
			 }
		 }
	 }

	final protected static function FillFragment(DOMDocumentFragment $fragment, $code, $layout_name, $src, array $caller)
	 {
		if(SUNDER_DEBUG) set_error_handler(function($no, $str, $file, $line, $context) use($code, $layout_name, $src, $caller){
			set_exception_handler('\Sunder\Debug\ExceptionHandler');
			restore_error_handler();
			throw new ESunderInvalidXMLFragment(str_replace('DOMDocumentFragment::appendXML(): ', '', $str), $no, libxml_get_last_error(), $code, $layout_name, $src, $caller);
		});
		if(!@$fragment->appendXML(false === strpos($code, '&') ? $code : n::ReplaceHTMLEntities($code)))
		 {
			if(self::$on_invalid_fragment) call_user_func(self::$on_invalid_fragment, libxml_get_last_error(), $layout_name, $src, $caller);
			$fragment->appendXML('<div class="sunder_invalid_fragment">An error has occurred during fragment processing.</div>');
		 }
		if(SUNDER_DEBUG) restore_error_handler();
		return $fragment;
	 }

	final protected static function PrepareParameters(array $src, array &$dest = null)
	 {
		$dest = ['__r' => [], '__p' => []];
		foreach($src as $key => &$value)
		 {
			if(strpos($key, '::') === false) $dest['__p'][$key] = &$value;
			else
			 {
				list($r, $p) = explode('::', $key);
				if($r[0] !== ':') $r = ":$r";
				if(!isset($dest['__r'][$r])) $dest['__r'][$r] = [];
				$dest['__r'][$r][$p] = &$value;
			 }
		 }
	 }

	final protected static function FormatValue(DOMNode $node, $value) { return ($format = $node->GetAttribute('format')) ? self::$formats[$format]->__invoke($value) : $value; }

	final protected static function FullFName($name, &$fname = null)
	 {
		$fname = self::FName($name, $root);
		return $root ? self::$html_root.$fname : $fname;
	 }

	final protected static function FName($name, &$root = null)
	 {
		$name .= '.html';
		if($root = ($name[0] === '/')) return $name;
		$dir = dirname($_SERVER['SCRIPT_FILENAME']);
		if('\\' === $dir) $dir = '';
		return "$dir/$name";
	 }

	protected static $layouts = [];
	protected static $f_files = [];
	protected static $on_invalid_fragment = null;
	protected static $on_remove_data_node = null;
	protected static $html_root;
	protected static $formats = [];

	private static $global_rule_set = [];
}

abstract class MSLayout extends SunderLayout implements ILayout
{
	use TEvents;

	abstract protected function Build(DOMElement $layout);

	public function __construct($name, $data_set, $fr_src, array $rule_set = null, array $parameters = null)
	 {
		$this->name = "$name";
		if('' === $this->name) throw new ESunderEmptyName('Empty layout name!');
		if(isset(self::$layouts[$this->name])) throw new ESunderDuplicateLayout("Duplicate layout `$this->name`.");
		$this->data_set = $data_set;
		$this->fr_src = $fr_src;
		if($rule_set) foreach($rule_set as $key => $rule) $this->rule_set[$key] = self::InitRule($rule, $key);
		if($parameters) $this->PrepareParameters($parameters, $this->parameters);
		self::$layouts[$this->name] = $this;
		$this->RegisterEvents('before_run', 'on_fetch', 'empty', 'after_run');
	 }

	public function __debugInfo() { return ['name' => $this->name, 'data_set' => MSLayoutInfo::GetDataSetType($this->GetDataSet())]; }

	final public static function Get($name) { return self::$layouts[$name]; }

	final public function GetName() { return $this->name; }
	final public function GetDataSet() { return $this->data_set; }

	final public function GetInfo()
	 {
		if(null === $this->cache__info) $this->cache__info = new MSLayoutInfo($this->name, get_class($this), $this->fragment_type, $this->fragment_file, $this->fragment_num_static, !$this->fragment || !$this->fragment->childNodes->length, $this->GetDataSet());
		return $this->cache__info;
	 }

	final protected function GetRule($name)
	 {
		if(isset($this->rule_set[$name])) return $this->rule_set[$name];
		elseif(':' === $name[0]) return $this->rule_set[$name] = self::GetGlobalRule($name, $this->parameters, $this);
		switch($this->fragment_type)
		 {
			case 'inline': $fr = 'inline fragment'; break;
			case 'callback': $fr = gettype($this->fr_src); break;
			case 'file': $fr = "file `{$this->fragment_file}`"; break;
			default: $fr = 'undefined fragment';
		 }
		throw new ESunderMissingRule("Layout[name='{$this->GetName()}']: rule `$name` is missing in $fr!");// on line # ???
	 }

	final protected function CreateFragment(DOMElement $layout)
	 {
		if(null === $this->fragment)
		 {
			$this->fragment = $layout->ownerDocument->createDocumentFragment();
			if(!$this->fr_src)
			 {
				if(!$layout->childNodes->length)
				 {
					if(empty(self::$inline_fragments[$this->name])) throw new ESunderMissingFragment("HTML fragment `$this->name` is empty (empty '".self::LAYOUT_NODE_NAME."' tag)!");
					else $layout = self::$inline_fragments[$this->name]->cloneNode(true);
				 }
				elseif(empty(self::$inline_fragments[$this->name])) self::$inline_fragments[$this->name] = $layout->cloneNode(true);
				while($layout->firstChild) $this->fragment->appendChild($layout->firstChild);
				$this->ReplaceStaticIF($this->fragment, $this->fragment_num_static);
				$this->fragment_type = 'inline';
			 }
			else
			 {
				if(is_callable($this->fr_src, false, $cname) || is_array($this->fr_src))
				 {
					if($code = call_user_func($this->fr_src))
					 {
						self::FillFragment($this->fragment, $code, $layout->getAttribute('name'), "Callback: $cname", ['class' => __CLASS__, 'type' => '->', 'function' => __FUNCTION__]);
						$this->ReplaceStaticIF($this->fragment, $this->fragment_num_static);
					 }
					elseif(!empty($this->data_set)) throw new ESunderMissingFragment("HTML fragment `$this->name` is empty (callback returned an empty value)!");
					$this->fragment_type = 'callback';
				 }
				else
				 {
					$full_name = $this->FullFName($this->fr_src, $fname);
					if(isset(self::$f_files[$fname])) $code = self::$f_files[$fname];
					else
					 {
						self::$f_files[$fname] = $code = file_get_contents($full_name);
						if(!$code) throw new ESunderMissingFragment("HTML fragment `$this->name` is empty (file `$fname`)!");
					 }
					self::FillFragment($this->fragment, $code, $layout->getAttribute('name'), "File: $fname", ['class' => __CLASS__, 'type' => '->', 'function' => __FUNCTION__]);
					$this->ReplaceStaticIF($this->fragment, $this->fragment_num_static);
					$this->fragment_type = 'file';
					$this->fragment_file = $fname;
				 }
			 }
		 }
		return $this->fragment->cloneNode(true);
	 }

	final protected function ClearFragment()
	 {
		if($this->fragment_type !== 'file' || $this->fragment_num_static > 0)
		 {
			$this->fragment_type = $this->fragment = null;
			$this->fragment_num_static = 0;
		 }
	 }

	final protected function ReplaceStaticIF(DOMDocumentFragment $fragment, &$count = null)
	 {
		if($this->fragment_num_static === false) $count = false;
		else
		 {
			n::WalkFragmentWith($fragment, 'if', function(DOMNode $node){
				if($name = $node->GetAttribute('static'))
				 {
					n::ReplaceIfElseNodes($this->GetRule($name), null, $node);
					return true;
				 }
			}, $count);
		 }
		return $fragment;
	 }

	final protected function ReplaceLayouts(DOMDocumentFragment $fragment)
	 {
		$replaced = [];
		foreach(n::GetLayoutsFromFragment($fragment, self::LAYOUT_NODE_NAME) as $node) if($this->ReplaceLayout($node, $this, $name)) $replaced[] = $name;
		return $replaced;
	 }

	final protected function ReplaceData(DOMDocumentFragment $fragment, stdClass $data)
	 {
		n::CleanFragmentWith($fragment, 'if', function(DOMNode $node) use($data){
			if($name = $node->GetAttribute('name')) n::ReplaceIfElseNodes($this->GetRule($name), $data, $node);
			elseif($name = $node->GetAttribute('static'))
			 {
				n::ReplaceIfElseNodes($this->GetRule($name), null, $node);
				$this->fragment_num_static = true;
			 }
			else throw new ESunderMissingAttribute("Element `if` is missing required attribute `name`.");
		});
		foreach(n::GetTagsFromFragment($fragment, self::DATA_NODE_NAME) as $node) $this->ReplaceDataNode($node, $data, $this, self::$on_remove_data_node);
		if(true === $this->fragment_num_static) $this->fragment = null;
		return $fragment;
	 }

	final protected function InitInsertFragment()
	 {
		$this->counter = 0;
		$this->insert_fragment = $this->HandlerExists('on_fetch') ? 'InsertFragmentNode_Handler' : 'InsertFragmentNode';
	 }

	final protected function InsertFragment(stdClass $data, DOMElement $layout)
	 {
		if(0 === $this->counter) $this->DispatchEvent('before_run', false, ['layout' => $this, 'build_number' => $this->build_number++]);
		$this->{$this->insert_fragment}($data, $layout);
		++$this->counter;
	 }

	final private function InsertFragmentNode(stdClass $data, DOMElement $layout)
	 {
		$data->__number = $this->counter;
		$fragment = $this->CreateFragment($layout);
		if(SUNDER_MARK_LAYOUTS) \Sunder\Debug\MarkFragment($fragment, $layout, $this->counter);
		$data->__replaced_layouts = $this->ReplaceLayouts($fragment);
		$this->ReplaceData($fragment, $data);
		if($fragment->childNodes->length) $layout->parentNode->insertBefore($fragment, $layout);
	 }

	final private function InsertFragmentNode_Handler(stdClass $data, DOMElement $layout)
	 {
		$this->DispatchEvent('on_fetch', false, ['layout' => $this, 'data' => $data, 'number' => $this->counter], ['data' => ['set' => true]]);
		$this->InsertFragmentNode($data, $layout);
	 }

	protected $build_number = 0;// это счётчик количества вызовов метода Build в пределах одного объекта.
	protected $counter = 0;// это счётчик количества извлечённых из набора данных рядов.

	private static $inline_fragments = [];

	private $name;
	private $data_set;
	private $fr_src;
	private $rule_set = [];
	private $parameters = null;
	private $fragment = null;
	private $fragment_type = null;
	private $fragment_file = null;
	private $fragment_num_static = false;
	private $cache__info = null;
	private $insert_fragment = null;
}

class Layout extends MSLayout
{
	final protected function Build(DOMElement $layout)
	 {
		$this->InitInsertFragment();
		if(SUNDER_MARK_LAYOUTS) $anchor = \Sunder\Debug\AnchorLayout($layout);
		$data_set = $this->GetDataSet();
		if($data_set instanceof Iterator) foreach($data_set as $data) $this->InsertFragment($data, $layout);
		elseif(is_callable($data_set)) while($data = call_user_func($data_set)) $this->InsertFragment($data, $layout);
		elseif(is_array($data_set)) foreach($data_set as $data) $this->InsertFragment($data, $layout);
		else throw new ESunderInvalidDataSet(__CLASS__ ."[name='{$this->GetName()}']: invalid data set! Must be array, instance of Iterator or callable; {$this->GetVarType($data_set)} given.");
		if(SUNDER_MARK_LAYOUTS) \Sunder\Debug\MarkLayout($anchor, $layout, $this);
		n::Remove($layout);
		$this->ClearFragment();
		$edt = ['layout' => $this];
		if($is_empty = 0 === $this->counter) $event_name = 'empty';
		else
		 {
			$event_name = 'after_run';
			$edt['count'] = $this->counter;
		 }
		$this->DispatchEvent($event_name, false, $edt);
		return !$is_empty;
	 }
}

class RLayout extends MSLayout implements IRecursiveLayout
{
	final protected function Build(DOMElement $layout)
	 {
		$obj = $this->GetDataSet();
		$insert_fragment = function(stdClass $data) use($layout, $obj){
			$obj->SetParent($data);
			$this->InsertFragment($data, $layout);
		};
		if(SUNDER_MARK_LAYOUTS) $anchor = \Sunder\Debug\AnchorLayout($layout);
		if($obj instanceof IRecursiveDataSet)
		 {
			if($data_set = $obj->GetDataSet())
			 {
				$this->InitInsertFragment();
				foreach($data_set as $data) $insert_fragment($data);
			 }
			else $this->counter = 0;
		 }
		else throw new ESunderInvalidDataSet(__CLASS__ ."[name='{$this->GetName()}']: invalid data set! Must be instance of IRecursiveDataSet; {$this->GetVarType($obj)} given.");
		$replaced = false;
		if(0 === $this->counter) ;// empty iterator event... здесь будет несколько проходов - как узнать, когда итератор пустой, а когда это была последняя группа итераций?
		else $replaced = true;// end of iteration event???
		// если реализовывать события, указанные выше, то каким образом увязать их действие с рекурсивностью?
		if(SUNDER_MARK_LAYOUTS) \Sunder\Debug\MarkLayout($anchor, $layout, $this);
		n::Remove($layout);
		$this->ClearFragment();
		return $replaced;
	 }
}

class SLayout extends MSLayout
{
	final public function __construct($name, $data_set, $fr_src, array $rule_set = null, array $parameters = null)
	 {
		// if(empty($fr_src)) throw new ESunderMissingFragment("Layout[name='$name']: HTML-fragment is missing! Please, specify it explicitly; inline fragments are prohibited for this type of layouts.");
		if(is_array($data_set)) $data_set = (object)$data_set;
		parent::__construct($name, $data_set, $fr_src, $rule_set, $parameters);
	 }

	final protected function Build(DOMElement $layout)
	 {
		$fragment = $this->CreateFragment($layout);
		if($fragment->childNodes->length)
		 {
			$this->DispatchEvent('before_run', false, ['layout' => $this]);
			$this->ReplaceLayouts($fragment);
			if($data = $this->GetDataSet()) $this->ReplaceData($fragment, $data);
			else
			 {
				n::WalkFragmentWith($fragment, 'if', function(DOMNode $node){
					if($name = $node->GetAttribute('name'))
					 {
						n::ReplaceIfElseNodes($this->GetRule($name), null, $node);
						return true;
					 }
					elseif($name = $node->GetAttribute('static'))
					 {
						n::ReplaceIfElseNodes(Sunder::GetIfValue($name), null, $node);
						return true;
					 }
					else throw new ESunderMissingAttribute("Element `if` is missing required attribute `name`.");
				});
			 }
			if(SUNDER_MARK_LAYOUTS) \Sunder\Debug\MarkLayout(null, $layout, $this);
			n::Replace($layout, $fragment);
			$replaced = true;
			$this->DispatchEvent('after_run', false, ['layout' => $this, 'count' => 1]);
		 }
		else
		 {
			if(SUNDER_MARK_LAYOUTS) \Sunder\Debug\MarkLayout(null, $layout, $this);
			n::Remove($layout);
			$replaced = false;
			$this->DispatchEvent('empty', false, ['layout' => $this]);
		 }
		$this->ClearFragment();
		return $replaced;
	 }
}

class Sunder extends SunderLayout
{
	use TOptions;

	final public function __construct(array $options = null)
	 {
		if(self::$instance) throw new ESunderInitialized('Instance of '. __CLASS__ .' already exists!');
		$this->AddOptionsMeta(['values_class' => ['type' => 'string', 'value' => 'SunderValues'],
							   'debug' => ['type' => 'bool', 'value' => false],
							   'mark_layouts' => ['type' => 'bool', 'value' => false],
							   'on_invalid_fragment' => ['type' => 'callback,null'],
							   'on_remove_data_node' => ['type' => 'callback,null'],
							   'html_root' => ['type' => 'string,null'],
							   'l10n' => []]);
		$this->SetOptionsData($options);
		self::$instance = $this;
		$opt = $this->GetOption('values_class', $is_default);
		self::$values = new $opt();
		if(!$is_default && !is_subclass_of(self::$values, 'SunderValues', false)) throw new ESunderInvalidHandler('Sunder values must be a subclass of SunderValues, instance of '.get_class(self::$values).' given!');
		define('SUNDER_DEBUG', $this->GetOption('debug'));
		define('SUNDER_MARK_LAYOUTS', $this->GetOption('mark_layouts'));
		if(SUNDER_DEBUG || SUNDER_MARK_LAYOUTS) require_once(MSSE_SYS_INC_DIR.'/sunder.debug.php');
		self::$on_invalid_fragment = $this->GetOption('on_invalid_fragment');
		if($opt = $this->GetOption('on_remove_data_node')) self::$on_remove_data_node = $opt;
		if(null === ($opt = $this->GetOption('html_root'))) self::$html_root = "$_SERVER[DOCUMENT_ROOT]/html";
		elseif('' === $opt || '\\' === $opt[0]) self::$html_root = "$_SERVER[DOCUMENT_ROOT]$opt";
		else self::$html_root = $opt;
		$this->dom_doc = new domDocument('1.0', 'UTF-8');
		$this->dom_doc->recover = false;
		$this->dom_doc->strictErrorChecking = false;
		$this->dom_doc->validateOnParse = true;
		$this->dom_doc->preserveWhiteSpace = true;
		$this->dom_doc->formatOutput = false;
	 }

	/* final public static function Replace($source, $data_set, array $rule_set = null, array $parameters = null, DOMDocumentFragment &$fragment = null)
	 {
		if(null === self::$dom_doc_2)
		 {
			self::$dom_doc_2 = new domDocument('1.0', 'UTF-8');
			self::$dom_doc_2->recover = false;
			self::$dom_doc_2->strictErrorChecking = false;
			self::$dom_doc_2->validateOnParse = true;
			self::$dom_doc_2->preserveWhiteSpace = true;
			self::$dom_doc_2->formatOutput = false;
		 }
		if($source instanceof DOMDocumentFragment)
		 {
			$fragment = $source;
			if(self::$dom_doc_2_fragment !== $fragment) self::$dom_doc_2_fragment = $fragment;
		 }
		elseif(is_string($source))
		 {
			$fragment = self::$dom_doc_2->createDocumentFragment();
			self::FillFragment($fragment, $source, 1, '', ['class' => __CLASS__, 'type' => '::', 'function' => __FUNCTION__]);
			self::$dom_doc_2_fragment = $fragment;
		 }
		elseif(true === $source)
		 {
			if(self::$dom_doc_2_fragment) $fragment = self::$dom_doc_2_fragment;
			else throw new ESunderInvalidFragment(__CLASS__.'::Replace: HTML-fragment is invalid! Stored DOMDocumentFragment no longer exists.');
		 }
		elseif(false === $source) return self::$dom_doc_2_fragment = null;
		else throw new ESunderInvalidFragment(__CLASS__ .'::Replace: HTML-fragment is invalid! String, DOMDocumentFragment or Boolean expected; '.self::GetVarType($source).' given.');
		if(!$fragment->childNodes->length) return '';
		if($rule_set) foreach($rule_set as $key => &$rule) $rule = self::InitRule($rule, $key);
		if($parameters) self::PrepareParameters($parameters, $parameters);
		$replace_static_if = function() use(&$fragment, &$rule_set, &$parameters){
			n::WalkFragmentWith($fragment, 'if', function(DOMNode $node){
				if($name = $node->GetAttribute('static'))
				 {
					n::ReplaceIfElseNodes(self::GetRuleFromArray($name, $rule_set, $parameters), null, $node);
					return true;
				 }
			});
		};
		$counter = 0;
		$has_static_if = false;
		$replace = function(DOMDocumentFragment $fragment, stdClass $data) use(&$counter, &$replace_static_if, &$has_static_if, &$rule_set, &$parameters){
			$data->__number = $counter++;
			$fragment = $fragment->cloneNode(true);
			if(SUNDER_DEBUG)
			 {
				n::AddComment($fragment, " $counter - ".__CLASS__.'::Replace ', 'first', ['eol_before' => 0]);
				n::AddComment($fragment, " - [$counter] - ");
			 }
			n::CleanFragmentWith($fragment, 'if', function(DOMNode $node) use(&$has_static_if, &$rule_set, &$parameters, $data){
				if($name = $node->GetAttribute('name'))
				 {
					n::ReplaceIfElseNodes(self::GetRuleFromArray($name, $rule_set, $parameters), $data, $node);
				 }
				elseif($name = $node->GetAttribute('static'))
				 {
					n::ReplaceIfElseNodes(self::GetRuleFromArray($name, $rule_set, $parameters), null, $node);
					$has_static_if = true;
				 }
				else throw new ESunderMissingAttribute("Element `if` is missing required attribute `name`.");
			});
			foreach(n::GetTagsFromFragment($fragment, self::DATA_NODE_NAME) as $node) self::ReplaceDataNode($node, $data);
			if(true === $has_static_if)
			 {
				$replace_static_if();
				$has_static_if = false;
			 }
			return $fragment->ownerDocument->saveXML($fragment);
		};
		$ret_val = '';
		if($data_set instanceof Iterator) foreach($data_set as $data) $ret_val .= $replace($fragment, $data);
		elseif(is_callable($data_set)) while($data = call_user_func($data_set)) $ret_val .= $replace($fragment, $data);
		elseif(is_array($data_set)) foreach($data_set as $data) $ret_val .= $replace($fragment, $data);
		else throw new ESunderInvalidDataSet('Invalid data set: '.self::GetVarType($data_set).'.');
		return $ret_val;
	 } */

	final public static function RegisterFormat($name, Closure $callback)
	 {
		if(isset(self::$formats[$name])) throw new ESunderDuplicateFormat("Format `$name` is already registered!");
		self::$formats[$name] = $callback;
	 }

	final public static function RegisterFormats(array $formats) { foreach($formats as $name => $format) self::RegisterFormat($name, $format); }

	final public static function GetRuleFromArray($name, array &$rule_set = null, array &$parameters = null)
	 {
		if(isset($rule_set[$name])) return $rule_set[$name];
		elseif(':' === $name[0]) return $rule_set[$name] = self::GetGlobalRule($name, $parameters);
		else throw new ESunderMissingRule(__CLASS__."::Replace: rule `$name` is missing!");// on line # ???
	 }

	final public static function GetValue($name) { if(isset(self::$values->$name)) return self::$values->$name; }

	// нельзя возвращать self::$values! поля должны задаваться единственный раз, поэтому возвращается заместитель, закрывающий доступ к полям.
	final public static function GetValues()
	 {
		static $values_proxy = null;
		if(null === $values_proxy) $values_proxy = new SunderValuesProxy(self::$values);
		return $values_proxy;
	 }

	final public static function SetValue($name, $value)
	 {
		if(isset(self::$values->$name)) throw new ESunderValueIsSet("Value `$name` is set.");
		self::$values->$name = $value;
	 }

	final public static function SetIfValue($name, $value) { self::$if_values[$name] = $value; }

	final public static function GetIfValue($name) { return ($f = !empty(self::$if_values[$name])) && is_callable(self::$if_values[$name]) ? call_user_func(self::$if_values[$name]) : $f; }

	// SetValues принимают либо массив, либо Iterator
	final public static function SetValues($values)
	 {
		if(!is_array($values) && !is_object($values)) throw new ESunderInvalidDataSet('Argument 1 passed to '. __METHOD__ .' must be of the type array or an instance of Iterator, '.gettype($values).' given.');
		foreach($values as $name => $value) self::SetValue($name, $value);
	 }

	final public static function SetIfValues(array $values)
	 {
		foreach($values as $name => $value) self::SetIfValue($name, $value);
	 }

	final public static function SetBodyAttr($name, $value, $force = false)
	 {
		if(!$force && isset(self::$body_attrs[$name])) throw new ESunderValueIsSet("Attribute body[name='$name'] is set.");
		self::$body_attrs[$name] = $value;
	 }

	final public static function SetHTMLAttr($name, $value, $force = false)
	 {
		if(!$force && isset(self::$html_attrs[$name])) throw new ESunderValueIsSet("Attribute html[name='$name'] is set.");
		self::$html_attrs[$name] = $value;
	 }

	final public static function AddBeforeBuild($callback, ...$args)
	 {
		if(!is_callable($callback)) throw new ESunderInvalidHandler(__METHOD__ .'() expects parameter 1 to be a valid callback, '.self::GetVarType($callback).' given!');
		self::$before_build[] = [$callback, $args];
	 }

	final public static function AddAfterBuild($callback, ...$args)
	 {
		if(!is_callable($callback)) throw new ESunderInvalidHandler(__METHOD__ .'() expects parameter 1 to be a valid callback, '.self::GetVarType($callback).' given!');
		self::$after_build[] = [$callback, $args];
	 }

	final public static function Build()
	 {
		$inst = self::Instance();
		$code = self::$root[1] ? file_get_contents($inst->FullFName(self::$root[0])) : self::$root[0];
		libxml_use_internal_errors(true);
		@$inst->dom_doc->loadHTML('<?xml encoding="utf-8" ?>'.$code);// это хак, необходимый для устранения ошибки: иногда domDocument неправильно загружает документ, и символы (кодировка) распознаются неправильно.
		libxml_use_internal_errors(false);
		if(self::$before_build) foreach(self::$before_build as list($callback, $args)) call_user_func($callback, $inst->dom_doc, ...$args);
		// 1. Первыми обрабатываются "статические" теги <if> - это должно происходить ДО обработки размещений
		self::ReplaceIFValues($inst->dom_doc->documentElement);
		// 2. Находим теги <layout> и обрабатываем их (включая их вложенные теги layout).
		$nodes = $inst->dom_doc->GetElementsByTagName(self::LAYOUT_NODE_NAME);
		while($nodes->length) self::ReplaceLayout($nodes->item(0), null);
		// 3. Обрабатываем "корневые" теги <sdn>. Это должно быть после обработки размещений, чтобы заодно убрать теги <sdn>, пропущенные при обработке размещений.
		$nodes = $inst->dom_doc->GetElementsByTagName(self::DATA_NODE_NAME);
		while($nodes->length) self::ReplaceDataNode($nodes->item(0), self::$values, null, self::$on_remove_data_node);
		// 4. Модифицируем ссылки, ведущие на эту страницу или относящиеся к текущей группе.
		$href = urldecode($_SERVER['REQUEST_URI']);
		$nodes = $inst->dom_doc->GetElementsByTagName('hlink');
		for($i = $nodes->length - 1; $i >= 0; --$i)
		 {
			$node = $nodes->item($i);
			$hnds = $node->getElementsByTagName('node');
			$links = $hnds->item(0)->GetElementsByTagName('a');
			$link = $links->item(0);
			$k = false;
			if($link->HasAttribute('href') && $link->GetAttribute('href') === $href)
			 {
				if($hnds->length > 1) $k = 1;
				else n::Remove($node);
			 }
			else $k = (3 == $hnds->length && self::IsCurrentGroup($hnds->item(2))) ? 2 : 0;
			if(false !== $k) n::Replace($node, n::MoveChildNodes($hnds->item($k), $inst->dom_doc->createDocumentFragment()));
		 }
		foreach($inst->dom_doc->GetElementsByTagName('a') as $link)
		 if($link->HasAttribute('preserve-href')) $link->RemoveAttribute('preserve-href');
		 elseif($link->GetAttribute('href') === $href) $link->RemoveAttribute('href');
		// 5. Локализация, тэги <lang>.
		$nodes = $inst->dom_doc->GetElementsByTagName(self::LANG_NODE_NAME);
		if($l10n = $inst->GetOption('l10n'))
		 {
			if($nodes->length) while($nodes->length) $l10n->ReplaceNode($nodes->item(0));
			self::SetHTMLAttr('lang', $l10n->GetLang());
		 }
		elseif($nodes->length) throw new ESunderEmptyL10N('Language object is not initialized!');
		// 6. Установка атрибутов тегов <html> и <body>.
		$doc_el = $inst->dom_doc->documentElement;
		if(self::$body_attrs)
		 {
			$body = $doc_el->getElementsByTagName('body')->item(0);
			foreach(self::$body_attrs as $key => $value) $body->setAttribute($key, $value);
		 }
		if(self::$html_attrs) foreach(self::$html_attrs as $key => $value) $doc_el->setAttribute($key, $value);
		if(self::$after_build) foreach(self::$after_build as list($callback, $args)) call_user_func($callback, $inst->dom_doc, ...$args);
		if(SUNDER_DEBUG)
		 {
			if(\Sunder\Debug\Console::IsEnabled()) \Sunder\Debug\Console::ShowInfo($inst->dom_doc, self::$layouts);
			else ;
		 }
		return $inst->dom_doc->saveXML($inst->dom_doc->doctype).PHP_EOL.$inst->dom_doc->saveHTML($doc_el);
	 }

	final public static function GetGroupId()
	 {
		if(self::$group_id === null)
		 {
			$dir = dirname($_SERVER['PHP_SELF']);
			if($dir === '/' || $dir === '\\' || ($pos = strrpos($dir, '/')) === false) self::$group_id = 'home';
			else
			 {
				$folders = explode('/', $dir);
				if(is_numeric(end($folders))) self::$group_id = $folders[count($folders) - 2] ? $folders[count($folders) - 2] : 'home';
				else self::$group_id = end($folders);
			 }
		 }
		return self::$group_id;
	 }

	final public static function SetGroupId($value) { self::$group_id = $value; }
	final public static function SetRoot($val, $is_file = true) { self::$root = [$val, $is_file]; }

	final private static function IsCurrentGroup($node)
	 {
		$gid = $node->GetAttribute('gid');
		return false === strpos($gid, ' ') ? self::GetGroupId() == $gid : in_array(self::GetGroupId(), explode(' ', $gid));
	 }

	final private static function Instance()
	 {
		if(self::$instance) return self::$instance;
		throw new ESunderNotInitialized(__CLASS__ .' is not initialized! Call constructor explicitly.');
	 }

	private static $instance;
	private static $values;
	private static $root = ['/html', true];
	private static $body_attrs = [];
	private static $html_attrs = [];
	private static $if_values = [];
	private static $before_build = [];
	private static $after_build = [];
	private static $group_id = null;
	private static $dom_doc_2 = null;
	private static $dom_doc_2_fragment = null;

	private $dom_doc;
}

class SunderValues extends stdClass
{
	final public function __get($name)
	 {
		if(isset($this->__callable_values[$name])) return call_user_func($this->__callable_values[$name]);
		if(isset($this->__reserved[$name])) return $this->__reserved[$name];
	 }

	final public function __set($name, $value)
	 {
		if(!is_scalar($value) && is_callable($value)) $this->__callable_values[$name] = $value;
		elseif('__callable_values' === $name || '__reserved' === $name) $this->__reserved[$name] = $value;
		else $this->$name = $value;
	 }

	final public function __isset($name) { return isset($this->__callable_values[$name]) || isset($this->__reserved[$name]); }

	final public function IsCallable($name, &$callback = null)
	 {
		if(isset($this->__callable_values[$name]))
		 {
			$callback = $this->__callable_values[$name];
			return true;
		 }
		else
		 {
			$callback = null;
			return false;
		 }
	 }

	final protected function Set($k, $v) { return ($this->__reserved[$k] = $v); }
	final protected function Get($k) { return $this->__reserved[$k]; }

	final protected function ValueExists($k, &$v = null)
	 {
		if(isset($this->__reserved[$k]))
		 {
			$v = $this->__reserved[$k];
			return true;
		 }
		else
		 {
			$v = null;
			return false;
		 }
	 }

	private $__callable_values = [];
	private $__reserved = [];
}

class SunderValuesProxy
{
	final public function __construct(stdClass $values)
	 {
		$this->values = $values;
	 }

	final public function __call($name, array $arguments)
	 {
		if(isset(self::$forbidden[$name])) throw new Exception('Call to undefined method '. __CLASS__ ."::$name()!");
		return $this->values->$name(...$arguments);
	 }

	private $values;

	private static $forbidden = ['__get' => true, '__set' => true];
}

class MSLayoutInfo extends stdClass
{
	final public function __construct($name, $class, $fragment_type, $fragment_file, $fragment_num_static, $fragment_is_empty, $data_set)
	 {
		$this->data['name'] = $name;
		$this->data['class'] = $class;
		$this->data['fragment_type'] = $fragment_type;
		$this->data['fragment_file'] = $fragment_file;
		$this->data['fragment_num_static'] = $fragment_num_static;
		$this->data['fragment_is_empty'] = $fragment_is_empty;
		$this->data['data_set_type'] = self::GetDataSetType($data_set);
	 }

	final public function __get($name)
	 {
		if(array_key_exists($name, $this->data)) return $this->data[$name];
		else throw new \Exception('Undefined property: '. __CLASS__ .'::$'.$name);
	 }

	final public function __toString()
	 {
		$i_f = $this->fragment_type;
		if('file' === $this->fragment_type) $i_f .= " '$this->fragment_file'";
		if($this->fragment_is_empty) $i_f .= " - empty";
		if($this->fragment_num_static > 0) $i_f .= ", has static rules ($this->fragment_num_static)";
		return "$this->name - $this->class. Fragment: $i_f. Data set: $this->data_set_type.";
	 }

	final public static function GetDataSetType($data_set)
	 {
		if($data_set)
		 {
			if($data_set instanceof Iterator) $ds = 'Iterator ('.get_class($data_set).')';
			elseif(is_object($data_set) && !method_exists($data_set, '__invoke')) $ds = 'Object ('.get_class($data_set).')';
			elseif(is_callable($data_set, false, $ds));
			elseif(is_array($data_set)) $ds = 'array['.count($data_set).']';
			else $ds = 'undefined';
		 }
		else $ds = 'none';
		return $ds;
	 }

	final public function __debugInfo() { return $this->data; }

	private $data = [];
}
?>
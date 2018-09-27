<?php
require_once(MSSE_LIB_DIR.'/events.php');
require_once(MSSE_LIB_DIR.'/traits.php');
require_once(MSSE_LIB_DIR.'/iengine.php');

class EEngine extends Exception {}
	class EEngineInitialized extends EEngine {}
	class EEngineInvalidHandler extends EEngine {}
	class EEngineDuplicateHandler extends EEngine {}
	class EEngineDuplicateMetaTag extends EEngine {}
	class EEnginePageFilterIsSet extends EEngine {}
	class EEngineInvalidTableAlias extends EEngine {}
	class EEnginePageRequired extends EEngine {}

abstract class Engine implements IEngine
{
	use TEngine;

	const TITLE_SEP = ' ‹ ';

	public function __construct(array $options = null)
	 {
		if(self::$instance) throw new EEngineInitialized('Instance of engine already exists!');
		$this->Init($options);
		$this->AddMetaTag(new EngineMetaTag('name', 'description', 'meta_description'));
		$this->AddMetaTag(new EngineMetaTag('name', 'keywords', 'meta_keywords'));
		$this->AddMetaTag(new EngineMetaTag('name', 'robots', 'meta_robots'));
		$this->AddMetaTag(new EngineMetaTag('name', 'twitter:card', 'smo_twitter_card', ['default' => true]));
		$this->AddMetaTag(new EngineMetaTag('name', 'twitter:site', 'smo_twitter_site', ['default' => true]));
		$this->AddMetaTag(new EngineMetaTag('property', 'og:image', 'og_image', ['callback' => function($v, stdClass $page, $is_default, $add_item){
			$dir = \IATL::DIR_SMO;
			$id = "_$page->id";
			if($is_default)
			 {
				if($page->ext)
				 {
					$v = $page->ext;
					$dir = \IATL::DIR_FEATURED;
					$get_value = function($n) use($page){return $page->$n;};
				 }
				else
				 {
					$id = '';
					$get_value = function($n){return Registry::GetValue('site', "og_image_$n");};
				 }
			 }
			else $get_value = function($n) use($page){return $page->{"og_image_$n"};};
			foreach(['width', 'height'] as $n) $add_item('property', "og:image:$n", $get_value($n));
			return Page::GetStaticHost(true)."$dir/image$id.$v";
		}, 'default' => true]));
		$this->AddMetaTag(new EngineMetaTag('property', 'og:type', 'og_type'));
		$this->AddMetaTag(new EngineMetaTag('property', 'og:site_name', 'og_site_name', ['default' => true]));
		$this->AddMetaTag(new EngineMetaTag('property', 'og:title', 'og_title', ['default' => true]));
		$this->AddMetaTag(new EngineMetaTag('property', 'og:description', 'og_description', ['default' => true]));
		// $this->AddMetaTag('og_see_also' => ['property', 'og:see_also']
		self::$instance = $this;
		MSConfig::AddAutoload(function($lower_class_name){
			if(file_exists($fname = "$_SERVER[DOCUMENT_ROOT]/include/h_paths/$lower_class_name.php"))
			 {
				require_once($fname);
				return true;
			 }
		});
		$this->methods = new stdClass();
		$this->methods->has_parent = function($id){
			if(null === ($tmp = $this->GetParents())) $this->methods->has_parent = function(){};
			else
			 {
				$items = [];
				foreach($tmp as $p) $items[$p->id] = $p->id;
				$this->methods->has_parent = function($id) use($items){ return isset($items[$id]); };
				return $this->methods->has_parent->__invoke($id);
			 }
		};
	 }

	final public static function Instance() { return self::$instance; }

	final public static function Type2String($item, &$type = null)
	 {
		$type = gettype($item);
		return is_object($item) ? 'instance of '.get_class($item) : $type.' '.var_export($item, true);
	 }

	final public function GetTitle() { return '' === $this->title ? ($this->page ? $this->page->title : null) : $this->title; }
	final public function GetPage() { return $this->page; }
	final public function GetTypeHandler() { return $this->htype; }
	final public function GetBreadCrumbs() { return $this->breadcrumbs ?: ($this->page ? $this->GetParents() : []); }
	final public function IsErrorPage() { return $this->http_status; }

	final public function IsHomePage(stdClass $page = null)
	 {
		if(null === $page)
		 {
			if(false === $this->page) return false;
			if(null === $this->page) throw new EEnginePageRequired('Can not invoke '. __METHOD__ . ' without a page retrieved!');
			$page = $this->page;
		 }
		return '' === $page->sid;
	 }

	final public function AddPathHandler($path, $handler)
	 {
		if(isset($this->path_handlers[$path])) throw new EEngineDuplicateHandler("Handler for the path '$path' already exists ({$this->Type2String($this->path_handlers[$path])})!");
		$this->path_handlers[$path] = $handler;
		return $this;
	 }

	final public function AddMetaTag(EngineMetaTag $tag)
	 {
		$i = $tag->GetAttr().' '.$tag->GetValue();
		if(isset($this->meta_tags[$i])) throw new EEngineDuplicateMetaTag("meta[{$tag->GetAttr()}='{$tag->GetValue()}'] already exists!");
		$this->meta_tags[$i] = $tag;
		return $this;
	 }

	final public function GetHomePage()
	 {
		if(null === $this->home_page) $this->home_page = $this->GetPageByKey('');
		return $this->home_page;
	 }

	final public function GetDocumentTitle()
	 {
		return $this->document_title ?: ($this->page ? ($this->page->document_title ?: $this->MakeDocumentTitle($this->GetParents(), $this->page->title)) : '');
	 }

	final public function SetPageFilter($callback, $method = null)
	 {
		if($this->page_filter) throw new EEnginePageFilterIsSet('Can not change page filter that was set before!');
		$this->page_filter = $method ? [$callback, $method] : $callback;
		return $this;
	 }

	final public function Run()
	 {
		$this->DispatchEvent('before_run', false, ['engine' => $this]);
		$page = null;
		$status = 404;
		if(isset($_GET['404_not_found']));
		elseif(isset($_GET['403_forbidden'])) $status = 403;
		elseif(isset($_GET['__page_id']) && null !== ($id = call_user_func($this->GetURLFilter(), $_GET['__page_id'])))
		 {
			$get_subpath = function($path, $id, &$eq = null){return ($eq = $path === $id) ? null : substr_replace($id, '', 0, strlen($path) + 1);};
			$hdlr = null;
			$path = $id;
			do
			 {
				if(isset($this->path_handlers[$path]))
				 {
					if(is_callable($this->path_handlers[$path])) $hdlr = $this->path_handlers[$path]();
					elseif(is_string($this->path_handlers[$path])) $hdlr = new $this->path_handlers[$path]();
					else $hdlr = $this->path_handlers[$path];
					$status = $this->CheckPathHandler($hdlr, $path)->CreatePage($path, $get_subpath($path, $id));
				 }
				elseif($page = $this->GetPageByKey($path))
				 {
					$subpath = $get_subpath($path, $id, $eq);
					if($eq || $page->type__subpath_allowed) $status = $this->CreatePage($page, $subpath);
					$hdlr = false;
				 }
				elseif(false === ($pos = strrpos($path, '/'))) $hdlr = false;
				else $path = substr($path, 0, $pos);
			 }
			while(null === $hdlr);
		 }
		elseif($page = $this->GetHomePage()) $status = $this->CreatePage($page);
		$page_meta = ['title' => [$this, 'GetTitle'], 'document_title' => [$this, 'GetDocumentTitle']];
		if($this->GetOption('enable_page_tags'))
		 {
			$page_meta['page_meta__head'] = ['Page', 'GetHeadTags'];
			$page_meta['page_meta__bottom'] = ['Page', 'GetBottomTags'];
		 }
		if(404 === $status) $this->Show404($page ?: null);
		elseif(403 === $status) $this->Show403($page ?: null);
		elseif($page = $this->GetPage())
		 {
			$values = clone $page;
			foreach($page_meta as $key => $dummy) unset($values->$key);
			Sunder::SetValues($values);
		 }
		if($cr = $this->GetBreadCrumbs())
		 {
			$edt = $this->DispatchEvent('breadcrumbs:on_show', false, ['engine' => $this, 'items' => $cr, 'enabled' => true], ['items' => ['set' => true], 'enabled' => ['set' => true]]);
			if($edt->enabled)
			 {
				Sunder::SetIfValue('has_breadcrumbs', true);
				new Layout('breadcrumbs', $edt->items, null);
			 }
		 }
		Sunder::SetValues($page_meta);
		$this->DispatchEvent('after_run', false, ['engine' => $this]);
	 }

	final public function GetParents()
	 {
		static $chain = false;
		if(false === $chain)
		 {
			if(null === $this->page) throw new EEnginePageRequired('Can not invoke '. __METHOD__ . ' without a page retrieved!');
			if(false === $this->page) $chain = null;
			else
			 {
				$chain = [];
				$id = $this->page->parent_id;
				while($id && ($page = DB::GetRowById($this->GetTName(), $id)))// нужны ли здесь все поля? или хватит только некоторых?
				 {
					$this->AddHref($page);
					array_unshift($chain, $page);
					$id = $page->parent_id;
				 }
			 }
		 }
		return $chain;
	 }

	final public function HasParent($id) { return $this->methods->has_parent->__invoke($id); }

	abstract protected function HandleDefault(stdClass $page, DataContainer $data);

	protected function GetSourceTables(&$callback, array &$options = null) {}
	protected function GetHandlerData($values = null) { return new DataContainer([]); }

	final protected function GetPageByKey($value, $is_path = true)
	 {
		static $source_tables = null, $callback = false, $options = null;
		if(null === $source_tables)
		 {
			$source_tables = ['page' => [$this->GetTName(), '*'], 'type' => [$this->GetTName('_type'), 'subpath_allowed', '`page`.`type` = `type`.`name`']];
			if($tbls = $this->GetSourceTables($callback, $options))
			 foreach($tbls as $alias => $tbl)
			  if(isset($source_tables[$alias])) throw new EEngineInvalidTableAlias();
			  else $source_tables[$alias] = $tbl;
		 }
		$page = DB::GetRowByKeyLJ($source_tables, $is_path ? 'sid' : 'id', $value, false, $options);
		if($callback && $page) call_user_func($callback, $page);
		return $page;
	 }

	final protected static function SetPageDataAttributes($type, $id, $force = false)
	 {
		Sunder::SetBodyAttr('data-type', $type, $force);
		if($id) Sunder::SetBodyAttr('data-id', $id, $force);
	 }

	final protected function Show403(stdClass $page = null)
	 {
		$this->ShowHTTPErrorPage(403, 'forbidden', 'Отказано в доступе', $page);
		Header('HTTP/1.1: 403 Forbidden');
	 }

	final protected function Show404(stdClass $page = null)
	 {
		$this->ShowHTTPErrorPage(404, 'not-found', 'Страница не найдена', $page);
		Header('HTTP/1.1: 404 Not Found');
	 }

	final private function ShowHTTPErrorPage($code, $attr_status, $title, stdClass $page = null)
	 {
		$this->page = false;
		ms::LogHTTPError($code);
		$data = $this->DispatchEvent("$code", false, ['tpl' => true, 'document_title' => $title, 'title' => $title, 'page' => $page, 'engine' => $this], ['tpl' => ['set' => true], 'document_title' => ['set' => true], 'title' => ['set' => true]]);
		if($data->tpl) new SLayout('work_area', null, true === $data->tpl ? "/$code" : $data->tpl);
		$this->document_title = "$data->document_title";
		$this->title = "$data->title";
		self::SetPageDataAttributes("$code-$attr_status", null, true);
		Page::AddMetaTag('name', 'robots', 'noindex,follow');
		$this->http_status = $code;
	 }

	final private function MakeDocumentTitle(array $parents, $current)
	 {
		$s = '';
		foreach($parents as $p) if('' !== $p->title) $s = static::TITLE_SEP.$p->title.$s;
		return $current.$s;
	 }

	final private function IsLink($s, $ext) { return ($s[0] === '/' && substr($s, -4) === ".$ext") || strpos($s, 'http://') === 0 || strpos($s, '//') === 0 || strpos($s, 'https://') === 0; }

	final private function CreatePage(stdClass $page, $subpath = null)
	 {
		if($page->hidden) return 404;
		$this->htype = $page->type ? $this->CheckTypeHandler($page->type) : false;
		$status = null;
		$is_homepage = '' === $page->sid;
		$event_data = new EventData(['page' => $page, 'subpath' => $subpath, 'engine' => $this, 'is_homepage' => $is_homepage, 'handler' => $this->GetHandlerData()], ['handler' => ['set' => true]]);
		if($this->htype) $status = $this->htype->BeforeCreate($event_data);
		if(null === $status && $this->page_filter) $status = call_user_func($this->page_filter, $page, 'page');
		if(403 === $status || false === $status) return 403;
		elseif(404 === $status) return 404;
		$this->page = &$page;
		$this->DispatchEventData('page:before_create', false, $event_data);
		if($is_homepage) $this->DispatchEventData('homepage:before_create', false, $event_data);
		self::SetPageDataAttributes(($is_homepage ? 'home ' : '').'page'.($page->type ? "-$page->type" : ''), $page->id);
		if(false !== $event_data->handler && null !== $event_data->handler) $this->HandleDefault($page, $event_data->handler);
		Page::SetCanonical($this->GetHref($page));
		foreach($this->meta_tags as $m) foreach($m->GetContent($page) as list($attr, $value, $content)) Page::AddMetaTag($attr, $value, $content);
		$event_data = new EventData(['page' => clone $page, 'subpath' => $subpath, 'engine' => $this, 'is_homepage' => $is_homepage]);
		if($this->htype) $this->htype->AfterCreate($event_data);
		$this->DispatchEventData('page:after_create', false, $event_data);
		if($is_homepage)
		 {
			$this->DispatchEventData('homepage:after_create', false, $event_data);
			Sunder::SetIfValue('is_homepage', true);
		 }
	 }

	final private function CheckTypeHandler($type)
	 {
		$handler = (require "$_SERVER[DOCUMENT_ROOT]/include/h_types/$type.php");
		if(($is_object = is_object($handler)) && ($handler instanceof IPageType)) return $handler;
		throw new EEngineInvalidHandler("Handler '$type' must implement interface IPageType, {$this->Type2String($handler)} given.");
	 }

	final private function CheckPathHandler($handler, $path)
	 {
		if(($is_object = is_object($handler)) && ($handler instanceof IPathHandler)) return $handler;
		throw new EEngineInvalidHandler("Handler '$path' must implement interface IPathHandler, {$this->Type2String($handler)} given.");
	 }

	private $page = null;
	private $htype = null;
	private $title = '';
	private $document_title;
	private $home_page = null;
	private $breadcrumbs = [];
	private $page_filter;
	private $meta_tags = [];
	private $path_handlers = [];
	private $http_status;
	private $methods;

	private static $instance = null;
}

class EngineMetaTag implements IEngineMetaTag
{
	use TOptions;

	public function __construct($attr, $value, $index, array $options = null)
	 {
		$this->attr = $attr;
		$this->value = $value;
		$this->index = $index;
		$this->AddOptionsMeta(['default' => ['type' => 'bool', 'value' => false], 'callback' => ['value' => false], 'empty' => ['value' => false]]);
		$this->SetOptionsData($options);
	 }

	public function GetContent(stdClass $page)
	 {
		$r = [];
		$add_item = function($attr, $value, $content) use(&$r){ $r[] = [$attr, $value, $content]; };
		if($this->NotEmpty($page->{$this->index}))
		 {
			$v = $page->{$this->index};
			$is_default = false;
		 }
		elseif($this->GetOption('default') && $this->NotEmpty($v = Registry::GetValue('site', $this->index))) $is_default = true;
		else
		 {
			if(($c = $this->GetOption('empty')) && $this->NotEmpty($v = call_user_func($c, $page, $add_item))) $r[] = $this->MakeItem($v);
			return $r;
		 }
		if($c = $this->GetOption('callback')) $v = call_user_func($c, $v, $page, $is_default, $add_item);
		$item = $this->MakeItem($v);
		if($r) array_unshift($r, $item);
		else $r[] = $item;
		return $r;
	 }

	final public function GetAttr() { return $this->attr; }
	final public function GetValue() { return $this->value; }
	final public function GetIndex() { return $this->index; }

	final protected static function NotEmpty($v) { return '' !== "$v"; }

	final protected function MakeItem($content, $attr = null, $value = null) { return [$attr ?: $this->attr, $value ?: $this->value, $content]; }

	private $attr;
	private $value;
	private $index;
}
?>
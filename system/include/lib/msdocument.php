<?php
class EDocument extends Exception {}
	abstract class EDocumentShow extends EDocument {}
		class EDocument400 extends EDocumentShow
		 {
			public function __construct($message = '', $code = 400, Exception $previous = null)
			 {
				parent::__construct($message ?: 'Неправильный запрос!', $code, $previous);
			 }
		 }
		class EDocument403 extends EDocumentShow
		 {
			public function __construct($message = '', $code = 403, Exception $previous = null)
			 {
				parent::__construct($message ?: 'Доступ запрещён!', $code, $previous);
			 }
		 }
		class EDocument404 extends EDocumentShow
		 {
			public function __construct($message = '', $code = 404, Exception $previous = null)
			 {
				parent::__construct($message ?: 'Страница не найдена!', $code, $previous);
			 }
		 }
	class EDocumentHandle extends EDocument {}

class EMainMenu extends Exception {}
	class EMainMenuDuplicateItemId extends EMainMenu {}
	class EMainMenuDefaultOptionIsSet extends EMainMenu {}

class EMSLoader extends Exception {}
	class EMSLoaderDuplicateFactoryId extends EMSLoader {}
	class EMSLoaderUndefinedFactoryId extends EMSLoader {}

class a
{
	final public function __construct($text, $href)
	 {
		$this->text = $text;
		$this->href = $href;
	 }

	final public static function MapArray($item) { return $item instanceof A ? $item->GetText() : $item; }

	final public function __toString() { return "<a href='{$this->href}'>{$this->text}</a>"; }
	final public function GetText() { return $this->text; }

	protected $text;
	protected $href;
}

function a($text, $href) { return new a($text, $href); }

interface IMSFactory
{
	public function CreateDocument($id);
	public function CreateMenuGroup($id);
}

interface IMSNav
{
	function GetCaption();
	function GetTitle();
	function GetItem();
}

class OutputBuffer
{
	final public function __construct()
	 {
		ob_start();
	 }

	final public function GetContents()
	 {
		$ret_val = ob_get_clean();
		$this->cleaned = true;
		return $ret_val;
	 }

	final public function __destruct()
	 {
		if(!$this->cleaned) ob_end_clean();
	 }

	private $cleaned = false;
}

MSConfig::RequireFile('tsystemmessages');

abstract class MSDocument
{
	use TSystemMessages;

	abstract public function Show();
	abstract public function Handle();

	public function __debugInfo() { return []; }

	final public static function IsJSLinkRegistered($id) { return ResourceManager::IsJSLinkRegistered($id); }
	final public static function AddCSSString($str) { self::$css_code .= $str; }
	final public static function GetLang() { return self::$lang = 'ru'; /*$_SERVER['HTTP_ACCEPT_LANGUAGE'];*/}
	final public static function DisableLogging() { self::$disable_logging = true; }
	final public static function SetProductInfo($val) { self::$product_info = $val; }
	final public static function GetURL($url = true, $root = true) { return ($root ? MSConfig::GetMSSMDir() : '').'/'.(true === $url ? MSLoader::GetId().'/' : $url); }

	final public function GetNav() { return $this->nav; }

	final public function GetContent()
	 {
		MainMenu::Init();
		self::ResetTitle();
		$ob = new OutputBuffer();
		$this->curr_action = 'show';
		$this->t = microtime(true);
		$this->Log();
		try
		 {
			$this->Show();
			$content = $ob->GetContents();
		 }
		catch(EDocumentShow $e)
		 {
			HTTP::Status($e->getCode(), false);
			$content = ui::ErrorMsg($e->getMessage() ?: 'Ошибка!')."<code>#{$e->getCode()} \ ".get_class($e).'</code>';
			MSConfig::LogException($e);
		 }
		$ob = null;
		$ob = new OutputBuffer();
		ResourceManager::AddJSLink('jquery');
		ResourceManager::AddJS('lib.mssm');
		ResourceManager::AddCSS('lib.mssm');
		if(!self::Discarded()) ResourceManager::AddCSS('lib.mssm_ui');
		$this->GetContentImp($content);
		return $ob->GetContents();
	 }

	final public function WHandle()
	 {
		$this->curr_action = 'handle';
		$this->Log();
		try
		 {
			$this->t = microtime(true);
			$this->Handle();
			$status = true;
		 }
		catch(Exception $e)
		 {
			if(!($e instanceof EDocumentHandle)) MSConfig::HandleException($e, false);
			$this->AddErrorMsg($e->GetMessage());
			$status = false;
		 }
		if($t = $this->IsAsync())
		 {
			if('xml' === $t) self::SendXML(null, '', $status);
			elseif('json' === $t) self::SendJSON(null, '', $status);
			else exit();
		 }
		ms::Redirect(empty($_POST['__redirect']) ? (empty($_GET['__mssm_id']) ? (($d = dirname($_SERVER['PHP_SELF'])) === '\\' ? '/' : $d) : MSConfig::GetMSSMDir().'/'.$_GET['__mssm_id'].'/') : MSConfig::GetMSSMDir().$_POST['__redirect']);
	 }

	final public function SetNav(IMSNav $nav)
	 {
		$this->nav = $nav;
		return $this;
	 }

	final protected static function SetCaption(...$args) { self::$caption = $args; }
	final protected static function ResetCaption() { if($title = MainMenu::GetTitle()) self::SetCaption(...$title); }
	final protected static function GetCaption() { return self::$caption; }
	final protected static function SetDocTitle(...$args) { self::$title = $args; }
	final protected static function InsertDocTitleItem($item, $index) { array_splice(self::$title, $index, 0, $item); }
	final protected static function ResetDocTitle() { if($title = MainMenu::GetTitle()) self::SetDocTitle(...$title); }
	final protected static function InsertCaptionItem($item, $index) { array_splice(self::$caption, $index, 0, $item); }
	final protected static function GetDocTitle() { return self::$title; }
	final protected static function GetProductInfo() { return self::$product_info; }
	final protected static function SetViewport($val) { self::$viewport = $val; }
	final protected static function GetViewport() { return self::$viewport; }
	final protected static function Discard($back = false, $title = 'Закрыть') { self::$nowr_back = ['href' => $back ? MSConfig::GetMSSMDir().$back : MSLoader::GetURL(), 'title' => $title]; }
	final protected static function Discarded() { return !empty(self::$nowr_back); }
	final protected static function PushCaptionItem(...$args) { foreach($args as $arg) self::$caption[] = $arg; }
	final protected static function PushDocTitleItem(...$args) { foreach($args as $arg) self::$title[] = $arg; }

	final protected static function Redirect($href, $base = 'root')
	 {
		if('root' === $base) $base = MSConfig::GetMSSMDir();
		elseif('this' === $base) $base = MSLoader::GetURL();
		ms::Redirect($base.$href);
	 }

	final protected static function RemoveTitleItem($index = 0)
	 {
		if(empty($index))
		 {
			array_shift(self::$title);
			array_shift(self::$caption);
		 }
		elseif($index > 0);
		else ;//$index < 0
	 }

	final protected static function ActionPOST()
	 {
		if(isset($_POST['__mssm_action'])) return $_POST['__mssm_action'];
	 }

	final protected static function ActionGET()
	 {
		if(isset($_GET['__mssm_action'])) return $_GET['__mssm_action'];
	 }

	final protected static function SetCaptionHref($href, $base = 'root')
	 {
		if(self::$caption)
		 {
			$item = end(self::$caption);
			if('root' === $base) $base = MSConfig::GetMSSMDir();
			elseif('this' === $base) $base = MSLoader::GetURL();
			$url = $base.$href;
			self::$caption[key(self::$caption)] = new a($item instanceof a ? $item->GetText() : $item, $url);
			return $url;
		 }
	 }

	final protected static function GetCharset()
	 {
		static $charsets = array('ru' => 'UTF-8');
		return isset($charsets[self::GetLang()]) ? $charsets[self::GetLang()] : 'UTF-8';
	 }

	final protected static function SetTitle(...$args)
	 {
		self::SetDocTitle(...$args);
		self::SetCaption(...$args);
	 }

	final protected static function PushTitleItem(...$args)
	 {
		foreach($args as $arg)
		 {
			self::$title[] = $arg;
			self::$caption[] = $arg;
		 }
	 }

	final protected static function ResetTitle()
	 {
		self::ResetDocTitle();
		self::ResetCaption();
	 }

	final protected static function InsertTitleItem($item, $index)
	 {
		self::InsertDocTitleItem($item, $index);
		self::InsertCaptionItem($item, $index);
	 }

	final protected function GetExecTime() { return microtime(true) - $this->t; }
	final protected function IsAsync() { return empty($_REQUEST['__disable_redirect']) ? false : $_REQUEST['__disable_redirect']; }

	final protected function AddJSLink($id)
	 {
		ResourceManager::AddJSLink($id);
		return $this;
	 }

	final protected function RequireScript($id)
	 {
		ResourceManager::RequireScript($id);
		return $this;
	 }

	final protected function AddCSS(...$fnames)
	 {
		ResourceManager::AddCSS(...$fnames);
		return $this;
	 }

	final protected function AddJS(...$fnames)
	 {
		ResourceManager::AddJS(...$fnames);
		return $this;
	 }

	final protected function Log()
	 {
		if(self::$disable_logging) return;
		DB::Insert(MSSMAI()->GetPrefix().'_visit', $this->GetLogData());
	 }

	final protected function Profile($msg = null)
	 {
		$data = $this->GetLogData();
		$data['message'] = $msg;
		$data['time'] = $this->GetExecTime();
		$data['memory'] = memory_get_peak_usage(true);
		DB::Insert('sys_profile', $data);
		return $data;
	 }

	final private function RequireFiles($type)
	 {
		$files = array('lib' => [], 'other' => []);
		$m = new ReflectionMethod('ResourceManager', "Fetch$type");
		while($name = $m->invoke(null))
		 {
			if('lib.' === substr($name, 0, 4))
			 {
				$name = substr($name, 4);
				$files['lib'][] = MSConfig::GetLibDir()."/$type/$name.$type";
			 }
			else $files['other'][] = $_SERVER['DOCUMENT_ROOT'].MSConfig::GetMSSMDir()."/include/$type/$name.$type";
		 }
		foreach($files as $groups) foreach($groups as $file) include_once($file);
	 }

	final private function GetLogData()
	 {
		$data = ['date_time' => DB::Now(), 'section_id' => MSLoader::GetURL(false), 'class' => get_class($this), 'action' => $this->curr_action, 'request_uri' => $_SERVER['REQUEST_URI'], 'request_method' => $_SERVER['REQUEST_METHOD'], 'remote_addr' => MSConfig::GetIP()];
		if('GET' === $data['request_method']) $data['document_action'] = $this->ActionGET();
		elseif('POST' === $data['request_method']) $data['document_action'] = $this->ActionPOST();
		$a = MSSMAI();
		if($suid = $a->GetSUID())
		 {
			$data['suid'] = $suid;
			$data['uid'] = $a->GetUID();
			$data['session_id'] = $a->GetSID();
		 }
		return $data;
	 }

	final private function GetContentImp($content) { require_once(MSConfig::GetLibDir().'/mssm.php'); }

	final private static function GetJSLinksAsHTML()
	 {
		$ret_val = '';
		while($link = ResourceManager::FetchJSLink()) $ret_val .= '<script type="text/javascript" src="'.$link.'"></script>';
		return $ret_val;
	 }

	final private static function GetCSSLinksAsHTML()
	 {
		$ret_val = '';
		while($link = ResourceManager::FetchCSSLink()) $ret_val .= '<link rel="stylesheet" href="'.$link.'" type="text/css" />';
		return $ret_val;
	 }

	final private static function GetJSCode() { return self::$js_code; }

	private static $lang;
	private static $js_code = '';
	private static $css_code = '';
	private static $caption = [];
	private static $title = [];
	private static $initialized = false;
	private static $disable_logging = false;
	private static $product_info;
	private static $viewport = 'width=device-width, initial-scale=1';
	private static $nowr_back;
	private $nav;
	private $t = 0;
	private $curr_action;

	const TITLE_DIVIDER = ' &larr; ';
	const CAPTION_DIVIDER = ' &rarr; ';
}

abstract class MainMenu
{
	final public static function Init()
	 {
		static $initialized = false;
		if($initialized) throw new Exception('MainMenu is initialized');
		while($factory = MSLoader::NextFactory()) $factory->CreateMenuGroup(MSLoader::GetId());
		$initialized = true;
	 }

	final public static function Make()
	 {
		$ret_val = '';
		foreach(self::$items as $item) if(!$item->GetParentId()) $ret_val .= $item;
		return $ret_val;
	 }

	final public static function SetDefaults(array $options)
	 {
		static $allowed = ['caption' => 'caption', 'hide' => 'hide'];// filter, type, title, url, collapse
		foreach($options as $name => $value)
		 {
			if(!isset($allowed[$name])) throw new Exception("Can not set option `$name`! Allowed options are: ".implode(', ', $allowed).'.');
			if(isset(self::$default_options[$name])) throw new EMainMenuDefaultOptionIsSet("Default option `$name` was set before!");
			self::$default_options[$name] = $value;
		 }
	 }

	final public static function DefaultExists($name, &$value = null)
	 {
		if(isset(self::$default_options[$name]))
		 {
			$value = self::$default_options[$name];
			return true;
		 }
		else
		 {
			$value = null;
			return false;
		 }
	 }

	final public static function AddItemsFromButtons(stdClass $data, array $btns, $parent_id)
	 {
		foreach($btns as $name => $b)
		 if($d = call_user_func($b, $data))
		  {
			$o = ['url' => $d['href']];
			if(isset($d['menu_type'])) $o['type'] = $d['menu_type'];
			self::AddItem($name, $d['title'], $parent_id, $o);
		  }
	 }

	final public static function AddItem($id, $caption, $parent_id = false, array $options = null)
	 {
		if(isset($options['filter']) && !call_user_func($options['filter'], $id, 'menu_item')) return;
		if(null === $caption && isset(self::$default_options['caption'])) $caption = self::$default_options['caption'];
		if(!is_string($caption) && is_callable($caption)) $caption = call_user_func($caption, $id, $parent_id, $options);
		return self::AttachItem(new MenuItem($id, $caption, $parent_id, $options));
	 }

	final public static function AddExternal($id, $caption, $url, $parent_id = false, array $options = null)
	 {
		$o = ['hide' => false, 'url' => $url];
		if(isset($options['type'])) $o['type'] = $options['type'];
		if(isset($options['title'])) $o['title'] = $options['title'];
		self::AttachItem(new MenuExternalItem($id, $caption, $parent_id, $o));
	 }

	final public static function GetItem($id) { return self::$items[$id]; }
	final public static function ItemExists($id) { return isset(self::$items[$id]); }

	final public static function GetTitle()
	 {
		if(empty(self::$items[MSLoader::GetId()])) return;
		$item = self::$items[MSLoader::GetId()];
		$ret_val = array(0 => $item->GetCaption(true));
		while($parent_id = $item->GetParentId())
		 {
			$item = self::$items[$parent_id];
			array_unshift($ret_val, $item->GetCaption(true));
		 }
		return array_filter($ret_val);
	 }

	final private static function AttachItem(AMenuItem $item)
	 {
		if(self::ItemExists($item->GetId())) throw new EMainMenuDuplicateItemId("Menu item with id '{$item->GetId()}' already exists.");
		self::$items[$item->GetId()] = $item;
		if($item->GetParentId()) self::$items[$item->GetParentId()]->AttachItem(self::$items[$item->GetId()]);
		return $item;
	 }

	final private function __construct() {}

	private static $items = [];
	private static $default_options = [];
}

abstract class AMenuItem
{
	public function __construct($id, $caption, $parent_id, array $options = null)
	 {
		$this->id = $id;
		$this->caption = $caption;
		$this->url = isset($options['url']) ? $options['url'] : "/$id/";
		$this->parent_id = $parent_id;
		if(isset($options['type'])) $this->type = $options['type'];
		if(isset($options['hide'])) $this->hide = $options['hide'];
		elseif(MainMenu::DefaultExists('hide', $this->hide));
		if($this->hide && is_callable($this->hide)) $this->hide = call_user_func($this->hide, $id, $parent_id, $options);
		if(!empty($options['collapse'])) $this->collapse = true;
		if(isset($options['title'])) $this->title = $options['title'];
		elseif($this->type && in_array('small', explode(' ', $this->type))) $this->title = true;
	 }

	final public function GetId() { return $this->id; }
	final public function GetParentId() { return $this->parent_id; }
	final public function GetCaption($for_nav = false) { return is_array($this->caption) ? @$this->caption[$for_nav ? 1 : 0] : $this->caption; }
	final public function GetURL() { return $this->url; }
	final public function GetLevel() { return ($pid = $this->GetParentId()) ? MainMenu::GetItem($pid)->GetLevel() + 1 : 1; }
	final public function IsCollapsed() { return $this->collapse; }
	final public function GetCollapsedClass() { if($this->collapse) return ' _toggle _collapsed'; }

	final public function AttachItem(AMenuItem $item)
	 {
		if(isset($this->items[$item->GetId()])) throw new Exception('Menu item with id "'.$item->GetId().'" was already attached.');
		$this->items[$item->GetId()] = $item;
	 }

	final public function Show()
	 {
		$this->hide = false;
		return $this;
	 }

	final public function SetUrl($val)
	 {
		$this->url = $val;
		return $this;
	 }

	final public function SetCaption($val)
	 {
		$this->caption = $val;
		return $this;
	 }

	final public function __toString()
	 {
		if($this->hide) return '';
		$ret_val = $this->Make();
		$tmp = '';
		foreach($this->items as $item) $tmp .= $item;
		if($tmp) $ret_val .= "<ul class='main_menu__group _l{$this->GetLevel()}'>$tmp</ul>";
		return "<li class='main_menu__block'>$ret_val</li>";
	 }

	abstract protected function Make();

	final protected function GetTypeAttr() { if($this->type) return " data-type='{$this->type}'"; }
	final protected function GetTitle() { return $this->title; }

	private $id;
	private $caption;
	private $parent_id;
	private $url;
	private $type;
	private $hide;
	private $title;
	private $collapse;
	private $items = [];
}

class MenuItem extends AMenuItem
{
	final protected function Make() { return MSLoader::GetId() === $this->GetId() ? $this->MakeSelected() : ($this->GetURL() ? $this->MakeActive() : $this->MakeInactive()); }

	final private function MakeActive() { return '<a class="main_menu__item" href="'.MSConfig::GetMSSMDir().$this->GetURL().'"'.$this->GetTypeAttr().$this->GetTitleAttr($caption).">$caption</a>".$this->GetToggleButton(); }

	final private function MakeInactive() { return "<h3 class='main_menu__item _inactive{$this->GetCollapsedClass()}'{$this->GetTypeAttr()}{$this->GetTitleAttr($caption)}>$caption</h3>"; }

	final private function GetToggleButton() { if($this->IsCollapsed()) return '<input type="button" class="main_menu__toggle _collapsed" value="" />'; }

	final private function MakeSelected()
	 {
		$url = MSConfig::GetMSSMDir().$this->GetURL();
		$attrs = ' class="main_menu__item _selected"'.$this->GetTypeAttr();
		return ($_SERVER['REQUEST_URI'] === $url ? "<h3$attrs>{$this->GetCaption()}</h3>" : "<a href='$url'$attrs{$this->GetTitleAttr($caption)}>$caption</a>").$this->GetToggleButton();
	 }

	final private function GetTitleAttr(&$caption)
	 {
		$caption = $this->GetCaption();
		if($title = $this->GetTitle()) return ' title="'.Filter::TextAttribute(true === $title ? $caption : $title).'"';
	 }
}

class MenuExternalItem extends AMenuItem
{
	final protected function Make() { return "<a class='main_menu__item' href='{$this->GetURL()}' target='_blank'{$this->GetTypeAttr()}>{$this->GetCaption()}</a>"; }
}

abstract class ResourceManager
{
	final public static function RegisterJSLink($id, $fname) { self::RegisterLink(self::$js_links, $id, $fname, 'JS-link'); }
	final public static function RegisterCSSLink($id, $fname) { self::RegisterLink(self::$css_links, $id, $fname, 'CSS-link'); }
	final public static function RegisterScript($id, $func) { self::RegisterLink(self::$scripts, $id, $func, 'Script'); }
	final public static function IsJSLinkRegistered($id) { return isset(self::$js_links[$id]); }
	final public static function IsCSSLinkRegistered($id) { return isset(self::$css_links[$id]); }
	final public static function AddJSLink($id) { self::RequireLink(self::$js_links, $id, 'JS-link'); }
	final public static function AddCSSLink($id) { self::RequireLink(self::$css_links, $id, 'CSS-link'); }
	final public static function FetchJSLink() { return self::FetchRegistered(self::$js_links); }
	final public static function FetchCSSLink() { return self::FetchRegistered(self::$css_links); }
	final public static function AddCSS(...$ids) { foreach($ids as $id) self::$css[$id] = true; }
	final public static function AddJS(...$ids) { foreach($ids as $id) self::$js[$id] = true; }
	final public static function FetchCSS() { if($item = each(self::$css)) return $item[0]; }
	final public static function FetchJS() { if($item = each(self::$js)) return $item[0]; }

	final public static function RequireScript($id)
	 {
		if(!self::$scripts[$id]['required'])
		 {
			$f = self::$scripts[$id]['value'];
			$f();
			self::$scripts[$id]['required'] = true;
		 }
	 }

	final private static function RegisterLink(array &$array, $id, $fname, $title)
	 {
		if(empty($array[$id])) $array[$id] = self::CreateFileEntity($fname);
		else throw new Exception("$title with ID `$id` is registered");
	 }

	final private static function RequireLink(array &$array, $id, $title)
	 {
		if(isset($array[$id])) $array[$id]['required'] = true;
		else throw new Exception("$title with ID `$id` is not registered.");
	 }

	final private static function FetchRegistered(&$array)
	 {
		do list($key, $ret_val) = each($array);
		while($ret_val && !$ret_val['required']);
		if(isset($ret_val['value'])) return $ret_val['value'];
	 }

	final private static function CreateFileEntity($fname) { return array('required' => false, 'value' => $fname); }

	final private function __construct() {}

	private static $js_links = [];
	private static $css_links = [];
	private static $scripts = [];
	private static $js = [];
	private static $css = [];
}

abstract class MSLoader
{
	final public static function Document()
	 {
		if(!self::$document) self::SetInstance(self::GetFactory(self::GetFactoryId())->CreateDocument(self::GetId()));
		return self::$document;
	 }

	final public static function AddFactory($class_name, $path = '')
	 {
		if(isset(self::$factories[$class_name])) throw new EMSLoaderDuplicateFactoryId();
		self::$factories[$class_name] = array('path' => $path);
	 }

	final public static function AddDefaultFactory($class_name, $path = '')
	 {
		self::AddFactory($class_name, $path);
		self::SetFactoryId($class_name);
	 }

	final public static function GetId()
	 {
		if(self::$id !== null) return self::$id;
		else
		 {
			$dir = dirname($_SERVER['PHP_SELF']);
			return (self::$id = (($pos = strrpos($dir, '/')) !== false ? substr($dir, -(strlen($dir) - $pos - 1)) : 'main'));
		 }
	 }

	final public static function RunDocument($action)
	 {
		switch($action)
		 {
			case 'show':
				self::$action_id = $action;
				die(MSLoader::Document()->GetContent());
			case 'handle':
				self::$action_id = $action;
				MSLoader::Document()->WHandle();
				exit();
			default: throw new Exception("Invalid type of action: `$action` ('show' or 'handle' required).");
		 }
	 }

	final public static function GetURL($root = true) { return ($root ? MSConfig::GetMSSMDir() : '').'/'.self::GetId().'/'; }
	final public static function SetId($id) { self::$id = $id; }
	final public static function SetFactoryId($factory_id) { self::$factory_id = $factory_id; }
	final public static function GetFactoryId() { return self::$factory_id; }
	final public static function GetActionId() { return self::$action_id; }
	final public static function NextFactory() { return ($item = each(self::$factories)) ? self::GetFactory($item['key']) : null; }

	final private static function GetFactory($id)
	 {
		if(!isset(self::$factories[$id])) throw new EMSLoaderUndefinedFactoryId();
		if(empty(self::$factories[$id]['object']))
		 {
			require_once($_SERVER['DOCUMENT_ROOT'].MSConfig::GetMSSMDir().self::$factories[$id]['path'].'/include/factory.php');
			return self::$factories[$id]['object'] = self::CheckFactory(new $id);
		 }
		else return self::$factories[$id]['object'];
	 }

	final private static function SetInstance(MSDocument $document) { self::$document = $document; }
	final private static function CheckFactory(IMSFactory $factory) { return $factory; }

	final private function __construct() {}

	private static $document = null;
	private static $factories = [];
	private static $id = null;
	private static $factory_id = null;
	private static $action_id;
}

class MSDocument403 extends MSDocument
{
	public function Show()
	 {
		$this->SetTitle('Доступ запрещён');
		print(ui::ErrorMsg('Доступ запрещён! Вы не можете редактировать или просматривать эту страницу.'));
	 }

	public function Handle() {}
}

class MSDocument404 extends MSDocument
{
	public function Show()
	 {
		$this->SetTitle('Страница не найдена');
		print(ui::ErrorMsg('Возможно, вы ошиблись, набирая адрес вручную.<br />Пожалуйста, воспользуйтесь главным меню, чтобы перейти на нужную страницу.'));
	 }

	public function Handle() { }
}
?>
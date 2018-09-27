<?php
class CombineScriptsAlgorithmB0
{
	public function DecodeNames($s, $ext)
	 {
		$len = strlen($s);
		$k = 0;
		$p = [];
		$prev_type = null;
		for($i = 0; $i < $len; ++$i)
		 {
			if(isset($this->num_a[$s[$i]])) $curr_type = 'num';
			elseif(isset($this->chr_a[$s[$i]])) $curr_type = 'chr';
			else throw new Exception('Invalid symbol!');
			if(null === $prev_type) $p[$k] = '';
			elseif($prev_type !== $curr_type) $p[++$k] = '';
			$p[$k] .= $s[$i];
			$prev_type = $curr_type;
		 }
		if(null === $this->f_names[$ext]) $this->InitFiles($ext);
		foreach($p as $k => $v)
		 {
			if(!is_numeric($v)) $p[$k] = strtr($v, $this->chr_s, $this->num_s);
			if(isset($this->f_names[$ext][$p[$k]])) $p[$k] = $this->f_names[$ext][$p[$k]];
			else throw new Exception('Invalid position!');
		 }
		return $p;
	 }

	public function EncodeNames(array $names, $ext)
	 {
		if(null === $this->f_names[$ext]) $this->InitFiles($ext);
		$i = 0;
		$s = '';
		foreach($names as $item)
		 {
			$pos = $this->f_pos[$ext]["$item[name].$ext"];
			if($i++ % 2) $pos = strtr("$pos", $this->num_s, $this->chr_s);
			$s .= $pos;
		 }
		return $s;
	 }

	public function CreateUrl(array $names, $ext, $c)
	 {
		$s = $this->EncodeNames($names, $ext);
		if($c) $s .= '.'.hash('crc32b', $c);
		return "/b0.$s.$ext";
	 }

	final private function InitFiles($ext)
	 {
		$this->f_names[$ext] = $this->f_pos[$ext] = [];
		$i = 0;
		foreach(scandir(Page::GetStaticRoot()."/$ext", SCANDIR_SORT_ASCENDING) as $k => $v)
		 if($v !== '.' && $v !== '..')
		  {
			$this->f_names[$ext][$i] = $v;
			$this->f_pos[$ext][$v] = $i;
			++$i;
		  }
	 }

	private $f_names = ['js' => null, 'css' => null];
	private $f_pos = ['js' => null, 'css' => null];
	private $chr_s = 'abcdefghij';
	private $chr_a = ['a' => 'a', 'b' => 'b', 'c' => 'c', 'd' => 'd', 'e' => 'e', 'f' => 'f', 'g' => 'g', 'h' => 'h', 'i' => 'i', 'j' => 'j'];
	private $num_s = '0123456789';
	private $num_a = [0 => '0', 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8', 9 => '9'];
}

class Page
{
	final public static function AddCSS($name, $media = 'all') { self::$css[$media][$name] = ['name' => $name, 'head' => true]; }
	final public static function AddCSSLink($url, $media = 'all') { self::$css_links[$url] = ['url' => $url, 'media' => $media]; }

	final public static function AddJSLink($url, $head = true)
	 {
		if(0 === strpos($url, 'https://') || 0 === strpos($url, '//') || 0 === strpos($url, 'http://'));
		elseif(isset(self::$js_links_registered[$url])) $url = self::$js_links_registered[$url];
		else throw new Exception("JS link '$url' is not registered!");
		self::$js_links[$url] = ['url' => $url, 'head' => (bool)$head];
	 }

	final public static function AddInlineCSS($code) { self::$inline_css .= PHP_EOL.$code; }
	final public static function SetStaticRoot($val) { self::$static_root = $val; }
	final public static function SetStaticHost($val) { self::$static_host = $val; }
	final public static function GetStaticRoot() { return self::$static_root; }
	final public static function SetCanonical($val, $add_host = true) { self::$canonical = $add_host ? self::GetHost().$val : $val; }
	final public static function GetProtocol() { return 'http'.(empty($_SERVER['HTTPS']) || 'off' == $_SERVER['HTTPS'] ? '' : 's').'://'; }
	final public static function GetHost() { return self::GetProtocol().$_SERVER['HTTP_HOST']; }
	final public static function RegisterJSLinks(array $links) { foreach($links as $k => $v) self::RegisterJSLink($k, $v); }
	final public static function GetRegisteredJSLinks() { return self::$js_links_registered; }

	final public static function RegisterJSLink($alias, $url)
	 {
		if(isset(self::$js_links_registered[$alias])) throw new Exception("JS link '$url' was registered!");
		self::$js_links_registered[$alias] = $url;
	 }

	final public static function SetHreflang($langs)
	 {
		if(!is_array($langs) && !is_object($langs)) throw new Exception('Argument 1 passed to '. __METHOD__ .' must be of the type array or an instance of Iterator, '.gettype($values).' given.');
		self::$alternate_langs = $langs;
	 }

	final public static function SetOption($name, $value)
	 {
		if(null === self::$options) self::InitOptions();
		self::$options->$name = $value;
	 }

	final public static function GetOption($name)
	 {
		if(null === self::$options) self::InitOptions();
		return self::$options->$name;
	 }

	final public static function RequireScript($id)
	 {
		if(empty(self::$required_scripts[$id]))
		 {
			call_user_func(self::$scripts[$id]);
			self::$required_scripts[$id] = $id;
		 }
	 }

	final public static function AddJS($name, $head = true, $host = false)
	 {
		$item = ['name' => $name, 'head' => (bool)$head];
		if($host) self::$js_host[$host][$name] = $item;
		else self::$js[$name] = $item;
	 }

	final public static function AddMetaTag($attr, $value, $content)
	 {
		if(!isset(self::$allowed[$attr])) throw new Exception("Invalid attribute name: meta[$attr='$value'][content='$content']. Allowed values are: ".implode(', ', self::$allowed).'.');
		self::$meta_tags["$attr-$value"] = ['attr' => $attr, 'value' => $value, 'content' => $content];
	 }

	final public static function RegisterScript($id, $function, $method = null)
	 {
		if(isset(self::$scripts[$id])) throw new Exception('Script with ID `'.$id.'` is already registered.');
		self::$scripts[$id] = $method ? [$function, $method] : $function;
	 }

	final public static function GetHeadTags()
	 {
		$host = self::GetStaticHost();
		$root = self::GetStaticRoot();
		$html = '';
		foreach(self::$meta_tags as $tag) $html .= '<meta '.$tag['attr'].'="'.$tag['value'].'" content="'.Filter::TextAttribute($tag['content']).'" />';
		foreach(self::$alternate_langs as $lang => $href) $html .= "<link rel='alternate' href='$href' hreflang='$lang' />";
		if(self::GetOption('combine'))
		 {
			foreach(self::$css as $media => $names)
			 {
				$c = '';
				foreach($names as $item) $c .= file_get_contents("$root/css/$item[name].css");
				$html .= '<link rel="stylesheet" href="'.$host.self::MakeLink($names, 'css', $c).'" type="text/css" media="'.$media.'" />';
			 }
		 }
		else foreach(self::$css as $media => $names) foreach($names as $name) $html .= "<link rel='stylesheet' href='$host/css/$name.css' type='text/css' media='$media' />";
		foreach(self::$css_links as $link) $html .= '<link rel="stylesheet" href="'.$link['url'].'" type="text/css" media="'.$link['media'].'" />';
		foreach(self::$js_host as $js_host => $names) $html .= '<script type="text/javascript" src="'.$js_host.self::MakeLink($names, 'js').'"></script>';
		$html .= self::JSLinks2HTML(true).self::JSNames2HTML(true, $host, $root);
		if(self::$inline_css) $html .= '<style type="text/css">/* <![CDATA[ */'.self::$inline_css.PHP_EOL.'/* ]]> */</style>';
		if(self::$canonical) $html .= '<link rel="canonical" href="'.self::$canonical.'" />';
		return $html;
	 }

	final public static function GetBottomTags()
	 {
		$host = self::GetStaticHost();
		$root = self::GetStaticRoot();
		return self::JSLinks2HTML(false).self::JSNames2HTML(false, $host, $root);
	 }

	final public static function GetStaticHost($protocol = '//')
	 {
		if(true === $protocol) $protocol = self::GetProtocol();
		if(null === self::$static_host)
		 {
			$s = 'static.';
			self::$static_host = strpos($_SERVER['HTTP_HOST'], 'www.') === 0 ? self::str_replace_first('www.', $s, $_SERVER['HTTP_HOST']) : $s.$_SERVER['HTTP_HOST'];
		 }
		return $protocol.self::$static_host;
	 }

	final private static function JSLinks2HTML($head)
	 {
		$html = '';
		foreach(self::$js_links as $link) if($link['head'] === $head) $html .= '<script type="text/javascript" src="'.$link['url'].'"></script>';
		return $html;
	 }

	final private static function JSNames2HTML($head, $host, $root)
	 {
		if(self::$js)
		 {
			$html = $c = '';
			$js = [];
			foreach(self::$js as $name => $item)
			 if($item['head'] === $head)
			  {
				$c .= file_get_contents("$root/js/$item[name].js");
				$js[$name] = $item;
			  }
			if($js)
			 {
				$inline = strlen($c) <= self::$max_inline_size;
				if(self::GetOption('combine'))
				 {
					$html .= '<script type="text/javascript"'.($inline ? ">/* <![CDATA[ */ $c /* ]]> */" : ' src="'.$host.self::MakeLink($js, 'js', $c).'">').'</script>';
				 }
				elseif($inline) $html .= "<script type='text/javascript'>/* <![CDATA[ */ $c /* ]]> */</script>";
				else foreach($js as $item) if($item['head'] === $head) $html .= "<script type='text/javascript' src='$host/js/$item[name].js'></script>";
				return $html;
			 }
		 }
	 }

	final private static function MakeLink(array $names, $ext, $c = '')
	 {
		// if($c)
		 // {
			// if(null === self::$algorythms['b0']) self::$algorythms['b0'] = new CombineScriptsAlgorithmB0();
			// return self::$algorythms['b0']->CreateUrl($names, $ext, $c);
		 // }
		$s = '';
		foreach($names as $item) $s .= ($s ? '~' : '/').$item['name'];
		if($c) $s .= '.'.hash('crc32b', $c);
		return "$s.$ext";
	 }

	final private static function str_replace_first($search, $replace, $subject)
	 {
		$pos = strpos($subject, $search);
		if($pos !== false) $subject = substr_replace($subject, $replace, $pos, strlen($search));
		return $subject;
	 }

	final private static function InitOptions()
	 {
		self::$options = new DataContainer(['combine' => ['type' => 'bool', 'value' => true]]);
	 }

	private static $allowed = ['http-equiv' => 'http-equiv', 'name' => 'name', 'property' => 'property', 'itemprop' => 'itemprop'];
	private static $meta_tags = [];
	private static $css = [];
	private static $js = [];
	private static $js_host = [];
	private static $css_links = [];
	private static $inline_css = '';
	private static $js_links = [];
	private static $js_links_registered = [];
	private static $static_host = null;
	private static $static_root = null;
	private static $required_scripts = [];
	private static $scripts = [];
	private static $alternate_langs = [];
	private static $canonical;
	private static $max_inline_size = 2048;
	private static $options = null;
	private static $algorythms = ['a0' => null, 'b0' => null];
}
?>
<?php
MSConfig::RequireFile('traits', 'datacontainer');

class EHTTP extends Exception {}
	class EHTTPCURL extends EHTTP {}
	class EHTTPTooManyRedirects extends EHTTP {}

class ms_http_response
{
	final public function __construct($value, array $data, array $headers, array $cookie)
	 {
		$this->value = $value;
		$this->data = $data;
		$this->headers = $headers;
		$this->cookie = $cookie;
	 }

	final public function GetHeader($name)
	 {
		$header = [];
		foreach($this->headers as $h)
		 {
			$h = explode(':', $h, 2);
			if(2 === count($h) && 0 === strcasecmp($name, $h[0])) $header[] = ltrim($h[1]);
		 }
		switch(count($header))
		 {
			case 0: return;
			case 1: return $header[0];
			default: return $header;
		 }
	 }

	final public function __toString()
	 {
		return "$this->value";
	 }

	final public function __get($name)
	 {
		if('url' === $name)
		 {
			if(null === $this->url) $this->url = new MSURL($this->data['url']);
			return $this->url;
		 }
		if('code' === $name) return $this->data['http_code'];
		if('mime' === $name || 'charset' === $name)
		 {
			if(null === $this->content_type)
			 {
				$this->content_type = new stdClass;
				$this->content_type->mime = $this->content_type->charset = null;
				if(empty($this->data['content_type'])) return;
				$a = explode(';', $this->data['content_type'], 2);
				$this->content_type->mime = strtolower($a[0]);
				if(!empty($a[1]))
				 {
					$s = 'charset=';
					if(false !== ($pos = strpos($a[1], $s))) $this->content_type->charset = strtolower(trim(substr($a[1], $pos + strlen($s)), ' \'"'));
				 }
			 }
			return $this->content_type->$name;
		 }
		if('content_type' === $name) return $this->data['content_type'];
		if('cookie' === $name) return $this->cookie;
		if('headers' === $name) return $this->headers;
		throw new Exception("Undefined property: $name");
	 }

	final public function __debugInfo()
	 {
		return ['url' => $this->__get('url'), 'code' => $this->__get('code'), 'content_type' => $this->__get('content_type')];
	 }

	final public function __set($name, $value)
	 {
		throw new Exception('Read only!');
	 }

	final public function __unset($name)
	 {
		throw new Exception('Read only!');
	 }

	private $value;
	private $data;
	private $content_type = null;
	private $cookie;
	private $headers;
	private $url = null;
}

class HTTP
{
	use TOptions;

	final public function __construct(array $o = null)
	 {
		$this->AddOptionsMeta([
			'basic' => [],
			'no_ssl_verifypeer' => ['type' => 'bool', 'value' => false],
			'user_agent' => ['type' => 'string', 'value' => ''],
			'referer' => ['type' => 'string', 'value' => ''],
			'accept_encoding' => ['type' => 'string', 'value' => ''],
			'cookie_file' => ['type' => 'string,array', 'value' => ''],
			'follow_location' => ['type' => 'int,gte0', 'value' => 0],
			'on_redirect' => ['type' => 'callback,null'],
			'connect_timeout' => ['type' => 'int,gte0', 'value' => 5],
			'proxy' => ['type' => 'iterator,array,null'],
			'raw' => ['type' => 'bool', 'value' => false],
		]);
		if($o) $this->SetOptionsData($o);
	 }

	final public function GET($url, array $data = [], array $o = null)
	 {
		$o = new OptionsGroup($o, self::$meta['request_options']);
		if($data) $url .= '?'.http_build_query($data);
		$ch = $this->Init($url, $o->headers, $o->cookie);
		$r = $this->Exec($ch);
		if($max = $this->GetOption('follow_location')) $this->FollowLocation('GET', $r, $max, $o);
		return $r;
	 }

	final public function POST($url, array $data = [], array $o = null)
	 {
		$o = new OptionsGroup($o, self::$meta['request_options']);
		$ch = $this->Init($url, $o->headers, $o->cookie);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data ? http_build_query($data) : '');
		$r = $this->Exec($ch);
		if($max = $this->GetOption('follow_location')) $this->FollowLocation('POST', $r, $max, $o);
		return $r;
	 }

	final public function GetCookie() { return $this->cookies; }
	final public function GetHTTPCode() { return $this->curl_info['http_code']; }
	final public function GetURL() { return $this->curl_info['url']; }
	final public function GetMIME() { if(!empty($this->curl_info['content_type'])) return explode(';', $this->curl_info['content_type'], 2)[0]; }
	final public function GetResponseHeaders($as_array = false) { return $as_array ? $this->response_headers_array : $this->response_headers; }

	final public function GetResponseHeader($name)
	 {
		$header = [];
		foreach($this->response_headers_array as $h)
		 {
			$h = explode(':', $h, 2);
			if(2 === count($h) && 0 === strcasecmp($name, $h[0])) $header[] = ltrim($h[1]);
		 }
		switch(count($header))
		 {
			case 0: return;
			case 1: return $header[0];
			default: return $header;
		 }
	 }

	final public function CurlHeaderFunction($ch, $headerLine)
	 {
		if(preg_match('/^Set-Cookie:\s*(.+?)=([^;]*)/i', rtrim($headerLine), $c)) $this->cookies[$c[1]] = $c[2];
		if($s = trim($headerLine)) $this->response_headers_array[] = $s;
		return strlen($headerLine);
	 }

	final public static function Redirect($url = false, $status = 302, $this_host = true)
	 {
		$host = $this_host ? MSConfig::GetProtocol().$_SERVER['HTTP_HOST'] : '';
		header('Location: '.$host.($url ?: $_SERVER['PHP_SELF']), true, $status);
		exit();
	 }

	final public static function Status($status, $exit = true)
	 {
		static $list = [
			400 => 'Bad Request',// The server cannot or will not process the request due to an apparent client error (e.g., malformed request syntax, invalid request message framing, or deceptive request routing).
			401 => 'Unauthorized', // (RFC 7235) Similar to 403 Forbidden, but specifically for use when authentication is required and has failed or has not yet been provided. The response must include a WWW-Authenticate header field containing a challenge applicable to the requested resource. See Basic access authentication and Digest access authentication. 401 semantically means "unauthenticated", i.e. the user does not have the necessary credentials.
			402 => 'Payment Required', // Reserved for future use. The original intention was that this code might be used as part of some form of digital cash or micropayment scheme, but that has not happened, and this code is not usually used. Google Developers API uses this status if a particular developer has exceeded the daily limit on requests.
			403 => 'Forbidden',// The request was a valid request, but the server is refusing to respond to it. 403 error semantically means "unauthorized", i.e. the user does not have the necessary permissions for the resource.
			404 => 'Not Found',// The requested resource could not be found but may be available in the future. Subsequent requests by the client are permissible.
			405 => 'Method Not Allowed',// A request method is not supported for the requested resource; for example, a GET request on a form which requires data to be presented via POST, or a PUT request on a read-only resource.
			406 => 'Not Acceptable',// The requested resource is capable of generating only content not acceptable according to the Accept headers sent in the request.
			500 => 'Internal Server Error',// A generic error message, given when an unexpected condition was encountered and no more specific message is suitable.
		];
		header("$_SERVER[SERVER_PROTOCOL] $status $list[$status]");
		if($exit) exit();
	 }

	final public static function TransformHdrArr(array $arr)
	 {
		$a = [];
		foreach($arr as $key => $value) $a[] = "$key: $value";
		return $a;
	 }

	final public static function GetClassMeta($name) { return $name ? self::$meta[$name] : self::$meta; }
	final public static function IsAssoc(array $arr) { return array_keys($arr) !== range(0, count($arr) - 1); }

	final private function FollowLocation($method, &$r, $max, DataContainer $o)
	 {
		static $n = 0;
		$redirects = [301 => true, 302 => true, 303 => true, 307 => true, 308 => true];//303 ВСЕГДА меняет метод на GET. 307 НИКОГДА не меняет метод.
		$code = $this->GetHTTPCode();
		if(isset($redirects[$code]))
		 {
			++$n;
			if($n > $max) throw new EHTTPTooManyRedirects("Maximum ($max) redirects followed");
			if(!($hdr = $this->GetResponseHeader('location'))) throw new EHTTPCURL('Location header is undefined.');
			$url_0 = new MSURL($this->GetURL());
			$url_1 = new MSURL($hdr, $url_0);
			if(($c = null === $o->on_redirect ? $this->GetOption('on_redirect') : $o->on_redirect) && (false === call_user_func($c, $r, $code, $url_1, $n, $this))) return ($n = 0);
			if(303 === $code) $method = 'GET';
			$r = $this->$method("$url_1", [], $o->ToArray());
		 }
		else $n = 0;
	 }

	final private function Init($url, array $headers = null, array $cookie = null)
	 {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if($o = $this->GetOption('basic'))
		 {
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, $o);
		 }
		if($this->GetOption('no_ssl_verifypeer')) curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, true);
		$this->cookies = $this->response_headers_array = [];
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, [$this, 'CurlHeaderFunction']);
		if($o = $this->GetOption('user_agent')) curl_setopt($ch, CURLOPT_USERAGENT, $o);
		if($o = $this->GetOption('referer')) curl_setopt($ch, CURLOPT_REFERER, $o);
		if($o = $this->GetOption('accept_encoding')) curl_setopt($ch, CURLOPT_ENCODING, $o);
		if($o = $this->GetOption('cookie_file'))
		 {
			if(is_array($o)) list($fwrite, $fread) = $o;
			else $fwrite = $fread = $o;
			curl_setopt($ch, CURLOPT_COOKIEJAR, $fwrite);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $fread);
		 }
		// if($o = $this->GetOption('follow_location'))
		 // {
			// curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			// if(is_int($o)) curl_setopt($ch, CURLOPT_MAXREDIRS, $o);
		 // }
		$this->config = [];
		if($o = $this->GetOption('proxy'))
		 {
			if(is_array($o)) $this->ConfigProxy($ch, (object)$o);
			elseif($c = count($o))
			 {
				if($c > 1)
				 {
					$servers = [];
					foreach($o as $k => $v) $servers[$k] = $v;
					$f = function($ch, $e_num) use(&$servers){
						static $k = null;
						if(null !== $e_num)
						 {
							if(56 !== $e_num) return;
							unset($servers[$k]);
						 }
						if(!count($servers)) return;
						$k = array_rand($servers);
						$this->ConfigProxy($ch, $servers[$k]);
						return true;
					};
					$f($ch, null);
					$this->config[] = $f;
				 }
				else
				 {
					$o->rewind();
					$this->ConfigProxy($ch, $o->current());
				 }
			 }
			else ;//throw new Exception();
		 }
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->GetOption('connect_timeout'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, self::IsAssoc($headers) ? self::TransformHdrArr($headers) : $headers);
		if($cookie) curl_setopt($ch, CURLOPT_COOKIE, $this->ArrayToCookie($cookie));
		return $ch;
	 }

	final private function Exec($ch)
	 {
		if($this->config)
		 {
			do
			 {
				if(($result = curl_exec($ch)) === false)
				 {
					$e_num = curl_errno($ch);
					foreach($this->config as $c) if(true !== $c($ch, $e_num)) break 2;
				 }
			 }
			while(false === $result);
		 }
		else $result = curl_exec($ch);
		if($result === false)
		 {
			$e_msg = curl_error($ch);
			$e_num = curl_errno($ch);
			$this->curl_info = curl_getinfo($ch);
			curl_close($ch);
			throw new EHTTPCURL($e_msg, $e_num);
		 }
		$hsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$this->response_headers = substr($result, 0, $hsize);
		$this->curl_info = curl_getinfo($ch);
		curl_close($ch);
		return $this->GetOption('raw') ? $result : new ms_http_response(substr($result, $hsize), $this->curl_info, $this->response_headers_array, $this->cookies);
	 }

	final private static function ArrayToCookie(array $a)
	 {
		$s = '';
		foreach($a as $k => $v) $s .= "$k=$v;";
		return $s;
	 }

	final private static function ConfigProxy($ch, stdClass $p)
	 {
		curl_setopt($ch, CURLOPT_PROXY, $p->host);
		if(!empty($p->port)) curl_setopt($ch, CURLOPT_PROXYPORT, $p->port);
		curl_setopt($ch, CURLOPT_PROXYUSERPWD, $p->user ? "$p->user:$p->password" : '');
		if(isset($p->type))
		 {
			if(empty(self::$meta['proxy_types'][$p->type])) throw new UnexpectedValueException('Invalid proxy type: '.MSConfig::GetVarType($p->type));
			curl_setopt($ch, CURLOPT_PROXYTYPE, $p->type);
		 }
		else curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, !empty($p->tunnel));
		// CURLOPT_PROXYHEADER				An array of custom HTTP headers to pass to proxies.
		// CURLOPT_PROXY_SERVICE_NAME		The proxy authentication service name.
		// CURLOPT_PROXYAUTH				The HTTP authentication method(s) to use for the proxy connection. Use the same bitmasks as described in CURLOPT_HTTPAUTH. For proxy authentication, only CURLAUTH_BASIC and CURLAUTH_NTLM are currently supported.
	 }

	private $response_headers = null;
	private $response_headers_array = [];
	private $cookies = [];
	private $curl_info = null;
	private $config = null;

	private static $meta = [
		'proxy_types' => [CURLPROXY_HTTP => 'HTTP', CURLPROXY_SOCKS4 => 'SOCKS4', CURLPROXY_SOCKS5 => 'SOCKS5', CURLPROXY_SOCKS4A => 'SOCKS4A', CURLPROXY_SOCKS5_HOSTNAME => 'SOCKS5 HOSTNAME'],
		'request_options' => ['headers' => ['type' => 'array,null'], 'cookie' => ['type' => 'array,null'], 'on_redirect' => ['type' => 'callback,false,null']],
	];
}
?>
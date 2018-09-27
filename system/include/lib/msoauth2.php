<?php
class EMSOAuth1 extends Exception {}
class EMSOAuth2 extends Exception {}
	class EMSOAuth2InvalidIndex extends EMSOAuth2 {}
	class EMSOAuth2DuplicateIndex extends EMSOAuth2 {}
	class EMSOAuth2NoApps extends EMSOAuth2 {}
	class EMSOAuth2EmptyOnAuth extends EMSOAuth2 {}
	class EMSOAuth2InvalidType extends EMSOAuth2 {}
	class EMSOAuth2ErrorResponse extends EMSOAuth2 {}
		class EMSOAuth2InvalidRequest extends EMSOAuth2ErrorResponse {}
		class EMSOAuth2UnauthorizedClient extends EMSOAuth2ErrorResponse {}
		class EMSOAuth2AccessDenied extends EMSOAuth2ErrorResponse {}
			class EMSOAuth2UserDenied extends EMSOAuth2AccessDenied {}
		class EMSOAuth2UnsupportedResponseType extends EMSOAuth2ErrorResponse {}
		class EMSOAuth2InvalidScope extends EMSOAuth2ErrorResponse {}
		class EMSOAuth2ServerError extends EMSOAuth2ErrorResponse {}
		class EMSOAuth2TemporarilyUnavailable extends EMSOAuth2ErrorResponse {}
		class EMSOAuth2InvalidClient extends EMSOAuth2ErrorResponse {}
		class EMSOAuth2InvalidGrant extends EMSOAuth2ErrorResponse {}
		class EMSOAuth2UnsupportedGrantType extends EMSOAuth2ErrorResponse {}
		class EMSOAuth2Undefined extends EMSOAuth2ErrorResponse {}
		class EMSOAuthException extends EMSOAuth2ErrorResponse {}

class OAuthAccessToken
{
    final public function __construct(MSOAuth2Config $config, $type, $value, $user_id, $expires_in, $secret = null)
	 {
        $this->config = $config;
        $this->type = $type;
        $this->value = $value;
        $this->user_id = $user_id;
        $this->expires_in = $expires_in;
        $this->secret = $secret;
	 }

	final public function GetConfig() { return $this->config; }
	final public function GetType() { return $this->type; }
	final public function GetValue() { return $this->value; }
	final public function GetUserId() { return $this->user_id; }
	final public function GetExpiresIn() { return $this->expires_in; }
	final public function GetSecret() { return $this->secret; }

    private $config;
    private $type;
    private $value;
    private $user_id;
    private $expires_in;
    private $secret;
}

abstract class MSOAuth2Static
{
	final public static function GetHost() { return 'http'.(empty($_SERVER['HTTPS']) || 'off' == $_SERVER['HTTPS'] ? '' : 's').'://'.$_SERVER['HTTP_HOST']; }

	final public static function Redirect($url)
	 {
		Header('Location: '.$url, true, 302);
		exit();
	 }

	const F_TYPE = '__msoauth2_type';
	const F_ACTION = '__msoauth2_action';
	const F_REDIRECT = '__msoauth2_redirect';
}

class MSOAuth2AppConfig
{
	final public function __construct(array $data)
	 {
		$this->data = $data;
		foreach(self::$required as $k => $i)
		 {
			$r = 2 * isset($this->data[$k]) + 1 * isset($this->data[$i]);
			if(3 === $r) throw new Exception("Option `$k` is duplicated at index `$i`!");
			elseif(2 === $r) ;
			elseif(1 === $r)
			 {
				$this->data[$k] = $this->data[$i];
				unset($this->data[$i]);
			 }
			elseif(0 === $r) throw new Exception("Required option `$k` is missing!");
			else ;
		 }
	 }

	final public function __get($name) { if(isset($this->data[$name])) return $this->data[$name]; }
	final public function __set($name, $value) { throw new Exception(__CLASS__ .' is read only!'); }
	final public function __debugInfo() { return $this->data; }

	private $data;

	private static $required = ['id' => 0, 'secret' => 1, 'scope' => 2];
}

class MSOAuth2Config extends MSOAuth2Static
{
	final public function __construct(array $opts, array $apps, $index)
	 {
		if(!$apps) throw new EMSOAuth2NoApps("MSOAuth2[$index] is not configured: no apps added! (Use MSOAuth2::Config())");
		if(empty($opts['on_auth'])) throw new EMSOAuth2EmptyOnAuth("MSOAuth2[$index]: `on_auth` option is not set! (Use MSOAuth2::Config())");
		$this->opts = $opts;
		$this->apps = $apps;
		foreach($this->apps as &$a) $a = new MSOAuth2AppConfig($a);
		$this->index = $index;
		$o = [];
		foreach(['no_ssl_verifypeer'] as $i) if($v = $this->__get($i)) $o[$i] = $v;
		$this->http = new HTTP($o);
	 }

	final public function GetBaseUrl() { return ($this->host ?: self::GetHost()).($this->url ?: '/'); }

	final public function GetHTTP() { return $this->http; }

	final public function GetBack()
	 {
		$url = $this->redirect;
		if(null === $url)
		 {
			if(!session_id()) session_start();
			if(empty($_SESSION[self::F_REDIRECT])) $url = false;
			else
			 {
				$url = $_SESSION[self::F_REDIRECT];
				unset($_SESSION[self::F_REDIRECT]);
			 }
			if($url && !$this->no_host) $url = self::GetHost().$url;
		 }
		if(!$url) $url = self::GetHost();
		self::Redirect($url);
	 }

	final public function __get($name)
	 {
		if('apps' === $name) return $this->apps;
		if('index' === $name) return $this->index;
		if(isset($this->opts[$name])) return $this->opts[$name];
	 }

	final public function __set($name, $value) { throw new Exception('MSOAuth2[$this->index] config is read only!'); }

	private $opts;
	private $apps;
	private $index;
	private $http;
}

abstract class MSOAuth2 extends MSOAuth2Static
{
	final public static function Exists($index = 0, MSOAuth2Config &$c = null)
	 {
		$c = ($r = isset(self::$configs[$index])) ? self::$configs[$index] : null;
		return $r;
	 }

	final public static function GetConf($index = 0)
	 {
		if(!isset(self::$configs[$index])) throw new EMSOAuth2InvalidIndex("MSOAuth2[$index]: invalid index!");
		return self::$configs[$index];
	 }

	final public static function Config(array $opts, array $apps, $index = 0)// 'FB' => [APP_ID, APP_SECRET, APP_SCOPE]
	 {
		if(isset(self::$configs[$index])) throw new EMSOAuth2DuplicateIndex("MSOAuth2[$index]: duplicate index!");
		self::$configs[$index] = new MSOAuth2Config($opts, $apps, $index);
	 }

	final public static function GetButtons($index = 0)
	 {
		$c = self::GetConf($index);
		$ret_val = [];
		$url = $c->GetBaseUrl();
		$short_url = !$c->long_url;
		$r = false === $c->redirect ? '' : ($short_url ? '?' : '&').self::F_REDIRECT.'='.urlencode($c->redirect ?: $_SERVER['REQUEST_URI']);
		$url .= $short_url ? 'msoauth2.1.' : (false === strpos($url, '?') ? '?' : '&').self::F_ACTION.'=step_1&'.self::F_TYPE.'=';
		foreach($c->apps as $type => $o)
		 {
			$b = new stdClass();
			$b->type = $type;
			$b->href = $url.$type.($o->redirect ? ($short_url ? '?' : '&').self::F_REDIRECT.'='.urlencode($o['redirect']) : $r);
			$b->title = $o->title;
			$ret_val[] = $b;
		 }
		return $ret_val;
	 }

	final public static function Run($index = 0)
	 {
		try
		 {
			$c = self::GetConf($index);
			if(isset($_GET[self::F_ACTION]))
			 {
				if('step_1' === $_GET[self::F_ACTION])
				 {
					if(!session_id()) session_start();
					$_SESSION[self::F_REDIRECT] = empty($_REQUEST[self::F_REDIRECT]) ? null : $_REQUEST[self::F_REDIRECT];
					self::Instance($index)->Authorize();
				 }
				elseif('step_2' === $_GET[self::F_ACTION])
				 {
					self::Instance($index)->Authenticate($_REQUEST);
					$c->GetBack();
				 }
				else ;// ???
			 }
			else ;// ???
		 }
		catch(Exception $e)
		 {
			if($c->e_handler) call_user_func($c->e_handler, $e, $c);
			$c->GetBack();
		 }
	 }

	final public static function ConvertE(array $error)
	 {
		switch(@$error['type'])
		 {
			case 'OAuthException': $e = 'EMS'.$error['type'];
			default: $e = 'EMSOAuth2ErrorResponse';
		 }
		throw new $e(@$error['message'], @$error['code']);
	 }

	final protected static function Instance($index = 0)
	 {
		if(!isset(self::$instances[$index]))
		 {
			$c = self::GetConf($index);
			if(empty($_REQUEST[self::F_TYPE]) || empty($c->apps[$_REQUEST[self::F_TYPE]])) throw new EMSOAuth2InvalidType();
			$class = 'MSOAuth2'.$_REQUEST[self::F_TYPE];
			$o = $c->apps[$_REQUEST[self::F_TYPE]];
			require_once(str_replace('msoauth2.php', 'msoauth2'.$_REQUEST[self::F_TYPE].'.php', __FILE__));
			self::SetInstance($index, new $class($o->id, $o->secret, $o->scope, $c));
		 }
		return self::$instances[$index];
	 }

	protected function __construct($id, $secret, $scope, MSOAuth2Config $config)
	 {
		$this->id = $id;
		$this->secret = $secret;
		$this->scope = $scope;
		$this->config = $config;
	 }

	abstract public function GetAuthUrl();
	abstract public function GetTokenUrl();

	abstract protected function CreateToken($token);

	protected function IsValidToken($token) { return !empty($token['access_token']) && isset($token['expires_in']); }
	protected function GetAuthorizeUrl() { return $this->GetAuthUrl().'?client_id='.$this->GetId().'&scope='.urlencode($this->GetScope()).'&state=profile&redirect_uri='.urlencode($this->GetRedirectUri()).'&response_type=code'; }
	protected function Authorize() { self::Redirect($this->GetAuthorizeUrl()); }

	final protected function Authenticate(array $request)
	 {
		$token = $this->RequestAccessToken($request);
		if(!$token || !$this->IsValidToken($token)) self::ThrowE($token);
		call_user_func($this->config->on_auth, $this->CreateToken($token));
	 }

	final protected function GetType()
	 {
		if(null === $this->type) $this->type = strtolower(str_replace('MSOAuth2', '', get_class($this)));
		return $this->type;
	 }

	final protected function GetConfig() { return $this->config; }
	final protected function GetId() { return $this->id; }
	final protected function GetScope() { return $this->scope; }
	final protected function GetSecret() { return $this->secret; }

	final protected function GetRedirectUri()
	 {
		$url = $this->config->GetBaseUrl();
		$short_url = !$this->config->long_url;
		return $url.($short_url ? 'msoauth2.2.' : (false === strpos($url, '?') ? '?' : '&').self::F_ACTION.'=step_2&'.self::F_TYPE.'=').$this->GetType();
	 }

	final protected static function ThrowE(array $request)
	 {
		if(!empty($request['error']))
		 {
			if(is_array($request['error'])) self::ConvertE($request['error']);
			else switch($request['error'])
			 {
				case 'invalid_request': $e = 'EMSOAuth2InvalidRequest'; break;
				case 'unauthorized_client': $e = 'EMSOAuth2UnauthorizedClient'; break;
				case 'access_denied': $e = isset($request['error_reason']) && 'user_denied' === $request['error_reason'] ? 'EMSOAuth2UserDenied' : 'EMSOAuth2AccessDenied'; break;
				case 'unsupported_response_type': $e = 'EMSOAuth2UnsupportedResponseType'; break;
				case 'invalid_scope': $e = 'EMSOAuth2InvalidScope'; break;
				case 'server_error': $e = 'EMSOAuth2ServerError'; break;
				case 'temporarily_unavailable': $e = 'EMSOAuth2TemporarilyUnavailable'; break;
				case 'invalid_client': $e = 'EMSOAuth2InvalidClient'; break;
				case 'invalid_grant': $e = 'EMSOAuth2InvalidGrant'; break;
				case 'unsupported_grant_type': $e = 'EMSOAuth2UnsupportedGrantType'; break;
				case 'emsoauth1': $e = 'EMSOAuth1'; break;
				default: $e = 'EMSOAuth2ErrorResponse';
			 }
		 }
		else $e = 'EMSOAuth2Undefined';
		throw new $e(@$request['error_description']);
	 }

	protected function RequestAccessToken(array $request)
	 {
		if(empty($request['code'])) self::ThrowE($request);
		$resp = $this->config->GetHTTP()->POST($this->GetTokenUrl(), ['client_id' => $this->GetId(), 'client_secret' => $this->GetSecret(), 'redirect_uri' => $this->GetRedirectUri(), 'code' => (string)$request['code'], 'grant_type' => 'authorization_code']);
		$data = json_decode($resp, true);
		if($data) return $data;
		if(!$resp) $this->OnInvalidAccessToken();
		return $resp;
	 }

	protected function OnInvalidAccessToken($resp) { throw new EMSOAuth2ErrorResponse(); }

	final private static function SetInstance($index, MSOAuth2 $instance) { self::$instances[$index] = $instance; }

	private $id;
	private $secret;
	private $scope;
	private $type = null;
	private $config;

	private static $instances;
	private static $configs = [];
}
?>
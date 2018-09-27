<?php
class MSOAuth2twitter extends MSOAuth2
{
	final public static function CalculateSignature($method, $url, array $p, $consumer_secret, $accessToken = '')
	 {
		$parameters = array();
		foreach($p as $key => $val) $parameters[rawurlencode($key)] = rawurlencode($val);
		ksort($parameters);
		$s = '';
		foreach($parameters as $key => $val) $s .= ($s ? '&' : '')."$key=$val";
		return base64_encode(hash_hmac('SHA1', strtoupper($method).'&'.rawurlencode($url).'&'.rawurlencode($s), rawurlencode($consumer_secret).'&'.$accessToken, true));
	 }

	final public static function GetAuthorizationHeader(array $parameters)
	 {
		$s = '';
		foreach($parameters as $key => $val) $s .= ($s ? ', ' : '').$key.'="'.rawurlencode($val).'"';
		return "Authorization: OAuth $s";
	 }

	final public function GetAuthUrl() { return 'https://oauth.yandex.ru/authorize'; }
	final public function GetTokenUrl() { return 'https://api.twitter.com/oauth/request_token'; }

	final protected function IsValidToken($token) { return !empty($token['user_id']) && !empty($token['oauth_token']) && !empty($token['oauth_token_secret']); }
	final protected function CreateToken($token) { return new OAuthAccessToken($this->getType(), $token['oauth_token'], $token['user_id'], null, $token['oauth_token_secret']); }

	final protected function Authorize()
	 {
		$time = (string)time();
		$oauth_callback = $this->GetRedirectUri();
		$p = array('oauth_callback' => $oauth_callback, 'oauth_consumer_key' => $this->GetId(), 'oauth_nonce' => md5($oauth_callback.$this->GetId().$time), 'oauth_signature_method' => 'HMAC-SHA1', 'oauth_timestamp' => $time, 'oauth_version' => '1.0');
		$p['oauth_signature'] = $this->CalculateSignature('post', $this->GetTokenUrl(), $p, $this->GetSecret());
		$result = self::ExecPOST($this->GetTokenUrl(), array(), array($this->GetAuthorizationHeader($p)));
		if(is_array($result) && isset($result['error'])) throw new Exception($result['error']);
		$data = array();
		parse_str($result, $data);
		if(empty($data['oauth_token']))
		 {
			$doc = new DOMDocument();
			try
			 {
				$doc->LoadXML($result);
				$nodes = $doc->getElementsByTagName('error');
				$error = $nodes->length ? $nodes->item(0)->nodeValue : false;
			 }
			catch(Exception $e)
			 {
				$error = false;
			 }
			throw new Exception($error);
		 }
		header('Location: https://api.twitter.com/oauth/authenticate?oauth_token='.$data['oauth_token']);
		exit();
	 }

	final protected function RequestAccessToken(array $request)
	 {
		if(!empty($_GET['oauth_token']) && !empty($_GET['oauth_verifier']))
		 {
			$time = (string)time();
			$url = 'https://api.twitter.com/oauth/access_token';
			$p = array('oauth_consumer_key' => $this->GetId(), 'oauth_nonce' => md5($url.$this->GetId().$time), 'oauth_signature_method' => 'HMAC-SHA1', 'oauth_timestamp' => $time, 'oauth_token' => $_GET['oauth_token'], 'oauth_version' => '1.0');
			$p['oauth_signature'] = $this->CalculateSignature('post', $url, $p, $this->GetSecret());
			$hdr = $this->GetAuthorizationHeader($p);
			$result = self::ExecPOST($url, array('oauth_verifier' => $_GET['oauth_verifier']), array($hdr));
			if(is_array($result) && isset($result['error'])) throw new Exception($result['error']);
			$data = array();
			parse_str($result, $data);
			if($this->IsValidToken($data)) return $data;
			$doc = new DOMDocument();
			try
			 {
				$doc->LoadXML($result);
				$nodes = $doc->getElementsByTagName('error');
				$error = $nodes->length ? $nodes->item(0)->nodeValue : false;
			 }
			catch(Exception $e)
			 {
				$error = false;
			 }
			return array('error' => 'emsoauth1', 'error_description' => $error ?: 'Неизвестная ошибка.');
		 }
		elseif(!empty($_GET['denied'])) return array('error' => 'access_denied', 'error_reason' => 'user_denied');
		else return array();
	 }
}
?>
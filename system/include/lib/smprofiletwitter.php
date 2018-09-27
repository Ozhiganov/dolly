<?php
class SMProfiletwitter extends SMProfile
{
	final public function GetData()
	 {
		$consumer_key = MSOAuth2::GetAppConf('twitter', 'id');
		$time = (string)time();
		$url = 'https://api.twitter.com/1.1/users/lookup.json';
		$token = $this->GetToken();
		$p = array('oauth_consumer_key' => $consumer_key, 'oauth_nonce' => md5($url.$consumer_key.$time), 'oauth_signature_method' => 'HMAC-SHA1', 'oauth_timestamp' => $time, 'oauth_token' => $token->GetValue(), 'user_id' => $token->GetUserId(), 'oauth_version' => '1.0');
		$p['oauth_signature'] = MSOAuth2twitter::CalculateSignature('get', $url, $p, MSOAuth2::GetAppConf('twitter', 'secret'), $token->GetSecret());
		$hdr = MSOAuth2twitter::GetAuthorizationHeader($p);
		$result = MSOAuth2::ExecGET($url, array('user_id' => $token->GetUserId()), array($hdr));
		$data = json_decode($result, true);
		if($data)
		 {
			if(empty($data['errors']))
			 {
				if(!empty($data[0])) $data = $data[0];
				return $this->CreateRetVal($data['id_str'], $data['name'], '', $data['name'], null, null, empty($data['profile_image_url']) ? null : $data['profile_image_url']);
			 }
			$msgs = array();
			foreach($data['errors'] as $error) $msgs[] = $error['message'];
			throw new ESNProfileErrorResponse(implode(PHP_EOL, $msgs));
		 }
		else throw new ESNProfileErrorResponse('wrong_response');
	 }
}
?>
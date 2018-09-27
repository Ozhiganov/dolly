<?php
class MSOAuth2fb extends MSOAuth2
{
	public function GetAuthUrl() { return 'https://graph.facebook.com/oauth/authorize'; }
	public function GetTokenUrl() { return 'https://graph.facebook.com/oauth/access_token'; }

	protected function ProcessToken($body) { return $body; }
	protected function CreateToken($token) { return new OAuthAccessToken($this->GetConfig(), $this->GetType(), $token['access_token'], null, $token['expires_in']); }
}
?>
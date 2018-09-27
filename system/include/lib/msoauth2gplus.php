<?php
class MSOAuth2gplus extends MSOAuth2
{
	public function GetAuthUrl() { return 'https://accounts.google.com/o/oauth2/auth'; }
	public function GetTokenUrl() { return 'https://www.googleapis.com/oauth2/v3/token'; }

	protected function CreateToken($token) { return new OAuthAccessToken($this->getType(), $token['access_token'], null, $token['expires_in']); }
}
?>
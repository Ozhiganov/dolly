<?php
class MSOAuth2ok extends MSOAuth2
{
	public function GetAuthUrl() { return 'http://www.odnoklassniki.ru/oauth/authorize'; }
	public function GetTokenUrl() { return 'http://api.odnoklassniki.ru/oauth/token.do'; }

	protected function IsValidToken($token) { return !empty($token['access_token']); }
	protected function CreateToken($token) { return new OAuthAccessToken($this->getType(), $token['access_token'], null, null); }
}
?>
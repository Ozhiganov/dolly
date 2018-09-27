<?php
class MSOAuth2ya extends MSOAuth2
{
	public function GetAuthUrl() { return 'https://oauth.yandex.ru/authorize'; }
	public function GetTokenUrl() { return 'https://oauth.yandex.ru/token'; }

	protected function CreateToken($token) { return new OAuthAccessToken($this->getType(), $token['access_token'], null, $token['expires_in']); }
}
?>
<?php
class MSOAuth2mmir extends MSOAuth2
{
	public function GetAuthUrl() { return 'https://connect.mail.ru/oauth/authorize'; }
	public function GetTokenUrl() { return 'https://connect.mail.ru/oauth/token'; }

	protected function IsValidToken($token) { return parent::IsValidToken($token) && !empty($token['x_mailru_vid']); }
	protected function CreateToken($token) { return new OAuthAccessToken($this->getType(), $token['access_token'], $token['x_mailru_vid'], $token['expires_in']); }
}
?>
<?php
class MSOAuth2vk extends MSOAuth2
{
	public function GetAuthUrl() { return 'https://oauth.vk.com/authorize'; }
	public function GetTokenUrl() { return 'https://oauth.vk.com/access_token'; }

	protected function IsValidToken($token) { return parent::IsValidToken($token) && !empty($token['user_id']); }
	protected function CreateToken($token) { return new OAuthAccessToken($this->GetConfig(), $this->getType(), $token['access_token'], $token['user_id'], $token['expires_in']); }
}
?>
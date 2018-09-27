<?php
class ESMProfileErrorResponse extends Exception {}

abstract class SMProfile
{
	final public static function GetLink($type, $id)
	 {
		switch($type)
		 {
			case 'fb': return 'https://www.facebook.com/profile.php?id='.$id;
			case 'vk': return 'https://vk.com/id'.$id;
			case 'ok': return 'http://ok.ru/profile/'.$id;
			case 'gplus': return 'https://plus.google.com/u/0/'.$id;
			case 'twitter': return 'https://twitter.com/intent/user?user_id='.$id;
			// case 'mmir': return 'http://'; $row['href_type'] = '';
			// case 'ya': return 'http://'; $row['href_type'] = '';
		 }
	 }

	final public static function Create(OAuthAccessToken $token)
	 {
		MSConfig::RequireFile('smprofile'.$token->GetType());
		$c = 'SMProfile'.$token->GetType();
		return new $c($token);
	 }

	final protected function __construct(OAuthAccessToken $token)
	 {
		$this->token = $token;
	 }

	final protected function ConvertGender($gender)
	 {
		switch($gender)
		 {
			case 'male': return 1;
			case 'female': return 0;
			default: return null;
		 }
	 }

	final protected function CreateRetVal($id, $first_name, $last_name, $full_name, $sex, $birthday, $userpic)
	 {
		return ['id' => $id, 'first_name' => $first_name, 'last_name' => $last_name, 'full_name' => $full_name, 'sex' => $sex, 'birthday' => $birthday, 'userpic' => $userpic];
	 }

	abstract public function GetData();// must return array('id', 'first_name', 'last_name', 'full_name', 'sex', 'birthday', 'userpic');

	final public function GetToken() { return $this->token; }

	private $token;
}
?>
<?php
class SNProfileya extends SNProfile
{
	final public function GetData()
	 {
		$body = MSOAuth2::ExecGET('https://login.yandex.ru/info', array('oauth_token' => $this->GetToken()->GetValue(), 'format' => 'json'));
		$data = json_decode($body, true);
		if(isset($data['error']))
		 {
			if(is_array($data['error'])) MSOAuth2::ConvertE($data['error']);
			else throw new ESNProfileErrorResponse($data['error']);
		 }
		if(!isset($data['id'])) throw new ESNProfileErrorResponse('wrong_response');
		return $this->CreateRetVal($data['id'], $data['first_name'], $data['last_name'], trim($data['first_name'].' '.$data['last_name']), $this->ConvertGender($data['sex']), empty($data['birthday']) ? null : $data['birthday'], empty($data['default_avatar_id']) ? null : $this->GetUserpic($data['default_avatar_id']));
	 }

	final protected function GetUserpic($pic_id)
	 {
		return "https://avatars.yandex.net/get-yapic/$pic_id/islands-200";
	 }
}
?>
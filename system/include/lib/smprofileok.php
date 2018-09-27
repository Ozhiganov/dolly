<?php
class SNProfileok extends SNProfile
{
	final public function GetData()
	 {
		$public_key = MSOAuth2::GetAppConf('ok', 'public_key');
		$fields = 'uid,first_name,last_name,name,gender,birthday,pic_3';
		$body = MSOAuth2::ExecGET('http://api.odnoklassniki.ru/fb.do?'.http_build_query(array('method' => 'users.getCurrentUser', 'fields' => $fields, 'access_token' => $this->GetToken()->GetValue(), 'application_key' => $public_key, 'format' => 'json', 'sig' => md5("application_key={$public_key}fields={$fields}format=jsonmethod=users.getCurrentUser".md5($this->GetToken()->GetValue().MSOAuth2::GetAppConf('ok', 'secret'))))));
		$data = json_decode($body, true);
		if(isset($data['error_code'])) throw new ESNProfileErrorResponse($data['error_msg'], $data['error_code']);
		if(!isset($data['uid'])) throw new ESNProfileErrorResponse('wrong_response');
		return $this->CreateRetVal($data['uid'], $data['first_name'], $data['last_name'], $data['name'], $this->ConvertGender($data['gender']), empty($data['birthday']) ? null : $data['birthday'], empty($data['pic_3']) ? null : $data['pic_3']);
	 }
}
?>
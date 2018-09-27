<?php
class SMProfilevk extends SMProfile
{
	final public function GetData()
	 {
		$token = $this->GetToken();
		$body = $token->GetConfig()->GetHTTP()->GET('https://api.vk.com/method/users.get', ['user_ids' => $token->GetUserId(), 'access_token' => $token->GetValue(), 'fields' => 'first_name,last_name,sex,photo_max,bdate', 'v' => '5.2']);
		$data = json_decode($body, true);
		if(isset($data['error'])) throw new ESMProfileErrorResponse($data['error']);
		if(isset($data['response'][0])) $data = $data['response'][0];
		if(!isset($data['id'])) throw new ESMProfileErrorResponse('wrong_response');
		return $this->CreateRetVal($data['id'], $data['first_name'], $data['last_name'], trim($data['first_name'].' '.$data['last_name']), empty($data['sex']) ? null : $data['sex'] - 1, empty($data['bdate']) ? null : $this->ConvertDate($data['bdate']), @$data['photo_max']);
	 }

	final protected function ConvertDate($date)
	 {
		$date = explode('.', $date);
		switch(count($date))
		 {
			case 2: return "0000-$date[1]-$date[0]";
			case 3: return "$date[2]-$date[1]-$date[0]";
		 }
	 }
}
//Дата рождения, выдаётся в формате: "23.11.1981" или "21.9" (если год скрыт).
?>
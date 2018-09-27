<?php
class SNProfilemmir extends SNProfile
{
	final public function GetData()
	 {
		$parameters = array('client_id' => MSOAuth2::GetAppConf('mmir', 'id'), 'uids' => $this->GetToken()->GetUserId(), 'secure' => '1', 'method' => 'users.getInfo', 'session_key' => $this->GetToken()->GetValue());
		$parameters['sig'] = $this->GenerateSIG($parameters, MSOAuth2::GetAppConf('mmir', 'secret'));
		$body = MSOAuth2::ExecGET('https://www.appsmail.ru/platform/api', $parameters);
		$data = json_decode($body, true);
		if(isset($data['error']))
		 {
			if(is_array($data['error'])) MSOAuth2::ConvertE($data['error']);
			else throw new ESNProfileErrorResponse($data['error']);
		 }
		if(isset($data[0])) $data = $data[0];
		if(!isset($data['uid'])) throw new ESNProfileErrorResponse('wrong_response');
		return $this->CreateRetVal($data['uid'], $data['first_name'], $data['last_name'], trim($data['first_name'].' '.$data['last_name']), $data['sex'] == 0 ? 1 : 0, empty($data['birthday']) ? null : $this->ConvertDate($data['birthday']), empty($data['pic']) ? null : $data['pic']);
	 }

	final private function GenerateSIG(array $parameters, $app_secret)
	 {
		ksort($parameters);
		$params = '';
		foreach($parameters as $key => $value) $params .= "$key=$value";
		return md5($params.$app_secret);
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
?>
<?php
class SNProfilegplus extends SNProfile
{
	final public function GetData()
	 {
		$body = MSOAuth2::ExecGET('https://www.googleapis.com/plus/v1/people/me', array('access_token' => $this->GetToken()->GetValue()));
		$data = json_decode($body, true);
		if(isset($data['error']))
		 {
			if(is_array($data['error'])) MSOAuth2::ConvertE($data['error']);
			else throw new ESNProfileErrorResponse($data['error']);
		 }
		if(!isset($data['id'])) throw new ESNProfileErrorResponse('wrong_response');
		return $this->CreateRetVal($data['id'], $data['name']['familyName'], $data['name']['givenName'], $data['displayName'], isset($data['gender']) ? $this->ConvertGender($data['gender']) : null, empty($data['birthday']) ? null : $data['birthday'], empty($data['image']) ? null : $this->GetUserpic($data['image']));
	 }

	final protected function GetUserpic($p)
	 {
		return @$p['url'];
	 }
}
?>
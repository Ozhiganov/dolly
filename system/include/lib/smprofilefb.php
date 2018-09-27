<?php
class SMProfilefb extends SMProfile
{
	final public function GetData()
	 {
		MSConfig::RequireFile('rest');
		$r = (new REST('https://graph.facebook.com'))->GET('/v2.2/me', ['access_token' => $this->GetToken()->GetValue(), 'fields' => 'id,name,first_name,last_name,gender,picture,birthday']);
		$data = $r->value;
		if(isset($data->error)) throw new ESMProfileErrorResponse($data->error->message, $data->error->code);
		return $this->CreateRetVal($data->id, $data->first_name, $data->last_name, trim("$data->first_name $data->last_name"), isset($data->gender) ? $this->ConvertGender($data->gender) : null, empty($data->birthday) ? null : $this->ConvertDate($data->birthday), empty($data->picture) ? null : $this->GetUserpic($data->picture));
	 }

	final protected function GetUserpic($p)
	 {
		return @$p->data->url;
	 }

	final protected function ConvertDate($date)
	 {
		$date = explode('/', $date);
		switch(count($date))
		 {
			case 1: return "$date[0]-00-00";
			case 2: return "0000-$date[0]-$date[1]";
			case 3: return "$date[2]-$date[0]-$date[1]";
		 }
	 }
}
?>
<?php
require_once(dirname(__FILE__).'/amsemailtpl.php');

class MSEmailTpl extends AMSEmailTpl
{
	final public function Run()
	 {
		$data = $this->GetData();
		$id = $this->GetId();
		if($tpl = DB::GetRowById('email_template', $id))
		 {
			$this->SetTypeId($tpl['type_id']);
			if(!$this->GetSubject()) $this->SetSubject(trim($tpl['subject']));
			$from = $this->GetFrom() ?: trim($tpl['from']);
			$this->SetText(trim($tpl['text']));
		 }
		else throw new Exception("Шаблон письма `$id` не существует.");
		$this->ProcessData($data);
		$text = $this->GetText();
		$this->ProcessTpl($data, $text);
		$subject = $this->ProcessSubj();
		$mail = new MSMail();
		foreach($this->GetFiles() as $file) $mail->AddFileContent($file['data'], $file['name']);
		if('html' == $this->GetTypeId()) $mail->SetHTMLType();
		$reply_to = $this->ProcessReplTo($mail, $data);
		if($this->UseStorage()) DB::Insert('email_fs_message', ['email_tpl_id' => $id, 'type_id' => $this->GetTypeId(), 'from' => $reply_to, 'subject' => $subject, 'text' => $text], ['date_time' => 'NOW()']);
		foreach(explode(',', str_replace(' ', '', $this->GetTo() ?: trim($tpl['to']))) as $to) $this->SendEmail($mail, $to, $from, $subject, $text, $data);
	 }
}
?>
<?php
require_once(dirname(__FILE__).'/amsemailtpl.php');

class MSEmailTplNoDB extends AMSEmailTpl
{
	final public function Run()
	 {
		$this->SetText($this->GetOption('tpl'));
		$data = $this->GetData();
		$this->ProcessData($data);
		if(!($text = $this->GetText())) throw new Exception('Не указан шаблон письма (опция `tpl`).');
		$this->ProcessTpl($data, $text);
		$subject = $this->ProcessSubj();
		$mail = new MSMail();
		foreach($this->GetFiles() as $file) $mail->AddFileContent($file['data'], $file['name']);
		if('html' == $this->GetTypeId()) $mail->SetHTMLType();
		$reply_to = $this->ProcessReplTo($mail);
		if($this->UseStorage()) throw new Exception('Storage is not implemented yet.');
		foreach(explode(',', str_replace(' ', '', $this->GetTo())) as $to) $this->SendEmail($mail, $to, $this->GetFrom(), $subject, $text, $data);
	 }
}
?>
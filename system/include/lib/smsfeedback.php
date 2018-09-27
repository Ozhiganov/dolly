<?php
class SMSFeedback extends SMS
{
	final protected function SendMessage($phone_num, $text, $from = '', array $options = NULL)
	 {
		echo (new HTTP(['basic' => $this->GetOption('auth')]))->GET("http://api.smsfeedback.ru/messages/v2/send/?phone=%2B$phone_num&text=$text");
	 }
}
?>
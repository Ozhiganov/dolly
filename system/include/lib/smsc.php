<?php
class SMSC extends SMS
{
	final protected function SendMessage($phone_num, $text, $from = '', array $options = NULL)
	 {
		if($t = $this->GetOption('type')) $t = "&$t=1";
		(new HTTP)->GET("http://smsc.ru/sys/send.php?login={$this->GetOption('login')}&psw={$this->GetOption('password')}&phones=$phone_num&charset=utf-8&mes=$text$t");
	 }
}
?>
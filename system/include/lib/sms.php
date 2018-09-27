<?php
require_once(dirname(__FILE__).'/traits.php');
class ESMS extends Exception {}
// для отправки сообщения нужно указать номер телефона (в формате 71234567890), текст, подпись (опционально)
// кроме этих трёх параметров, могут быть и другие, специфичные для api компании смс-рассылки
// лог отправки смс?

// options:
// email - email, на который будут приходить уведомления об ошибках.
// email_from - email, с которого будут приходить уведомления об ошибках (поле "от кого").
abstract class SMS
{
	use TOptions, TInstances;

	final public function __construct(array $options = null, $index = 0)
	 {
		if(self::$instances && !$index)
		 {
			$s = count(self::$instances) > 1 ? 'ами:' : 'ом';
			$s1 = '';
			foreach(self::$instances as $key => $inst) $s1 .= ($s1 ? ', ' : '')."[$key => ".get_class($inst).']';
			throw new Exception("СМС-интерфейс уже инициализирован объект$s $s1.");
		 }
		self::SetInstance($index, $this, 'SMS-interface: ');
		$this->options = $options;
		if($email = $this->GetOption('email'))
		 {
			$this->mail = new MSMail();
			$this->mail->SetTo($email);
			if($from = $this->GetOption('email_from')) $this->mail->SetFrom($from);
		 }
	 }

	abstract protected function SendMessage($phone_num, $text, $from = '', array $options = null);

	// Данная проверка принимает только 10 значные номера (9031234567) состоящие только из цифр, без скобок, дефисов и пробелов
	final public static function CheckNum($n, $code = '7')
	 {
		if(preg_match("/^{$code}[0-9]{10,10}$/", $n)) return $n;
		throw new Exception('Телефон задан в неправильном формате.');
	 }

	final public function Send($phone_num, $text, $from = '', array $options = null)
	 {
		try
		 {
			if(is_array($phone_num)) foreach($phone_num as $n) $this->SendMessage($n, $text, $from ?: $this->GetOption('from'), $options);
			else $this->SendMessage($phone_num, $text, $from ?: $this->GetOption('from'), $options);
		 }
		catch(Exception $e)
		 {
			if($this->mail)
			 {
				$host = $this->GetDecodedHost();
				$this->mail->SetSubject('SMS error — '.$host);
				$this->mail->SetText('Host: '.$host.PHP_EOL.date('Y-m-d H:i:s').PHP_EOL.($e->GetMessage() ?: 'Exception with no message.'))->Send();
			 }
			throw $e;
		 }
	 }

	final protected function GetDecodedHost()
	 {
		if(empty($_SERVER['HTTP_HOST'])) return 'undefined host';
		$idn = new idna_convert();
		return $idn->decode($_SERVER['HTTP_HOST']);
	 }

	private $mail;
}

class SMSDummy extends SMS
{
	final protected function SendMessage($phone_num, $text, $from = '', array $options = NULL)
	 {
		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/system/include/sms/'.((new DateTime())->format('Y-m-d_H-i-s_')).round(($_SERVER['REQUEST_TIME_FLOAT'] - floor($_SERVER['REQUEST_TIME_FLOAT'])) * 1000000)."__$phone_num.txt", $phone_num.PHP_EOL.PHP_EOL.$text.PHP_EOL.PHP_EOL.$from);
	 }
}
?>
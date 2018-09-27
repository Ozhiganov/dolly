<?php
/*  SMS-Agent API/PHP v1.2
 *  SEE: http://www.sms-agent.ru
 */

// ВАЖНО!  ----------------------------------------------------------------------------------------- 
// ВАЖНО! | При использовании собственной подписи она должна быть добавлена в список на странице    |
// ВАЖНО! | "Подписи" в личном кабинете (https://office.sms-agent.ru/sender.html).                  |
// ВАЖНО!  -----------------------------------------------------------------------------------------
 
// ----------------------------------------------------------------------------------------------------------
/*  Примеры использования и вызова
 
 Пример №1 (Отправка СМС):
    include('smsagent.php');
	sms_send('79034567890','Hello');

 Пример №2 (Отправка с заменой отправителя):
	include('smsagent.php');
 	if ($sms_id = sms_send("79034567890","Hello","sms-agent.ru"))
		echo 'Сообщение id#'.$sms_id.' успешно отправлено';
	else
		echo 'Ошибка: '.sms_error();
		
 Пример №3 (Проверка статуса сообщения):
	include('smsagent.php');
 	$status = sms_status('131313');
		- где "131313" номер сообщения, который возвратила функция отправки "sms_send"
		  (в примере №2 значение переменной $sms_id)
		  
	!!!!! ОПИСАНИЕ СТАТУСОВ СМС СООБЩЕНИЙ И ОШИБОК В КОНЦЕ СКРИПТА !!!!!
*/
/*

//------- Описание статусов, функция sms_status() -------//
 "0" : в ожидании
 "1" : в ожидании, в очереди у оператора
 "2" : доставлено
"-1" : не доставлено
"-2" : не принято, неправильный номер, ID не найден

//------- Описание ошибок, функция sms_send() -------//
        "invalid apikey" : передан неверный API-ключ клиента(см. https://office.sms-agent.ru/help.html?act=api)
   "invalid sender name" : неверный формат имени отправителя (см. https://office.sms-agent.ru/help.html?act=sender)
           "phone empty" : не указан номер телефона получателя
         "invalid phone" : неверный формат номера телефона получателя (правильный формат 11 цифр, пример: 79034567890)
            "text empty" : не указан текст сообщения
    "account is blocked" : аккаунт пользователя с переданным API-ключом ЗАБЛОКИРОВАН (необходимо связаться с персональным менеджером)
 "account is not active" : аккаунт пользователя с переданным API-ключом НЕАКТИВЕН (необходимо пройти процесс активации в личном кабинете)
 "not find sender names" : необходимо добавить ИМЯ ОТПРАВИТЕЛЯ в личном кабинете 	
 "not match sender name" : не найдено указанное имя отправителя (необходимо добавить ИМЯ ОТПРАВИТЕЛЯ в личном кабинете)
"it is not enough money" : недостаточно денежных средств для отправки (необходимо пополнить баланс в личном кабинете)

*/

class SMSAgent extends SMS
{
	final protected function SendMessage($phone_num, $text, $from = '', array $options = NULL)
	 {
		if($sms_id = $this->sms_send($phone_num, $text, $from)) ;// log it?
		else throw new ESMS($this->sms_error());
	 }

	final protected function sms_error($set = NULL)
	 {
		static $e;
		if($set !== NULL) return $e = $set;
		else return (isset($e)) ? $e : false;
	 }

	final protected function curl_query($postfields)
	 {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://office.sms-agent.ru/api{$this->GetOption('api_num')}.php"); 
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3); 
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$postfields);
		$result = curl_exec($ch);
		curl_close($ch);	
		return $result;
	}

	function sms_send($to, $text, $from = '')
	 {
		$result = $this->curl_query("apikey={$this->GetOption('api_key')}&act=send&from=$from&to=$to&text=$text");
		if(preg_match("/^[0-9]+$/", $result)) return $result;
		$this->sms_error($result);
		return false;
	 }

	final protected function sms_status($id) { return $this->curl_query("apikey={$this->GetOption('api_key')}&act=status&id=$id"); }
}
?>
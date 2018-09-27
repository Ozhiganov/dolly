<?php
class EMSPassword extends Exception {}
	class EMSPasswordUnsafe extends EMSPassword {}
	/* class EMSPasswordEmptyUID extends EMSPassword {}
	class EMSPasswordUnsafeUID extends EMSPassword {}
	class EMSPasswordInvalidUID extends EMSPassword {}
	class EMSPasswordEMailError extends EMSPassword {} */

class MSPassword
{
	/* final public function __construct($rel_name = null)
	 {
		$this->rel_name = $rel_name ? $rel_name : 'user_data';
	 }

	final public function QueryReset($uid)
	 {
		if(!$uid) throw new EMSPasswordEmptyUID();
		if(!preg_match('/^([a-z0-9_\-]+\.)*[a-z0-9_\-]+@([a-z0-9][a-z0-9\-]*[a-z0-9]\.)+[a-z]{2,4}$/i', $uid)) throw new EMSPasswordUnsafeUID();
		if(!Relation::Get($this->rel_name)->GetCount('uid', '`uid` = "'.mysql_real_escape_string($uid).'"')) throw new EMSPasswordInvalidUID();
		$site_name = str_replace('www.', '', $_SERVER['HTTP_HOST']);
		$site_email = 'no-reply@'.$site_name;
		$uniqid = sha1('cttC5DtEGWPHuPVcug3Izr6zh1Mec'.$uid.'ws2HeLSdNKmZPfXrgXvfz').sha1(ms::Random(60));
		if(mail($uid,
				'Восстановление пароля от личного кабинета на сайте '.$site_name,
				"Здравствуйте!<br />
Ваш адрес почты был указан в запросе на восстановление пароля от личного кабинета на сайте ".$site_name.".<br />
Чтобы получить новый пароль, пожалуйста, пройдите <a href='http://".$site_name."/reset-password/core.php?uniqid=".$uniqid."'>по этой ссылке</a>.<br />
Если вы считаете, что получили это письмо по ошибке, просто проигнорируйте его.",
				implode("\r\n", array('Content-type: text/html; charset=utf-8', 'From: '.$site_email)))) Relation::Get('reset_password')->Replace($uid, $uniqid);
		else throw new EMSPasswordEMailError();
	 }

	final public function Reset()
	 {
		if(preg_match('/^[a-z0-9_\-]{80}$/', @$_GET['uniqid']))
		 {
			if($uid = Relation::Get('reset_password')->GetFieldById(array('uniqid' => $_GET['uniqid']), 'uid'))
			 {
				$new_pass = substr(sha1(uniqid(mt_rand(0, 500000), true)), 5, 10);
				$site_name = str_replace('www.', '', $_SERVER['HTTP_HOST']);
				$site_email = 'no-reply@'.$site_name;
				if(mail($uid,
						'Новый пароль от личного кабинета на сайте '.$site_name,
						"Здравствуйте!<br />Ваш новый пароль &mdash; ".$new_pass.".",
						implode("\r\n", array('Content-type: text/html; charset=utf-8', 'From: '.$site_email))))
				 Relation::Get($this->rel_name)->Update(array('password_hash' => MSAuthenticator::Encrypt($new_pass), 'num_of_denials' => 0), '`uid` = "'.$uid.'"');
				else throw new EMSPasswordEMailError();
				Relation::Get('reset_password')->Delete('`uniqid` = "'.$_GET['uniqid'].'"');
				return true;
			 }
			else return false;
		 }
		else return false;
	 } */

	final public static function Check($str)
	 {
		$stat = ms::GetStrStat($str);
		if(self::GetMinLength() > $stat['length']) throw new EMSPasswordUnsafe($stat['length'].' символ'.Format::GetAmountStr($stat['length'], '', 'а', 'ов').' — слишком короткий пароль! Минимум — '.self::GetMinLength().' символов.');
		if(!$stat['letter'] && !$stat['other']) throw new EMSPasswordUnsafe('Нельзя использовать только цифры! Добавьте буквы или спецсимволы.');
		if($stat['count'] < ceil($stat['length'] / 2)) throw new EMSPasswordUnsafe('Слишком много повторяющихся символов! Добавьте другие буквы, цифры или спецсимволы.');
	 }

	final public static function GetHint()
	 {
		return 'Пароль должен быть не короче '.self::$min_length.' символ'.Format::GetAmountStr(self::$min_length, '', 'а', 'ов').';'.PHP_EOL.'содержать не только цифры, но также буквы или спецсимволы;'.PHP_EOL.'не содержать много повторяющихся символов.';
	 }

	final public static function SetMinLength($val) { self::$min_length = $val; }
	final public static function GetMinLength() { return self::$min_length; }

	// private $rel_name;
	private static $min_length = 8;
}
?>
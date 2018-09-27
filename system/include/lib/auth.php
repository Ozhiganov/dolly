<?php
session_start();
MSConfig::RequireFile('traits', 'datacontainer', 'msauthenticator');
class MSSMAuthenticator extends MSAuthenticator
{
	final public static function ShowResetPasswordForm($err_msg = false, $notice = false)
	 {
		$content = '<div><input type="hidden" name="__auth_action" value="reset_password" /><label for="email">Email</label><input type="email" name="email" id="email" required="required" /><input type="submit" value="Отправить" id="submit" /></div>';
		if($err_msg) $content .= "<div id='err_msg'>$err_msg</div>";
		if($notice) $content .= "<div id='notice'>$notice</div>";
		self::ShowForm('Сброс пароля', 'login_form', '<form method="post" action="'.MSConfig::GetMSSMDir().'/?__auth_action=reset_password" autocomplete="off">'.$content.'</form><script type="text/javascript">document.getElementsByName("email")[0].focus();</script>');
	 }

	final public static function ShowForm($title, $css_file_name, $body)
	 {
		header('Content-Type: text/html; charset=utf-8');
		header('Expires: Sun, 01 Jan 2006 00:00:00 GMT');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s'));
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: private, post-check=0, pre-check=0, max-age=0', false);
		header('Pragma: no-cache');
?><!DOCTYPE html><html lang="ru"><head><link rel="shortcut icon" href="/system/img/ui/keys.ico"/><meta name="viewport" content="width=device-width"/><meta name="robots" content="noindex, nofollow"/><style type="text/css">/*<![CDATA[*/<?php require_once(MSConfig::GetLibDir()."/css/$css_file_name.css"); ?>/*]]>*/</style><title><?=$title?></title></head><body><?=$body?></body></html><?php
		exit();
	 }

	final public static function HandleResetStep1($email)
	 {
		if(!$email || !preg_match('/^[a-z0-9._-]+@[a-z0-9._-]+\.[a-z]{2,10}$/', $email)) return 'Укажите правильный email!';
		$t_name = MSSMAI()->GetDataTName();
		$res = DB::Select($t_name, '*', '`email` = ?', [$email]);
		switch(count($res))
		 {
			case 0: return 'Укажите правильный email!';
			case 1:
				$user = $res->Fetch();
				if($user->banned) return 'Учётная запись заблокирована.';
				list($from, $site, $site_url, $mssm_url) = self::GetResetEmailData();
				do
				 {
					$uniqid = [microtime(), mt_rand(), $user->uid, $user->suid, $user->password_hash];
					shuffle($uniqid);
					$uniqid = hash('sha384', implode(':', $uniqid));
				 }
				while(DB::ValueExists($t_name, 'reset_password_uniqid', $uniqid));
				DB::Update($t_name, ['reset_password_uniqid' => $uniqid, '~uid' => $user->uid], '`uid` = :uid');
				(new MSMail())->SetHTMLType()->SetTo($email)->SetFrom($from)->SetSubject('Сброс пароля к сайту '.$site)->SetText('Здравствуйте!<br />
Адрес '.$email.' был указан для получения нового пароля к системе администрирования сайта <a href="'.$site_url.'">'.$site.'</a>.<br />
Если вы хотите получить новый пароль, пожалуйста, <a href="'.$mssm_url.'?__auth_action=reset_password&uniqid='.$uniqid.'">подтвердите своё решение</a>.<br />
Если же вы не запрашивали получение нового пароля, удалите это письмо.<br />
<br />
Это письмо создано автоматически. Пожалуйста, не отвечайте на него.')->Send();
				break;
			default: return 'Указанный email присвоен более, чем одному аккаунту. Автоматический сброс пароля невозможен. Обратитесь к администратору сайта.';
		 }
	 }

	final public static function HandleResetStep2($uniqid, &$mssm_url)
	 {
		$t_name = MSSMAI()->GetDataTName();
		if(!preg_match('/^[a-f0-9]{96}$/', $uniqid)) return 'Неправильный или устаревший идентификатор!';
		DB::BeginTransaction();
		$res = DB::Select($t_name, '*', '`reset_password_uniqid` = ?', [$uniqid]);
		switch(count($res))
		 {
			case 0: return 'Неправильный или устаревший идентификатор!';
			case 1:
				$user = $res->Fetch();
				if($user->banned) return 'Учётная запись заблокирована.';
				break;
			default: return 'Указанный идентификатор присвоен более, чем одному аккаунту. Укажите ваш email ещё раз.';
		 }
		$password = ms::Random(12);
		list($from, $site, $site_url, $mssm_url) = self::GetResetEmailData();
		DB::Update($t_name, ['password_hash' => MSAuthenticator::Encrypt($password), 'reset_password_uniqid' => null, '~suid' => $user->suid], '`suid` = :suid');
		(new MSMail())->SetHTMLType()->SetTo($user->email)->SetFrom($from)->SetSubject('Новый пароль к сайту '.$site)->SetText('Здравствуйте!<br />
Ваш новый пароль: '.$password.'<br />
Теперь вы можете войти в <a href="'.$mssm_url.'">систему администрирования</a> сайта.
<br />
Это письмо создано автоматически. Пожалуйста, не отвечайте на него.')->Send();
		DB::Commit();
	 }

	final protected function CheckUser() { return !$this->GetUserData('banned'); }
	final protected function OnLogOut() { ms::Redirect(MSConfig::GetMSSMDir().'/'); }

	final protected function PreCheck()
	 {
		if(($ip = MSConfig::GetIP()) && $this->UseCaptcha($ip) && ($key = Registry::GetValue('recaptcha', 'secret_key')))
		 {
			$r = (new HTTP())->POST('https://www.google.com/recaptcha/api/siteverify', ['secret' => $key, 'response' => $_POST['g-recaptcha-response'], 'remoteip' => $ip]);
			$r = json_decode($r, true);
			if(!$r['success'])
			 {
				$_SESSION['err_msg'] = 'Проверка не пройдена'.(empty($r['error-codes']) ? '' : ': '.implode(', ', array_map(function($err){
						if('missing-input-response' === $err) return 'отсутствует ответ';
						if('invalid-input-response' === $err) return 'неправильный ответ';
						return $err;
					}, $r['error-codes']))).'.';
				return false;
			 }
		 }
		return true;
	 }

	final protected function OnLogIn($forced = false)
	 {
		if($ip = MSConfig::GetIP()) DB::Delete($this->GetPrefix().'_denial', '`ip` = ?', [$ip]);
		ms::Redirect($_SERVER['REQUEST_URI']);
	 }

	final public function EHandler($func, ...$args)
	 {
		try
		 {
			$this->$func(...$args);
		 }
		catch(EAuthenticationInvalidData $e) { $this->ShowLoginForm('Неправильная пара логин-пароль.<br />Проверьте раскладку клавиатуры, нажата ли клавиша «Caps Lock» и введите логин и пароль снова.'); }// EAuthenticationUnsafeString, EAuthenticationInvalidUID, EAuthenticationInvalidPassword
		catch(EAuthenticationFailed $e) { $this->ShowLoginForm(); }
		catch(EAuthenticationInvalidSID $e) { $this->ShowLoginForm('Неправильный идентификатор сессии.<br />Пожалуйста, авторизуйтесь заново.'); }
		catch(EAuthenticationSessionExpired $e) { $this->ShowLoginForm('Сессия устарела.<br />Пожалуйста, авторизуйтесь заново.'); }
		catch(EAuthenticationUserCheckFailed $e) { $this->ShowLoginForm('Учётная запись заблокирована.'); }
		catch(EAuthenticationPreCheckFailed $e)
		 {
			if(isset($_SESSION['err_msg']))
			 {
				$msg = $_SESSION['err_msg'];
				unset($_SESSION['err_msg']);
			 }
			else $msg = 'Неправильно указаны контрольные символы.';
			$this->ShowLoginForm($msg);
		 }
		// catch(EAuthenticationDuplicateEntries $e) { $this->ShowLoginForm('Невозможно авторизоваться с помощью выбранной соцсети.'); }
	 }

	final private static function GetResetEmailData()
	 {
		$site_url = MSConfig::GetProtocol().$_SERVER['HTTP_HOST'];
		return ["reset-password@$_SERVER[HTTP_HOST]", (new idna_convert())->decode($_SERVER['HTTP_HOST']), $site_url, $site_url.MSConfig::GetMSSMDir().'/'];
	 }

	final private function UseCaptcha($ip) { return $this->GetDenial($ip)->count > 3; }

	final private function ShowLoginForm($err_msg = false)
	 {
		$ip = MSConfig::GetIP();
		$captcha = '';
		if($this->UseCaptcha($ip) && ($key = Registry::GetValue('recaptcha', 'site_key'))) $captcha = "<div class='g-recaptcha' data-sitekey='$key'></div><script type='text/javascript' src='https://www.google.com/recaptcha/api.js'></script>";
		$edt = Events::Dispatch('mssm:show_login_form', false, ['form_after' => ''], ['form_after' => ['set' => true]]);
		self::ShowForm('Вход', 'login_form', '<form method="post" action="'.$_SERVER['REQUEST_URI'].'" autocomplete="off"><div><input type="hidden" name="__auth_action" value="login"/><input type="text" id="uid" name="uid" maxlength="32" placeholder="Логин" required="required" autocapitalize="none"'.(empty($_POST['uid']) ? '' : ' value="'.Filter::TextAttribute($_POST['uid']).'"').'/><input id="password" type="password" name="password" placeholder="Пароль" required="required"/>'.$captcha.'<input type="submit" value="Войти" id="submit"/></div>'.($err_msg ? '<div id="err_msg">'.$err_msg.'</div>' : '').'</form>'.$edt->form_after.'<div id="content"><a href="?__auth_action=reset_password">Забыли пароль?</a></div><script type="text/javascript">document.getElementById("uid").focus();</script>');
	 }
}
$a = new MSSMAuthenticator(MSCfg::GetOption('msauth_prefix'), 'mssm');
function MSSMAI() { return MSAuthenticator::Instance('mssm'); }
if(!empty($_REQUEST['__auth_action']))
 {
	switch($_REQUEST['__auth_action'])
	 {
		case 'login':
			$a->SetSessionInitLength(0)->EHandler('LogIn', trim(@$_POST['uid']), trim(@$_POST['password']));
			exit();
		case 'logout':
			unset($_COOKIE[session_name()]);
			$_SESSION = [];
			session_destroy();
			$a->LogOut();
			exit();
		case 'reset_password':
			if(DB::Count(MSSMAI()->GetDataTName(), '`email` <> ""'))
			 {
				$err_msg = $notice = false;
				$from = "reset-password@$_SERVER[HTTP_HOST]";
				$site = (new idna_convert())->decode($_SERVER['HTTP_HOST']);
				$site_url = MSConfig::GetProtocol().$_SERVER['HTTP_HOST'];
				$mssm_url = $site_url.MSConfig::GetMSSMDir().'/';
				try
				 {
					if(!empty($_POST['email']))
					 {
						if($err_msg = MSSMAuthenticator::HandleResetStep1(trim($_POST['email'])));
						else $notice = 'На указанный адрес отправлено письмо для&nbsp;подтверждения запроса.';
					 }
					elseif(!empty($_GET['uniqid']))
					 {
						if($err_msg = MSSMAuthenticator::HandleResetStep2($_GET['uniqid'], $mssm_url));
						else $notice = 'На указанный адрес отправлено письмо с&nbsp;новым паролем.<br /><a href="'.$mssm_url.'">Вход</a>';
					 }
				 }
				catch(Exception $e)
				 {
					$err_msg = $e->GetMessage();
					MSConfig::LogException($e);
				 }
				MSSMAuthenticator::ShowResetPasswordForm($err_msg, $notice);// exit(); - ::ShowForm terminates script.
			 }
			else MSSMAuthenticator::ShowForm('Сброс пароля', 'reset_password', '<div id="err_msg">Автоматический сброс пароля невозможен. Обратитесь к администратору сайта.</div>');
		default: Events::Dispatch("mssm:auth_action:$_REQUEST[__auth_action]", false, []);
	 }
 }
$a->EHandler('Run');
?>
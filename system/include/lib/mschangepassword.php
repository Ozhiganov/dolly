<?php
class MSChangePassword extends MSDocument
{
	final public function __construct(array $options = null)
	 {
		$this->options = $options;
	 }

	final public function Show()
	 {
		$mid = '';
		if($this->LoginUnlocked()) $mid .= $this->MkRow('Логин', ui::Text('name', 'uid', 'value', MSSMAI()->GetUID()), 'login');
		$mid .= $this->MkRow('Новый пароль', ui::Password('name', 'new_password'), 'new_password').
				$this->MkRow('Подтвердите новый пароль', ui::Password('name', 'new_password_copy')).
				$this->MkRow('Старый пароль', ui::Password('name', 'old_password'), 'old_password');
		echo ui::Form('class', 'form', 'autocomplete', false)/* ->SetCaption('Смена логина и пароля') */->SetMiddle($mid)->SetBottom(ui::Submit('value', 'Сохранить').ui::FAction('apply_auth_data'));
	 }

	final public function Handle()
	 {
		if($this->ActionPOST() === 'apply_auth_data')
		 {
			$success = true;
			if($this->LoginUnlocked())
			 {
				try
				 {
					MSAuthenticator::CheckString($_POST['uid']);
				 }
				catch(EAuthenticationUnsafeString $e)
				 {
					$this->AddErrorMsg('В логине есть недопустимые символы! Нельзя использовать пробел, одинарные и двойные кавычки, точку с запятой и знак равенства.', 'login');
					$success = false;
				 }
			 }
			if(!empty($_POST['new_password']))
			 {
				if($_POST['new_password'] !== $_POST['new_password_copy'])
				 {
					$this->AddErrorMsg('Новый пароль и его копия не совпадают!', 'new_password');
					$success = false;
				 }
			 }
			if(!$success) return;
			if($this->LoginUnlocked())
			 {
				if(mb_strlen($_POST['uid'], 'utf-8') < 6)
				 {
					$this->AddErrorMsg('Новый логин слишком короткий! Минимальная длина &mdash; 6 символов.', 'login');
					$success = false;
				 }
			 }
			try
			 {
				MSPassword::Check($_POST['new_password']);
			 }
			catch(Exception $e)
			 {
				$this->AddErrorMsg($e->GetMessage(), 'new_password');
				$success = false;
			 }
			if(MSSMAI()->GetUID() == $_POST['new_password'])
			 {
				$this->AddErrorMsg('Пароль не может совпадать с логином!', 'new_password');
				$success = false;
			 }
			if(MSAuthenticator::Encrypt($_POST['old_password']) !== MSSMAI()->GetUserData('password_hash'))
			 {
				$this->AddErrorMsg('Старый пароль неправильный!', 'old_password');
				$success = false;
			 }
			if($success)
			 {
				$attrs = [];
				if($this->LoginUnlocked()) $attrs['uid'] = $_POST['uid'];
				if(!empty($_POST['new_password'])) $attrs['password_hash'] = MSAuthenticator::Encrypt($_POST['new_password']);
				if($attrs && MSSMAI()->Update($attrs)) $this->AddSuccessMsg('Изменения сохранены');
			 }
		 }
	 }

	final private function LoginUnlocked() { return empty($this->options['lock_login']); }

	final private function MkRow($label, $input, $err_msgs = false)
	 {
		$input->SetAttr('required', true, 'autocomplete', false);
		$msgs = $err_msgs ? $this->GetMessagesByName($err_msgs, '!form__err_msg') : '';
		if(is_array($label))
		 {
			$lbl_class = " $label[1]";
			$label = $label[0];
		 }
		else $lbl_class = '';
		return '<div class="form__row'.($msgs ? ' _error' : '').($label ? '' : ' _no_label').'">'.($label ? "<label class='form__label$lbl_class'>$label</label>" : '')."$input$msgs</div>";
	 }

	private $options;
}
?>
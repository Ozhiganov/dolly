<?php
namespace MSOAuth2\Utils;

function ConfigMSSMAuth(array $opts, array $apps)
{
	$opts['long_url'] = true;
	$opts['url'] = \MSConfig::GetMSSMDir().'/?__auth_action=msoauth2';
	$opts['e_handler'] = function($e){
		if($e instanceof \EAuthenticationInvalidKey) $msg = 'Профиль выбранной соцсети не зарегистрирован.';
		elseif($e instanceof \EAuthenticationDuplicateEntries)
		 {
			\MSConfig::HandleException($e, false);
			$msg = 'Невозможно авторизоваться с помощью выбранной соцсети.';
		 }
		else
		 {
			\MSConfig::HandleException($e, false);
			$msg = $e->GetMessage();
		 }
		\MsgQueue::Get('mssm_oauth2')->AddError($msg);
	};
	$opts['on_auth'] = function($token){
		\MSConfig::RequireFile('smprofile');
		$data = \SMProfile::Create($token)->GetData();
		MSSMAI()->ForceLogIn($data['id'], "sm_{$token->GetType()}_id");
	};
	\MSOAuth2::Config($opts, $apps, 'mssm');
	\Events::BindTo('mssm:auth_action:msoauth2', function(\EventData $edt){\MSOAuth2::Run('mssm');});
	\Events::BindTo('mssm:show_login_form', function(\EventData $edt){
		$html = '';
		foreach(\MSOAuth2::GetButtons('mssm') as $b) $html .= "<a href='$b->href' data-type='$b->type' class='msoauth2__button' rel='nofollow'>$b->title</a>";
		$q = \MsgQueue::Get('mssm_oauth2');
		if($q->HasErrors())
		 {
			$html .= '<div id="err_msg">';
			while($msg = $q->Fetch()) $html .= "<div>$msg</div>";
			$html .= '</div>';
		 }
		$edt->form_after .= "<div class='msoauth2'><div class='msoauth2__title'>Войти через соцсеть</div>$html</div>";
	});
}
?>
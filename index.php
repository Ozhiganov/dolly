<?php
define('DOCUMENT_ROOT', substr(__FILE__, 0, -10));
define('MSSE_INC_DIR', DOCUMENT_ROOT.'/system/include');
define('MSSE_LIB_DIR', MSSE_INC_DIR.'/lib');
error_reporting(0);
ini_set('display_errors', 'Off');
ini_set('display_startup_errors', 'Off');
@set_time_limit(180);
@session_start();
if(!defined('CURLPROXY_SOCKS4A')) define('CURLPROXY_SOCKS4A', 6);
if(!defined('CURLPROXY_SOCKS5_HOSTNAME')) define('CURLPROXY_SOCKS5_HOSTNAME', 7);
require_once(DOCUMENT_ROOT.'/lib/Settings.php');
if(isset($_GET['__dolly_action']) && 'set_lang' === $_GET['__dolly_action'])
 {
	$_SESSION['language'] = $_GET['lang'];
	$settings = new Settings();
	$settings->set('language', $_GET['lang'])->save();
	header('Location: http'.(empty($_SERVER['HTTPS']) || 'off' === $_SERVER['HTTPS'] ? '' : 's')."://$_SERVER[HTTP_HOST]/", true, 302);
	exit();
 }

function RunDollySites($installed)
{
	require_once(MSSE_INC_DIR.'/sys_config.php');
	MSConfig::RequireFile('traits', 'datacontainer', 'filesystemstorage', 'l10n', 'tsystemmessages');
	$lang = Settings::staticGet('language');
	new L10N($lang ? $lang : 'ru', 'ru', array('root' => DOCUMENT_ROOT, 'dir' => '/languages'));
	require_once(DOCUMENT_ROOT.'/lib/IndexController.php');
	$prefix = 'controller.';
	$action = false;
	if(!empty($_REQUEST['__dolly_action']) && 0 === strpos($_REQUEST['__dolly_action'], $prefix) && ($a = substr($_REQUEST['__dolly_action'], strlen($prefix))))
	 {
		$controllers = array('parse' => 2, 'get_encoding' => 1);
		if(isset($controllers[$a]) && ($i = 2 * isset($_POST['__dolly_action']) + isset($_GET['__dolly_action'])) && ($i & $controllers[$a]))
		 {
			$action = $a;
			unset($_GET['__dolly_action']);
			unset($_POST['__dolly_action']);
		 }
		else HTTP::Status(400);
	 }
	if($installed)
	 {
		if(isset($_GET['dollyeditor'])) $_GET['__dolly_action'] = 'editor';
		if(!empty($_GET['__dolly_action'])) require_once(MSSE_INC_DIR.'/actions.php');
	 }
	Controllers::setCharset('UTF-8');
	$obj = new IndexController();
	if($action)
	 {
		$obj->{$action}();
		exit;
	 }
	return $obj;
}

if(Settings::staticGet('base_url'))
 {
	$obj = RunDollySites(true);
	$obj->default_action();
	exit;
 }

require_once(MSSE_INC_DIR.'/lib/mspreinstallcheckmanager.php');

class PreInstallCheckManager extends MSPreInstallCheckManager
{
	final function __construct()
	 {
		$this->SetChecksMeta(array('php:version' => 'PHPVersionCheck', /* 'php:extensions' => 'PHPExtensionsCheck', 'apache:modules' => 'ApacheModulesCheck' */));
	 }

	protected function OnFail($id, stdClass $r)
	 {
		require_once(DOCUMENT_ROOT.'/lib/php52_l10n.php');
		switch($id)
		 {
			case 'php:version':
				if(-1 === $r->result) $r->message = php52_l10n()->php_ver_lower($r->val, $r->min);
				if(1 === $r->result) $r->message = php52_l10n()->php_ver_higher($r->val, $r->max);
				break;
			case 'php:extensions':
				$r->message = php52_l10n()->php_extensions_missing(implode(', ', $r->items));
				break;
			case 'apache:modules':
				$r->message = php52_l10n()->apache_modules_missing(implode(', ', $r->items));
				break;
			default:
		 }
	 }
}

$obj = new PreInstallCheckManager();
$obj->SetData('php:version', '5.6.0', '50.0.0');
// $obj->SetData('php:extensions', array('dom', 'mbstring', 'reflection'));
// $obj->SetData('apache:modules', array('mod_headers', 'mod_rewrite'));
try
 {
	$r = $obj->Run();
	$obj = RunDollySites(false);
	$obj->startAuth();
	$obj->start_dolly(true === $r ? null : $r);
 }
catch(EPreInstallCheckFailed $e)
 {
	$message = $e->GetMessage();
	require_once(DOCUMENT_ROOT.'/dolly_templates/no_install.php');
 }
exit;
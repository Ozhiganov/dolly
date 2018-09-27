<?php
require_once 'Parser.php';
require_once 'simple_html_dom.php';
require_once 'Settings.php';
error_reporting(E_ALL);
if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

abstract class Controllers
{
	const VERSION = '1.7.5';
	const DEFAULT_CHARSET = 'utf-8';
	const API_URL = 'https://localhost.pl/system/api.php';

	protected $_parser;
	public $_settings;
	protected $_api;

    public function __construct()
    {
        $settings = new Settings();
        $this->_parser = new Parser();
        $this->_settings = $settings;
        $this->_api = new ServerApiClient();
    }

    public static function getApiKey()
    {
        return '21232f297a57a5a743894a0e4a801fc3';
    }

    public static function setCharset($charset = null)
    {
        if(!$charset) $charset = self::DEFAULT_CHARSET;
        header('Content-Type: text/html; charset=' . $charset, true);
    }

	public static function GetAdminMenu()
	 {
		return array(
			'dashboard' => l10n()->dashboard,
			// 'editor' => l10n()->file_editor,
			'forms_handler' => l10n()->forms_handler,
			'scripts' => l10n()->scripts,
			'replaces' => l10n()->text_replacements,
			'content' => l10n()->content,
			'images' => l10n()->images,
			'proxy' => l10n()->proxy,
			'auth_info' => l10n()->auth_prefs,
		);
	 }

    protected function _auth($user, $password, $to = null, $redirect = true)
    {
        $authData = explode('|', trim(file_get_contents('login.ini')));
        if ($user == $authData[0] AND $password == $authData[1]) {
            $this->_setAuthCookies($authData);
            if ($redirect) {
                Controllers::redirect($to ?: '/admin.php');
            }
            return true;
        }
        return false;
    }

    /**
     * @param $authData
     */
    protected function _setAuthCookies($authData)
    {
        $hour = 3600;
        $day = $hour * 24;
        $year = $day * 364;

        $setCookieTime = $year * 2;
        setcookie('auth', md5($authData[0] . $_SERVER['HTTP_HOST']), time() + $setCookieTime);
    }

    public static function isAdmin()
    {
        $authData = explode('|', trim(file_get_contents('login.ini')));
        return (isset($_COOKIE['auth']) AND $_COOKIE['auth'] == md5($authData[0] . $_SERVER['HTTP_HOST']));
    }

    public static function redirect($to)
    {
        header("Location: {$to}", true);
    }

    public function returnSavedPage($page = null, $echo = true, $iconv = false)
    {
        if (!$page) {
            $page = "./{$this->_parser->cacheDir()}/index.html";
        } else {
            $page = urldecode($page) . (($this->_parser->equals->needAddHtmlToFileName('text/html', $page)) ? '.html' : '');
        }
        $return = CacheBackend::getFile($page);
        if (!$echo) {
            return $return;
        }
        echo $return;
    }

    public function saveCacheSettings()
    {
        $settings = new Settings();

        @file_put_contents('notCacheUrls', $_POST['not_cached']);

        return $settings;
    }


}
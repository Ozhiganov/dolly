<?php
require_once(DOCUMENT_ROOT.'/Constants.php');
require_once(DOCUMENT_ROOT.'/lib/Controllers.php');

class IndexController extends Controllers
{
	use TSystemMessages;

	public function get_encoding()
	 {
		try
		 {
			$r = $this->_parser->getEncode(trim($_GET['url']));
			self::SendJSON($r, '');
		 }
		catch(EHTTP $e)
		 {
			self::SendJSON(['error_code' => $e->GetCode()], $e->GetMessage(), false);
		 }
	 }

	public function default_action() { $this->_parser->parser(@$_SERVER['REQUEST_URI']); }

	public function start_dolly(array $status = null)
	 {
		$this->_settings->set('baseDir', dirname(__FILE__))->save();
		$this->_parser->getHtaccess();
		$i_mysql_host = html::Text('class', 'input', 'id', 'dbhost', 'placeholder', 'localhost', 'name', 'dbhost', 'value', Settings::staticGet('dbHost') ?: 'localhost', 'autocomplete', false);
		$i_mysql_dbname = html::Text('class', 'input', 'required', true, 'id', 'dbname', 'name', 'dbname', 'value', Settings::staticGet('dbName'), 'autocomplete', false);
		$i_mysql_username = html::Text('class', 'input', 'required', true, 'id', 'dbusername', 'name', 'dbuser', 'value', Settings::staticGet('dbUser'), 'autocomplete', false);
		$i_mysql_password = html::Text('class', 'input', 'required', true, 'id', 'dbpassword', 'name', 'dbpassword', 'value', Settings::staticGet('dbPassword'), 'autocomplete', false);
		include 'dolly_templates/view_new.php';
	 }

	public function parse()
	{
		if(!$this->isAdmin()) Controllers::redirect('admin.php?action=login');
		if($_POST['main'] === 'true')
		 {
			$data = $_POST;
			$data['language'] = Settings::staticGet('language') ?: (isset($_SESSION['language']) ? $_SESSION['language'] : 'ru');
			if(strpos($data['url'], '//') === false) $data['url'] = "http://$data[url]";
			$url = $this->_api->getBaseUrl($data);
			if($url instanceof SystemMessage) $url->SendJSON();
			$data['ip'] = $_SERVER['REMOTE_ADDR'];
			$data['cacheBackend'] = $data['cache_adapter'];
			$conf = $this->_api->getConfigFile($data);
			if($conf instanceof SystemMessage) $conf->SendJSON();
			$conf_file = 'config.ini';
			try
			 {
				$settings = $this->saveCacheSettings();
				$settings->set('cacheBackend', $_POST['cache_adapter'])
						 ->set('cacheLimitType', $_POST['cache_limit_type'])
						 ->save();
				CacheBackend::install();
				file_put_contents($conf_file, $conf);
				$this->_parser->loadMainPage($url);
				self::SendJSON([]);
			 }
			catch(Exception $e)
			 {
				self::SendJSON(['error_code' => $e->GetCode()], $e->GetMessage(), false);
			 }
		 }
	}

    // private function _getPagePath($page = null)
    // {
        // $page = trim($page) ?: @$_POST['page'];
        // $page = $this->_parser->paths()->clearStartSlash($page);
        // $pageUrl = $page;
        // $page = $this->_parser->paths()->handleEndSlashInPath($page);
        // if (!$page) {
            // $page = 'index';
        // }
        // return array($this->_parser->paths()->replaceSpecialChars(urldecode($page)),
                     // $this->_parser->paths()->replaceSpecialChars(urldecode($pageUrl), true));
    // }

    public function save_page()
    {
        $path = $this->_parser->paths()->handleEndSlashInPath(trim($_POST['path'])) . '.html';
        $this->_parser->editorSavePage($_POST['page'], $path);
        Controllers::redirect('/?action=parse');
    }

    public function startAuth()
    {
        if (!$this->isAdmin() AND !is_dir($this->_parser->cacheDir() . '/')) {
            $this->_auth('admin', 'admin', null, false);
        }
    }

    // public function parser()
    // {
        // list($page, $pageUrl) = $this->_getPagePath($_SERVER['REQUEST_URI']);
        // $page = $this->_parser->_createDirsAndGetFileName($page);
        // $baseDomain = $this->_parser->getBaseDomainArray($pageUrl);
        // $pageUrl = $this->handleSubDomains($pageUrl, $baseDomain);
        // $pageUrl = $this->handleOutDomains($pageUrl);
        // if ($this->_parser->equals->fileNotSaved($page)) {
            // $this->parsePage($pageUrl, $page);
        // } else {
            // $this->returnSavedPageNew($page);
        // }
    // }

    // private function handleSubDomains($pageUrl, $baseDomain)
    // {
        // if (stripos(' ' . $pageUrl, 's__')) {
            // list($pageUrl) = $this->_parser->_getPageUrlForSubdomains($pageUrl, $baseDomain);
            // return $pageUrl;
        // }
        // return $pageUrl;
    // }

    // private function handleOutDomains($pageUrl)
    // {
        // if (strpos(" {$pageUrl}", 'o__')) {
            // $pageUrl = str_replace('o__', 'http://', $pageUrl);
            // return $pageUrl;
        // }
        // return $pageUrl;
    // }

    // private function parsePage($pageUrl, $page)
    // {
        // $client = new HttpClient();
        // if ($this->_parser->equals->_isRelativePath($pageUrl)) {
            // $file = $client->get(urldecode($this->_settings->get('base_url') . $pageUrl));
        // } else {
            // $file = $client->get($pageUrl);
        // }
        // $type = $client->getMime();
        // $charset = $this->getSiteCharset();
        // $page = urldecode($page);
        // if ($type !== 'text/html') {
            // $this->saveAndPrintFile($page, $file, $type);
        // } else {
            // $this->parseAndPrintPage($page, $charset, $file, $client, $type);
        // }
        // @header('refresh:0');
    // }

    // private function getSiteCharset()
    // {
        // $charset = Settings::staticGet('charset');
        // if (!$charset) {
            // $charset = $_POST['charset_site'] ?: 'utf-8';
            // return $charset;
        // }
        // return $charset;
    // }

    // private function saveAndPrintFile($page, $file, $type)
    // {
        // file_put_contents(urldecode($this->_parser->paths()->replaceSpecialChars($page)), $file);
        // header("Content-type: {$type}", true);
        // echo $file;
    // }

    // private function returnSavedPageNew($page)
    // {
        // if (file_exists(urldecode($page))) {
            // @$type = mime_content_type(urldecode($page));
            // if ($type == 'text/plain' OR $type == 'text/x-asm' OR strpos(' ' . $type, 'x-c')) {
                // $type = 'text/css';
            // }
            // if (stripos(urldecode($page), '.ttf') OR stripos(urldecode($page), '.woff')) {
                // $type = 'font/opentype';
            // }
            // header("Content-type: {$type}");
            // echo trim(file_get_contents(urldecode($page)));
        // } else {
            // $this->returnSavedPage($page);
        // }
    // }

    public function getVersion()
    {
        return $this->_api->getVersion();
    }
}
?>
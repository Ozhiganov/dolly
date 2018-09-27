<?php
interface CacheBackendInterface
{
	public function fileExists($fileName);
	public function saveFile($fileName, $data, $mimeType = null, $url = null);
	public function getFile($fileName);
	public function createDir($path);
	public function getPages();
	public function updateFile($fileName, $data);
	public function clearCache($dir = null, $type = 'all');
	public function install();
	public static function PreInstallationCheck();
}

interface TranslateAdapter
{
    public function setSource();

    public function setTarget();

    public function translate($string);
}

class PluginsInstaller
{
    public static function install($name)
    {
        $api = new ServerApiClient();
        $ext = Updater::getArchiveExtension();

        $api->getPlugin($name, $ext);

        $plugins = @file_get_contents('plugins.json');
        $plugins = @json_decode($plugins, true);

        $plugin = @json_decode(file_get_contents("/plugins/{$name}/manifest.json"));

        $plugins[] = array('name' => $plugin->name,
            'title' => $plugin->title);

        @file_put_contents('plugins.json', json_encode($plugins, JSON_PRETTY_PRINT));
    }

    public static function uninstall($name)
    {
        $plugins = @file_get_contents('plugins.json');
        $plugins = @json_decode($plugins);

        foreach ($plugins as $key => $value) {
            if ($value->name === $name) {
                unlink($plugins[$key]);
                break;
            }
        }

        OtherFunctions::removeDir("./plugins/{$name}");

        @file_put_contents('plugins.json', json_encode($plugins, JSON_PRETTY_PRINT));
    }
}

class PluginsContaier
{
    private $_pageUrl;
    private $_pageName;
    private $_subDomain;

    private $_plugins = array();

    public function setParams($pageUrl, $pageName, $subDomain)
    {
        $this->_pageUrl = $pageUrl;
        $this->_pageName = $pageName;
        $this->_subDomain = $subDomain;

        return $this;
    }

    public function addPlugin(Plugin $plugin)
    {
        $this->_plugins[] = $plugin;

        return $this;
    }

    public function run(simple_html_dom &$dom)
    {
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->setParams($this->_pageUrl, $this->_pageName, $this->_subDomain)
                    ->run($dom);
            } catch (Exception $e) {
            }
        }

        return $this;
    }
}

abstract class Plugin
{
	protected $parser;
    protected $_pageUrl;
    protected $_pageName;
    protected $_baseDomain;
    protected $_subDomain;
    protected $_url;

    public function setParams($pageUrl, $pageName, $subDomain)
    {
        $this->_pageUrl = $pageUrl;
        $this->_pageName = $pageName;
        $this->_baseDomain = Paths::getBaseDomainArray($pageUrl);
        $this->_subDomain = $subDomain;
        $this->_url = trim(Settings::staticGet('base_url'));

        return $this;
    }

	public function __construct(Parser $parser)
	 {
		$this->parser = $parser;
	 }

    abstract public function run(simple_html_dom &$dom);
}

class ReplaceDonorsDomain extends Plugin
{
    public function run(simple_html_dom &$dom)
    {
        $str = $dom;
        $domain = Settings::staticGet('base_url');
        $domain = parse_url($domain, PHP_URL_HOST);

        $scriptDomain = (new Settings())->get('script_url');
        $scriptDomain = parse_url($scriptDomain, PHP_URL_HOST);


        $re = '/(\/\/)' . $domain . '/iU';
        $subst = "//{$scriptDomain}";
        $str = preg_replace($re, $subst, $str);

        $re = '/([^\/])' . $domain . '/iU';
        $subst = "\${1}" . $scriptDomain;
        $str = preg_replace($re, $subst, $str);

        $str = str_replace("www.{$scriptDomain}", $scriptDomain, $str);

        $dom->load($str);
    }
}

class ParseCssPlugin extends Plugin
{
    private $_fileContent = '';

    public function run(simple_html_dom &$dom)
    {
        $this->_fileContent = (string)$dom;
        $matches = $this->_getFilesInCss($this->_fileContent);
        $file = $this->_downloadFilesInCss($matches);

        $dom = Parser::_loadSimpleHtmlDom($file);
    }

    public function _getFilesInCss($content)
    {
        $re = "/[url]\([\"|\']*(.+)[\"|\']*\)/Uim";
        preg_match_all($re, $content, $matches);

        return $matches;
    }

    public function _downloadFilesInCss($matches)
    {
        $file = str_replace(Settings::staticGet('base_url'), '/', $this->_fileContent);

        foreach ($matches[1] as $url) {
            $url = str_replace(array('\'',
                '"'), '', $url);

            if (ParserEquals::isNotIgnoredFiles($url)) {
                continue;
            }

            $isFile = (strpos($url, ';') === false OR
                strpos($url, ')') === false OR
                strpos($url, ' ') === false OR
                strpos($url, ',') === false OR
                strpos($url, '{') === false OR
                strpos($url, '}') === false);
            if ($isFile) {
                $file = $this->_replaceFilesPathInCssFiles($url, $file);
            }
        }
        $file = str_replace('_replaced_', '', $file);
        return $file;
    }

    /**
     * @param $url
     * @param $file
     * @return string
     */
    private function _replaceFilesPathInCssFiles($url, $file)
    {
        if (ParserEquals::isSubdomain(Paths::getBaseDomainArray($url),
            Paths::getBaseDomainArray(Settings::staticGet('base_url')),
            $url)
        ) {
            $file = $this->handleDownloadedCssFromSubDomain($file, $url);
        } else if (ParserEquals::isRelativePath($url)) {
            if ($url{0} !== '/') {
                $url = "/{$url}";
            }
            $file = $this->_handleDownloadedCssFromRelativePath($file, $this->_pageName, $url);
        } else {
            if (Settings::staticGet('otherImg')) {
                $file = str_replace($url, Paths::getSitesFilesUrlForSubdomain($url), $file);
            }
        }
        return $file;
    }

    private function handleDownloadedCssFromSubDomain($file, $url)
    {
        $file = str_replace($url, Paths::subdomainPath(Paths::getBaseDomainArray($url), $url), $file);
        return $file;
    }


    private function _handleDownloadedCssFromRelativePath($file, $href, $url)
    {
        if (strpos(" {$url}", '../')) {
            return $this->_handleCssWithUpLevelFile($file, $href, $url);
        } else {
            return $this->_handleCssWithThisLevelFile($file, $href, $url);
        }
    }

    private function _handleCssWithUpLevelFile($file, $href, $url)
    {
        $hrefArray = $this->deleteDirsInPathForUpLevelCssFile($href, $url);
        $newPath = $this->getNewPathForUpLevelCssFile($url, $hrefArray);

        $file = str_replace($url, $newPath, $file);
        return $file;
    }

    /**
     * @param $href
     * @param $url
     * @return array
     */
    private function deleteDirsInPathForUpLevelCssFile($href, $url)
    {
        $upLevelsCount = substr_count($url, '../');

        $hrefNewPathOnly = '/' . str_replace(Settings::staticGet('base_url'), '', $href);
        $hrefArray = explode('/', $hrefNewPathOnly);
        unset($hrefArray[sizeof($hrefArray) - 1]);

        for ($i = 0; $i < $upLevelsCount; ++$i) {
            unset($hrefArray[sizeof($hrefArray) - 1]);
        }
        return $hrefArray;
    }

    /**
     * @param $url
     * @param $hrefArray
     * @return mixed|string
     */
    private function getNewPathForUpLevelCssFile($url, $hrefArray)
    {
        $hrefNew = implode('/', $hrefArray);
        $tempUrl = str_replace('../', '', $url);
        $tempUrl = "{$hrefNew}/{$tempUrl}";
        return $tempUrl;
    }

    private function _handleCssWithThisLevelFile($file, $href, $url)
    {
        if ($url{0} !== '/') {
            $hrefArray = $this->deleteFileNameForThisLevelCssFile($href);
            $newPath = $this->getNewPathForThisLevelCssFile($url, $hrefArray);

            $file = str_replace($url, $newPath, $file);
            return $file;
        }
        return $file;
    }

    /**
     * @param $href
     * @return array
     */
    private function deleteFileNameForThisLevelCssFile($href)
    {
        $hrefNewPathOnly = '/' . str_replace(Settings::staticGet('base_url'), '', $href);
        $hrefArray = explode('/', $hrefNewPathOnly);
        unset($hrefArray[sizeof($hrefArray) - 1]);
        return $hrefArray;
    }

    /**
     * @param $url
     * @param $hrefArray
     * @return string
     */
    private function getNewPathForThisLevelCssFile($url, $hrefArray)
    {
        $replacedUrl = substr($url, 0, 4);
        $replacedUrl .= '_replaced_';
        $replacedUrl .= substr($url, 4);

        $newUrl = implode('/', $hrefArray);
        $newUrl = "{$newUrl}/{$replacedUrl}";
        return $newUrl;
    }


}

class ParseFiles extends Plugin
{
    public function run(simple_html_dom &$dom)
    {
        $this->_parseCssFiesAndReplaceLinks($dom);
        $this->_parseScriptsFiesAndReplaceLinks($dom);
        $this->_replaceImagesSrcUrls($dom);
        $dom->save();

    }

	protected function _parseCssFiesAndReplaceLinks(&$page)
	{
		$css = $page->find('link[rel="stylesheet"]');
		foreach ($css as $style) {
			if (!$style->href) {
				continue;
			}
			$old = $style->href;
			@$return = $this->_parseFile($style->href, $old, 'css');
			if (ParserEquals::isRelativePath($return[0])) {

				$href = $this->_getNewFileNameFromFilesWithExtension($return[0], 'css');
				$style->href = $href;
			} else {
				$style->href = $return[0];
			}
			// $style->href = str_replace('./', '/', $style->href);
			$style->type = 'text/css';
		}
	}

    public function _parseFile(&$href, &$old, $type = 'css')
    {
        $domain = parse_url($href, PHP_URL_HOST);
        $domain = explode('.', $domain);
        $urlHasEndSlash = ($this->_url{strlen($this->_url) - 1} === '/');
        $baseUrl = ($urlHasEndSlash) ? substr($this->_url, 0, -1) : $this->_url;
        $baseDomain = parse_url($this->_url, PHP_URL_HOST);
        $baseDomain = explode('.', $baseDomain);
        $old = $href;
        if (ParserEquals::isSubdomain($domain, $baseDomain, $href)) {
            $href = Paths::subdomainPath($domain, $href);
        } else if (strpos($href, $baseUrl) === 0) {
            $href = Paths::rusToLat(str_replace($baseUrl, '', $href));
        } else if (strpos($href, 'http://') === false and strpos($href, 'https://') === false) {
            if ($this->_subDomain) {
                $tempUrl = explode('//', $baseUrl);
                $domainHasWww = (strpos($tempUrl[1], 'www') === 0 AND stripos($baseUrl, 'www') === false);
                $tempUrl[1] = ($domainHasWww) ? str_replace('www.', substr($this->_subDomain, 4) . '.', $tempUrl[1])
                    : substr($this->_subDomain, 4) . '.' . $tempUrl[1];
                $old = "{$tempUrl[0]}//{$tempUrl[1]}{$href}";
            } else {
                $old = $baseUrl . '/' . $href;
            }
            $href = $this->_subDomain . Paths::rusToLat($href);
            $href = ($href{0} === '.' and strpos($href, '../') === false) ? substr($href, 1) : $href;
        } else {
            if (strpos($href, '//') === 0) {
                $old = 'http:' . $href;
                $href = Paths::rusToLat($old);
            }
            if (!$this->_subDomain AND ParserEquals::isNotGoogleFiles($href)) {
                if (Settings::staticGet('otherCss') AND in_array($type, array('css',
                        'js'))
                ) {
                    $href = Paths::getSitesFilesUrlForSubdomain($href);
                }
                if (Settings::staticGet('otherImg') AND $type == 'img') {
                    $href = Paths::getSitesFilesUrlForSubdomain($href);
                }
            }
        }

        return array($href,
            Paths::clearStartSlash($old));
    }

    protected function _getNewFileNameFromFilesWithExtension($oldHref, $extension)
    {
        $href = Paths::replaceSpecialChars($oldHref);
        $href = trim($href);

        $extensionInHref = (strrpos($href, ".{$extension}") > 0);
        $extensionInHrefEnd = (strpos($href, ".{$extension}") == (strlen($href) - (strlen($extension) + 1)));
        $fileHasExtension = ($extensionInHref AND $extensionInHrefEnd);
        $href = ($fileHasExtension) ? $href : "{$href}.{$extension}";

        return $href;
    }

    protected function _parseScriptsFiesAndReplaceLinks(&$page)
    {
        $js = $page->find('script');
        foreach ($js as $script) {

			if(Settings::staticGet('cacheBackend') == 'File')
			{
				$scr = $script->outertext;
				$fileName = Parser::$CACHE_DIR . '/_scripts';
				if (!file_exists($fileName)) {
					@file_put_contents($fileName, ' ');
				}
				$file = @file_get_contents($fileName);

				if (strpos($file, $scr) === false) {
					@file_put_contents($fileName, PHP_EOL . $scr . '#D_END_SCRIPTS#', FILE_APPEND);
				}
            }
			if (!$script->src) {
				continue;
			}
            $old = $script->src;
            @$return = $this->_parseFile($script->src, $old, 'js');
            if (ParserEquals::isRelativePath($return[0])) {
                $script->src = $this->_getNewFileNameFromFilesWithExtension($return[0], 'js');
            } else {
                continue;
            }

        }
    }

    protected function _replaceImagesSrcUrls(&$page)
    {
        $images = $page->find('img');
        foreach ($images as $image) {
            if (!$image->src) {
                continue;
            }
            $old = $image->src;
            @$return = $this->_parseFile($image->src, $old, 'img');
            if (ParserEquals::isRelativePath($return[0])) {
                $image->src = $return[0];
            } else {
                continue;
            }
        }
        return $page;
    }
}

class ReplaceLinks extends Plugin
{
    public function run(simple_html_dom &$dom)
    {
        $links = $dom->find('a');
        foreach ($links as $a) {
            $notHandledHref = (!isset($a->href) OR
                strpos($a->href, 'mailto:') === 0 OR
                strpos($a->href, 'javascript:') === 0 OR
                strpos($a->href, 'skype:') === 0);
            if ($notHandledHref) {
                continue;
            }
            $domain = $this->getDomainsArray($a);
            $this->replaceLinksHref($this->_baseDomain, $this->_subDomain, $domain, $a);
        }
    }

    /**
     * @param $a
     * @return array|mixed
     */
    private function getDomainsArray($a)
    {
        $domain = parse_url($a->href, PHP_URL_HOST);
        $domain = explode('.', $domain);
        return $domain;
    }

    /**
     * @param $baseDomain
     * @param $subDomain
     * @param $domain
     * @param $a
     */
    public function replaceLinksHref($baseDomain, $subDomain, $domain, &$a)
    {
        $url = $this->deleteProtocolAndFileNameFromUrl();
        if ($url == '/') {
            $url = '';
        }
        if (ParserEquals::isSubdomain($domain, $baseDomain, $a->href)) {
            $a->href = Paths::subdomainPath($domain, $a->href);
        } else if (ParserEquals::isRelativePath($a->href)) {
            if (@$a->href{0} !== '/' AND @$a->href{0} !== '#' and $url !== '/') {
                $a->href = "{$a->href}";
            }
            $a->href = $subDomain . $a->href;
        } else {

            $linkHasNotStartSlash = (@$a->href{0} !== '/');

            if ($linkHasNotStartSlash) {
                $this->deleteBaseUrlFromLink($a);
                $a->href = "{$a->href}";
            }
        }
    }


    /**
     * @return array|string
     */
    private function deleteProtocolAndFileNameFromUrl()
    {
        $url = substr($this->_pageUrl, strpos($this->_pageUrl, '//') + 2);
        $url = explode('/', $url);
        unset($url[0]);
        unset($url[sizeof($url)]);
        $url = implode('/', $url);
        $url = "/{$url}";
        return $url;
    }

    /**
     * @param $a
     */
    private function deleteBaseUrlFromLink(&$a)
    {
        $host = '/';
        $urlWithoutWww = str_replace('www.', '', $this->_url);
        $urlWithoutHttps = str_replace(array('https://www.',
            'https://'), '', $this->_url);
        $urlWithHttps = str_replace('http://', 'https://', $this->_url);
        $urlWithHttp = str_replace('https://', 'http://', $this->_url);


        $a->href = str_replace($urlWithHttps, $host, $a->href);
        $a->href = str_replace($urlWithHttp, $host, $a->href);
        $a->href = str_replace($urlWithoutHttps, $host, $a->href);
        $a->href = str_replace($urlWithoutWww, $host, $a->href);
        $a->href = str_replace($this->_url, $host, $a->href);
    }


}

class ReplaceBaseLinks extends Plugin
{
    public function run(simple_html_dom &$dom)
    {
        $this->_addBaseHref($dom);
        $this->_addCanonical($dom);
    }

    private function _addBaseHref(&$dom)
    {
        $base = @$dom->find('base', 0);
        $this->_replaceLink($base);

    }

    /**
     * @param $link
     */
    private function _replaceLink($link)
    {
        if ($link) {
            $url = Settings::staticGet('donor_url');
            if (@$url{strlen($url) - 1} == '/') {
                $url = @substr($url, 0, -1);
            }
            $urlWithHttps = str_replace('http://', 'https://', $url);

            $link->href = str_replace(array($url,
                $urlWithHttps), "http://{$_SERVER['SERVER_NAME']}", $link->href);
        }
    }

    private function _addCanonical(&$dom)
    {
        $link = @$dom->find("link[rel='canonical']", 0);
        $this->_replaceLink($link);
    }


}


class Parser
{
    const PAGES_FILE = 'dolly_pages';
    public static $CACHE_DIR = 'd-site';
    public static $files = array('pdf',
        'jpeg',
        'jpg',
        'png',
        'gif',
        'avi',
        'mp4',
        'flw',
        'swf',
        'css',
        'js',
        'ico',
        'ttf',
        'eot',
        'svg',
        'woff');
    public $equals = '';
    public $_cacheDir = 'd-site';
    public $_page = '';
    public $_url = '';
    public $_domParser = null;
    public $_mainUrl = '';
    public $_urlInfo = array();
    public $_baseDir;
    public $_loadOtherDomains = false;
    protected $_domObject;
    protected $_pageUrl;
    protected $_pageName;
    private $_pathsClass;
    private static $_badParams = array('utm_source',
        'utm_medium',
        'gclid',
        'utm_campaing',
        'yclid',
        'utm_term',
        'utm_content',
        'utm_campaign',
        'dollyeditor',
		'__dolly_action');
    private $_fileContent;
    private $_siteCharset;
    private $_fileMimeType;
    private $_host;

	private $http;

	final public function GetURLTransform(...$hosts)
	 {
		$c = ['scheme' => MSConfig::GetProtocol(''), 'host' => $_SERVER['HTTP_HOST']];
		$url = new MSURL(Settings::staticGet('base_url'));
		$conf = [$url->host => $c];
		if($host = MSURL::ToggleSubdomain('www', $url->host)) $conf[$host] = $c;
		foreach($hosts as $host) $conf[$host] = $c;
		return new MSURLTransform($conf, $_SERVER['HTTP_HOST']);
	 }

    public function __construct()
    {
        $this->_domParser = new simple_html_dom();
        $this->equals = new ParserEquals();
		$o = ['accept_encoding' => 'gzip,deflate', 'follow_location' => 10, 'user_agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36', 'cookie_file' => []];
		$proxy = new FileSystemStorage('/storage/proxyservers.php', ['readonly' => true, 'root' => MSSE_INC_DIR]);
		if(count($proxy)) $o['proxy'] = $proxy;
		foreach(['write', 'read'] as $i => $s)
		 {
			$o['cookie_file'][$i] = (string)Settings::staticGet("http_cookies_$s");
			if('' !== $o['cookie_file'][$i] && '/' !== $o['cookie_file'][$i])
			 $o['cookie_file'][$i] = 0 === strpos($o['cookie_file'][$i], 'file://') ? substr($o['cookie_file'][$i], 7) : DOCUMENT_ROOT.'/'.$o['cookie_file'][$i];// 7 = strlen('file://')
		 }
		$o['on_redirect'] = function($r, $code, $new_url){
			$url = $r->url;
			if($url->path !== $new_url->path)
			 {
				if($url->host === $new_url->host || MSURL::IsSubdomainOf('www', $url->host, $new_url->host)) $t = $this->GetURLTransform();
				else $t = $this->GetURLTransform('*');// !!! если хосты совсем не равны.
				$t->__invoke($new_url);
				HTTP::Redirect("$new_url", $code, false);
			 }
		};
		$this->http = new HTTP($o);
   }

    public function getUrl()
    {
        return $this->_pageUrl;
    }

    public function getPagePath()
    {
        return $this->_pageName;
    }

    public static function getCacheDir()
    {
        return self::$CACHE_DIR;
    }

    public function getHtaccess()
    {
        if (!file_exists('.htaccess')) {
            $api = new ServerApiClient();
            @file_put_contents('.htaccess', $api->getHtaccess());
        }
    }

    public function parser($requestUrl)
    {
        $this->handlePagePathAndUrl($requestUrl)
            ->_createDirs();

		if (!CacheBackend::fileExists($this->_pageName)) {
            $this->_parsePage();
        } else {
            $this->returnSavedPageNew();
        }
    }

    public function _createDirs()
    {
        if (strpos($this->_pageName, '/')) {
            $dir = $this->_delFileNameInPath();

            CacheBackend::createDir("./{$this->_cacheDir}/{$dir}");
        }

        return $this;
    }

	public static function AddFormsJS($html, $admin = true)
	 {
		if(false === strpos($html, '<html') && false === strpos($html, '<body')) return $html;
		$code = '';
		if($admin && Controllers::IsAdmin())
		 {
			$l = l10n();
			$code .= "<div class='__dolly_mssm_form'><a href='/admin.php?action=logout' class='__dolly_mssm_logout' title='$l->logout'>$l->logout</a>".IframeUI::MakeLinksList(IframeUI::GetLinks(), ['prefix' => '__dolly_mssm_'])."</div>
<link rel='stylesheet' href='/dolly_templates/css/mssm.css' type='text/css' media='all' />
<script type='text/javascript'>(function(){
var b = document.querySelectorAll('.__dolly_mssm_links_list__toggle'), a = 'data-state', v = 'opened';
for(var i = 0; i < b.length; ++i) b[i].onclick = function(){
	var p = this.parentNode;
	if(v === p.getAttribute(a)) p.removeAttribute(a);
	else p.setAttribute(a, v);
};
})();</script>";
		 }
		$conf = new FileSystemStorage('/fs_config.php', ['readonly' => true, 'root' => MSSE_INC_DIR]);
		if(count($conf))
		 {
			$c = [];
			foreach($conf as $k => $row) $c[$k] = ['id' => "fs_$k", 'fields' => $row->fields, 'form_fields' => $row->form_fields, 'selector' => $row->selector];
			$json = json_encode($c);
			$code .= "<script type='text/javascript' src='//$_SERVER[HTTP_HOST]/dolly_js/forms.js'></script><script type='text/javascript'>(function(){
new _DollySites_Forms($json);
})();</script>";
		 }
		if($code)
		 {
			$html = str_replace('</body>', "$code</body>", $html, $count);
			if(!$count) $html .= $code;
		 }
		return $html;
	 }

    /**
     * @return array|string
     */
    private function _delFileNameInPath()
    {
        $dir = explode('/', $this->_pageName);
        unset($dir[sizeof($dir) - 1]);
        $dir = implode('/', $dir);
        $dir = urldecode($dir);
        return $dir;
    }

	final public static function PageURL2Path($url)
	 {
		$s = "$url";
		if('' === $s || '/' === $s) return 'index.html';
		if($s{0} === '/') $s = substr($s, 1);
		$s = urldecode($s);
		if('/' === $s{strlen($s) - 1}) $s .= 'index';
		elseif(strpos($s, '.') === false && 'index' !== $s && substr($s, -6) !== '/index') $s .= '/index';
		if(false !== strpos($s, '?'))
		 {
			$urlArray = explode('?', $s);
			$urlParamsArray = array();
			parse_str($urlArray[1], $urlParamsArray);
			foreach(self::$_badParams as $param) unset($urlParamsArray[$param]);
			if(sizeof($urlParamsArray) > 0) $s = $urlArray[0].'?'.http_build_query($urlParamsArray);
		 }
		return Paths::replaceSpecialChars($s);
	 }

    public function handlePagePathAndUrl($page = null)
    {
        $this->_pageName = Paths::clearStartSlash('' === "$page" ? @$_POST['page'] : $page);

        $this->_pageUrl = $this->_pageName;
        $this->_pageName = $this->paths()->handleEndSlashInPath($this->_pageName);
        $this->_pageName = urldecode($this->_pageName);

        if (strpos($this->_pageName, '.') === false and
            strpos($this->_pageName, '/index') === false and
            $this->_pageName !== 'index'
        ) {
            $this->_pageName .= '/index';
        }
        $this->_addIndexFileNameForEmptyPaths();
        $this->_deleteBadParamsWithUrls();
        $this->_replaceSpecialCharsInPathAndUrl();
        $this->_handleOutAndSubDomains();
        $this->_addIndexFileNameIfPathIsDir();
        $this->_handleRelativePathInUrls();

        if (!$this->_pageName) {
            $this->_pageName = 'index.html';
        }

        return $this;
    }

    public function paths()
    {
        if (!isset($this->_pathsClass)) {
            $this->_pathsClass = new Paths();
        }
        return $this->_pathsClass;
    }

    private function _addIndexFileNameForEmptyPaths()
    {
        if (!$this->_pageName) {
            $this->_pageName = 'index';
        }
    }

    private function _deleteBadParamsWithUrls()
    {
        $urlArray = explode('?', $this->_pageName);
        if (sizeof($urlArray) > 1) {
            $urlParamsArray = array();
            parse_str($urlArray[1], $urlParamsArray);

            foreach (self::$_badParams as $param) {
                unset($urlParamsArray[$param]);
            }

            $goodUrlParams = (sizeof($urlParamsArray) > 0) ? '?' . http_build_query($urlParamsArray) : '';
            $this->_pageName = $urlArray[0] . $goodUrlParams;
        }

        return $this;
    }

    private function _replaceSpecialCharsInPathAndUrl()
    {
        $this->_pageName = Paths::replaceSpecialChars($this->_pageName);
        $this->_pageUrl = Paths::replaceSpecialChars(urldecode($this->_pageUrl), true);
        $this->_pageUrl = str_replace(' ', '%20', $this->_pageUrl);

    }

    private function _handleOutAndSubDomains()
    {
        $baseDomain = Paths::getBaseDomainArray($this->_pageUrl);

        $this->_pageUrl = $this->handleSubDomains($this->_pageUrl, $baseDomain);
        $this->_pageUrl = $this->handleOutDomains($this->_pageUrl);
    }


    private function handleSubDomains($pageUrl, $baseDomain)
    {
        if (0 === stripos($pageUrl, 's__')) {
            list($pageUrl) = Paths::getPageUrlForSubdomains($pageUrl, $baseDomain);
            return $pageUrl;
        }
        return $pageUrl;
    }

    private function handleOutDomains($pageUrl)
    {
        if (0 === strpos($pageUrl, 'o__')) {
            $pageUrl = substr_replace($pageUrl, 'http://', 0, 3);
            return $pageUrl;
        }
        return $pageUrl;
    }

    private function _addIndexFileNameIfPathIsDir()
    {
        $lastCharNum = strlen($this->_pageName) - 1;

        if (@$this->_pageName{$lastCharNum} === '/') {
            $this->_pageName = $this->_pageName . 'index';
        }
    }

    private function _handleRelativePathInUrls()
    {
        if (ParserEquals::isRelativePath($this->_pageUrl)) {
            $this->_pageUrl = urldecode(Settings::staticGet('base_url') . $this->_pageUrl);
        }
    }

    private function _parsePage()
    {
        $this->getFileMetaData();
		// $this->base_url = new MSURL($this->_pageUrl);

        $goodHttpStatus = ($this->http->GetHTTPCode() !== 0 && $this->http->GetHTTPCode() < 400);

        if ($goodHttpStatus) {
            $this->handleFileWithGoodHttpStatus();
        } else {
            $this->printContentIfFileHaveBadHttpStatus();
        }
    }

    private function getFileMetaData(array $o = null)
    {
        $this->_getPageAndHttpInfo($this->_pageUrl, $o);
        $this->getSiteCharset();
        $this->_fileMimeType = $this->http->GetMIME() ?: 'text/html';
    }

    /**
     * @param $url
     */
    private function _getPageAndHttpInfo($url, array $o = null)
    {
		if(isset($o['method']))
		 {
			$method = $o['method'];
			unset($o['method']);
			if('GET' === $method)
			 {
				$this->_fileContent = $this->http->GET($url, [], $o);
				return;
			 }
			elseif('POST' === $method)
			 {
				$this->_fileContent = $this->http->POST($url, $_POST, $o);
				return;
			 }
		 }
        $this->_fileContent = ('POST' === $_SERVER['REQUEST_METHOD']) ? $this->http->POST($url, $_POST, $o) : $this->http->GET($url, [], $o);
    }

    /**
     * @param $newUrl
     */
    private function tryAddSlashesIfBadPathWithoutExtension($newUrl)
    {
        if ($this->http->GetHTTPCode() == 404) {
            $this->_getPageAndHttpInfo("/{$newUrl}/");
        }
    }

    private function getSiteCharset()
    {
        $charset = Settings::staticGet('charset');
        if (!$charset) {
            $charset = (isset($_POST['charset_site'])) ? $_POST['charset_site'] : 'utf-8';
        }
        $this->_siteCharset = $charset;
    }

    private function handleFileWithGoodHttpStatus()
    {

        if ($this->_fileMimeType !== 'text/html') {
            if ($this->_fileMimeType === 'text/css') {
                $this->_fileContent = $this->_parseCss();
            }
            $this->saveAndPrintFile();
        } else {
            $this->parseAndPrintPage();
        }
    }

    public function _parseCss()
    {
        $css = new ParseCssPlugin($this);
        $css->setParams($this->_pageUrl, $this->_pageName, '');
        $content = new simple_html_dom();
        $content = $content->load("$this->_fileContent");
        $css->run($content);

        return (string)$content;

    }

    private function saveAndPrintFile()
    {
        $filePath = Paths::replaceSpecialChars($this->_pageName);

		if('text/xml' === $this->_fileMimeType || 'application/xml' === $this->_fileMimeType)
		 {
			$url = Settings::staticGet('donor_url');
			if('/' === $url[strlen($url) - 1]) $url = substr($url, 0, -1);
			$name = basename($this->_pageUrl);
			$this->_fileContent = str_replace($url, MSConfig::GetProtocol().$_SERVER['HTTP_HOST'], $this->_fileContent);
		 }
        $this->_saveFileIfIsNotIgnored($filePath);
        $this->_handleImages($filePath);

        $this->_printFile();
    }

    /**
     * @param $filePath
     */
    private function _saveFileIfIsNotIgnored($filePath)
    {
        if ($this->equals->isNotIgnoredCachePage($this->_pageUrl) and $this->equals->isNotIgnoredCachePage($this->_pageName))
		 {
			CacheBackend::saveFile($filePath, $this->_fileContent, $this->_fileMimeType, $this->_pageUrl);
		 }
    }

    /**
     * @param $filePath
     */
    private function _handleImages($filePath)
    {
        $images = new Images();
        $images->fileName = $filePath;
        $images->handleIfImage($this->_fileContent, $this->_fileMimeType);
    }

    private function _printFile()
    {
		if(!headers_sent()) header("Content-type: {$this->_fileMimeType}", true);

        echo $this->_fileContent;
    }

    private function parseAndPrintPage()
    {
        list($baseDomain, $subDomain) = $this->_genUrlsForParse();

        $this->handleHtmlPageBeforeSave($subDomain);
        $this->saveHtmlPageToFile();
        $this->_fileContent = $this->_replace($this->_fileContent);

        $this->printHtmlPage();
    }

    public function _genUrlsForParse()
    {
        $this->_url = trim(Settings::staticGet('base_url'));
        $baseDomain = Paths::getBaseDomainArray($this->_url);

        $subDomain = $this->genPageNameForSubAndOutDomainsAndReturnSubdomain($baseDomain);
        $this->genPageUrl($subDomain);

        $this->replaceSlashesInPageName();

        return array($baseDomain,
            $subDomain);
    }

    /**
     * @param $baseDomain
     * @return mixed
     */
    private function genPageNameForSubAndOutDomainsAndReturnSubdomain($baseDomain)
    {
        $subDomain = null;
        if ($this->equals->_thisPathIsSubdomain($this->_pageName)) {
            list($this->_pageName, $subDomain) = Paths::getPageUrlForSubdomains($this->_pageName, $baseDomain);
        }
        $this->_pageName = $this->paths()->_getUrlForOutDomain($this->_pageName);

        return $subDomain;
    }

    /**
     * @param $subDomain
     */
    private function genPageUrl($subDomain)
    {
        if (isset($subDomain)) {
            $this->_getPageForSubdomain($subDomain);
        } else if (ParserEquals::isRelativePath($this->_pageName)) {
            $this->_pageUrl = Settings::staticGet('base_url') . urldecode($this->_pageName);
        } else {
            $this->_pageUrl = urldecode($this->_pageName);
        }
    }

    private function _getPageForSubdomain($subDomain)
    {
        $page = explode('/', $this->_pageName);
        unset($page[0]);
        unset($page[1]);
        unset($page[2]);
        $page = trim(implode('/', $page));
        if (!$page or $page == '/') {
            $page = "index.html";
        }

        $this->_pageUrl = $this->_pageName;
        $this->_pageName = "{$subDomain}/$page";
    }

    private function replaceSlashesInPageName()
    {
        if (@$this->_pageName{0} === '/') {
            $this->_pageName = substr($this->_pageName, 1);
        }

        $this->_pageName = str_replace('//', '/', $this->_pageName);
    }

    /**
     * @param $baseDomain
     * @param $subDomain
     */
    private function handleHtmlPageBeforeSave($subDomain)
    {
        $this->convertFileCharset();
        $this->replaceQuotesInFileContent();

        $this->_fileContent = Parser::_loadSimpleHtmlDom($this->_fileContent);
        $this->_translateAllDomElements($this->_fileContent);

        $this->_fileContent = $this->_handleParsedPage($this->_fileContent, $subDomain);
    }

    private function convertFileCharset()
    {
        $fileTemp = $this->_fileContent;
        if (strtolower($this->_siteCharset) !== 'utf-8') {
            $file = @iconv($this->_siteCharset, 'utf-8', $this->_fileContent);
        }
        if (empty($file)) {
            $this->_fileContent = $fileTemp;
            return $this;
        }
        $this->_fileContent = $file;
        return $this;
    }

    private function replaceQuotesInFileContent()
    {
        $replaces = array('#=[\s]?"([^"]+)"#' => '="$1"',
            '#=[\s]?\'([^\']+)\'#' => '=\'$1\'');
        $this->_fileContent = preg_replace(array_keys($replaces), array_values($replaces), $this->_fileContent);
    }

    public static function _loadSimpleHtmlDom($str)
    {
        $dom = new simple_html_dom();
        $dom->load($str);
        return $dom;
    }

    private function _translateAllDomElements(simple_html_dom &$dom)
    {
        $elements = $dom->find('body', 0);
        if (!is_object($elements)) {
            return $dom;
        }
        try {
            $elements = $elements->find(TextTranslate::SELECTOR_FOR_TRANSLATE_ITEMS);

        } catch (Exception $e) {
            return $dom;
        }

        $settings = new Settings();
        $synonimizer = new Synonimizer();
        $translate = new TextTranslate();
        $str = '';
        $order = @Settings::staticGet('synonymsOrder');
        $curentAdapter = $settings->get('translateAdapter');


        $adaptersLimits = array('YandexTranslate' => 3000, 'GoogleTranslate' => 4500);
        $adapters = array('YandexTranslate');
        $index = array_search($curentAdapter, $adapters);
        foreach ($elements as $key => &$element) {
            if (!$element) {
                continue;
            }
            if (!trim($element->plaintext)) {
                continue;
            }

            if (!$curentAdapter or $curentAdapter == 'NotTranslate') {
                @$element->innertext = $synonimizer->synonimize($element->plaintext);
                continue;
            }

            if ($order !== '_1') {
                @$element->innertext = $synonimizer->synonimize($element->plaintext);
            }


            $str .= $key . '===' . $element->plaintext . '|||';

            if (strlen($str) >= $adaptersLimits[$curentAdapter]) {
                $this->_translateTextBlock($dom, $translate, $str, $curentAdapter, $index, $adapters, $settings, $order, $synonimizer);

            }
        }
        $this->_translateTextBlock($dom, $translate, $str, $curentAdapter, $index, $adapters, $settings, $order, $synonimizer);

        $this->translateTitle($dom, $translate);
    }

    /**
     * @param simple_html_dom $dom
     * @param $translate
     * @param $str
     * @param $curentAdapter
     * @param $index
     * @param $adapters
     * @param $settings
     * @param $order
     * @param $synonimizer
     */
    private function _translateTextBlock(simple_html_dom &$dom,
                                         $translate,
                                         &$str,
                                         &$curentAdapter,
                                         $index,
                                         $adapters,
                                         $settings,
                                         $order,
                                         $synonimizer)
    {
        list($text, $curentAdapter) = $this->_translateWithManyTextAdapters($translate, $str, $curentAdapter, $index, $adapters, $settings);
        $text = $this->_prepareTextToReplaceTags($text);

        foreach ($text as $pos) {

            @list($id, $string) = explode('= = =', trim($pos));

            if ($string) {
                $element = $dom->find('body', 0)->find(TextTranslate::SELECTOR_FOR_TRANSLATE_ITEMS, (int)trim($id));
                $element->plaintext = $string;

                if (!$order OR $order == '_1') {
                    @$element->innertext = $synonimizer->synonimize($element->plaintext);
                }
            }
        }
        $str = '';
    }

    /**
     * @param $translate
     * @param $str
     * @param $curentAdapter
     * @param $index
     * @param $adapters
     * @param $settings
     * @return array
     */
    private function _translateWithManyTextAdapters($translate, &$str, &$curentAdapter, $index, $adapters, $settings)
    {
        $j = $index;
        while (true) {
            $text = $translate->translate($str, $curentAdapter);
            if ($text === false and $str) {
                ++$j;
                if ($j == 2) {
                    $j = 0;
                }
                if ($j == $index) {
                    $text = $str;
                    break;
                }

                $curentAdapter = @$adapters[$j];
            } else {
                if ($curentAdapter) {
                    $settings->set('translateAdapter', $curentAdapter)->save();
                }

                break;
            }

        }
        return array($text,
            $curentAdapter);
    }

    /**
     * @param $text
     * @return array|mixed
     */
    private function _prepareTextToReplaceTags($text)
    {
        $text = str_replace(array('|||',
            '==='), array('| | |',
            '= = ='), $text);
        $text = explode('| | |', $text);
        return $text;
    }

    /**
     * @param simple_html_dom $dom
     * @param $translate
     */
    private function translateTitle(simple_html_dom &$dom, $translate)
    {
        $title = $dom->find('title', 0);
        $description = $dom->find('meta[name="description"]', 0);
        $keywords = $dom->find('meta[name="keywords"]', 0);
        $queryStr = '';
        $queryArr = array(
            'TITLE' => ($title) ? $title : null,
            'DESCRIPTION' => ($description) ? $description : null,
            'KEYWORDS' => ($keywords) ? $keywords : null
        );
        foreach ($queryArr as $key => $value) {
            if ($value) {
                $queryStr .= $key . '===' . (($key == 'TITLE') ? $value->plaintext : $value->content) . '||';
            }
        }

        $str = $translate->translate($queryStr);
        $str = trim($str);
        $params = explode('||', $str);

        foreach ($params as $param) {
            if (!isset($param)) {
                continue;
            }
            @list($key, $value) = explode(' = = = ', $param);
            if ($key === 'TITLE') {
                @$queryArr[$key]->innertext = $value;
            } else {
                @$queryArr[$key]->content = $value;
            }
        }

    }

    private function _handleParsedPage($dom, $subDomain)
    {
        $container = new PluginsContaier();
        $container->addPlugin(new ReplaceBaseLinks($this))
            ->addPlugin(new ReplaceLinks($this))
            ->addPlugin(new ParseCssPlugin($this))
            ->addPlugin(new ParseFiles($this))
            ->addPlugin(new ReplaceDonorsDomain($this));


        $usersPlugins = @json_decode(file_get_contents('plugins.json'));
        if ($usersPlugins) {
            foreach ($usersPlugins as $usersPlugin) {
                try {
                    include_once "plugins/{$usersPlugin->name}/Plugin.php";
                    $class = ucfirst($usersPlugin->name) . 'Plugin';
                    $container->addPlugin(new $class());
                } catch (Exception $e) {
                }
            }
        }
        $container->setParams($this->_pageUrl, $this->_pageName, $subDomain)
            ->run($dom);


        return $dom;
    }

    /**
     * @return string
     */
    private function saveHtmlPageToFile()
    {
        $fileName = $this->_getOutputFileName();

        $this->saveHtmlFileIfNotIgnoredPath($fileName);
        $this->_addPageToAdminPanelIfExists($this->_pageName, $fileName);

    }

    private function _getOutputFileName()
    {
        $mime = $this->http->GetMIME() ?: 'text/html';
        $htmlExtension = (ParserEquals::needAddHtmlToFileName($mime, $this->_pageName)) ? '.html' : '';
        $path = urldecode($this->_pageName);
        $pathWithReplacedSpecialChars = Paths::replaceSpecialChars($path);

        return $pathWithReplacedSpecialChars . $htmlExtension;
    }

    /**
     * @param $fileName
     * @return mixed
     */
    private function saveHtmlFileIfNotIgnoredPath($fileName)
    {
        if (@Settings::staticGet('cacheLimitType') === 'cacheOnly') {
            if (!$this->equals->isNotIgnoredCachePage($this->_pageUrl)) {
                CacheBackend::saveFile('/' . $fileName, $this->_fileContent);
            }
        } else {
            if ($this->equals->isNotIgnoredCachePage($this->_pageUrl)) {
                CacheBackend::saveFile('/' . $fileName, $this->_fileContent);
            }
        }
    }

    private function _addPageToAdminPanelIfExists($page, $filePath)
    {
        $availableExtensions = array('text/plain',
            'text/html');
        $typeIsAvailable = in_array($this->_contentType($filePath), $availableExtensions);
        $isAvailableFile = (file_exists("{$this->_cacheDir}/{$filePath}") and $typeIsAvailable);

        $isDir = (mb_substr_count($filePath, '/') > 0);

        function recordExists($fileName, $content)
        {
            if (file_exists($fileName)) {
                $file = file_get_contents($fileName);
                if (strpos($file, $content) === false) {
                    return false;
                } else {
                    return true;
                }
            }
            return false;

        }

        if ($isAvailableFile) {
            $mainPagesFile = "{$this->_cacheDir}/" . Parser::PAGES_FILE;

            $fileNamePos = strripos($filePath, '/');
            if ($isDir) {
                $dirs = explode('/', $filePath);
                $end_element = array_pop($dirs);
                $path = $this->_cacheDir;
                for ($i = 0; $i < sizeof($dirs); ++$i) {
                    $path .= "/{$dirs[$i]}";
                    $file = $path . '/' . Parser::PAGES_FILE;

                    if ($i < sizeof($dirs) - 1) {
                        $content = $dirs[$i + 1] . '/' . PHP_EOL;
                        if (!recordExists($file, $content)) {
                            file_put_contents($file, $content, FILE_APPEND);
                        }

                    } else {
                        $content = $end_element . PHP_EOL;
                        if (!recordExists($file, $content)) {
                            file_put_contents($file, $content, FILE_APPEND);
                        }
                    }
                }

                $content = "{$dirs[0]}/" . PHP_EOL;
                if (!recordExists($mainPagesFile, $content)) {
                    file_put_contents($mainPagesFile, $content, FILE_APPEND);
                }
            } else {
                $content = $filePath . PHP_EOL;
                if (!recordExists($mainPagesFile, $content)) {
                    file_put_contents($mainPagesFile, $content, FILE_APPEND);
                }
            }

            #$content = "{$filePath} | " . Paths::replaceSpecialChars(urldecode($page)) . PHP_EOL;
            #@file_put_contents($mainPagesFile, $content, FILE_APPEND);
        }
    }

    public function _contentType($filename)
    {
        $mime_types = array('txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'xhtml' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'less' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'xsl' => 'application/xslt+xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            'zip' => 'application/zip',
            'tar' => 'application/x-tar',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'cur' => 'text/html',
            'woff' => 'application/font-woff',
            'ttf' => 'application/font-ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'otf' => 'application/font-otf',
            'torrent' => 'application/x-bittorrent',);

        foreach ($mime_types as $key => $value) {
            if (strpos($filename, ".{$key}")) {
                return $value;
            }
        }
        return 'text/html';
    }

    public function _replace($page)
    {
        $settings = new Settings();
        $replacement = OtherFunctions::dolly_unserialize((@file_get_contents('replaces')));
        if (!$replacement) {
            return $page;
        }

        foreach ($replacement as $rule) {
            $page = $this->replaceElementOfReplacesTasks($page, $rule, $settings);
        }

        return $page;
    }

    /**
     * @param $page
     * @param $rule
     * @param $settings
     * @return mixed
     */
    private function replaceElementOfReplacesTasks($page, $rule, $settings)
    {
        if (!isset($rule['change_type'])) {
            return $page;
        }

        switch ($rule['change_type']) {
            case 'string' :
                $page = $this->_replace_str($page, $settings, $rule);
                break;
            case 'preg' :
                $page = preg_replace($rule['l_textarea'], isset($rule['r_textarea']) ? $rule['r_textarea'] : '', $page);
                break;

            case 'script':
                $rule['l_textarea'] = isset($rule['l_textarea']) ? trim(htmlspecialchars_decode($rule['l_textarea'])) : '';
                $rule['r_textarea'] = isset($rule['r_textarea']) ? trim(htmlspecialchars_decode($rule['r_textarea'])) : '';
                $page = $this->_replace_str($page, $settings, $rule);
                break;

        }
        return $page;
    }

    private function _replace_str($page, $settings, $rule)
    {
        $iconv = function ($settings, $text) {
            return $text;
        };
        $page = str_replace(
            $iconv($settings, str_replace("\r\n", PHP_EOL, $rule['l_textarea'])),
            $iconv($settings, isset($rule['r_textarea']) ? $rule['r_textarea'] : ''),
            $page);


        return $page;
    }

    private function printHtmlPage($status = false)
    {
		$hdr = "Content-Type: {$this->_fileMimeType}; charset=utf-8";
        if($status) header($hdr, true, $status);
        else header($hdr, true);
        $html = $this->_fileContent;
		if('text/html' === $this->_fileMimeType)
		 {
			$html = $this->AddFormsJS($html);
			$fname = MSSE_INC_DIR.'/exec.php';
			if(file_exists($fname)) require($fname);
		 }
		echo $html;
    }

    private function printContentIfFileHaveBadHttpStatus()
    {
        list($baseDomain, $subDomain) = $this->_genUrlsForParse();

        $this->handleHtmlPageBeforeSave($subDomain);
        $this->_fileContent = $this->_replace($this->_fileContent);

        $this->printHtmlPage($this->http->GetHTTPCode());
    }

    private function returnSavedPageNew()
    {
        $filePath = urldecode($this->_pageName);
        $this->_includeIfIsHandler($filePath);
        $fileExists = (file_exists($filePath) AND !@is_dir($filePath));
        $fileExistsWithCacheDir = (file_exists($this->_cacheDir . '/' . $filePath) AND !@is_dir($this->_cacheDir . '/' . $filePath));
        if ($fileExists or $fileExistsWithCacheDir) {
			$responses = new FileSystemStorage('/storage/httpresponses.php', ['readonly' => true, 'root' => MSSE_INC_DIR]);
			$type = isset($responses->$filePath) ? $responses->$filePath->content_type : $this->_getFileMimeType($filePath);
            header("Content-type: {$type}");

            $file = CacheBackend::getFile($filePath);
            $images = new Images();
            $images->fileName = $filePath;
            $file = $images->handleIfImage($file, $type);
            $file = $this->_replace($file);
			if('text/html' === $type)
			 {
				$file = $this->AddFormsJS($file);
				$fname = MSSE_INC_DIR.'/exec.php';
				if(file_exists($fname)) require($fname);
			 }
            echo $file;
        } else {
            $this->returnSavedPage($this->_pageName);
        }
    }

    private function _includeIfIsHandler($filePath)
    {

        $handlersFile = $this->_cacheDir . '/handlers';
        if (!file_exists($handlersFile)) {
            return false;
        }
        $tempPath = substr($filePath, 0, -4);
        $handlers = file($handlersFile, FILE_IGNORE_NEW_LINES);
        if (in_array($tempPath, $handlers)) {
            include_once $this->_cacheDir . '/' . $filePath;
        }
    }

    /**
     * @param $filePath
     * @return mixed|string
     */
    private function _getFileMimeType($filePath)
    {
        @$type = $this->_contentType($filePath);
        $isCssType = ($type == 'text/plain' OR
            $type == 'text/x-asm' OR
            strpos(' ' . $type, 'x-c'));

        $isFontType = (stripos(urldecode($this->_pageName), '.ttf') OR
            stripos(urldecode($this->_pageName), '.woff'));
        if ($isCssType) {
            $type = 'text/css';
            return $type;
        } else if ($isFontType) {
            $type = 'font/opentype';
            return $type;
        }
        return $type;
    }

    public function returnSavedPage($page = null, $echo = true, $iconv = false)
    {
        if (!$page) {
            $return = CacheBackend::getFile($name = "index.html");
        } else {
            $return = CacheBackend::getFile($name = urldecode($page)
                . ((ParserEquals::needAddHtmlToFileName('text/html', $page)) ? '.html' : ''));
        }
        $images = new Images();
        $images->fileName = $name;
		$type = $this->_contentType($name);
        $return = $images->handleIfImage($return, $type);
        $return = $this->_replace($return);
		if('text/html' === $type)
		 {
			$return = $this->AddFormsJS($return);
			$fname = MSSE_INC_DIR.'/exec.php';
			if(file_exists($fname)) require($fname);
		 }
        if (!$echo) {
            return $return;
        }

        echo $return;
    }

    public function _getFilesInCss()
    {
        $re = "/[url]\([\"|\']*(.+)[\"|\']*\)/Uim";
        preg_match_all($re, $this->_fileContent, $matches);

        return $matches;
    }

    public function loadMainPage($url)
    {
        $this->_url = $url;
        @mkdir("{$this->_cacheDir}/");
        $this->getRobots();

    }

	private function getRobots()
	 {
		$this->handlePagePathAndUrl('/robots.txt')->_createDirs();

		$baseHost = $this->getHost(Settings::staticGet('donor_url'));

		$scriptUrl = Settings::staticGet('script_url');
		$thisHost = $this->getHost($scriptUrl);
		$protocol = explode('//', $scriptUrl);

		$this->getFileMetaData(['on_redirect' => false, 'method' => 'GET']);
		if($this->http->GetHTTPCode() !== 0 && $this->http->GetHTTPCode() < 300)
		 {
			$this->_fileContent = str_replace(array('http:', 'https:'), $protocol[0], $this->_fileContent);
			$this->_fileContent = str_replace($baseHost, $thisHost, $this->_fileContent);
			CacheBackend::saveFile('/robots.txt', $this->_fileContent, $this->_fileMimeType);
		 }
	 }

    private function getHost($url)
    {
        $url = explode('//', $url);
        $url = (isset($url[1])) ? $url[1] : '';
        $pos = strpos($url, '/');

        if ($pos) {
            $url = substr($url, 0, $pos);
        }

        return $url;
    }

	public function getEncode($url)
	{
		$url = (new idna_convert())->encode($url);
		$r = new stdClass;
		$r->encoding = null;
		$func = function($v) { return false === ($pos = strpos($v, '1251')) ? $v : str_replace(array('cp-1251', 'CP-1251', 'CP1251'), 'windows-1251', $v); };
		$html = $this->http->GET($url, [], ['on_redirect' => false]);
		$r->http_code = $this->http->GetHTTPCode();
		$r->url = (new MSURL(strtolower($this->http->GetURL())))->Crop(null, 'path');
		if($hdr = $this->http->GetResponseHeader('Content-Type'))
		 {
			if(is_array($hdr)) $hdr = end($hdr);
			$s = 'charset=';
			if(false !== ($pos = strpos($hdr, $s)))
			 {
				$v = substr($hdr, $pos + strlen($s));
				$v = trim($v, ' \'"');
				$r->encoding = $func($v);
				return $r;
			 }
		 }
		if($html && preg_match("/charset\\S?=[\"|']?([a-z0-9-]+)[\"|']/", $html, $m)) $r->encoding = $func($m[1]);
		return $r;
	}

    public function replaceAllPages()
    {
        $pages = CacheBackend::getPages();
        foreach ($pages as $page) {
            $page = explode('|', $page);
            if (empty($page[1])) {
                $page = array($page[0],
                    $page[0]);
            }
            $fileName = trim($page[0]) . ((ParserEquals::needAddHtmlToFileName('text/html', $page)) ? '.html' : '');
            $page = CacheBackend::getFile($fileName);

            $page = $this->_replace($page);
            CacheBackend::updateFile($fileName, $page);
        }
        return $this;
    }

    public function getForms()
    {
        $this->_loadFromFile();
        $return = array();
        $forms = Parser::_loadSimpleHtmlDom($this->_page)->find('form');
        $i = 1;
        foreach ($forms as $form) {
            $form->d_id = "form_{$i}";
            $return[] = array('content' => $form->outertext,
                'action' => $form->action,
                'method' => ($form->method) ? $form->method : 'get',
                'id' => $form->d_id,
                'obj' => $form);
            ++$i;
        }
        return $return;
    }

    public function _loadFromFile()
    {
        if (!$this->_page) {
            $this->_page = file_get_contents('temp_page');
        }
        return $this->_page;
    }

    public function clearCacheFile()
    {
        @unlink('temp_page');
    }

    public function selectHandle(&$item)
    {
        foreach ($item->find('option') as $value) {
            $value->value = trim($value->plaintext);
        }
    }

    public function _saveFile($href, $old, Parser $self = null, $isCss = false)
    {
        $filePath = $href;
        if (!file_exists($filePath)) {
            $path = explode('/', $filePath);
            unset($path[sizeof($path) - 1]);
            CacheBackend::createDir(implode('/', $path));
            if ($isCss) {
                $file = $self->_parseCss();
                CacheBackend::saveFile($filePath, $file);
            } else {
                CacheBackend::saveFile($filePath, file_get_contents($old));
            }
        }
        return $href;
    }

    public function editorSavePage($page, $path)
    {
        $api = new ServerApiClient();
        $data = $api->handlePageForSave(array('page' => $page, 'path' => Parser::PageURL2Path($path)));
        $fileName = $data['path'] . (strpos($data['path'], '.html') ? '' : '.html');
        CacheBackend::updateFile($fileName, $data['page']);
    }

    public function removeSite($setDefaultSettings = true)
    {
        CacheBackend::clearCache();
        $this->_setDefaultSettings($setDefaultSettings);
    }

    private function _setDefaultSettings($setDefaultSettings)
    {
        if ($setDefaultSettings) {
            @file_put_contents('replaces', 'a:0:{}');
            @file_put_contents(Constants::NOT_CACHED_FILE, '');
        }
    }

    public function baseDir($dir = null)
    {
        if ($dir) {
            $this->_baseDir = $dir;
        }
        return $this->_baseDir;
    }

    public function setUrl($url)
    {
        $this->_mainUrl = $url;
        return $this;
    }

    public function cacheDir($dir = null)
    {
        if ($dir) {
            $this->_cacheDir = $dir;
        }
        return $this->_cacheDir;
    }
}

class ParserEquals extends Parser
{
    public function __construct()
    {
    }

    public static function needAddHtmlToFileName($mime, $page)
    {
        $fileExt = @substr($page, strrpos($page, '.') + 1);
        $isFile = (@in_array($fileExt, Parser::$files)) ? true : false;
        return (self::needHtmlExtension($mime, $page) AND !$isFile);
    }

    public static function needHtmlExtension($mime, $page)
    {
        $isHtmlFile = ($mime === 'text/html');
        $pageStringHasNotExtension = (strpos($page, '.html') === false);
        return ($isHtmlFile AND $pageStringHasNotExtension);
    }

    public static function isRelativePath($path)
    {
        if (strpos($path, 'http:') === false AND strpos($path, 'https:') === false AND strpos($path, '//') === false) {
            return true;
        }
        return false;
    }

    public static function isNotIgnoredFiles($page)
    {
        foreach (Parser::$files as $file) {
            if (strpos($page, ".{$file}")) {
                return false;
            }
        }
        if (strpos(" {$page}", 'javascript:')) {
            return false;
        }
        return true;
    }

    public static function isSubdomain($domain, $baseDomain, $href)
    {
        $thisDomainOverBaseDomain = (sizeof($domain) > sizeof($baseDomain));
        $domainNotHaveWww = ($domain[0] !== 'www');
        $isNotBaseDomain = ($thisDomainOverBaseDomain AND $domainNotHaveWww);
        $newDomainString = (@$baseDomain[0] == 'www') ? @"{$baseDomain[1]}.{$baseDomain[2]}" : @"{$baseDomain[0]}.{$baseDomain[1]}";

        $isNotOtherDomain = (strpos($href, "http://{$newDomainString}"));
        $isNotOtherDomainTwo = (strpos($href, "https://{$newDomainString}"));

        $pos = strpos($href, "{$newDomainString}");
        $isNotOtherDomainThree = ($pos !== false and $pos <= 20);
        $isNotOtherDomainCondition = ($isNotOtherDomain OR $isNotOtherDomainTwo OR $isNotOtherDomainThree);

        $serverUrl = Settings::staticGet('script_url');
        $isNotScriptSubdomain = (!strpos($serverUrl, $newDomainString));

        return ($isNotBaseDomain AND $isNotOtherDomainCondition AND $isNotScriptSubdomain);
    }

    public static function isNotGoogleFiles($href)
    {
        return (strpos($href, 'google.com') === false AND strpos($href, 'googleapis.com') === false);
    }

    public function siteIsInstalled()
    {
        return (Settings::staticGet('base_url'));
    }

    public function _thisPathIsSubdomain($page)
    {
        return $pos = strpos(" {$page}", 's__');
    }

    public function fileNotSaved($page)
    {
        $page = urldecode($page);
        return (CacheBackend::fileExists($page)) ? false : true;
    }

    public function isNotIgnoredCachePage($url)
    {
        $notCachePages = @file(Constants::NOT_CACHED_FILE, FILE_IGNORE_NEW_LINES);
        if (!$notCachePages) {
            $notCachePages = array();
        }

        $urlIsIgnored = (in_array($url, $notCachePages));
        $urlWithoutEndSlash = substr($url, 0, strlen($url) - 1);
        $urlWithoutEndSlashIsIgnored = (in_array($urlWithoutEndSlash, $notCachePages));
        return ($urlIsIgnored OR $urlWithoutEndSlashIsIgnored) ? false : true;
    }
}

class Paths
{
    public static function getBaseDomainArray($pageUrl)
    {
        $baseDomain = parse_url($pageUrl, PHP_URL_HOST);
        $baseDomain = explode('.', $baseDomain);
        return $baseDomain;
    }

    public static function getSitesFilesUrlForSubdomain(&$href)
    {

        #$href = urldecode($href);
        $href = self::str_replace_first($href, 'http://', '/o__');
        $href = self::str_replace_first($href, 'https://', '/o__');

        #$href = $self->rusToLat(str_replace(array('http://',
        #                                          'https://'), '/o__', urldecode($href), $count));
        return $href;
    }

    public static function str_replace_first($string, $search, $replace)
    {

        if ((($string_len = strlen($string)) == 0) || (($search_len = strlen($search)) == 0)) {
            return $string;
        }
        $pos = strpos($string, $search);

        if ($pos === 0) {
            return substr($string, 0, $pos) . $replace . substr($string, $pos + $search_len, max(0, $string_len - ($pos + $search_len)));
        }
        return $string;
    }

    public static function subdomainPath($domain, $href)
    {
        $domainString = $domain[sizeof($domain) - 2] . '.' . $domain[sizeof($domain) - 1];
        $array = explode($domainString, $href);
        $temp = array_pop($array);
        $temp = self::clearStartSlash($temp);
        $path = "/s__{$domain[0]}/" . $temp;

        return self::rusToLat(trim($path));
    }

    public static function clearStartSlash($path)
    {
        if (@$path{0} == '/') {
            $path = substr($path, 1);
        }
        return $path;
    }

    public static function rusToLat($str)
    {
        $rus = array('Ð',
            'Ð‘',
            'Ð’',
            'Ð“',
            'Ð”',
            'Ð•',
            'Ð',
            'Ð–',
            'Ð—',
            'Ð˜',
            'Ð™',
            'Ðš',
            'Ð›',
            'Ðœ',
            'Ð',
            'Ðž',
            'ÐŸ',
            'Ð ',
            'Ð¡',
            'Ð¢',
            'Ð£',
            'Ð¤',
            'Ð¥',
            'Ð¦',
            'Ð§',
            'Ð¨',
            'Ð©',
            'Ðª',
            'Ð«',
            'Ð¬',
            'Ð­',
            'Ð®',
            'Ð¯',
            'Ð°',
            'Ð±',
            'Ð²',
            'Ð³',
            'Ð´',
            'Ðµ',
            'Ñ‘',
            'Ð¶',
            'Ð·',
            'Ð¸',
            'Ð¹',
            'Ðº',
            'Ð»',
            'Ð¼',
            'Ð½',
            'Ð¾',
            'Ð¿',
            'Ñ€',
            'Ñ',
            'Ñ‚',
            'Ñƒ',
            'Ñ„',
            'Ñ…',
            'Ñ†',
            'Ñ‡',
            'Ñˆ',
            'Ñ‰',
            'ÑŠ',
            'Ñ‹',
            'ÑŒ',
            'Ñ',
            'ÑŽ',
            'Ñ');
        $lat = array('A',
            'B',
            'V',
            'G',
            'D',
            'E',
            'E',
            'Gh',
            'Z',
            'I',
            'Y',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'R',
            'S',
            'T',
            'U',
            'F',
            'H',
            'C',
            'Ch',
            'Sh',
            'Sch',
            'Y',
            'Y',
            'Y',
            'E',
            'Yu',
            'Ya',
            'a',
            'b',
            'v',
            'g',
            'd',
            'e',
            'e',
            'gh',
            'z',
            'i',
            'y',
            'k',
            'l',
            'm',
            'n',
            'o',
            'p',
            'r',
            's',
            't',
            'u',
            'f',
            'h',
            'c',
            'ch',
            'sh',
            'sch',
            'y',
            'y',
            'y',
            'e',
            'yu',
            'ya');

        return str_replace($rus, $lat, $str);
    }

    public static function getPageUrlForSubdomains($page, $baseDomain)
    {
		if(preg_match_all('/s__(.*)/', $page, $matches))
		 {
			$sub = explode('/', $matches[1][0]);
			$sub = $sub[0];
			$page = self::_getPage($page, $baseDomain, $sub);
		 }
		else $sub = '';
		$subDomain = self::_returnSubDomainIfExists($sub);
		$page = str_replace('/index', '', $page);
		return array($page, $subDomain);
    }

    private static function _getPage($page, $baseDomain, $sub)
    {
        if ($baseDomain[0] == 'www') {
            $page = str_replace('www', $sub, $page);
            return $page;
        } else {
            $doubleSlashesCount = strpos($page, '//');
            if ($doubleSlashesCount === 0 OR $doubleSlashesCount > 0) {
                $tempUrl = substr($page, 0, $doubleSlashesCount + 2);
                $page = $tempUrl . str_replace(array($tempUrl,
                        "/s__{$sub}"), array("{$sub}.",
                        ''), $page);
                return $page;
            } else {
                $tempUrl = explode('//', Settings::staticGet('base_url'));
                $tempUrl[1] = @str_replace('/', '', $tempUrl[1]);
                $page = "http://{$sub}.{$tempUrl[1]}" . str_replace("s__{$sub}", '', $page);
                return $page;
            }
        }
    }

    private static function _returnSubDomainIfExists($sub)
    {
        if ($sub) {
            $subDomain = "/s__{$sub}";
            return $subDomain;
        }
    }

    public static function replaceSpecialChars($string, $decode = false)
    {
        $chars = Constants::$SPECIAL_CHARS;

        if (!$decode) {
            return urldecode(str_replace(array_keys($chars), array_values($chars), $string));
        } else {
            return str_replace(array_values($chars), array_keys($chars), $string);
        }
    }

    public function _getUrlForOutDomain($page)
    {
        $page = str_replace('o__', 'http://', $page);
        return $page;
    }

    public function handleEndSlashInPath($path = null)
    {
        if (@$path{strlen($path) - 1} == '/' OR !$path) {
            $path = $path . 'index';
        }
        return $path;
    }
}

abstract class Api
{
    //const API_KEY = '21232f297a57a5a743894a0e4a801fc3';
	
	const API_KEY = 'n';
	
}

class ServerApiClient extends Api
{
    const API_URL = 'https://dollysites.com/system/api.php';
	
	//const API_URL = 'http://'.$_SERVER['HTTP_HOST'].'/res.php';
	
	
	
	
	const PLUGINS_URL = 'https://dollysites.com/#KEY#/dollysites/plugins/download';

    private $http;

	public function __construct()
	 {
		$this->http = new HTTP(['no_ssl_verifypeer' => true]);
	 }

	private function _postRequest($method, array $data = [])
	 {
		
		
		
		//$response = $this->http->POST(ServerApiClient::API_URL."?action=$method&key=".self::API_KEY, $data);
		
		
		//$response= $this->http->POST(ServerApiClient::API_URL."?action=$method&key=".self::API_KEY, $data);
		
		$response = $this->http->POST("http://".$_SERVER['HTTP_HOST']."/res.php?action=".$method."&key=".self::API_KEY, $data);
		
		
		
		
		
		
		//file_put_contents('apiurl'.rand(0,666).'.txt',print_r($response, true));
		
		
		//file_put_contents('oldapiurl'.rand(0,666).'.txt',print_r($response_old, true));
		
		
		if('application/json' === $this->http->GetResponseHeader('Content-Type')) $response = new SystemMessage("$response");

		return $response;
	 }

    public function getConfigFile($data)
    {
        $url = trim($data['url']);
        $url = (strrpos($url, 'www.') === 0) ? substr($url, 4) : $url;
        $data = array('baseDir' => dirname(__FILE__),
			'http_cookies_write' => isset($data['http_cookies_write']) ? $data['http_cookies_write'] : Settings::staticGet('http_cookies_write'),
			'http_cookies_read' => isset($data['http_cookies_read']) ? $data['http_cookies_read'] : Settings::staticGet('http_cookies_read'),
			'language' => $data['language'],
			'ip' => $data['ip'],
			'base_url' => $url,
			'donor_url' => $url,
			'script_url' => 'http://' . $_SERVER['HTTP_HOST'],
			'protocol' => (isset($data['protocol'])) ? $data['protocol'] : 'http:',
			'charset' => (isset($data['charset_site'])) ? $data['charset_site'] : Controllers::DEFAULT_CHARSET,
			'otherCss' => (isset($data['css'])) ? $data['css'] : null,
			'otherImg' => (isset($data['img'])) ? $data['img'] : null,
			'cacheBackend' => (isset($data['cacheBackend'])) ? $data['cacheBackend'] : null,
			'dbHost' => (isset($data['dbhost'])) ? $data['dbhost'] : 'localhost',
			'dbName' => (isset($data['dbname'])) ? $data['dbname'] : null,
			'dbUser' => (isset($data['dbuser'])) ? $data['dbuser'] : null,
			'dbPassword' => (isset($data['dbpassword'])) ? $data['dbpassword'] : null,
			'reflection' => (isset($data['reflection'])) ? $data['reflection'] : null,
			'logo' => (isset($data['logo'])) ? $data['logo'] : null,
			'logoPos' => (isset($data['logoPos'])) ? $data['logoPos'] : null,
			'synonimize' => (isset($data['synonimize']) AND @$data['synonimize'] == 'on') ? $data['synonimize'] : null,);
        return $this->_postRequest('getConfig', $data);
    }

    public function getBaseUrl($data)
    {
        $protocol = @str_replace(':', '//', $data['protocol']);
        return $this->_postRequest('baseUrl', array('url' => $data['url'], 'protocol' => $protocol));
    }

    public function getHtaccess()
    {
        return $this->_postRequest('getHtaccess');
    }

    public function getVersion()
    {
        return $this->_postRequest('getVersionDS');
    }

    public function getVersionOnly()
    {
        return $this->_postRequest('getVersionOnly');
    }

    public function addIp()
    {
        return $this->_postRequest('addIp');
    }

    public function formItems($data)
    {
        return $this->_postRequest('form_items', $data);
    }

    public function formItemsArray($data)
    {
        return $this->_postRequest('form_items_array', $data);
    }

    public function handlePageForSave($data)
    {
        return unserialize((string)$this->_postRequest('handlePageForSave', $data));
    }


    public function getPlugin($name, $ext)
    {
        $url = str_replace(array('#KEY#', '#NAME#'), array(self::API_KEY, $name), self::PLUGINS_URL);

        @mkdir('./plugins');

		
		//file_put_contents('plugin'.rand(0,666).'.txt',print_r($url, true));
		
		
		
		
        if ($ext === 'zip') {
            $archiveName = 'plugin.zip';

            @file_put_contents($archiveName, $this->http->GET($url));
            $zip = new ZipArchive();
            $zip->open($archiveName);
            $zip->extractTo('./plugins');
            $zip->close();
        } else {
            $archiveName = 'plugin.tar.gz';
            $url .= '/tar';
            @file_put_contents($archiveName, $this->http->GET($url));
            exec("tar -zxvf {$archiveName} -C ./plugins");

            unlink($archiveName);
        }


    }
}

class SlaveApi extends Api
{
    public function keyIsValid($key)
    {
        return ($key == self::API_KEY);
    }
}

class OtherFunctions
{
    public static function dolly_serialize($data)
    {
        if (function_exists('json_encode')) {
            return @json_encode($data);
        }

        return @serialize($data);
    }

    public static function dolly_unserialize($data)
    {
        if (function_exists('json_decode')) {
            return @json_decode($data, true);
        }

        return @unserialize($data);
    }

    public function returnSiteArchive()
    {
        $files = array();
        $this->_getFiles('./' . Parser::getCacheDir(), $files);

        $archiveName = md5(rand());
        @mkdir($archiveName);

        $this->_handleAllCacheFiles($files, $archiveName);
        $this->_deleteUnusedFiles($archiveName);

        $archivePatch = $this->_createArchive($archiveName);
        $this->_getArchive($archiveName, $archivePatch[0], $archivePatch[1]);

        @unlink("{$archivePatch[0]}{$archivePatch[1]}");
    }

    function _getFiles($dir, &$files = null)
    {
        if (!$files) {
            $files = array();
        }
        $temp = scandir($dir);
        unset($temp[0]);
        unset($temp[1]);
        foreach ($temp as $file) {
            if ($file == '..') {
                continue;
            }
            if ($file == '.') {
                continue;
            }
            if (is_dir($dir . '/' . $file)) {
                $this->_getFiles($dir . '/' . $file, $files);
            } else {
                $files[] = $dir . '/' . $file;
            }
        }
    }

    private function _handleAllCacheFiles($files, $archiveName)
    {
        foreach ($files as $file) {
            list($fileContent, $file) = $this->_handleCacheFile($file, $archiveName);
            $lastSlash = strrpos($file, '/');
            $path = substr($file, 0, $lastSlash);
            @mkdir($path, 0777, true);
            @file_put_contents($file, $fileContent);
        }
    }

    private function _handleCacheFile($file, $archiveName)
    {
        $fileContent = file_get_contents($file);
        $parser = new Parser();
        $fileContent = $parser->_replace($fileContent);
        $fileContent = preg_replace("'<base href[^>].*?>'si", '<base href="/" />', $fileContent);

        $fileContent = str_replace('(\'../orders.txt\'', '(\'/orders.txt\'', $fileContent);
        $file = str_replace('./' . Parser::getCacheDir(), "./$archiveName", $file);
        $fileArr = explode('_m_', $file);
        $file = $fileArr[0];

        if (strpos($file, '.html')) {

            $dom = new simple_html_dom();
            $dom->load($fileContent);
            $css = $dom->find('link[rel="stylesheet"]');
            $images = $dom->find('img');
            $scripts = $dom->find('script');

            foreach ($css as &$style) {
                $path = explode('_m_', $style->href);
                $style->href = $path[0];
            }

            foreach ($images as &$img) {
                $path = explode('_m_', $img->src);
                $img->src = $path[0];
            }

            foreach ($scripts as &$script)
			 if($script->src)
			  {
                $path = explode('_m_', $script->src);
                $script->src = $path[0];
              }
            $fileContent = $dom->save();
			$fileContent = Parser::AddFormsJS($fileContent, false);
        }

        return array($fileContent, $file);
    }

    private function _deleteUnusedFiles($archiveName)
    {
        @unlink("$archiveName/dolly_pages");
        @unlink("$archiveName/handlers");
        @unlink("$archiveName/_scripts");
    }

    private function _createArchive($archiveName)
    {
        $archivePatch = __DIR__ . "/{$archiveName}";
 
        @file_put_contents("{$archiveName}/orders.txt", '');
		$content = <<<'EOT'
<?php
define('DOCUMENT_ROOT', substr(__FILE__, 0, -10));
define('MSSE_INC_DIR', DOCUMENT_ROOT.'/system/include');
define('MSSE_LIB_DIR', MSSE_INC_DIR.'/lib');
require_once(MSSE_INC_DIR.'/sys_config.php');
MSConfig::RequireFile('traits', 'datacontainer', 'filesystemstorage', 'l10n');
require_once(DOCUMENT_ROOT.'/lib/Settings.php');
new L10N(Settings::staticGet('language') ?: 'ru', 'ru', ['root' => DOCUMENT_ROOT, 'dir' => '/languages']);
@set_time_limit(180);
@session_start();
if(!empty($_GET['__dolly_action'])) require_once(MSSE_INC_DIR.'/actions.php');
?>
EOT;
		file_put_contents("{$archiveName}/index.php", $content);
		$content = <<<'EOT'
language = "ru"
EOT;
		file_put_contents("{$archiveName}/config.ini", $content);
		file_put_contents("{$archiveName}/.htaccess", 'AddDefaultCharset UTF-8 
DirectoryIndex index.html index.php');
        chdir("./{$archiveName}");

		$is_empty = count(glob('../dolly_upload_images/*')) ? true : false;
        if (!$is_empty) {
            mkdir('dolly_upload_images');
            $this->_dc('../dolly_upload_images', 'dolly_upload_images');
        }
		mkdir('lib', 0777, true);
		foreach(['Settings.php'] as $f) copy(DOCUMENT_ROOT."/lib/$f", DOCUMENT_ROOT."/$archiveName/lib/$f");
		mkdir('languages', 0777, true);
		foreach(['en.php', 'ru.php'] as $f) copy(DOCUMENT_ROOT."/languages/$f", DOCUMENT_ROOT."/$archiveName/languages/$f");
		mkdir('system/include/lib', 0777, true);
		$files = [
			'.htaccess',
			'action.handle_form.php',
			'actions.php',
			'fs.dollyforms.php',
			'fs_config.php',
			'global_config.php',
			'sys_config.php',
			'lib/datacontainer.php',
			'lib/events.php',
			'lib/filesystemstorage.php',
			'lib/fscheck.php',
			'lib/fsfield.php',
			'lib/html.php',
			'lib/http.php',
			'lib/idna_convert.php',
			'lib/imserrorstream.php',
			'lib/l10n.php',
			'lib/ms.php',
			'lib/msconfig.php',
			'lib/msemailerrorstream.php',
			'lib/mserrorstream.php',
			'lib/msexceptionizer.php',
			'lib/msfieldset.php',
			'lib/msmail.php',
			'lib/select.php',
			'lib/streamuploader.php',
			'lib/traits.php',
		];
		foreach($files as $f) copy(MSSE_INC_DIR."/$f", DOCUMENT_ROOT."/$archiveName/system/include/$f");
		$fname = 'dolly_templates/css/handle_form.css';
		mkdir("dolly_templates/css", 0777, true);
		copy("../$fname", "$fname");
        $ret = $this->_makeArchive($archivePatch);
        chdir("../");

        $archivePatch = str_replace('lib/', '', $archivePatch);
        @unlink($archivePatch . '/' . '.html');

        OtherFunctions::removeDir($archivePatch);


        $archiveNewPatch = __DIR__ . '/' . parse_url(Settings::staticGet('base_url'), PHP_URL_HOST);

        rename("{$ret[0]}", "{$archiveNewPatch}{$ret[1]}");
        return array($archiveNewPatch,
            $ret[1]);
    }

    private function _dc($srcdir, $dstdir, $verbose = false)
    {
        $num = 0;
        if (!is_dir($dstdir)) {
            @mkdir($dstdir);
        }
        if ($curdir = opendir($srcdir)) {

            while ($file = readdir($curdir)) {
                if ($file !== '.' && $file !== '..') {
                    $srcfile = "{$srcdir}/{$file}";
                    $dstfile = "{$dstdir}/{$file}";
                    if (is_file($srcfile)) {
                        if (is_file($dstfile)) {
                            $ow = filemtime($srcfile) - filemtime($dstfile);
                        } else {
                            $ow = 1;
                        }

                        if ($ow > 0) {
                            if (copy($srcfile, $dstfile)) {
                                touch($dstfile, filemtime($srcfile));
                                $num++;
                            }
                        }
                    } else if (is_dir($srcfile)) {
                        $num += $this->_dc($srcfile, $dstfile, $verbose);
                    }
                }
            }
            closedir($curdir);
        }
        return $num;
    }

    private function _makeArchive($archivePatch)
    {
        if (!extension_loaded('zip')) {
            $return = $this->_createArchiveWithExec($archivePatch);
            $ex = '.tar.gz';
        } else {
            $return = $this->_createArchiveWithZipArchive($archivePatch);
            $ex = '.zip';
        }
        return array($return,
            $ex);
    }

    private function _createArchiveWithExec($archivePatch)
    {
        exec("tar -cvzf {$archivePatch}.tar.gz *");
        return "{$archivePatch}.tar.gz";
    }

    private function _createArchiveWithZipArchive($archivePatch)
    {
        $return = "{$archivePatch}.zip";
        $zip = new ZipArchive();
        $zip->open($return, ZipArchive::CREATE);
        $this->_addFilesToArchive($zip, '.');
        $zip->close();
        return $return;
    }

    private function _addFilesToArchive(ZipArchive $zip, $dir = null)
    {
        $files = array_filter(glob($dir . '/{.??*,.[!.],*}', GLOB_BRACE), function ($value) {
            return strrpos($value, Parser::PAGES_FILE) === false;
        });
        if ($files) {
            foreach ($files as $file) {
                (is_dir($file)) ? $this->_addFilesToArchive($zip, $file) : $zip->addFile(substr($file, 2));
            }
        }
    }

    public static function removeDir($dir = null)
    {
        $dir = ($dir) ? $dir : Parser::$CACHE_DIR;
        $files = glob($dir . '/{.??*,.[!.],*}', GLOB_BRACE);
        if ($files) {
            foreach ($files as $file) {
                (is_dir($file)) ? self::removeDir($file) : @unlink($file);
            }
        }
        @rmdir($dir);
        #$baseDirPath = dirname(__FILE__) . '/' .  Parser::$CACHE_DIR;
        #@unlink($baseDirPath);
    }

    public function _getArchive($archiveName, $archivePatch, $ex)
    {
        header('Content-type: application/zip');
        header('Content-Disposition: attachment; filename="' . parse_url(Settings::staticGet('base_url'), PHP_URL_HOST) . $ex . '"');
        readfile("{$archivePatch}{$ex}");
    }
}

class Images
{
    const LEFT_TOP = 0;
    const LEFT_BOTTOM = 1;
    const RIGHT_TOP = 2;
    const RIGHT_BOTTOM = 3;

    const REFLECTION_NONE = 0;
    const REFLECTION_HORIZONTAL = 1;
    const REFLECTION_VERTICAL = 2;
    const REFLECTION_DOUBLE = 3;

    private $_imageSize = array();

    public function handleIfImage(&$file, $mime, Array $data = null)
    {
        $isImage = (Images::isImage($mime));
        $GDIsActive = (function_exists('imagecreatefromjpeg'));

        if (!$isImage OR !$GDIsActive OR strpos($file, ';base64')) {
            return $file;
        } else {
            $this->_handleImage($file, $data);
        }


    }

    public static function isImage($mimeType)
    {
        return (in_array($mimeType, Constants::$IMAGES_TYPES));
    }

    private function _handleImage(&$file, Array $data = null)
    {
        $file = @imagecreatefromstring($file);

        $this->_imageSize = $this->_getImageSize($file);

        $width = (int)@Settings::staticGet('img_min_w');
        $height = (int)@Settings::staticGet('img_min_h');
        $width = ($width) ? $width : 150;
        $height = ($height) ? $height : 150;

        $notWidth = ($this->_imageSize['width'] < $width);
        $notHeight = ($this->_imageSize['height'] < $height);

		if(!headers_sent()) header('Content-type: image/jpeg', true);
        if ($notHeight OR $notWidth) {
            return @imagejpeg($file);
        }

        $this->_reflectionImageIfNeed($file, $data);
        $this->_addLogoIfNeed($file, $data);

        return @imagejpeg($file);
    }

    private function _getImageSize($image)
    {
        return array('width' => @imagesx($image),
            'height' => @imagesy($image));
    }

    public function _reflectionImageIfNeed(&$file, Array $data = null)
    {
        $reflectionType = (isset($data['reflection'])) ? $data['reflection'] : @Settings::staticGet('reflection');
        if ($reflectionType) {
            $this->_reflectionImage($file, (int)$reflectionType);
        }
    }

    public function _reflectionImage(&$file, $reflectionType)
    {
        $size = $this->_imageSize;

        $reflected = imagecreatetruecolor($size['width'], $size['height']);

        imagealphablending($reflected, false);
        imagesavealpha($reflected, true);

        for ($y = 1; $y <= $size['height']; ++$y) {
            for ($x = 0; $x < $size['width']; ++$x) {
                $width = $size['width'] - ($x + 1);
                $height = $size['height'] - $y;
                switch ($reflectionType) {
                    case self::REFLECTION_HORIZONTAL:
                        $newX = $width;
                        $newY = $y - 1;
                        break;
                    case self::REFLECTION_VERTICAL:
                        $newY = $height;
                        $newX = $x;
                        break;
                    case self::REFLECTION_DOUBLE:
                        $newX = $width;
                        $newY = $height;
                        break;
                }
                $rgba = imagecolorat($file, $newX, $newY);
                $rgba = imagecolorsforindex($file, $rgba);
                $rgba = imagecolorallocatealpha($reflected, $rgba['red'], $rgba['green'], $rgba['blue'], $rgba['alpha']);
                imagesetpixel($reflected, $x, $y - 1, $rgba);
            }
        }
        $file = $reflected;
    }

    private function _addLogoIfNeed(&$file, Array $data = null)
    {
        $enable = (isset($data['enable_copyright'])) ? $data['enable_copyright'] : @Settings::staticGet('enable_copyright');

        if ($enable) {

            $logo = (isset($data['logo'])) ? $data['logo'] : Settings::staticGet('logo');
            if (isset($logo)) {
                $logoPos = (isset($data['logoPos'])) ? $data['logoPos'] : (int)@Settings::staticGet('logoPos');
                $this->_addLogoToImage($file, $logo, $logoPos, $data);
            }
        }
    }

    private function _addLogoToImage(&$file, $logoPath, $logoPos, Array $data = null)
    {
        $imageSize = $this->_getImageSize($file);

        $needSetFonCollor = (isset($data['logoFont'])) ? $data['logoFont'] : @Settings::staticGet('logoFont');
        $isTextImage = (strpos($logoPath, 'text::') === 0);

        if ($isTextImage and !$needSetFonCollor) {

            $str = substr($logoPath, 6);
            $logoSize = array('height' => 10,
                'width' => strlen($str) * 10);

            $font = (int)$imageSize['width'] / Constants::LOGO_FONT_DELIMITER;

            $logoPos = $this->_getLogoPos($imageSize,
                $logoSize,
                $logoPos,
                $font);
            $color = (isset($data['logo_collor'])) ? $data['logo_collor'] : @Settings::staticGet('logo_collor');

            @imagettftext($file,
                $font,
                0,
                $logoPos['x'],
                $logoPos['y'],
                str_replace('#', '0x', $color),
                './dolly_templates/fonts/arial.ttf',
                $str);

        } else {
            $font = (int)$imageSize['width'] / 50;

            $logo = file_get_contents($logoPath);
            $logo = @imagecreatefromstring($logo);

            $logoSize = $this->_getImageSize($logo);
            $logoPos = $this->_getLogoPos($imageSize,
                $logoSize,
                $logoPos,
                $font);

            imagealphablending($file, true);
            imagealphablending($logo, true);

            imagecopy($file,
                $logo,
                $logoPos['x'],
                $logoPos['y'],
                0,
                0,
                $logoSize['width'],
                $logoSize['height']);

        }
    }

    private function _getLogoPos($imageSize, $logoSize, $logoPos, $font)
    {
        switch ($logoPos) {
            case self::LEFT_TOP:
                $x = Constants::LOGO_PADDING + $font + 10;
                $y = Constants::LOGO_PADDING + ($font * 2);
                break;
            case self::LEFT_BOTTOM:
                $x = Constants::LOGO_PADDING + $font + 10;
                $y = $imageSize['height'] - $logoSize['height'] - Constants::LOGO_PADDING - ($font * 2);
                break;
            case self::RIGHT_TOP:
                $x = $imageSize['width'] - $logoSize['width'] - Constants::LOGO_PADDING - $font - 10;
                $y = Constants::LOGO_PADDING + ($font * 2);
                break;
            case self::RIGHT_BOTTOM:
                $x = $imageSize['width'] - $logoSize['width'] - Constants::LOGO_PADDING - $font - 10;
                $y = $imageSize['height'] - $logoSize['height'] - Constants::LOGO_PADDING - ($font * 2);
        }
        return array('x' => $x,
            'y' => $y);
    }


}

class CacheBackend
{
    protected static $_instance;

    public static function saveFile($fileName, $data, $mimeType = null, $url = null)
    {
        self::getInstance()->saveFile($fileName, $data, $mimeType, $url);
    }

	public static function getInstanceType() { return (Settings::staticGet('cacheBackend') ?: 'File').'CacheBackend'; }

	public static function getInstance()
	{
		if(!self::$_instance)
		 {
			$adapterName = self::getInstanceType();
			self::$_instance = new $adapterName();
		 }
		return self::$_instance;
	}

    public static function fileExists($fileName)
    {
        return self::getInstance()->fileExists($fileName);
    }

    public static function getFile($fileName)
    {

        return self::getInstance()->getFile($fileName);
    }

    public static function createDir($path)
    {
        self::getInstance()->createDir($path);
    }

    public static function getPages()
    {
        return self::getInstance()->getPages();
    }

    public static function updateFile($fileName, $data)
    {
        self::getInstance()->updateFile($fileName, $data);
    }

    public static function clearCache($dir = null, $type = 'all')
    {
        self::getInstance()->clearCache($dir, $type);
    }

    public static function install()
    {
		$c = self::getInstanceType();
		$c::PreInstallationCheck();
        self::getInstance()->install();
    }
}

class NotCacheBackend implements CacheBackendInterface
{
	public static function PreInstallationCheck() {}

    public function fileExists($fileName)
    {
        return false;
    }

    public function saveFile($fileName, $data, $mimeType = null, $url = null)
    {
    }

    public function getFile($fileName)
    {
    }

    public function createDir($path)
    {
    }

    public function getPages()
    {
    }

    public function updateFile($fileName, $data)
    {
    }

    public function clearCache($dir = null, $type = 'all')
    {
    }

    public function install()
    {
    }
}

class FileCacheBackend implements CacheBackendInterface
{
	public static function PreInstallationCheck() {}

	public function __construct()
	{
		$this->httpresponses = new FileSystemStorage('/storage/httpresponses.php', ['readonly' => false, 'root' => MSSE_INC_DIR]);
	}

    public function fileExists($fileName)
    {
        $fileName = $this->_fileName($fileName);
        if (!file_exists($fileName) AND
            !file_exists($fileName . '.html')
        ) {
            return false;
        }

        return true;
    }

    private function _fileName($fileName, $toGetFile = true)
    {
        $parser = new Parser();
        if (@$fileName{0} != '/') {
            $fileName = "/{$fileName}";
        }
        $fileName = './' . $parser->_cacheDir . "$fileName";

        if ((!file_exists($fileName) OR is_dir($fileName)) and $toGetFile) {
            $fileName = "{$fileName}.html";
        }

        return $fileName;
    }

    public function getFile($fileName)
    {
        @$type = Parser::_contentType($this->_fileName($fileName));
        if ($type == 'image/gif') {
            ob_start();
            header("Content-Type: image/gif");
            readfile($this->_fileName($fileName));
            ob_end_flush();
        } else {
            header("Content-type: {$type}");

            return @file_get_contents($this->_fileName($fileName));
        }
    }

    public function createDir($path)
    {
        @mkdir($path, 0777, true);
    }

    public function getPages()
    {
        $fileName = $this->_fileName(Parser::PAGES_FILE);
        if (file_exists($fileName)) {
            return file($fileName);
        }

        return array();
    }

    public function updateFile($fileName, $data)
    {
        $this->saveFile($fileName, $data);
    }

    public function saveFile($fileName, $data, $mimeType = null, $url = null)
    {
        if ($fileName and $fileName !== '.html') {
            if($mimeType && 'text/html' !== $mimeType) $this->httpresponses->$fileName->content_type = $mimeType;
			@file_put_contents($this->_fileName($fileName, false), $data);
        }
    }

    public function clearCache($dir = null, $type = 'all')
    {
        switch ($type) {
            case 'all':
                $dir = ($dir) ? $dir : Parser::getCacheDir();
                $files = glob($dir . '/{.??*,.[!.],*}', GLOB_BRACE);
                if ($files) {
                    foreach ($files as $file) {
                        (is_dir($file)) ? CacheBackend::clearCache($file) : @unlink($file);
                    }
                }
                @unlink($dir . '/.html');

                @rmdir($dir);
                $baseDirPath = dirname(__FILE__) . '/' . Parser::getCacheDir();
                @rmdir($baseDirPath);

                break;

            case 'pages':
                function getFiles($parent = '/', &$out = array())
                {
                    $pages = @file($parent . Parser::PAGES_FILE);
					if($pages) foreach ($pages as $page) {
                        $page = trim($page);
                        if (@$page{strlen($page) - 1} === '/') {
                            getFiles($parent . $page, $out);
                        } else {
                            $out[] = $parent . $page;
                        }
                    }
                }

                ;
                $out = array();
                getFiles(Parser::$CACHE_DIR . '/', $out);

                foreach ($out as $file) {
                    @unlink($file);
                }
                break;
        }

    }

    public function install()
    {
        @mkdir(Parser::getCacheDir());
    }

 	private $httpresponses;
}

class SqlAbstractCacheBackend
{
    protected static $DSN_STRING = '#BASE_TYPE#:host=#HOST#;dbname=#DBNAME#;charset=utf8';
    protected $_connect;
    private $_host;
    private $_dbName;
    private $_user;
    private $_password;

	public static function PreInstallationCheck()
	 {
        $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',);
		$dbhost = empty($_POST['dbhost']) ? 'localhost' : $_POST['dbhost'];
		try
		 {
			$dbc = new PDO("mysql:host=$dbhost;dbname=$_POST[dbname];charset=utf8", $_POST['dbuser'], $_POST['dbpassword'], $options);
		 }
		catch(PDOException $e)
		 {
			throw new Exception(l10n()->check_dbc_params.". {$e->GetMessage()}", $e->GetCode());
		 }
		$settings = new Settings();
		$settings->set('dbHost',     $dbhost);
		$settings->set('dbName',     $_POST['dbname']);
		$settings->set('dbUser',     $_POST['dbuser']);
		$settings->set('dbPassword', $_POST['dbpassword']);
		$settings->save();
	 }

    public function __construct()
    {
        $this->_host = Settings::staticGet('dbHost') ?: 'localhost';
        $this->_dbName = Settings::staticGet('dbName');
        $this->_user = Settings::staticGet('dbUser');
        $this->_password = Settings::staticGet('dbPassword');
        $dsn = $this->_createDsnString();
        $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',);
        $this->_connect = new PDO($dsn, $this->_user, $this->_password, $options);
		$this->_connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    protected function _createDsnString()
    {
        $params = array('#BASE_TYPE#' => $this->_dbType,
            '#HOST#' => $this->_host,
            '#DBNAME#' => $this->_dbName);
        $dsn = str_replace(array_keys($params), array_values($params), self::$DSN_STRING);
        return $dsn;
    }
}

class MysqlCacheBackend extends SqlAbstractCacheBackend implements CacheBackendInterface
{
    protected $_dbType = 'mysql';

    public function saveFile($fileName, $data, $mimeType = null, $url = null)
    {
        if (!$mimeType) {
            $mimeType = 'text/html';
        }
        $data = $this->_encodeImage($data, $mimeType);
        $sql = "INSERT INTO files(`file_name`, `content`, `mimeType`) VALUES
                    (:fileName,
                     :content,
                     :mimeType)";
        $sth = $this->_connect->prepare($sql);
        $sth->execute(array(':fileName' => $fileName,
            ':content' => $data,
            ':mimeType' => $mimeType));
    }

    protected function _encodeImage($data, $mimeType)
    {
        if (Images::isImage($mimeType)) {
            $data = base64_encode($data);
        }
        return $data;
    }

    public function updateFile($fileName, $data)
    {
        $content = $this->_connect->quote($data);
        $this->_connect->exec("UPDATE files SET content = {$content} WHERE file_name = '/{$fileName}'");
    }

    public function getFile($fileName)
    {
        $fileName = $this->_getFileName($fileName);
        $file = $this->_connect
            ->query("SELECT * FROM files WHERE file_name IN ('{$fileName}', '/{$fileName}', '{$fileName}.html', '/{$fileName}.html')")
            ->fetchObject();

        header("Content-type: {$file->mimeType}");
        $file->content = $this->_decodeImage($file->content,
            $file->mimeType);

        return $file->content;
    }

    protected function _getFileName($fileName)
    {
        if (!$this->fileExists($fileName)) {
            if (strrpos($fileName, '.html')) {
                $pos = strrpos($fileName, '.html');
                $fileName = substr($fileName, 0, $pos);
                return $fileName;
            } else {
                $fileName .= '.html';
                return $fileName;
            }
        }

        return $fileName;
    }

    public function fileExists($fileName)
    {
        $count = $this->_connect
            ->query("SELECT COUNT(*) FROM files
                               WHERE file_name IN ('{$fileName}', '/{$fileName}', '{$fileName}.html', '/{$fileName}.html')")
            ->fetchColumn();

        return ($count == '0') ? false : true;
    }

    protected function _decodeImage($data, $mimeType)
    {
        if (Images::isImage($mimeType)) {
            $data = base64_decode($data);
        }
        return $data;
    }

    public function createDir($path)
    {
    }

    public function getPages()
    {
        $pages = $this->_connect
            ->query("SELECT file_name FROM files WHERE mimeType = 'text/html'")
            ->fetchAll(PDO::FETCH_COLUMN);
        return $pages;
    }

    public function clearCache($dir = null, $type = 'all')
    {
        $this->_connect->exec('TRUNCATE TABLE files');
        rmdir(Parser::getCacheDir());
    }

    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `files` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `file_name` tinytext,
                    `content` longtext,
                    `mimeType` tinytext,
                    `isImage` tinyint(1) DEFAULT '0',
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `file_name` (`file_name`(250))
                )";
        $this->_connect->exec($sql);
    }
}

class MysqlMysqliCacheBackend implements CacheBackendInterface
{
	public static function PreInstallationCheck()
	 {
		$dbhost = empty($_POST['dbhost']) ? 'localhost' : $_POST['dbhost'];
		set_error_handler(function($no, $str, $file, $line, $context){});
		$dbc = new Mysqli($dbhost, $_POST['dbuser'], $_POST['dbpassword'], $_POST['dbname']);
		restore_error_handler();
		if($dbc->connect_error) throw new Exception(l10n()->check_dbc_params.". $dbc->connect_error", $dbc->connect_errno);
		$settings = new Settings();
		$settings->set('dbHost',     $dbhost);
		$settings->set('dbName',     $_POST['dbname']);
		$settings->set('dbUser',     $_POST['dbuser']);
		$settings->set('dbPassword', $_POST['dbpassword']);
		$settings->save();
	 }

    protected static $DSN_STRING = '#BASE_TYPE#:host=#HOST#;dbname=#DBNAME#;charset=utf8';
    protected $_dbType = 'mysql';
    protected $_connect;
    private $_host;
    private $_dbName;
    private $_user;
    private $_password;

    public function __construct()
    {
        $this->_host = Settings::staticGet('dbHost') ?: 'localhost';
        $this->_dbName = Settings::staticGet('dbName');
        $this->_user = Settings::staticGet('dbUser');
        $this->_password = Settings::staticGet('dbPassword');

        $this->_connect = new Mysqli($this->_host, $this->_user, $this->_password, $this->_dbName);
    }

    public function saveFile($fileName, $data, $mimeType = null, $url = null)
    {
        if (!$mimeType) {
            $mimeType = 'text/html';
        }
        $data = $this->_encodeImage($data, $mimeType);
        $sql = "INSERT INTO files(`file_name`, `content`, `mimeType`) VALUES
                    (?,
                     ?,
                     ?)";

        $sth = $this->_connect->prepare($sql);
        $sth->bind_param('sss',
            $fileName,
            $data,
            $mimeType);
        $sth->execute();
        $sth->close();
    }

    protected function _encodeImage($data, $mimeType)
    {
        if (Images::isImage($mimeType)) {
            $data = base64_encode($data);
        }
        return $data;
    }

    public function updateFile($fileName, $data)
    {
        $res = $this->_connect->prepare("UPDATE files SET content = ? WHERE file_name = ?");
        $name = "/{$fileName}";
        $res->bind_param('ss', $data, $name);
        $r = $res->execute();
        $res->close();
    }

    public function getFile($fileName)
    {
        $fileName = $this->_getFileName($fileName);
        $file = $this->_connect
            ->query("SELECT * FROM files WHERE file_name IN ('{$fileName}', '/{$fileName}', '{$fileName}.html', '/{$fileName}.html')")
            ->fetch_object();

        header("Content-type: {$file->mimeType}");
        $file->content = $this->_decodeImage($file->content,
            $file->mimeType);

        return $file->content;
    }

    protected function _getFileName($fileName)
    {
        if (!$this->fileExists($fileName)) {
            if (strrpos($fileName, '.html')) {
                $pos = strrpos($fileName, '.html');
                $fileName = substr($fileName, 0, $pos);
                return $fileName;
            } else {
                $fileName .= '.html';
                return $fileName;
            }
        }

        return $fileName;
    }

    public function fileExists($fileName)
    {
        $count = $this->_connect
            ->query("SELECT COUNT(*) FROM files
                     WHERE file_name IN ('{$fileName}', '/{$fileName}', '{$fileName}.html', '/{$fileName}.html')",
                MYSQLI_USE_RESULT)
            ->fetch_row();
        return ($count[0] == '0') ? false : true;
    }

    protected function _decodeImage($data, $mimeType)
    {
        if (Images::isImage($mimeType)) {
            $data = base64_decode($data);
        }
        return $data;
    }

    public function createDir($path)
    {
    }

    public function getPages()
    {
        $out = array();
        $pages = $this->_connect
            ->query("SELECT file_name FROM files WHERE mimeType = 'text/html'", MYSQLI_USE_RESULT);
        while ($res = $pages->fetch_row()) {
            $out[] = $res[0];
        }
        return $out;
    }

    public function clearCache($dir = null, $type = 'all')
    {
        $this->_connect->query('TRUNCATE TABLE files');
        @rmdir(Parser::getCacheDir());
    }

    public function install()
    {
		$sql = "CREATE TABLE IF NOT EXISTS `files` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`file_name` tinytext,
					`content` longtext,
					`mimeType` tinytext,
					`isImage` tinyint(1) DEFAULT '0',
					PRIMARY KEY (`id`),
					UNIQUE KEY `file_name` (`file_name`(250))
				)";
		if(true !== $this->_connect->query($sql)) throw new Exception($this->_connect->error, $this->_connect->errno);
    }

    protected function _createDsnString()
    {
        $params = array('#BASE_TYPE#' => $this->_dbType,
            '#HOST#' => $this->_host,
            '#DBNAME#' => $this->_dbName);
        $dsn = str_replace(array_keys($params), array_values($params), self::$DSN_STRING);
        return $dsn;
    }
}

class SqliteCacheBackend extends SqlAbstractCacheBackend implements CacheBackendInterface
{
	public static function PreInstallationCheck() {}

    protected $_dbType = 'sqlite';

    public function __construct()
    {
        $this->_connect = new PDO('sqlite:dolly.db');
    }

    public function saveFile($fileName, $data, $mimeType = null, $url = null)
    {
        if (!$mimeType) {
            $mimeType = 'text/html';
        }
        $data = $this->_encodeImage($data, $mimeType);
        $sql = "INSERT INTO files(`file_name`, `content`, `mimeType`) VALUES
                    (:fileName,
                     :content,
                     :mimeType)";
        $sth = $this->_connect->prepare($sql);
        $sth->execute(array(':fileName' => $fileName,
            ':content' => $data,
            ':mimeType' => $mimeType));
    }

    protected function _encodeImage($data, $mimeType)
    {
        if (Images::isImage($mimeType)) {
            $data = base64_encode($data);
        }
        return $data;
    }

    public function updateFile($fileName, $data)
    {
        $content = $this->_connect->quote($data);
        $this->_connect->exec($sql = "UPDATE files SET content = {$content} WHERE file_name = '/{$fileName}'");
    }

    public function getFile($fileName)
    {
        $fileName = $this->_getFileName($fileName);
        $file = $this->_connect->query("SELECT * FROM files
                                        WHERE file_name IN ('{$fileName}',
                                                            '/{$fileName}',
                                                            '{$fileName}.html',
                                                            '/{$fileName}.html')")->fetchObject();
        header("Content-type: {$file->mimeType}");
        $file->content = $this->_decodeImage($file->content, $file->mimeType);
        return $file->content;
    }

    protected function _getFileName($fileName)
    {
        if (!$this->fileExists($fileName)) {
            if (strrpos($fileName, '.html')) {
                $pos = strrpos($fileName, '.html');
                $fileName = substr($fileName, 0, $pos);
                return $fileName;
            } else {
                $fileName .= '.html';
                return $fileName;
            }
        }
        return $fileName;
    }

    public function fileExists($fileName)
    {
        $count = $this->_connect
            ->query("SELECT COUNT(*) FROM files
                               WHERE file_name IN ('{$fileName}', '/{$fileName}', '{$fileName}.html', '/{$fileName}.html')")
            ->fetchColumn();

        return ($count == '0') ? false : true;
    }

    protected function _decodeImage($data, $mimeType)
    {
        if (Images::isImage($mimeType)) {
            $data = base64_decode($data);
        }
        return $data;
    }

    public function createDir($path)
    {
    }

    public function getPages()
    {
        $pages = $this->_connect
            ->query("SELECT file_name FROM files WHERE mimeType = 'text/html' AND `isImage` = 0")
            ->fetchAll(PDO::FETCH_COLUMN);

        return $pages;
    }

    public function clearCache($dir = null, $type = 'all')
    {
        $this->_connect->exec('DELETE FROM files;');
        $this->_connect->exec('VACUUM;');
        @rmdir(Parser::getCacheDir());
    }

    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `files` (
                    `id` INTEGER PRIMARY KEY,
                    `file_name` TEXT,
                    `content` TEXT,
                    `mimeType` TEXT,
                    `isImage` INTEGER DEFAULT '0'
                )";
        $this->_connect->exec($sql);
    }
}

class Sqlite3CacheBackend implements CacheBackendInterface
{
	public static function PreInstallationCheck() {}

    protected $_dbType = 'sqlite';

    public function __construct()
    {
        $this->_connect = new SQLite3('dolly.db');
    }

    public function saveFile($fileName, $data, $mimeType = null, $url = null)
    {
        if (!$mimeType) {
            $mimeType = 'text/html';
        }
        $data = $this->_encodeImage($data, $mimeType);
        $sql = "INSERT INTO files(`file_name`, `content`, `mimeType`) VALUES
                    (:fileName,
                     :content,
                     :mime)";
        $stmt = $this->_connect->prepare($sql);
        $stmt->bindValue(':fileName', $fileName, SQLITE3_TEXT);
        $stmt->bindValue(':content', $data, SQLITE3_TEXT);
        $stmt->bindValue(':mime', $mimeType, SQLITE3_TEXT);

        $stmt->execute();
    }

    protected function _encodeImage($data, $mimeType)
    {
        if (Images::isImage($mimeType)) {
            $data = base64_encode($data);
        }
        return $data;
    }

    public function updateFile($fileName, $data)
    {
        $stmt = $this->_connect->prepare($sql = "UPDATE files SET content = :content WHERE file_name = '/{$fileName}'");
        $stmt->bindValue(':content', $data, SQLITE3_TEXT);
        $stmt->execute();
    }

    public function getFile($fileName)
    {
        $fileName = $this->_getFileName($fileName);
        $file = $this->_connect->query("SELECT * FROM files
                                        WHERE file_name IN ('{$fileName}',
                                                            '/{$fileName}',
                                                            '{$fileName}.html',
                                                            '/{$fileName}.html')");
        $result = $file->fetchArray();
        $file = (object)$result;
        @header("Content-type: {$file->mimeType}");
        @$file->content = $this->_decodeImage(@$file->content, @$file->mimeType);

        return $file->content;
    }

    protected function _getFileName($fileName)
    {
        if (!$this->fileExists($fileName)) {
            if (strrpos($fileName, '.html')) {
                $pos = strrpos($fileName, '.html');
                $fileName = substr($fileName, 0, $pos);
                return $fileName;
            } else {
                $fileName .= '.html';
                return $fileName;
            }
        }
        return $fileName;
    }

    public function fileExists($fileName)
    {
        $count = $this->_connect
            ->query("SELECT COUNT(*) FROM files
                               WHERE file_name IN ('{$fileName}', '/{$fileName}', '{$fileName}.html', '/{$fileName}.html')")
            ->fetchArray();

        return ($count[0] == 0) ? false : true;
    }

    protected function _decodeImage($data, $mimeType)
    {
        if (Images::isImage($mimeType)) {
            $data = base64_decode($data);
        }
        return $data;
    }

    public function createDir($path)
    {
    }

    public function getPages()
    {
        $pages = $this->_connect
            ->query("SELECT file_name FROM files WHERE mimeType = 'text/html' AND `isImage` = 0")->fetchArray(SQLITE3_ASSOC);
        return $pages;
    }

    public function clearCache($dir = null, $type = 'all')
    {
        $this->_connect->exec('DELETE FROM files;');
        $this->_connect->exec('VACUUM;');
        @rmdir(Parser::getCacheDir());
    }

    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `files` (
                    `id` INTEGER PRIMARY KEY,
                    `file_name` TEXT,
                    `content` TEXT,
                    `mimeType` TEXT,
                    `isImage` INTEGER DEFAULT '0'
                )";
        $this->_connect->exec($sql);
    }
}

class TextTranslate
{
    const DEFAULT_ADAPTER = 'NotTranslate';
    const SELECTOR_FOR_TRANSLATE_ITEMS = 'text';
    private static $_instance;

    public function translate($string, $adapter = null)
    {
        return self::getInstance($adapter)->translate($string);
    }

    public static function getInstance($adapter = null)
    {
        $settings = new Settings();
        if (!$adapter) {

            $adapter = @$settings->get('translateAdapter');
        }
        if (!isset($adapter)) {
            $adapter = self::DEFAULT_ADAPTER;
        }
        $adapter = "{$adapter}Adapter";
        $obj = new $adapter();

        $obj->setSource(@Settings::staticGet('translateSource'));
        $obj->setTarget(@Settings::staticGet('translateTarget'));
        return $obj;
    }
}

class NotTranslateAdapter implements TranslateAdapter
{
    public function setSource()
    {
    }

    public function setTarget()
    {
    }

    public function translate($string)
    {
        return $string;
    }
}

class YandexTranslateAdapter implements TranslateAdapter
{
    protected $_sl;
    protected $_tl;


    public function setSource($source = 'ru')
    {
        $this->_sl = $source;
    }

    public function setTarget($target = 'en')
    {
        $this->_tl = $target;
    }

	public function translate($string)
	 {
		$http = new http();
		$page = $http->get('https://translate.yandex.com');
		$sidpos = strpos($page, "SID: '") + 6;
		$sid = @substr($page, $sidpos, @strpos($page, "',", $sidpos) - $sidpos);
		$sid = @strrev(@substr($sid, 0, 8)) . '.' . strrev(substr($sid, 9, 8)) . '.' . strrev(substr($sid, 18, 8));
		$sid = $sid.'-0-0';
		$page = $http->post("https://translate.yandex.net/api/v1/tr.json/translate?lang={$this->_sl}-{$this->_tl}&srv=tr-url&format=plain&id={$sid}", array('text' => $string));
			$jsonarr = json_decode($page);
			if (isset($jsonarr->text)) {
				return str_replace(array('===',
					'|||'), array(' = = = ',
					' | | | '), $jsonarr->text[0]);
			} else if ($jsonarr->code == 413) {
				return true;
			}
			return false;
	 }
}

class BaiduTranslateAdapter implements TranslateAdapter
{
    protected $_sl;
    protected $_tl;

    public function setSource($source = 'ru')
    {
        $this->_sl = $this->_getLangCode($source);
    }

    private function _getLangCode($lang)
    {
        $langs = array('ar' => 'ara',
            'et' => 'est',
            'bg' => 'bul',
            'da' => 'dan',
            'fr' => 'fra',
            'fi' => 'fin',
            'ko' => 'kor',
            'ro' => 'rom',
            'sv' => 'swe',
            'sl' => 'slo',
            'es' => 'spa',
            'zh-tw' => 'cht');
        return (isset($langs[$lang])) ? $langs[$lang] : $lang;
    }

    public function setTarget($target = 'en')
    {
        $this->_tl = $this->_getLangCode($target);
    }

    public function translate($string)
    {

        $qstr = 'to=' . $this->_tl;
        if ($this->_sl) {
            $qstr .= '&from=' . $this->_sl;
        }

        $qstr .= '&query=';
        $qstr .= $string;

        $url = 'http://fanyi.baidu.com/v2transapi';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $qstr);
        $output = curl_exec($ch);
        curl_close($ch);

        $jsonarr = json_decode($output);

        if (!@is_object($jsonarr) or !@is_array($jsonarr->trans_result->data)) {
            return false;
        }

        foreach (@$jsonarr->trans_result->data as $val) {
            $result[] = $val->dst;
        }
        return $result[0];
    }
}

/**
 * GoogleTranslate.class.php
 *
 * Class to talk with Google Translator for free.
 *
 * @package PHP Google Translate Free;
 * @category Translation
 * @author Adrián Barrio Andrés
 * @author Paris N. Baltazar Salguero <sieg.sb@gmail.com>
 * @copyright 2016 Adrián Barrio Andrés
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License 3.0
 * @version 2.0
 * @link https://statickidz.com/
 */
/**
 * Main class GoogleTranslate
 *
 * @package GoogleTranslate
 *
 */
class GoogleTranslate
{
    /**
     * Retrieves the translation of a text
     *
     * @param string $source
     *            Original language of the text on notation xx. For example: es, en, it, fr...
     * @param string $target
     *            Language to which you want to translate the text in format xx. For example: es, en, it, fr...
     * @param string $text
     *            Text that you want to translate
     *
     * @return string a simple string with the translation of the text in the target language
     */
    public static function translate($source, $target, $text)
    {
        // Request translation
        $response = self::requestTranslation($source, $target, $text);
        // Get translation text
        // $response = self::getStringBetween("onmouseout=\"this.style.backgroundColor='#fff'\">", "</span></div>", strval($response));
        // Clean translation
        $translation = self::getSentencesFromJSON($response);
        return $translation;
    }
    /**
     * Internal function to make the request to the translator service
     *
     * @internal
     *
     * @param string $source
     *            Original language taken from the 'translate' function
     * @param string $target
     *            Target language taken from the ' translate' function
     * @param string $text
     *            Text to translate taken from the 'translate' function
     *
     * @return object[] The response of the translation service in JSON format
     */
    protected static function requestTranslation($source, $target, $text)
    {
        // Google translate URL
        $url = "https://translate.google.com/translate_a/single?client=at&dt=t&dt=ld&dt=qca&dt=rm&dt=bd&dj=1&hl=es-ES&ie=UTF-8&oe=UTF-8&inputm=2&otf=2&iid=1dd3b944-fa62-4b55-b330-74909a99969e";
        $fields = array(
            'sl' => urlencode($source),
            'tl' => urlencode($target),
            'q' => urlencode($text)
        );
        // if(strlen($fields['q'])>=5000)
            // throw new \Exception("Maximum number of characters exceeded: 5000");
        
        // URL-ify the data for the POST
        $fields_string = "";
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');
        // Open connection
        $ch = curl_init();
        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AndroidTranslate/5.3.0.RC02.130475354-53000263 5.1 phone TRANSLATE_OPM5_TEST_1');
        // Execute post
        $result = curl_exec($ch);
        // Close connection
        curl_close($ch);
        return $result;
    }
    /**
     * Dump of the JSON's response in an array
     *
     * @param string $json
     *            The JSON object returned by the request function
     *
     * @return string A single string with the translation
     */
    protected static function getSentencesFromJSON($json)
    {
        $sentencesArray = json_decode($json, true);
        $sentences = "";
        foreach ($sentencesArray["sentences"] as $s) {
            $sentences .= isset($s["trans"]) ? $s["trans"] : '';
        }
        return $sentences;
    }
}

class GoogleTranslateAdapter implements TranslateAdapter
{
    const TRANSLATE_URL = 'http://translate.google.com/translate_a/t';
    private $_config = array('client' => 'x',
        'hl' => 'en',
        'ie' => 'UTF-8',
        'oe' => 'UTF-8',
        'multires' => 1,
        'otf' => 0,
        'pc' => 1,
        'trs' => 1,
        'ssel' => 0,
        'tsel' => 0,
        'sc' => 1,);

    public function setSource($source = 'ru')
    {
        $this->_config['sl'] = $source;
    }

    public function setTarget($target = 'en')
    {
        $this->_config['tl'] = $target;
    }

    public function translate($string)
    {
		$obj = new GoogleTranslate();
		return $obj->translate($this->_config['sl'], $this->_config['tl'], $string);
		// -------
		$o = ['accept_encoding' => 'gzip,deflate', 'referer' => 'https://translate.google.ru', 'follow_location' => 5, 'user_agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36'];
		$proxy = new FileSystemStorage('/storage/proxyservers.php', ['readonly' => true, 'root' => MSSE_INC_DIR]);
		if(count($proxy)) $o['proxy'] = $proxy;
        $client = new HTTP($o);
        $this->_config['q'] = htmlspecialchars_decode($string);
        $response = $client->POST(self::TRANSLATE_URL, $this->_config);

        $response = json_decode($response);
        $result = (is_object($response)) ? $response->sentences[0]->trans : $response;
        if (strpos($result, '===') === false and strpos($result, '= = =') === false) {
            return false;
        }
        $result = str_replace(
            array('& nbsp;',
                '& Nbsp;',
                '& mdash;'),
            array('&nbsp;',
                '&nbsp;',
                '&mdash;'),
            $result
        );

        return $result;
    }
}

class Synonimizer
{
    static $_matches = false;
    private static $index = 0;
    private $_ROW_DELIMITER = '=>';
    private $_VALUE_DELIMITER = '|';
    private $_dictonary = '';
    private $_parsed = array();

    function __construct()
    {
        $this->_dictonary = @Settings::staticGet('synsDictonary');
        $this->_loadDictonary();


    }

    private function _loadDictonary()
    {
        $delimiters = Settings::staticGet('syns_delimiters') ?: Constants::SYNONYMS_DEFAULT_SEPARATOR;
        $delimiters = explode('#SEP#', $delimiters);

        $this->_ROW_DELIMITER = $delimiters[0];
        $this->_VALUE_DELIMITER = $delimiters[1];

        $dir = str_replace('/lib', '', dirname(__FILE__));
        $fileName = $dir . '/' . $this->_dictonary;
        if (!file_exists($fileName)) {
            $this->_parsed = array();
            return false;
        }
        $parsed = @file($fileName);
        if (!is_array($parsed)) {
            $parsed = array();
        }

        foreach ($parsed as $key => $value) {
            $value = trim($value);
            $strNotValid = (empty($value) || 0 === strpos($value, '#'));
            if ($strNotValid) {
                continue;
            }
            $value = explode($this->_ROW_DELIMITER, $value);

            $_key = @trim($value[0]);
            $_data = array();
            $delimiterExists = @(strpos($value[1], $this->_VALUE_DELIMITER) !== false);
            if ($delimiterExists) {
                $_data = $this->_parseSynonims($value, $_data);
            } else {
                $_data = $this->_handleAlias($value);
            }
            $this->_parsed[$_key] = $_data;
        }
    }

    private function _parseSynonims($value, $_data)
    {
        $data = @explode($this->_VALUE_DELIMITER, $value[1]);
        foreach ($data as $dataKey => $dataValue) {
            $_data[$dataKey] = trim($dataValue);
        }
        return $_data;
    }

    private function _handleAlias($value)
    {
        $_data = @array(trim($value[1]));
        $isAlias = @(strpos($_data[0], '@') === 0);
        if ($isAlias) {
            $_data = @$this->_parsed[substr($_data[0], 1)];
            return $_data;
        }
        return $_data;
    }

    static function synCallback($matches)
    {
        $out = @$matches[2];
        $matchesNotEmpty = (!empty(self::$_matches));
        if ($matchesNotEmpty) {
            $i = sizeof(self::$_matches) - 1;
            $i = ($i > 0) ? mt_rand(0, $i) : 0;
            $out = @self::$_matches[$i];
        }
        return @$matches[1] . $out . $matches[3];
    }

    public function synonimize($text)
    {


        $needSynonimize = @Settings::staticGet('synonimize');
        if (!isset($needSynonimize) or $needSynonimize == 'off') {
            return $text;
        }
        $text = ' ' . str_replace(array('\r\n',
                PHP_EOL), "\r\n", $text) . ' ';
        ++self::$index;


        foreach ($this->_parsed as $key => $matches) {
            $preg = '#([^\w\d\-])(' . preg_quote($key) . ')([^\w\d\-])#i';

            self::$_matches = &$matches;
            $text = preg_replace_callback($preg, 'Synonimizer::synCallback', $text);
        }
        return $text;
    }
}
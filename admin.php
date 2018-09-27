<?php
define('DOCUMENT_ROOT', substr(__FILE__, 0, -10));
define('MSSE_INC_DIR', DOCUMENT_ROOT.'/system/include');
define('MSSE_LIB_DIR', MSSE_INC_DIR.'/lib');
require_once(MSSE_INC_DIR.'/sys_config.php');
MSConfig::RequireFile('traits', 'datacontainer', 'filesystemstorage', 'l10n', 'tsystemmessages', 'tsystemupdates');
require_once(DOCUMENT_ROOT.'/Constants.php');
require_once(DOCUMENT_ROOT.'/lib/Controllers.php');
new L10N(Settings::staticGet('language') ?: 'ru', 'ru', ['root' => DOCUMENT_ROOT, 'dir' => '/languages']);

if(!defined('CURLPROXY_SOCKS4A')) define('CURLPROXY_SOCKS4A', 6);
if(!defined('CURLPROXY_SOCKS5_HOSTNAME')) define('CURLPROXY_SOCKS5_HOSTNAME', 7);

Controllers::setCharset('utf-8');

class AdminController extends Controllers
{
	use TSystemUpdates, TSystemMessages;

    public static function ignoredAuthActions()
    {
        return array('get_login',
                     'set_site_slave_type',
                     'get_token');
    }

    public function toAdmin()
    {
        Controllers::redirect('admin.php');
    }

    public function install()
    {
        $data = $_POST;
        $data['language'] = Settings::staticGet('language') ?: (@$_SESSION['language'] ?: 'ru');

        $url = $this->_api->getBaseUrl($data);
        $data['ip'] = $_SERVER['REMOTE_ADDR'];

        file_put_contents('notCacheUrls', trim($data['notCacheUrls']));
        unset($data['notCacheUrls']);

        file_put_contents('config.ini', $this->_api->getConfigFile($data));
        $this->_parser->loadMainPage($url);
    }

	public static function GetUpdatesURL() { 
	return 'https://localhost/'; 
	//return 'https://update.localhost.pl/'; 
	}
	public static function GetSiteRoot() { return DOCUMENT_ROOT; }

    public function msse_handle()
	{
		try
		 {
			$status = true;
			$msg = '';
			switch($this->ActionGET())
			 {
				case 'check_for_updates':
					// $plugins = [];
					// \Atlanta\Plugin::Each(function($pl) use(&$plugins){$plugins[$pl->GetName()] = $pl->GetVersion();});
					$data = ['product_id' => 'dollysites', 'version' => self::VERSION, 'key' => self::getApiKey()];
					// if($plugins) $data['plugins'] = $plugins;
					if($v = Settings::staticGet('updates__channel_id')) $data['channel_id'] = $v;
					if(!empty($_GET['lang'])) $data['lang'] = $_GET['lang'];
					// \Registry::SetValue('updates', 'last_check', time());
					$settings = new Settings();
					$settings->set('updates__last_check', time())->save();
					$this->CheckForUpdates($data, true);
					break;
			 }
			switch($this->ActionPOST())
			 {
				case 'apply_updates':
					$this->ApplyUpdates(['product_id' => 'dollysites', 'key' => self::getApiKey()]);
					break;
			 }
		 }
		catch(Exception $e)
		 {
			if(!($e instanceof EDocumentHandle)) MSConfig::HandleException($e, false);
			$msg = $e->GetMessage();
			$status = false;
		 }
		self::SendJSON(null, $msg, $status);
	}

    public function get_encoding()
    {
        echo $this->_parser->getEncode($_POST['url']);
        exit;
    }

    public function site_is_installed()
    {
        echo ($this->_parser->equals->siteIsInstalled()) ? '1' : '0';
        exit;
    }

    public function get_file()
    {

        $this->returnSavedPage(@$this->_pagePath($_POST['file']), true, 'utf-8');
        exit;
    }

    public function get_forms()
    {
        $forms = $this->_dom
            ->load($this->returnSavedPage(@$this->_pagePath($_POST['file']), false, 'utf-8'))
            ->find('form');
        include 'dolly_templates/forms.php';
    }

    public function save_page()
    {
        $this->_parser->editorSavePage($_POST['page'], $this->_pagePath($_POST['path']));
    }

    private function _pagePath($path)
    {
        if (@$path{0} == '//') {
            $path = substr($path, 2);
        }
        if (@$path{0} == '/') {
            $path = substr($path, 1);
        }
        $path = $this->_parser->paths()->replaceSpecialChars($path);
        return $path;
    }

    public function index()
    {
		$this->admin_dashboard();
    }

    public function admin_dashboard()
    {
        $orders = array();
        if (file_exists('./orders.txt')) {
            $file = file_get_contents('./orders.txt');
            $orders = unserialize($file);
			if(is_array($orders)) $orders = array_reverse($orders);
			else $orders = [];
        }

        $action = 'dashboard';
        include 'dolly_templates/admin_layout.php';
        exit;
    }

    public function admin_proxy()
    {
		$action = 'proxy';
		$proxy = new FileSystemStorage('/storage/proxyservers.php', ['readonly' => true, 'root' => MSSE_INC_DIR]);
		include 'dolly_templates/admin_layout.php';
		exit;
    }

	public function admin_proxy_save()
	{
		$proxy = new FileSystemStorage('/storage/proxyservers.php', ['readonly' => false, 'root' => MSSE_INC_DIR]);
		if(!empty($_POST['id']))
		 foreach($_POST['id'] as $k => $v)
		  {
			$proxy->$k->host = trim($_POST['host'][$k]);
			if('' === $proxy->$k->host) unset($proxy->$k);
			else
			 {
				$proxy->$k->port = Filter::GetIntOrNull($_POST['port'][$k], 'gt0');
				$proxy->$k->user = trim($_POST['user'][$k]);
				$proxy->$k->password = trim($_POST['password'][$k]);
				$proxy->$k->type = (int)$_POST['type'][$k];
				$proxy->$k->tunnel = !empty($_POST['tunnel'][$k]);
			 }
		  }
		if(!empty($_POST['host_new']))
		 foreach($_POST['host_new'] as $k => $v)
		  {
			$v = trim($v);
			if('' !== $v) $proxy(['host' => $v, 'port' => Filter::GetIntOrNull($_POST['port_new'][$k], 'gt0'), 'user' => trim($_POST['user_new'][$k]), 'password' => trim($_POST['password_new'][$k]), 'type' => (int)$_POST['type_new'][$k], 'tunnel' => !empty($_POST['tunnel_new'][$k])]);
		  }
	}

    public function admin_editor()
    {
        $action = 'editor';
        include 'dolly_templates/admin_layout.php';
        exit;
    }

    public function admin_auth_info()
    {
        $fileName = 'login.ini';
        if (isset($_POST['password'])) {
            file_put_contents($fileName, "{$_POST['login']}|{$_POST['password']}");
        }

        $file = file_get_contents($fileName);
        list($login, $password) = explode('|', $file);
        $action = 'auth_info';
        include 'dolly_templates/admin_layout.php';

    }

    public function admin_forms_handler()
    {
        @$handlers = file("./d-site/handlers");

        $action = 'forms_handler';
        include 'dolly_templates/admin_layout.php';
        exit;
    }

    public function admin_scripts()
    {
        $scripts = @file_get_contents("./{$this->_parser->cacheDir()}/_scripts");
        $scripts = @explode('#D_END_SCRIPTS#', $scripts);

        $action = 'scripts';
        include 'dolly_templates/admin_layout.php';
        exit;
    }

    public function admin_replaces()
    {
        $new = OtherFunctions::dolly_unserialize(@file_get_contents('replaces'));

        $action = 'replaces';
        include 'dolly_templates/admin_layout.php';
        exit;
    }

    public function admin_content()
    {
        $action = 'content';
        include 'dolly_templates/admin_layout.php';
        exit;
    }

    public function admin_images()
    {
        $action = 'images';
        include 'dolly_templates/admin_layout.php';
        exit;
    }

    public function api_login()
    {
        $api = new SlaveApi();
        if ($api->keyIsValid($_GET['key'])) {
            $authData = file_get_contents('login.ini');
            $authData = explode('|', $authData);

            $this->_setAuthCookies($authData);
        }

        Controllers::redirect('admin.php?');

    }

    public function login()
    {
        $this->_auth((string)@$_POST['user'], (string)@$_POST['password']);
        include 'dolly_templates/view_admin_login.php';
    }

    public function get_login()
    {
        $to = '';
        if (isset($_GET['action'])) {
            $to = "?action={$_GET['action']}";
        }

        if ($this->_auth($_GET['user'], $_GET['password'])) {
            Controllers::redirect("/admin.php{$to}");
        } else {
            Controllers::redirect('/admin.php?action=login');
        }
    }

    public function add_replacement()
    {
        @$out = array_merge($_POST['out'], array());
        if (@$_GET['sub'] === 'editor') {
            $replaces = @OtherFunctions::dolly_unserialize(file_get_contents('replaces'));
            $replaces[] = $out[0];

            @file_put_contents('replaces', OtherFunctions::dolly_serialize($replaces));
        } else {
            if (!isset($out)) {
                $out = array();
            }

            foreach ($out as $key => &$value) {
                if (!$value['l_input'] and $value['l_textarea']) {
                    $value['l_input'] = $value['l_textarea'];
                }
                if (!$value['r_input'] and $value['r_textarea']) {
                    $value['r_input'] = $value['r_textarea'];
                }

                if ($value['l_textarea']) {
                    @$value['change_type'] = ($value['change_type'] == 'on') ? 'preg' : 'string';

                    $value['l_input'] = @htmlspecialchars($value['l_textarea']);
                    $value['r_input'] = @htmlspecialchars($value['r_textarea']);
                } else {
                    unset($out[$key]);
                }

            }

            @file_put_contents('replaces', OtherFunctions::dolly_serialize($out));
        }
            //CacheBackend::clearCache(null, 'pages');
            Controllers::redirect('admin.php?action=admin_replaces');

    }

    public function auth_info()
    {
        if (@$login = $_POST['login']) {
            file_put_contents('login.ini', "{$login}|{$_POST['password']}");
        }

        list($login, $password) = explode('|', trim(file_get_contents('login.ini')));
        $this->toAdmin();

    }

    public function remove_site()
    {
        $this->_settings->set('base_url')->save();
        $this->_parser->removeSite();
		MSConfig::RequireFile('traits', 'datacontainer', 'filesystemstorage');
		$conf = new FileSystemStorage('/fs_config.php', ['readonly' => false, 'root' => MSSE_INC_DIR]);
		$conf->Clear();
        Controllers::redirect('/');
    }

    public function logout($redirect = true)
    {
        setcookie('auth');
        if ($redirect) {
            $this->toAdmin();
        }
    }

    public function handlers_settings()
    {
        if (isset($_POST['save'])) {

            $this->_settings
                ->set('mailSuccessPage', $_POST['mail_success_page'])
                ->save();

            file_put_contents('mail.tpl', $_POST['mail_template']);
        }
        $template = file_get_contents('mail.tpl');
        if (!isset($_GET['key'])) {

            $this->toAdmin();

        } else {

            Controllers::redirect("admin.php?mode=api&key={$_GET['key']}&action=get_handlers_from_iframe");
        }
    }

    public function getVersion() { return $this->_api->getVersion(); }

    public function lang()
    {
        $settings = new Settings();
        $settings->set('language', $_GET['lang'])->save();

        $this->toAdmin();

    }

    public function display_errors()
    {
        $settings = new Settings();
        if ($settings->get('displayErrors') == 'on') {

            $settings->set('displayErrors', 'off');
        } else {

            $settings->set('displayErrors', 'on');
        }
        $settings->save();
        $this->toAdmin();

    }

    public function get_site_archive()
    {
        $obj = new OtherFunctions();
        $obj->returnSiteArchive();
        exit;
    }

    public function get_files_selector_options()
    {
        echo '<option>' . $this->t('#SELECT_FILE#') . '</option>';
        $files = CacheBackend::getPages();
        @asort($files);
        $pages = array();
        $names = array();
        $namesUsed = array();
        if (isset($files) AND sizeof($files) > 0) {

            foreach ($files as $page) {
                $page = explode('|', $page);

                if (!in_array(trim($page[0]), $pages)) {
                    $pages[] = trim($page[0]);
                    $title = trim($page[1]);

                    if (in_array($title, $names)) {
                        $namesUsed[$title] += 1;
                        $title .= "({$namesUsed[$title]})";
                    } else {
                        $names[] = $title;
                    }
                    echo '<option value = "' . trim($page[0]) . '">' . $title . '</option>';
                }
            }
        }
    }

    public function add_ip_to_wl()
    {
        $this->_api->addIp();
		if(Settings::staticGet('base_url')) $this->toAdmin();
		else HTTP::Redirect('/');
    }

	public function translate_save()
	{
		$settings = new Settings();
		if(empty($_POST['translate'])) $settings->set('translateAdapter', 'NotTranslate');
		else
		 {
			$settings->set('translateAdapter', $_POST['translate_backend']);
			$settings->set('translateSource', $_POST['translate_source']);
			$settings->set('translateTarget', $_POST['translate_target']);
		 }
		$settings->save();
		CacheBackend::clearCache(null, 'pages');
		$this->redirect('?action=admin_content');
	}

    public function create_demo()
    {
        $fileName = 'admin_layout.php';
        $file = file_get_contents($fileName);
        $file = str_replace('<a href="#download" id ="get_archive"><?php echo $this->t(\'#MENU_GET_SITE#\');?></a>', '', $file);

        file_put_contents($fileName, $file);

        $this->toAdmin();

    }

    public function synonims_save()
    {

        $settings = new Settings();
        $settings->set('synonimize', isset($_POST['synonims_status']) ? $_POST['synonims_status'] : '');

        if (isset($_FILES['upload_files']['name'])) {
            $uploadfile = basename($_FILES['upload_files']['name']) . '.syns';

            if (move_uploaded_file($_FILES['upload_files']['tmp_name'], $uploadfile)) {
                @$settings->set('synsDictonary', $uploadfile);
                @$settings->set('syns_delimiters', "{$_POST['first_word']}#SEP#{$_POST['second_word']}");
            }
        } else {
            $settings->set('synsDictonary', $_POST['synonims_file']);
        }

        $settings->save();
        CacheBackend::clearCache(null, 'pages');

        $this->redirect('?action=admin_content');
    }

    public function images_save()
    {
        $settings = new Settings();
        @$settings->set('reflection', str_replace('_', '', $_POST['reflection']));
        @$settings->set('resize', $_POST['resize']);

        @$settings->set('img_min_h', $_POST['height']);
        @$settings->set('img_min_w', $_POST['width']);

        @$settings->set('scale', $_POST['scaling']);


        if (isset($_POST['enable_copyright'])) {
            @$settings->set('enable_copyright', $_POST['enable_copyright']);
            @$settings->set('logo_type', $_POST['copyright_type']);
            @$settings->set('logo_collor', $_POST['logo_collor']);
            @$settings->set('logoPos', str_replace('_', '', $_POST['logo_pos']));
            if ($_POST['copyright_type'] == 'image') {
                $uploadfile = basename($_FILES['upload_files']['name']);
                if ($uploadfile !== @$settings->get('logo')) {
                    if (move_uploaded_file($_FILES['upload_files']['tmp_name'], $uploadfile)) {
                        @$settings->set('logo', $uploadfile);
                    }
                }
            } else {
                @$settings->set('logo', "text::{$_POST['text_copyright']}");
            }
        } else {
            @$settings->remove('enable_copyright');
        }

        $settings->save();

        $this->redirect('?action=admin_images');

    }

    public function clear_orders()
    {
        file_put_contents('orders.txt', '');
        $this->toAdmin();
    }

    public function get_handler_code()
    {
        $fileName = "./{$this->_parser->cacheDir()}/{$_POST['handler']}.php";
        if (file_exists($fileName)) {
            echo file_get_contents($fileName);
        }
        exit;
    }

    public function save_handler()
    {
        $fileName = "./{$this->_parser->cacheDir()}/{$_POST['handlers_code']}.php";
        file_put_contents($fileName, $_POST['handlers_code_textarea']);
        $this->toAdmin();

    }

    public function save_content_settings()
    {
        $settings = new Settings();
        $settings->set('synonymsOrder', $_POST['syn_pos'])->save();

        CacheBackend::clearCache(null, 'pages');

        $this->redirect('?action=admin_content');

    }

    public function save_cache_settings()
    {
        $settings = $this->saveCacheSettings();
        if ($_POST['cache_adapter'] !== @Settings::staticGet('cacheBackend')) {
            $settings->set('cacheBackend', $_POST['cache_adapter'])->save();
            $this->clear_site_cache();
            CacheBackend::install();
        }
        $this->toAdmin();
    }

    public function clear_site_cache()
    {
        $this->_parser->removeSite(false);
        CacheBackend::install();
        @mkdir($this->_parser->cacheDir());
        $this->toAdmin();

    }

    public function remove_scripts()
    {

        $out = @file_get_contents('replaces');
        $out = OtherFunctions::dolly_unserialize($out);


        if (!isset($out)) {
            $out = array();
        }
        $fileName = $this->_parser->cacheDir() . '/_scripts';

        if (file_exists($fileName)) {


            $script = htmlspecialchars($_POST['script']);
            $new = (isset($_POST['new'])) ? htmlspecialchars($_POST['new']) : '';

            $file = file_get_contents($fileName);

            $file = str_replace(
                $_POST['script'] . '#D_END_SCRIPTS#',
                htmlspecialchars_decode($new) . (($new) ? '#D_END_SCRIPTS#' : ''),
                $file);

            file_put_contents($fileName, $file);


            $out[] = array('l_input' => $script,
                           'r_input' => $new,
                           'l_textarea' => $script,
                           'r_textarea' => $new,
                           'change_type' => 'script');

            @file_put_contents('replaces', OtherFunctions::dolly_serialize($out));
        }

        $this->toAdmin();
    }

    public function del_order()
    {
        $orders = $this->_getOrders();

        unset($orders[$_GET['id']]);

        $this->_saveOrders($orders);

        Controllers::redirect('admin.php');
    }

    /**
     * @return mixed|string
     */
    private function _getOrders()
    {
        $orders = file_get_contents('./orders.txt');
        $orders = unserialize($orders);
        return $orders;
    }

    /**
     * @param $orders
     */
    private function _saveOrders($orders)
    {
        $orders = serialize($orders);
        file_put_contents('./orders.txt', $orders);
    }

    public function success_order()
    {
        $this->_setOrderStatus($_GET['id'], 'success');
    }

    private function _setOrderStatus($id, $status)
    {
        $orders = $this->_getOrders();
        $orders[(int)$id]['status'] = $status;

        $this->_saveOrders($orders);

        Controllers::redirect('admin.php');
    }

    public function restart_order()
    {
        $this->_setOrderStatus($_GET['id'], 'in_work');
    }

    public function wait_order()
    {
        $this->_setOrderStatus($_GET['id'], 'wait');
    }

    public function get_template()
    {
        $template = $_GET['name'];
        switch ($template) {
            case 'forms-handler':
                $orders = array();
                if (file_exists('./orders.txt')) {
                    $file = file_get_contents('./orders.txt');
                    $orders = unserialize($file);
                }

                @$handlers = file("./d-site/handlers");

                break;
            case 'replaces':
                $new = OtherFunctions::dolly_unserialize(file_get_contents('replaces'));
                break;

            case 'scripts':
                $scripts = file_get_contents("./{$this->_parser->cacheDir()}/_scripts");
                $scripts = explode('#D_END_SCRIPTS#', $scripts);

                break;
        }
        @include_once "dolly_templates/{$template}.php";
        exit;
    }

    public function plugins()
    {
        ob_start();
        $name = $_GET['name'];
        $action = (isset($_GET['plugin_act'])) ? $_GET['plugin_act'] : 'index';
        include_once "plugins/{$name}/admin.php";
        $controller = new PluginController();

        $controller->run($action);
        $content = ob_get_contents();
        ob_end_clean();
        $action = 'plugins';
        include_once "dolly_templates/admin_layout.php";

    }

    public function get_pages()
    {
        $parent = (isset($_GET['parent'])) ? $_GET['parent'] : '/';

        $file = @file(Parser::$CACHE_DIR . "{$parent}/" . Parser::PAGES_FILE);

        $out = array();
        foreach ($file as $page) {
            $page = trim($page);
            $pageArr = array('id' => $parent . $page, 'text' => $page);
            if (@$page{strlen($page) - 1} == '/') {
                $pageArr['children'] = true;
                array_unshift($out, $pageArr);
            } else {
                $pageArr['icon'] = 'dolly_templates/js/dist/themes/html.ico';
                $out[] = $pageArr;
            }

        }
        @header('Content-type: application/json');
        echo json_encode($out);
        exit;
    }

    public function get_handled_image()
    {
        $options = array();


        if (@$_POST['reflection'] == 'on') {
            $options['reflection'] = '1';
        }
        if (@$_POST['enable_copyright'] == 'on') {
            $options['enable_copyright'] = $_POST['enable_copyright'];
            $options['logo_type'] = $_POST['type'];
            $options['logoPos'] = str_replace('_', '', $_POST['logo_pos']);


            if ($_POST['type'] !== 'image') {
                @$options['logo_collor'] = $_POST['collor'];
                @$options['logo'] = "text::{$_POST['text']}";
            } else {
                $uploadfile = @basename($_FILES['file']['name']);
                if ($uploadfile !== Settings::staticGet('logo')) {
                    if (@move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
                        $options['logo'] = $uploadfile;
                    }
                }
            }
        }

        @$options['scale'] = $_POST['scaling'];

        $image = file_get_contents('dolly_templates/images/base_preview.jpg');
        $images = new Images();
        header('Content-type: image/jpeg');

        ob_start();
        $images->fileName = 'dolly_templates/images/base_preview.jpg';
        $images->handleIfImage($image, 'image/jpeg', $options);
        $img = base64_encode(ob_get_contents());
        ob_end_clean();
        echo $img;

        exit;

    }

    public function install_plugin($name = null)
    {
        if (!$name) {
            $name = $_POST['name'];
        }

        PluginsInstaller::install($name);
    }

	public function delete_form()
	 {
		if($ids = Filter::NumArrFromPOST('ids'))
		 {
			MSConfig::RequireFile('traits', 'datacontainer', 'filesystemstorage');
			$fs_conf = new FileSystemStorage('/fs_config.php', ['readonly' => false, 'root' => MSSE_INC_DIR]);
			foreach($ids as $id) unset($fs_conf->$id);
			Controllers::redirect('admin.php?action=admin_forms_handler');
		 }
	 }

	final protected static function ActionPOST()
	 {
		if(isset($_POST['__mssm_action'])) return $_POST['__mssm_action'];
	 }

	final protected static function ActionGET()
	 {
		if(isset($_GET['__mssm_action'])) return $_GET['__mssm_action'];
	 }
}


$obj = new AdminController();
$action = @$_GET['action'] ?: 'index';

function runAction(AdminController $obj, $action)
{
    if (method_exists($obj, $action)) {
        $obj->{$action}();
		if(!empty($_REQUEST['__redirect']) && method_exists($obj, $_REQUEST['__redirect'])) HTTP::Redirect($_SERVER['PHP_SELF'].'?action='.$_REQUEST['__redirect']);
    } else {
        $obj->index();
    }
}

$api = new SlaveApi();
if (@$_GET['mode'] === 'api' AND @$api->keyIsValid($_GET['key'])) {
    runAction($obj, $action);
} else {
    if (($action == 'login' OR !$obj->isAdmin()) AND
        !in_array($action, AdminController::ignoredAuthActions())
    ) {
        $obj->login();
        exit;
    }
    runAction($obj, $action);
}
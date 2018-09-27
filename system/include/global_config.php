<?php
MSConfig::DisplayErrors(false);
MSConfig::ErrorTracking(false, E_STRICT, E_CORE_WARNING, E_DEPRECATED);
MSConfig::RegisterClasses('colconf', 'db', 'dbregval', 'dbtable', 'dropdown', 'dropdownlist', 'emptyresult', 'events', 'filter', 'filesizeproxy', 'filesystemstorage', 'fileuploader', 'form', 'format', 'formatdate', 'html', 'http', 'idna_convert', 'imageprocessor', 'imageuploader', 'imageuploaderurl', 'imserrorstream', 'langselector', 'mk', 'ms', 'ms4xxlog', 'msauthenticator', 'msbanners', 'msbreadcrumbs', 'mscache', 'mscfg', 'mschangepassword', 'mscontactinfo', 'msdataloader', 'msdebuginfo', 'msdberrorstream', 'msdbtable', 'msdownloadproxy', 'mseditpairs', 'msemailerrorstream', 'msemailtpl', 'mserrorstream', 'msfaq', 'msfbuttons', 'msfieldset', 'msfiles', 'msgqueue', 'msicons', 'msimages', 'msmail', 'msmaps', 'msmessagefieldset', 'msnotifications', 'msnotificationsviewer', 'msoauth2', 'msoptions', 'mspagenav', 'mspassword', 'msphpinfo', 'mssearch', 'mssedomains', 'mssimplelist', 'mssmusers', 'mstable', 'mstableorder', 'msurl', 'imsui', 'msvideos', 'mswatermark', 'page', 'queue', 'radio', 'registry', 'searchselect', 'select', 'smprofile', 'sqlexpr', 'streamuploader', 'sunder', 'timeleft', 'timemeter', 'pagetree', 'ui', 'unifiedresult', 'uploader', 'watermark');
$locale = 'ru_RU';
setlocale(LC_ALL, $locale);
setlocale(LC_NUMERIC, 'en_US');
date_default_timezone_set('Europe/Moscow');
MSConfig::SetErrorStreams(new MSErrorStream());

interface IConst
{
	const JQUERY = 'https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js';
	const YMAPS = 'https://api-maps.yandex.ru/2.0/?load=package.standard&lang=ru-RU';
	const MSAPIS = 'https://msapis.com';
	const AUTH_SESS_LEN = 60;
}

MSConfig::AddAutoload(function($lower_class_name){
	$fname = MSSE_INC_DIR."/class.$lower_class_name.php";
	if(file_exists($fname))
	 {
		require_once($fname);
		return true;
	 }
});
?>
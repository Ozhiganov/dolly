<?php
class MSDownloadProxy
{
	final public static function AddParam($path, $rel_name, $fld_name = 'title', $count_downloads = false)
	 {
		self::$params[$path] = array('rel' => $rel_name, 'fld' => $fld_name, 'count_downloads' => $count_downloads);
	 }

	final public static function Run()
	 {
		if(preg_match('/^(.*)\/[a-z]+_([0-9]{1,6})\.[a-z0-9]+$/', $_GET['file'], $matches) &&
		   isset(self::$params[$matches[1]]) &&
		   ($file = DB::GetRowById(self::$params[$matches[1]]['rel'], $matches[2], self::$params[$matches[1]]['fld'])))
		 {
			if(!($title = trim($file->{self::$params[$matches[1]]['fld']}))) $title = 'file_'.$matches[2];
			self::GetFile($_SERVER['DOCUMENT_ROOT'].$matches[0], $title);
			if(self::$params[$matches[1]]['count_downloads'])
			 DB::Update(self::$params[$matches[1]]['rel'], ['=num_of_downloads' => '`num_of_downloads` + 1', '~id' => $matches[2]], '`id` = :id');
			exit();
		 }
		else ms::Exit404();
	 }

	final public static function GetFile($file_name, $title = null)
	 {
		if(!file_exists($file_name)) ms::Exit404();
		if($title)
		 {
			$title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
			if(!empty($_SERVER['HTTP_USER_AGENT']) && false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) $title = str_replace('+', '%20', urlencode($title));
		 }
		self::SendHeaders($file_name, $title);
		print(file_get_contents($file_name));
	 }

	final public static function FromStream($content, $file_name)
	 {
		self::SendHeaders($file_name, null, strlen($content));
		print($content);
	 }

	final public static function GetFileEx($file_name, $title)
	 {
		if($files = glob($file_name))
		 {
			MSDownloadProxy::GetFile($files[0], $title);
			exit();
		 }
		else ms::Exit404();
	 }

	final private static function SendHeaders($file_name, $title = null, $content_length = null)
	 {
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		if(!$content_length) header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($file_name)).' GMT');
		header('Cache-Control: private',false);
		header('Content-Type: application/force-download');
		Header('Content-Disposition: attachment; filename="'.($title ? $title.'.'.ms::GetFileExt($file_name) : basename($file_name)).'"');
		header('Content-Transfer-Encoding: binary');
		if(!$content_length) header('Content-Length: '.filesize($file_name));
		header('Connection: close');
	 }

	private static $params;
}
?>
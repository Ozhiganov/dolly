<?php
abstract class MSIcons
{
	final public static function ClearCache($file_name = null, $root = null)
	 {
		if(null === $root) $root = $_SERVER['DOCUMENT_ROOT'];
		if($file_name)
		 {
			$params = ['fname' => $file_name];
			switch($file_name[mb_strlen($file_name, 'utf-8') - 1])
			 {
				case '*': $file_name[mb_strlen($file_name, 'utf-8') - 1] = '%';
				case '%': $cond = '`src` LIKE :fname';
						  $no_ext = true;
						  break;
				default: $cond = '`src` = :fname';
						 $no_ext = false;
			 }
		 }
		else $cond = $params = null;
		$res = DB::Select('image_cache', 'dest', $cond, $params, '`dest` ASC');
		$prev_dir = null;
		foreach($res as $row)
		 {
			$name = $root.$row->dest;
			$dir = dirname($row->dest);
			@unlink($name);
			if($prev_dir != $dir) self::RemoveDir($dir, $root);
			$prev_dir = $dir;
		 }
		DB::Delete('image_cache', $cond, $params);
		return $no_ext;
	 }

	final public static function Create()
	 {
		$w = Filter::NumFromGET('width');
		$h = Filter::NumFromGET('height');
		if(!empty($_GET['type_id']) && preg_match('/^[0-9a-z_\-\/]*\.(jpg|jpeg|gif|png)$/', $_GET['path']) && ($w || $h))
		 {
			$img = $_SERVER['DOCUMENT_ROOT'].'/'.$_GET['path'];
			if(!file_exists($img)) ms::Exit404();
			if(self::IsCacheEnabled())
			 {
				$dest = '/'.$_GET['type_id'];
				if($w) $dest .= "/w$w";
				if($h) $dest .= "/h$h";
			 }
			else $dest = '';
			switch($_GET['type_id'])
			 {
				case 'f':
					if(self::Send($img))
					 {
						self::Cache($dest .= "/$_GET[path]");
						ImageProcessor::Create($img)->CreateFittedImage($w, $h, self::IsCacheEnabled() ? $_SERVER['DOCUMENT_ROOT'].$dest : null, true);
					 }
					exit();
				case 'crop':
					if(($ratio = Filter::NumFromGET('ratio')) && ($left = Filter::NumFromGET('left')) >= 0 && ($top = Filter::NumFromGET('top')) >= 0)
					 {
						if(self::Send($img))
						 {
							self::Cache($dest .= "/left$left/top$top/ratio$ratio/$_GET[path]");
							ImageProcessor::Create($img)->CreateCroppedImage($w, $h, $left, $top, $ratio, self::IsCacheEnabled() ? $_SERVER['DOCUMENT_ROOT'].$dest : null, true);
						 }
						exit();
					 }
					// здесь не должно быть break; - управление в случае не всех указанных данных должно передаваться дальше в блок 'fc'
				case 'fc':
					if(self::Send($img))
					 {
						self::Cache($dest .= "/$_GET[path]");
						ImageProcessor::Create($img)->CreateFittedAndCroppedImage($w, $h, false, self::IsCacheEnabled() ? $_SERVER['DOCUMENT_ROOT'].$dest : null, true);
					 }
					exit();
				case 'fctop':
					if(self::Send($img))
					 {
						self::Cache($dest .= "/$_GET[path]");
						ImageProcessor::Create($img)->CreateFittedAndCroppedImage($w, $h, true, self::IsCacheEnabled() ? $_SERVER['DOCUMENT_ROOT'].$dest : null, true);
					 }
					exit();
			 }
		 }
		ms::Exit404();
	 }

	final public static function EnableCache($use_db = true)
	 {
		self::$enable_cache = true;
		self::$use_db = $use_db;
	 }

	final public static function DeleteImage($file_name, $root = null)
	 {
		if(null === $root) $root = $_SERVER['DOCUMENT_ROOT'];
		if(self::ClearCache($file_name, $root)) Uploader::UnlinkIgnoringExt($root.$file_name);
		else unlink($root.$file_name);
	 }

	final private static function RemoveDir($dir, $root)
	 {
		do
		 {
			$file = $root.$dir;
			if(file_exists($file) && count(scandir($file)) == 2) rmdir($file);
			else return;
			$pos = strrpos($dir, '/');
		 }
		while($pos !== false && ($dir = substr($dir, 0, $pos)));
	 }

	final private static function Send($img)
	 {
		$last_modified = filemtime($img);
		$etag = sha1_file($img);
		if(@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified || trim(@$_SERVER['HTTP_IF_NONE_MATCH']) == $etag)
		 {
			header('Last-modified: '.gmdate('r', $last_modified), true, 304);
			return false;
		 }
		$days = 31536000;
		header('Last-modified: '.gmdate('r', $last_modified), true, 200);
		header('Expires: '.gmdate('r', time() + $days));
		header('Cache-Control: private, max-age='.$days);
		header('Etag: '.$etag);
		return true;
	 }

	final private static function Cache($dest)
	 {
		if(!self::IsCacheEnabled()) return false;
		$dir = dirname($_SERVER['DOCUMENT_ROOT'].$dest);
		if(!file_exists($dir))
		 {
			try
			 {
				mkdir($dir, fileperms($_SERVER['DOCUMENT_ROOT']), true);
			 }
			catch(Exception $e)
			 {
				MSConfig::LogException($e);
			 }
		 }
		if(self::UseDB()) DB::Replace('image_cache', ['src' => '/' === $_GET['path'][0] ? $_GET['path'] : "/$_GET[path]", 'dest' => $dest]);
	 }

	final private static function IsCacheEnabled() { return self::$enable_cache; }
	final private static function UseDB() { return self::$use_db; }

	final private function __construct() {}

	private static $enable_cache = false;
	private static $use_db = false;
}
?>
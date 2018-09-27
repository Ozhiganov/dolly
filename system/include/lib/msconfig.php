<?php
class UnserializedProxy extends stdClass
{
	public function __construct($class)
	 {
		$this->class = $class;
	 }

	public function __toString()
	 {
		return "$this->class";
	 }

	private $class;
}

abstract class MSConfig
{
	final public static function DisplayErrors($state = null) { return null === $state ? 'On' === ini_get('display_errors') : ini_set('display_errors', $state ? 'On' : 'Off'); }
	final public static function GetMSSMDir() { return null === self::$mssm_dir ? '/system' : self::$mssm_dir; }
	final public static function SetCompression($state) { self::$compress = $state; }
	final public static function CompressionEnabled() { return self::$compress; }
	final public static function AddAutoload($func, $method = false) { self::$autoload[] = $method ? array($func, $method) : $func; }
	final public static function Autoload($lower_class_name, $class_name) { foreach(self::$autoload as $func) if(call_user_func($func, $lower_class_name, $class_name)) return; }
	final public static function IsSecured() { return !(empty($_SERVER['HTTPS']) || 'off' === $_SERVER['HTTPS']) || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO']) || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && 'on' === $_SERVER['HTTP_X_FORWARDED_SSL']); }
	final public static function GetProtocol($s = '://') { if(isset($_SERVER['SERVER_PROTOCOL']) && 0 === strpos($_SERVER['SERVER_PROTOCOL'], 'HTTP')) return 'http'.(empty($_SERVER['HTTPS']) || 'off' == $_SERVER['HTTPS'] ? '' : 's').$s; }
	final public static function GetLibDir() { return MSSE_LIB_DIR; }
	final public static function GetVarType($v, $add_value = true, $s = 'instance of ') { return 'object' === ($t = gettype($v)) ? $s.get_class($v) : $t.(is_scalar($v) ? ' '.var_export($v, true) : ''); }

	final public static function SendHTML($html)
	 {
		header('Content-Type: text/html; charset=UTF-8');
		if($encoding = self::CompressionEnabled())
		 {
			if(empty($_SERVER['HTTP_ACCEPT_ENCODING'])) $encoding = false;
			else
			 {
				$enc = $_SERVER['HTTP_ACCEPT_ENCODING']; 
				if(strpos($enc, 'x-gzip') !== false) $encoding = 'x-gzip';
				elseif(strpos($enc, 'gzip') !== false) $encoding = 'gzip';
				else $encoding = false;
			 }
		 }
		if($encoding)
		 {
			$len = strlen($html); 
			header("Content-Encoding: $encoding");
			$gzip_4_chars = function($v){
				$r = '';
				for($i = 0; $i < 4; ++$i)
				 {
					$r .= chr($v % 256);
					$v = floor($v / 256);
				 }
				return $r;
			};
			$crc = crc32($html);
			$html = gzcompress($html, 9);
			$html = "\x1f\x8b\x08\x00\x00\x00\x00\x00".substr($html, 0, strlen($html) - 4).$gzip_4_chars($crc).$gzip_4_chars($len);
		 }
		die($html);
	 }

	final public static function ErrorTracking($state, ...$errors)
	 {
		foreach($errors as $error)
		 switch($error)
		  {
			case E_STRICT:
			case E_CORE_WARNING:
			case E_DEPRECATED: self::$disable_error_tracking[$error] = is_callable($state) ? $state : !$state; break;
			default: die(__METHOD__.': you can change tracking only for E_STRICT, E_CORE_WARNING and E_DEPRECATED.');
		  }
	 }

	final public static function OnShutDown()
	 {
		if($error = error_get_last())
		 {
			if(self::DisplayErrors() && ob_get_level() > 0) ob_flush();
			self::HandleError($error);
		 }
	 }

	final public static function HandleError(array $error)
	 {
		if(!empty(self::$disable_error_tracking[$error['type']]))
		 {
			if(is_callable(self::$disable_error_tracking[$error['type']]))
			 {
				if(!call_user_func(self::$disable_error_tracking[$error['type']], $error)) return;
			 }
			else return;
		 }
		if(self::$error_streams)
		 {
			set_time_limit(4);
			self::CloseDBResults();
			foreach(self::$error_streams as $stream)
			 {
				try
				 {
					$stream->InsertError($error);
				 }
				catch(Exception $e2) {}
			 }
		 }
	 }

	final public static function SetErrorStreams(...$streams)
	 {
		self::RequireFile('imserrorstream');
		foreach($streams as $i => $s) if(!in_array('IMSErrorStream', class_implements($s))) throw new Exception('Argument number '.($i + 1).' passed into MSConfig::SetErrorStreams must implement IMSErrorStream, '.(is_object($s) ? 'instance of '.get_class($s) : gettype($s)).' given.');
		self::$error_streams = $streams;
	 }

	final public static function SetMSSMDir($val)
	 {
		if(null !== self::$mssm_dir) throw new Exception('Can\'t change MSSM dir.');
		self::$mssm_dir = $val;
	 }

	final public static function RequireFile(...$names) { foreach($names as $name) require_once(MSSE_LIB_DIR."/$name.php"); }
	final public static function RegisterClasses(...$names) { self::$files = array_fill_keys($names, true); }
	final public static function HasRequiredFile($name) { return isset(self::$files[$name]); }
	final public static function GetIP() { if(!empty($_SERVER['REMOTE_ADDR'])) return filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP); }

	final public static function Exception2Array(Exception $e)
	 {
		$data = ['protocol' => self::GetProtocol(''), 'host' => @$_SERVER['HTTP_HOST'], 'uri' => @$_SERVER['REQUEST_URI'], 'referer' => @$_SERVER['HTTP_REFERER'], 'file' => $e->getFile(), 'line' => Filter::GetIntOrNull($e->getLine()), 'class' => get_class($e), 'message' => $e->getMessage(), 'code' => Filter::GetIntOrNull($e->getCode())];
		try
		 {
			$dump = $e->GetTrace();
			self::FilterDump($dump);
			$dump = serialize($dump);
			$data['dump'] = base64_encode($dump);
		 }
		catch(Exception $e3)
		 {
			$data['no_dump_message'] = $e3->GetMessage();
		 }
		return $data;
	 }

	final public static function LogException(Exception $e)
	 {
		try
		 {
			$data = self::Exception2Array($e);
			if($ip = self::GetIP()) $data['remote_addr'] = $ip;
			DB::Insert('sys_exception', $data, ['date_time' => 'NOW()']);
		 }
		catch(Exception $e2) {}
	 }

	final public static function Error2Array(array $error)
	 {
		$error['protocol'] = self::GetProtocol('');
		$error['host'] = @$_SERVER['HTTP_HOST'];
		$error['uri'] = @$_SERVER['REQUEST_URI'];
		$error['referer'] = @$_SERVER['HTTP_REFERER'];
		return $error;
	 }

	final public static function LogError(array $error)
	 {
		try
		 {
			$error = self::Error2Array($error);
			if($ip = self::GetIP()) $error['remote_addr'] = $ip;
			DB::Insert('sys_error', $error, ['date_time' => 'NOW()']);
		 }
		catch(Exception $e2) {}
	 }

	final public static function ShowException(Exception $e)
	 {
?><table class="exception">
<tr><th>Выброшено в файле</th><td><?=$e->getFile()?></td></tr>
<tr><th>на строке номер</th><td><?=$e->getLine()?></td></tr>
<tr><th>Класс</th><td><?=get_class($e)?></td></tr>
<tr><th>Сообщение</th><td><?=$e->getMessage()?></td></tr>
<tr><th>Код</th><td><?=$e->getCode()?></td></tr>
</table><?php
		self::ShowTrace($e->getTrace());
	 }

	final public static function ShowTrace(array $trace)
	 {
?><table class="exception trace"><?php
		$len = count($trace);
		foreach($trace as $key => $item)
		 {
?><tr><th class="num" colspan="3">#<?=($len - $key)?></th></tr>
<tr><th>file</th><td colspan="2"><?=@$item['file']?></td></tr>
<tr><th>line</th><td colspan="2"><?=@$item['line']?></td></tr>
<tr><th>caller</th><td colspan="2"><?=@$item['class'].@$item['type'].$item['function']?></td></tr><?php
			if(!empty($item['args']))
			 {
?><tr><th>args</th></tr><?php
				foreach($item['args'] as $i => $arg)
				 {
?><tr><th><?=$i?></th><td><em><?=gettype($arg)?></em></td><td><?php
					if(is_numeric($arg)) print($arg);
					elseif(is_string($arg)) print('<pre>'.htmlspecialchars($arg).'</pre>');
					elseif(is_object($arg)) print($arg instanceof UnserializedProxy ? $arg : get_class($arg));
					elseif(is_bool($arg)) print($arg ? 'true' : 'false');
					elseif(null === $arg);
					elseif(is_resource($arg)) print(get_resource_type($arg));
					elseif(is_array($arg)) print('['.(($count = count($arg)) ? 'array with '.$count.' element'.($count > 1 ? 's' : '') : 'empty array').']');
					else var_dump($arg);
?></td></tr><?php
				 }
			 }
		 }
?></table><?php
	 }

	final public static function HandleException(Exception $e, $display = true)
	 {
		self::CloseDBResults();
		foreach(self::$error_streams as $stream)
		 {
			try
			 {
				$stream->InsertException($e);
			 }
			catch(Exception $e2) {}
		 }
		if($display)
		 {
			if(!headers_sent()) HTTP::Status(500, false);
			if('On' === ini_get('display_errors'))
			 {
?><!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>MSSE exception</title>
<link rel="stylesheet" type="text/css" href="<?=IConst::MSAPIS?>/css/exception/1.0/exception.css" />
</head>
<body><?php
				self::ShowException($e);
?></body></html><?php
			 }
			else throw $e;
		 }
	 }

	final public static function FilterDump(array &$dump)
	 {
		static $walk = null;
		if(null === $walk)
		 {
			$marker = microtime();
			foreach($dump as $k => $v) $marker .= ":$k";
			$marker = '__'.sha1($marker);
			$walk = function(&$v, $k) use(&$walk, $marker){
				if(is_array($v))
				 {
					if(isset($v[$marker])) return;
					else
					 {
						$v[$marker] = true;
						array_walk($v, $walk);
						unset($v[$marker]);
					 }
				 }
				elseif(is_object($v))
				 {
					if(($v instanceof Serializable) || method_exists($v, '__sleep') || is_a($v, 'stdClass'));
					else
					 {
						$c = get_class($v);
						$tmp = $v;
						Events::Dispatch('system:filter_dump', false, ['value' => &$v, 'class' => $c], ['value' => ['set' => true]]);
						if($tmp === $v) $v = new UnserializedProxy($c);
					 }
				 }
			};
		 }
		array_walk($dump, $walk);
	 }

	final private static function CloseDBResults() { if(class_exists('SQLDBResult', false)) SQLDBResult::CloseAll(); }

	final private function __construct() {}

	private static $autoload = [];
	private static $mssm_dir = null;
	private static $files = [];
	private static $error_streams = [];
	private static $disable_error_tracking = [];
	private static $compress = false;
}
?>
<?php
trait TSystemUpdates
{
	public static function GetUpdatesURL() { return 'https://update.maxiesystems.com/'; }
	public static function GetSiteRoot() { return $_SERVER['DOCUMENT_ROOT']; }

	protected static function LogUpdatesAction($type, $message, $status = null)
	 {
		// $fname = self::GetSiteRoot().'/updates.log';
		// if(file_exists($fname)) $lines = file_get_contents($fname);
		// else $lines = '';
		// if($lines) $lines .= PHP_EOL;
		// $lines .= "$type	$message";
		// file_put_contents($fname, $lines);
		// echo $type, ' ', $message, ' ', var_export($status, true), '<br />';
	 }

	final protected static function CheckForUpdates(array $data, $silent = false)
	 {
		/*$http = new HTTP();
		$data['step'] = 1;
		$result = $http->GET(self::GetUpdatesURL(), $data);
		self::CheckHTTPCode($http);
		*/
		
		
		/*
		if(null === ($json = json_decode($result))) self::SendJSON([], 'Не получен список обновлений.', false);
		*/
		
		/*
		else
		 {
			if($silent)
			 {
				if('success' === $json->status && $json->data && $json->data->items)
				 {
					$updated = [];
					$data['product_id'] = $json->data->product_id;
					$tmp_dir = self::GetTmpDir(true);
					foreach($json->data->items as $k => $item)
					 if($item->compatible > 0)
					  {
						self::ApplyUpdate($item->name, $item->version, $data, $tmp_dir);
						$updated[] = $item;
						unset($json->data->items[$k]);
					  }
					if($updated)
					 {
						if(!$json->data->items)
						 {
							$json->status = 'warning';
							$json->status_text = 'Нет доступных обновлений.';
							$result = json_encode($json);
						 }
					 }
				 }
			 }
			header('Content-Type: application/json');
			die($result);
		 }*/
		 
		 self::SendJSON([], 'Не получен список обновлений.', false);
		 
	 }

	final protected static function GetTmpDir($create = false)
	 {
		return self::GetSiteRoot();
	 }

	final protected static function GetCmdFileName()
	 {
		$cmd_file = self::GetTmpDir().'/cmd.data';
		if(file_exists($cmd_file)) return $cmd_file;
	 }

	final protected static function ApplyUpdates(array $data)
	 {
		if(!empty($_POST['items']) && is_array($_POST['items']))
		 {
			$tmp_dir = self::GetTmpDir(true);
			foreach($_POST['items'] as $name => $version) self::ApplyUpdate($name, $version, $data, $tmp_dir);
			self::SendJSON(null, 'Сайт успешно обновлён.');
		 }
		else throw new Exception('Не указаны требуемые обновления!');
	 }

	final protected static function ApplyUpdate($item_id, $version, array $data, $tmp_dir)
	 {
		$item = "$item_id.$version";
		$http = new HTTP();
		$data['step'] = 2;
		$data['item'] = $item;
		$result = $http->GET(self::GetUpdatesURL(), $data);
		self::CheckHTTPCode($http, "($item)");
		$ctype = $http->GetResponseHeader('Content-Type');
		if('application/gzip' !== $ctype) throw new Exception("Недопустимый Content-Type: '$ctype'.");
		$fname = "$tmp_dir/$item.tar.gz";
		file_put_contents($fname, $result, LOCK_EX);
		chmod($fname, 0644);
		self::LogUpdatesAction('update started', "$item_id.$version. ".date('Y-m-d H:i:s'));
		try
		 {
			$dest = self::GetSiteRoot();
			$extract_files = function($fname, $dest, $sub = '') use(&$extract_files){
				$d = new PharData($fname, FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME);
				foreach($d as $p)
				 {
					$dest0 = "$sub/".$p->getFilename();
					$dest1 = "$dest$dest0";
					if($p->isDir())
					 {
						if(!file_exists($dest1))
						 {
							mkdir($dest1, 0755, true);
							self::LogUpdatesAction('dir', $dest1, 'success');
						 }
						$extract_files($p->getPathname(), $dest, $dest0);
					 }
					else
					 {
						copy($p->GetPathName(), $dest1);
						self::LogUpdatesAction('file', $dest1, 'success');
					 }
				 }
			};
			$extract_files($fname, $dest);
			self::RunCmd();
			self::LogUpdatesAction('update completed', '');
			unlink($fname);
		 }
		catch(Exception $e)
		 {
			unlink($fname);
			if($fname = self::GetCmdFileName()) unlink($fname);
			throw $e;
		 }
	 }

	final protected static function RunCmd()
	 {
		if(!($cmd_file = self::GetCmdFileName())) return;
		$data = (require $cmd_file);
		if($data && is_array($data))
		 foreach($data as $cmd)
		  {
			$message = '';
			$status = null;
			if(isset(self::$system_updates_commands[$cmd['type']]))
			 {
				if(isset(self::$system_updates_commands[$cmd['type']][$cmd['action']]))
				 {
					$cfg = self::$system_updates_commands[$cmd['type']][$cmd['action']];
					$meta = $cfg['meta'];
					$meta['type'] = ['type' => 'string'];
					$meta['action'] = ['type' => 'string'];
					$o = new OptionsGroup($cmd, $meta);
					$c = "SystemUpdatesCheck_$cfg[check]";
					$m = "SystemUpdatesAction_$cmd[type]_$cmd[action]";
					$data = new stdClass;
					$data->message = null;
					if(true === ($status = self::$c($cmd, $data))) $status = self::$m($cmd, $data);
					if(null !== $data->message) $message = $data->message;
				 }
				else $message = 'Unknown action';
			 }
			else $message = 'Unknown type';
			self::LogUpdatesAction("cmd:$cmd[type]:$cmd[action]", $message, $status);
		  }
		unlink($cmd_file);
	 }

	final protected static function rmdir($dir, $this_dir = true)
	 {
		if(!file_exists($dir)) return;
		if($fp = opendir($dir))
		 {
			while($f = readdir($fp))
			 {
				$file = "$dir/$f";
				if($f === '.' || $f === '..') continue;
				elseif(is_dir($file) && !is_link($file)) self::rmdir($file);
				else unlink($file);
			 }
			closedir($fp);
			if($this_dir) rmdir($dir);
		 }
	 }

	final protected static function CheckHTTPCode(HTTP $http, $text = '')
	 {
		switch($code = $http->GetHTTPCode())
		 {
			case 200: return true;
			case 400: $msg = 'Неправильный запрос.'; break;
			case 404: $msg = 'Файл обновления не найден.'; break;
			default: $msg = "HTTP status code: $code.";
		 }
		if('' !== $text) $msg .= " $text";
		throw new Exception($msg);
	 }

	final private static function SystemUpdatesAction_dir_delete(array $cmd, stdClass $data)
	 {
		self::rmdir($data->path);
		return true;
	 }

	final private static function SystemUpdatesAction_file_delete(array $cmd, stdClass $data)
	 {
		return unlink($data->path);
	 }

	final private static function SystemUpdatesAction_file_rename(array $cmd, stdClass $data)
	 {
		if('/' === $cmd['dest'][strlen($cmd['dest']) - 1]) $cmd['dest'] .= basename($cmd['name']);
		return rename($data->path, "$data->root/$cmd[dest]");
	 }

	final private static function SystemUpdatesAction_file_replace(array $cmd, stdClass $data)
	 {
		$f = fopen($data->path, 'r+');
		flock($f, LOCK_EX);
		$size_0 = filesize($data->path);
		$s = fread($f, $size_0);
		\MSConfig::RequireFile('msarchive');
		foreach($cmd['items'] as $from => $to) $s = \MSArchive::StrReplace($from, $to, $s, $count);
		$size_1 = strlen($s);
		if($size_1 < $size_0) ftruncate($f, $size_1);
		rewind($f);
		fwrite($f, $s);
		flock($f, LOCK_UN);
		fclose($f);
		return true;
	 }

	final private static function SystemUpdatesAction_fsys_storage_meta(array $cmd, stdClass $data)
	 {
		$h = fopen($data->path, 'c');
		$t = (require $data->path);
		if(!is_array($t) || !isset($t['meta']) || !is_array($t['meta']))
		 {
			$data->message .= ' Invalid file format.';
			return false;
		 }
		foreach($cmd['data'] as $k => $m) $t['meta'][$k] = $m;
		$code = '<?php'.PHP_EOL.'return '.var_export($t, true).';'.PHP_EOL.'?>';
		ftruncate($h, strlen($code));
		fwrite($h, $code);
		fclose($h);
		return true;
	 }

	final private static function SystemUpdatesCheck_file_exists(array $cmd, stdClass $data = null, $func = 'is_file')
	 {
		$data->name = $cmd['name'];
		$data->root = self::GetSiteRoot();
		$data->path = self::GetSiteRoot().'/'.$cmd['name'];
		$data->message = $data->path;
		return file_exists($data->path) && $func($data->path) ? true : 'not_found';
	 }

	final private static function SystemUpdatesCheck_dir_exists(array $cmd, stdClass $data = null) { return self::SystemUpdatesCheck_file_exists($cmd, $data, 'is_dir'); }

	private static $system_updates_commands = [
		'file' => [
			'delete' => [
				'check' => 'file_exists',
				'meta' => [
					'name' => ['type' => 'string,len_gt0'],
				],
			],
			'replace' => [
				'check' => 'file_exists',
				'meta' => [
					'name' => ['type' => 'string,len_gt0'],
					'items' => ['type' => 'array,cnt_gt0'],
				],
			],
			'rename' => [
				'check' => 'file_exists',
				'meta' => [
					'name' => ['type' => 'string,len_gt0'],
					'dest' => ['type' => 'string,len_gt0'],
				],
			],
		],
		'dir' => [
			'delete' => [
				'check' => 'dir_exists',
				'meta' => [
					'name' => ['type' => 'string,len_gt0'],
				],
			],
		],
		'fsys_storage' => [
			'meta' => [
				'check' => 'file_exists',
				'meta' => [
					'name' => ['type' => 'string,len_gt0'],
					'data' => ['type' => 'array,cnt_gt0'],
				],
			],
		],
	];
}

class SystemUpdatesCommands
{
	use TSystemUpdates;

	final public function __construct($file_name, array $options = null)
	 {
		$this->file_name = $file_name;
		$this->options = new OptionsGroup($options, ['overwrite' => ['type' => 'bool', 'value' => false]]);
	 }

	final public function __destruct()
	 {
		$h = fopen($this->file_name, 'c');
		if($this->options->overwrite) $t = $this->commands;
		else
		 {
			$t = (require $this->file_name);
			if(is_array($t))
			 {
				foreach($this->commands as $cmd)
				 {
					if($t && (null !== ($i = $this->SearchCommand($cmd, $t)))) $t[$i] = $cmd;
					else $t[] = $cmd;
				 }
			 }
			else $t = $this->commands;
		 }
		$code = '<?php'.PHP_EOL.'return '.var_export($t, true).';'.PHP_EOL.'?>';
		ftruncate($h, strlen($code));
		fwrite($h, $code);
		fclose($h);
	 }

	final public static function GetTypes()
	 {
		$k = array_keys(self::$system_updates_commands);
		return array_combine($k, $k);
	 }

	final public static function GetActions($type = null)
	 {
		if($type)
		 {
			self::Check($type, null, false);
			$a = array_keys(self::$system_updates_commands[$type]);
			return array_combine($a, $a);
		 }
		else
		 {
			$r = self::$system_updates_commands;
			foreach($r as $k => $v)
			 {
				$a = array_keys($r[$k]);
				$r[$k] = array_combine($a, $a);
			 }
			return $r;
		 }
	 }

	final public static function GetCommandMeta($type, $action)
	 {
		self::Check($type, $action, true);
		if(!isset(self::$system_updates_commands[$type][$action]['meta_0']))
		 {
			self::$system_updates_commands[$type][$action]['meta_0'] = [];
			foreach(self::$system_updates_commands[$type][$action]['meta'] as $k => $v) self::$system_updates_commands[$type][$action]['meta_0'][$k] = new OptionsGroup($v, ['type' => ['type' => 'string']]);
		 }
		return self::$system_updates_commands[$type][$action]['meta_0'];
	 }

	final public static function GetCommand($type, $action, array $data)
	 {
		$m = [];
		foreach(self::GetCommandMeta($type, $action) as $k => $v) $m[$k] = ['type' => $v->type];
		$o = new OptionsGroup($data, $m);
		$r = ['type' => $type, 'action' => $action];
		foreach($o as $k => $v) $r[$k] = $v;
		return $r;
	 }

	final public function AddCommand($type, $action, array $data)
	 {
		$cmd = self::GetCommand($type, $action, $data);
		if($this->commands && (null !== ($i = $this->SearchCommand($cmd)))) $this->commands[$i] = $cmd;
		else $this->commands[] = $cmd;
	 }

	final public function SearchCommand(array $cmd, array $commands = null)
	 {
		if(null === $commands) $commands = $this->commands;
		foreach($commands as $i => $c) if($c === $cmd) return $i;
	 }

	final private static function Check($type, $action, $use_action)
	 {
		if(empty(self::$system_updates_commands[$type])) throw new Exception("Invalid command type: $type.");
		if($use_action && empty(self::$system_updates_commands[$type][$action])) throw new Exception("Invalid command action: $type:$action.");
	 }

	private $file_name;
	private $commands = [];
	private $options;
}
?>
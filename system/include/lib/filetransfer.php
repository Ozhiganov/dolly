<?php
class remote_file_item extends stdClass
{
	final public function __construct(stdClass $data, FTPConnection $owner)
	 {
		foreach($data as $k => $v)
		 if(array_key_exists($k, $this->data)) $this->data[$k] = $v;
		 else throw new Exception("Invalid key: $k.");
		$this->owner = $owner;
	 }

	final public function __get($k)
	 {
		if('has_children' === $k) return 'dir' === $this->data['type'] ? $this->owner->ListItems($this->data['path'], 'dir') > 0 : null;
		if('is_dir' === $k) return 'dir' === $this->data['type'];
		if('is_file' === $k) return 'file' === $this->data['type'];
		if(array_key_exists($k, $this->data)) return $this->data[$k];
	 }

	final public function __set($k, $v)
	 {
		if(array_key_exists($k, $this->data)) throw new Exception("Field '$k' is read only!");
		$this->data[$k] = $v;
	 }

	private $data = ['name' => null, 'path' => null, 'type' => null, 'rights' => null, 'number' => null, 'user' => null, 'group' => null, 'size' => null, 'month' => null, 'day' => null, 'time' => null];
	private $owner;
}

class FTPConnection
{
	final public function __construct($server, $username, $password, array $options = null)
	 {
		$this->options = new OptionsGroup($options, ['port' => ['type' => 'int,string', 'value' => 21]]);
		$this->server = $server;
		$this->username = $username;
		$this->password = $password;
		$this->cid = ftp_connect($server, $this->options->port);//timeout
		ftp_login($this->cid, $username, $password);// if(!ftp_login
		ftp_pasv($this->cid, true);//enable passive mode
		$this->root_dir = ftp_pwd($this->cid);
	 }

	final public function SendFile($local_name, $remote_name)
	 {
		$remote_name = "$remote_name";
		if('' === $remote_name) $remote_name = basename($local_name);
		elseif('/' === $remote_name[strlen($remote_name) - 1]) $remote_name .= basename($local_name);
		ftp_put($this->cid, $remote_name, $local_name, FTP_BINARY);//if(!ftp_put
	 }

	final public function SendFiles(array $items)
	 {
		throw new Exception();// foreach($items as $dir => $files) $this->AddFromDir($dir, $files);
	 }

	final public function MkDir($dir)
	 {
		if(!$this->FileExists($dir)) return ftp_mkdir($this->cid, $this->ConcatFNames($this->root_dir, $dir));
	 }

	final public function RmDir($dir)
	 {
		if($this->FileExists($dir)) return ftp_rmdir($this->cid, $this->ConcatFNames($this->root_dir, $dir));
	 }

	final public function SendFromDir($dir, array $files = [], $dest_dir = '')
	 {
		if('' !== "$dest_dir" && '/' !== $dest_dir[strlen($dest_dir) - 1]) $dest_dir .= '/';
		if($files)
		 {
			foreach($files as $fname) $this->SendFile("$dir/$fname", $dest_dir.$fname);
		 }
		else
		 {
			$i = new RecursiveDirectoryIterator($dir, FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_SELF | FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS);
			$i = new RecursiveIteratorIterator($i, RecursiveIteratorIterator::SELF_FIRST);
			foreach($i as $key => $current)
			 {
				if($current->isFile()) $this->SendFile($current->getPathname(), $dest_dir.$current->getSubPathname());
				elseif($current->isDir()) $this->MkDir($dest_dir.$current->getSubPathname());
			 }
		 }
	 }

	final public function IsDir($v)
	 {
		if('' === "$v") throw new Exception('Empty dir name!');
		return is_dir($this->GetFTPStr($v));
	 }

	final public function IsFile($v)
	 {
		if('' === "$v") throw new Exception('Empty file name!');
		return is_file($this->GetFTPStr($v));
	 }

	final public function FileExists($v)
	 {
		if('' === "$v") throw new Exception('Empty file name!');
		return file_exists($this->GetFTPStr($v));
	 }

	final public function ListItems($dir, $filter = false)
	 {
		if($filter && empty($this->types[$filter])) throw new Exception("Invalid type: $filter.");
		$dir = $this->ConcatFNames($this->root_dir, $dir);
		$r = ftp_rawlist($this->cid, $dir);
        // if(!is_array($r)) throw new Exception('ftp_rawlist error');
		$items = [];
		foreach($r as $i)
		 {
			if(empty($i)) continue;
			$chunks = preg_split("/\s+/", $i, 9, PREG_SPLIT_NO_EMPTY);
			if(count($chunks) < 8) continue;
			$item = new stdClass;
			list($item->rights, $item->number, $item->user, $item->group, $item->size, $item->month, $item->day, $item->time) = $chunks;
			$item->type = $chunks[0]{0} === 'd' ? 'dir' : ($chunks[0]{0} === 'l' ? 'link' : 'file');
			array_splice($chunks, 0, 8);
			$fname = implode(' ', $chunks);
			if('dir' === $item->type && ('.' === $fname || '..' === $fname)) continue;
			$item->name = $fname;
			$item->path = $this->ConcatFNames($dir, $fname);
			if(!$filter || $item->type === $filter) $items[$fname] = new remote_file_item($item, $this);
		 }
		return $items;
	 }

	final public function GetRootDir() { return $this->root_dir; }

	final public function GetDir() { return ftp_pwd($this->cid); }

	final public function __destruct()
	 {
		if($this->cid) ftp_close($this->cid);
	 }

	final private static function ConcatFNames($n1, $n2)
	 {
		if('' === $n1) return $n2;
		if('' === $n2) return $n1;
		if('/' === $n1[strlen($n1) - 1])
		 {
			if('/' === $n2[0] && '/' !== $n2) return $n1.substr($n2, 1);
		 }
		elseif('/' !== $n2[0]) $n2 = "/$n2";
		return "$n1$n2";
	 }

	final private function GetFTPStr($path)
	 {
		$s = 'ftp://'.urlencode($this->username).':'.urlencode($this->password).'@'.$this->server.':'.$this->options->port;
		if('' !== "$path")
		 {
			if('/' !== $path[0]) $s .= '/';
			$s .= $path;
		 }
		return $s;
	 }

	private $cid;
	private $server;
	private $username;
	private $password;
	private $root_dir;
	private $options;
	private $types = ['dir' => 'dir', 'file' => 'file', 'link' => 'link'];
}
?>
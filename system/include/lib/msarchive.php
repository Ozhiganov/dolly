<?php
class MSArchive
{
	final public function __construct($dir, array $options = null)
	 {
		$this->dir = $dir;
		$this->sources = new stdClass;
		$this->sources->directories = [];
		$this->sources->strings = [];
		$this->sources->files = [];
		$this->options = new OptionsGroup($options, [
			'attachment' => ['type' => 'string', 'value' => ''],
			'basename' => ['type' => 'string', 'value' => ''],
			'replacements' => ['type' => 'array', 'value' => []],
		]);
	 }

	final public static function StrReplace($from, $to, $s, &$count = null)
	 {
		$count = 0;
		if(is_array($to))
		 {
			switch("$to[0]")
			 {
				case 'get': $s = str_replace($from, $_GET[$to[1]], $s, $count); break;
				// case 'regex': $to = call_user_func($to, $from); break;
				default: throw new Exception("Invalid replacement type: '$to[0]'!");
			 }
		 }
		else $s = str_replace($from, $to, $s, $count);
		return $s;
	 }

	final public function AddFromDir($dir, array $files = [], $prefix = '')
	 {
		$this->sources->directories[$dir] = new stdClass;
		$this->sources->directories[$dir]->files = $files;
		$this->sources->directories[$dir]->prefix = '' === "$prefix" ? '' : "$prefix/";
	 }

	final public function AddFromString($localname, $contents)
	 {
		$this->sources->strings[$localname] = $contents;
	 }

	final public function AddFile($file, $localname)
	 {
		$this->sources->files[$file] = $localname;
	 }

	final public function AddFiles(array $items)
	 {
		foreach($items as $dir => $files) $this->AddFromDir($dir, $files);
	 }

	final public function Compress($compression = true)
	 {
		if('' === $this->options->basename)
		 {
			$arr = [mt_rand(), microtime(), getmypid()];
			shuffle($arr);
			$fpath = hash('sha1', implode(mt_rand(), $arr));
		 }
		else $fpath = $this->options->basename;
		$fpath = "$this->dir/$fpath.tar";
		$a = new PharData($fpath);
		foreach($this->sources->directories as $dir => $items)
		 if($items->files)
		  {
			foreach($items->files as $fname) $this->AddFileToArchive($a, "$dir/$fname", $items->prefix.$fname);
		  }
		 else
		  {
			$i = new RecursiveDirectoryIterator($dir, FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_SELF | FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS);
			$i = new RecursiveIteratorIterator($i, RecursiveIteratorIterator::SELF_FIRST);
			foreach($i as $key => $current)
			 {
				if($current->isFile()) $this->AddFileToArchive($a, $current->getPathname(), $items->prefix.$current->getSubPathname());
				elseif($current->isDir()) $a->addEmptyDir($items->prefix.$current->getSubPathname());
			 }
		  }
		foreach($this->sources->strings as $localname => $s) $a->addFromString($localname, $s);
		foreach($this->sources->files as $fname => $localname) $this->AddFileToArchive($a, $fname, $localname);
		if($compression)
		 {
			$fpath0 = "$fpath.gz";
			if(file_exists($fpath0)) unlink($fpath0);
			$f = basename($fpath);
			$a->compress(Phar::GZ, substr($f, strpos($f, '.') + 1).'.gz');// второй аргумент - исправление бага, когда при компрессии теряется часть имени файла после точек.
			unset($a);
			if(file_exists($fpath)) Phar::unlinkArchive($fpath);
		 }
		else
		 {
			$fpath0 = $fpath;
			unset($a);
		 }
		if($this->options->attachment && file_exists($fpath0))
		 {
			header('Content-Type: application/gzip');
			header('Content-Disposition: attachment; filename="'.$this->options->attachment.'.tar.gz"');
			header('Content-Length: '.filesize($fpath0));
			readfile($fpath0);
			unlink($fpath0);
			exit;
		 }
		return $fpath0;
	 }

	final private function AddFileToArchive(PharData $a, $fname, $localname)
	 {
		if(isset($this->options->replacements[$localname]))
		 {
			$s = file_get_contents($fname);
			foreach($this->options->replacements[$localname] as $from => $to) $s = self::StrReplace($from, $to, $s);
			$a->addFromString($localname, $s);
		 }
		else $a->addFile($fname, $localname);
	 }

	private $dir;
	private $sources;
	private $options;
}
?>
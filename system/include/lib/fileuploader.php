<?php
class FileUploader extends Uploader
{
	public function __construct($input_name, $dest_dir, $root = null)
	 {
		parent::__construct($input_name);
		$this->dest_dir = $dest_dir;
		$this->root = null === $root ? $_SERVER['DOCUMENT_ROOT'] : $root;
	 }

	final protected function Action($dest_name = null)
	 {
		$args = func_get_args();
		array_shift($args);
		if(is_array($dest_name)) $dest_name = call_user_func_array(count($dest_name) > 1 ? $dest_name : reset($dest_name), $args);
		$fpath = $this->GetDir(true).'/'.($dest_name ? $dest_name.'.'.ms::GetFileExt($this->GetFileName()) : $this->GetFileName());
		if($this->rewrite)
		 {
			if($this->all) self::UnlinkIgnoringExt($fpath);
		 }
		elseif(file_exists($fpath)) throw new EUploaderFileExists(self::GetLang()->EFileExists());
		if(call_user_func($this->copy_callback, $this->GetFileTmpName(), $fpath)) return $fpath;
		else throw new EUploaderCopyTmp(self::GetLang()->ECopyTmp());
	 }

	protected function DBAction(&$data, $file_name, $rel_name, $id, array $options = null) {}

	final public function SetCopyCallback($callback, $method = null)
	 {
		$this->copy_callback = $method ? array($callback, $method) : $callback;
		return $this;
	 }

	final public function EnableRewriting($all = false)
	 {
		$this->rewrite = true;
		$this->all = (bool)$all;
		return $this;
	 }

	final public function DisableRewriting()
	 {
		$this->rewrite = false;
		return $this;
	 }

	final public function GetDir($root = false) { return $root ? $this->root.$this->dest_dir : $this->dest_dir; }
	final public function GetRoot() { return $this->root; }

	private $rewrite = false;
	private $all = false;
	private $dest_dir;
	private $root;
	private $copy_callback = 'copy';
}
?>
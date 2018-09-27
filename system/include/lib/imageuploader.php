<?php
trait TImageUploader
{
	final protected function DBAction(&$data, $file_name, $tbl_name, $id, array $options = null)
	 {
		if(empty($options['fields']))
		 {
			$f_ext = 'ext';
			$f_w = 'width';
			$f_h = 'height';
			if(isset($options['prefix']))
			 {
				$f_ext = $options['prefix'].$f_ext;
				$f_w = $options['prefix'].$f_w;
				$f_h = $options['prefix'].$f_h;
			 }
		 }
		else list($f_ext, $f_w, $f_h) = $options['fields'];
		$data[$f_ext] = ms::GetFileExt($file_name);
		if(empty($options['no_size']))
		 {
			$size = GetImageSize($file_name);
			$data[$f_w] = $size[0];
			$data[$f_h] = $size[1];
		 }
	 }

	final public function SetMaxWidth($width)
	 {
		$this->max_width = $width;
		return $this;
	 }

	final public function SetMaxHeight($height)
	 {
		$this->max_height = $height;
		return $this;
	 }

	final public function SetMaxSize($width, $height)
	 {
		$this->max_width = $width;
		$this->max_height = $height;
		return $this;
	 }

	final public function DisableCache()
	 {
		$this->cache_enabled = false;
		return $this;
	 }

	final public function SetCallback($c)
	 {
		$this->callback = $c;
		return $this;
	 }

	private $max_width = 0;
	private $max_height = 0;
	private $cache_enabled = true;
	private $callback;
}

class ImageUploader extends FileUploader
{
	use TImageUploader;

	public function __construct($input_name, $dest_dir, $root = null)
	 {
		parent::__construct($input_name, $dest_dir, $root);
		$this->SetAccepted(['jpg', 'jpeg', 'gif', 'png']);
		$this->SetCopyCallback(array($this, 'CopyImage'));
		$this->EnableRewriting(true);
	 }

	final public function LimitSize($width, $height)
	 {
		$this->limit_width = $width;
		$this->limit_height = $height;
		return $this;
	 }

	final public function CopyImage($src, $dest)
	 {
		$obj = ImageProcessor::Create($src, $info);
		if($info && $this->limit_width && $this->limit_height && $info[0] > $this->limit_width && $info[1] > $this->limit_height)
		 throw new EUploaderSizeExceeded(self::GetLang()->ESizeExceeded($this->limit_width, $this->limit_height, $info[0], $info[1]));
		$ret_val = $obj->SetCallback($this->callback)->CreateFittedImage($this->max_width, $this->max_height, $dest);
		if($this->cache_enabled) MSIcons::ClearCache($this->GetDir().'/'.basename($dest, ms::GetFileExt($dest)).'%', $this->GetRoot());
		return $ret_val;
	 }

	private $limit_width = 5000;
	private $limit_height = 5000;
}

class EUploaderSizeExceeded extends EUploader {}
?>
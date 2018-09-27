<?php
require_once(dirname(__FILE__).'/imageuploader.php');

class ImageUploaderUrl
{
	use TImageUploader;

	final public function __construct($dir, $root = null)
	 {
		$this->dir = $dir;
		$this->root = null === $root ? $_SERVER['DOCUMENT_ROOT'] : $root;
	 }

	final public function DBLoad($url, $name, $tbl_name, $id, array &$data = null, array $options = null)
	 {
		$file_name = $this->Load($url, $name, $data, $options);
		$this->DBAction($data, $file_name, $tbl_name, $id, $options);
		if($data && empty($options['no_update']))
		 {
			if(!empty($options['before_update'])) call_user_func($options['before_update'], $data, $file_name, $tbl_name, $id, $options);
			DB::UpdateById($tbl_name, $data, $id);
		 }
		return $file_name;
	 }

	final public function Load($url, $name = null, array &$data = null, array $options = null)
	 {
		$tmp_name = $this->GetData($url);
		$exts = array(1 => 'gif', 2 => 'jpg', 3 => 'png');
		if(($info = GetImageSize($tmp_name)) && isset($exts[$info[2]]))// GetImageSizeFromString
		 {
			$data['ext'] = $exts[$info[2]];
			ImageProcessor::Create($tmp_name)->SetCallback($this->callback)->CreateFittedImage($this->max_width, $this->max_height, $tmp_name);
			if($this->max_width || $this->max_height)
			 {
				$size = ImageProcessor::GetFittedImageSize($info[0], $info[1], $this->max_width, $this->max_height);
				$data['width'] = $size['width'];
				$data['height'] = $size['height'];
			 }
			else
			 {
				$data['width'] = $info[0];
				$data['height'] = $info[1];
			 }
			if(!empty($options['basename'])) $data['basename'] = basename($url);
			if(!empty($options['url'])) $data['url'] = $url;
			if(!$name) $name = basename($tmp_name, '.tmp');
			$new_name = $this->GetPath().'/'.$name.'.'.$exts[$info[2]];
			if(file_exists($new_name)) unlink($new_name);
			rename($tmp_name, $new_name);
			if($this->cache_enabled) MSIcons::ClearCache($this->dir.'/'.$name.'%', $this->root);
			return $new_name;
		 }
		else
		 {
			unlink($tmp_name);
			throw new Exception('Ссылка должна быть на изображения следующих типов: '.implode(', ', $exts).'.');
		 }
	 }

	final public function GetPath() { return $this->root.$this->dir; }

	final protected function GetData($url)
	 {
		$url = trim($url);
		if(empty($url)) throw new Exception('Не указана ссылка на изображение.');
		if(0 === strpos($url, 'http://'));
		elseif(0 === strpos($url, 'https://'))
		 {
			$wr = stream_get_wrappers();
			if(!in_array('https', $wr)) throw new Exception('Протокол https не поддерживается.');
		 }
		else $url = 'http:'.($url[0] == '/' && $url[1] == '/' ? '' : '//').$url;
		$fname = $this->GetPath().'/'.sha1($url.time()).'.tmp';
		set_time_limit(60);
		$http = new HTTP(['user_agent' => 'Mozilla/5.0 (Windows NT 6.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36']);
		for($i = 0; $i < 5 && empty($status); ++$i, sleep(2))
		 {
			try
			 {
				$content = $http->GET($url);
				if(false !== file_put_contents($fname, $content)) return $fname;
			 }
			catch(EHTTP $e) {}
		 }
		if(empty($e)) throw new Exception('Не удалось извлечь данные.');
		else throw $e;
	 }

	private $dir;
	private $root;
}
?>
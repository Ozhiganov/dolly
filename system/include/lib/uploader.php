<?php
interface IUploaderLang
{
	function EFileExists();
	function ECopyTmp();
	function EForbiddenExt();
	function ENoFile();
	function EIniSize();
	function EFormSize();
	function EPartial();
}

class UploaderLang implements IUploaderLang
{
	function EFileExists() { return 'Файл с таким именем уже существует на сервере'; }
	function ECopyTmp() { return 'Не удалось скопировать временный файл'; }
	function EForbiddenExt() { return 'Файл неверного типа; разрешены следующие расширения: '; }
	function ENoFile() { return 'Файл не был выбран'; }
	function EIniSize() { return 'Превышен размер загружаемого файла, заданный сервером'; }
	function EFormSize() { return 'Превышен размер загружаемого файла, заданный формой'; }
	function EPartial() { return 'В результате обрыва соединения файл не был докачан'; }
	function ESizeExceeded($limit_width, $limit_height, $width, $height) { return 'Превышен допустимый размер файла '.$limit_width.'×'.$limit_height.' пикселей (загружен файл размером '.$width.'×'.$height.' пикселей).'; }
}

class EUploader extends Exception {}
	class EUploaderNoFile extends EUploader {}
	class EUploaderIniSize extends EUploader {}
	class EUploaderFormSize extends EUploader {}
	class EUploaderPartial extends EUploader {}
	class EUploaderForbiddenExt extends EUploader {}
	class EUploaderCopyTmp extends EUploader {}
	class EUploaderFileExists extends EUploader {}

abstract class Uploader
{
	final public static function GetUploadMaxFileSize($precision = 2)
	 {
		static $val = null;
		if(null === $val)
		 {
			$val = min(self::FileSizeToBytes(ini_get('upload_max_filesize')), self::FileSizeToBytes(ini_get('post_max_size')));
			$val = $val ? Format::RoundFileSize($val, $precision) : 0;
		 }
		return $val;
	 }

	final public static function FileSizeToBytes($val)
	 {
        $val = trim($val);
		if(empty($val)) return 0;
		if(is_numeric($val)) return $val;
		if(preg_match('/^([0-9]+)[\s]*([a-z]+)$/i', $val, $matches))
		 {
			$val = (int)$matches[1];
			switch(strtolower($matches[2]))
			 {
				case 'g':
				case 'gb': $val *= 1024;
				case 'm':
				case 'mb': $val *= 1024;
				case 'k':
				case 'kb': $val *= 1024;
			 }
			return $val;
		 }
		else return 0;
	 }

	public function __construct($input_name)
	 {
		$this->input_name = $input_name;
		self::InitLang(new UploaderLang());
	 }

	final static private function InitLang(IUploaderLang $lang) { self::$lang = $lang; }
	final static protected function GetLang() { return self::$lang; }
	final private function InitFile($val) { $this->_file = $val; }
	final protected function GetFileError() { return $this->_file['error']; }
	final protected function GetFileName() { return $this->_file['name']; }
	final protected function GetFileMIME() { return $this->_file['type']; }
	final protected function GetFileTmpName() { return $this->_file['tmp_name']; }

	final public static function UnlinkIgnoringExt($fpath)
	 {
		if($ext = ms::GetFileExt($fpath)) $fpath = substr_replace($fpath, '.*', -strlen($ext) - 1);
		else $fpath .= '.*';
		if($files = glob($fpath)) foreach($files as $name) unlink($name);
		$fpath = substr($fpath, 0, -2);
		if(file_exists($fpath) && is_file($fpath)) unlink($fpath);
	 }

	abstract protected function Action();
	abstract protected function DBAction(&$data, $file_name, $tbl_name, $id, array $options = null);

	final public function DBLoadFile($file_name, $tbl_name, $id, array $options = null)
	 {
		if($file_name = $this->LoadFile($file_name))
		 {
			if(isset($options['data'])) $data = &$options['data'];
			else $data = [];
			$this->DBAction($data, $file_name, $tbl_name, $id, $options);
			if($data && empty($options['no_update']))
			 {
				if(!empty($options['before_update'])) call_user_func($options['before_update'], $data, $file_name, $tbl_name, $id, $options);
				DB::UpdateById($tbl_name, $data, $id);
			 }
		 }
		return $file_name;
	 }

	final public function LoadFile()
	 {
		if(!isset($_FILES[$this->input_name]))
		 if($this->e_no_file) throw new EUploaderNoFile(self::GetLang()->ENoFile());
		 else return null;
		$args = func_get_args();
		$this->InitFile($_FILES[$this->input_name]);
		return $this->Load($args);
	 }

	public function LoadFiles($before_load = null, $after_load = null)
	 {
		$args = array_slice(func_get_args(), 2);
		if(!isset($_FILES[$this->input_name]))
		 if($this->e_no_file) throw new EUploaderNoFile(self::GetLang()->ENoFile());
		 else return null;
		foreach($_FILES[$this->input_name]['error'] as $key => $err)
		 {
			array_unshift($args, $key, $_FILES[$this->input_name]['name'][$key]);
			$this->InitFile(array('error' => $err, 'name' => $_FILES[$this->input_name]['name'][$key], 'tmp_name' => $_FILES[$this->input_name]['tmp_name'][$key]));
			$file_name = $this->Load(array_merge(array($before_load), $args));
			if($after_load)
			 {
				$args[1] = $file_name;
				call_user_func_array($after_load, $args);
			 }
		 }
	 }

	final private function Load($args = null)
	 {
		switch($this->GetFileError())
		 {
			case UPLOAD_ERR_OK:
				if(!count($this->exts) || (count($this->exts) && in_array(ms::GetFileExt($this->GetFileName()), $this->exts)))
				 return call_user_func_array(array($this, 'Action'), $args);
				else throw new EUploaderForbiddenExt(self::GetLang()->EForbiddenExt().implode(', ', $this->exts));
			case UPLOAD_ERR_NO_FILE: if($this->e_no_file) throw new EUploaderNoFile(self::GetLang()->ENoFile()); else return null;
			case UPLOAD_ERR_INI_SIZE: throw new EUploaderIniSize(self::GetLang()->EIniSize());
			case UPLOAD_ERR_FORM_SIZE: throw new EUploaderFormSize(self::GetLang()->EFormSize());
			case UPLOAD_ERR_PARTIAL: throw new EUploaderPartial(self::GetLang()->EPartial());
		 }
	 }

	final public function SetAccepted($list, $explode = false)
	 {
		$this->exts = $explode ? explode($explode, $list) : (is_array($list) ? $list : [$list]);
		return $this;
	 }

	final public function GetAccepted() { return $this->exts; }

	final public function Required()
	 {
		$this->e_no_file = true;
		return $this;
	 }

	final public function DisableENoFile()
	 {
		$this->e_no_file = false;
		return $this;
	 }

	final protected function GetInputName() { return $this->input_name; }

	private $input_name;
	private $exts = [];
	private $e_no_file = false;
	private $_file;
	private static $lang;
}
?>
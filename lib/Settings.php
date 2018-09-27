<?php
class Settings
{
    const INI_FILE = DOCUMENT_ROOT.'/config.ini';
    private $_params = null;

	public static function staticGet($key) { if(file_exists(self::INI_FILE) && ($str = file_get_contents(self::INI_FILE)) && ($params = parse_ini_string($str)) && isset($params[$key])) return $params[$key]; }

	public function get($param = null)
	 {
		$this->_loadConfigFile();
		return $param ? (isset($this->_params[$param]) ? $this->_params[$param] : null) : $this->_params;
	 }

	public function set($key, $value = null)
	 {
		$this->_loadConfigFile();
		if (is_array($key)) {
			foreach ($key as $key => $value) {
				$this->_setParam($key, $value);
			}
		} else {
			$this->_setParam($key, $value);
		}
		return $this;
	 }

	public function remove($key) { return $this->set($key); }

	public function save()
	 {
		@unlink(self::INI_FILE);
		if(!empty($this->_params)) foreach ($this->_params as $key => $value) {
			file_put_contents(self::INI_FILE, "{$key} = \"{$value}\"" . PHP_EOL, FILE_APPEND);
		}
		return $this;
	 }

	private function _setParam($key, $value)
	 {
		if (!$value) {
			unset($this->_params[$key]);
		} else {
			$this->_params[$key] = $value;
		}
	 }

	private function _loadConfigFile()
	 {
		if(null === $this->_params) $this->_params = (file_exists(self::INI_FILE) && ($str = file_get_contents(self::INI_FILE))) ? parse_ini_string($str) : array();
		return $this->_params;
	 }
}
<?php
class RESTResponse
{
	final public function __construct($response, $curl_info, array $headers)
	 {
		$this->response = $response;
		$this->curl_info = $curl_info;
		$this->headers = $headers;
		$this->response_json = json_decode($response);
		// header_size request_size redirect_count total_time namelookup_time connect_time pretransfer_time
	 }

	final public function __get($name)
	 {
		if('value' === $name) return $this->response_json;
		if(isset($this->fields[$name]) && 'curl_info' === $this->fields[$name]) return $this->curl_info[$name];
		if('headers' === $name) return $this->headers['array'];
		if('headers_source' === $name) return $this->headers['string'];
		throw new Exception("Undefined property '$name'!");
	 }

	final public function __set($name, $value) { throw new Exception('Read only!'); }

	final public function GetHeader($name)
	 {
		$header = [];
		foreach($this->headers['array'] as $h)
		 {
			$h = explode(':', $h, 2);
			if(2 === count($h) && 0 === strcasecmp($name, $h[0])) $header[] = ltrim($h[1]);
		 }
		switch(count($header))
		 {
			case 0: return false;
			case 1: return $header[0];
			default: return $header;
		 }
	 }

	final public function __toString()
	 {
		return $this->response;
	 }

	final public function __debugInfo()
	 {
		return ['url' => $this->curl_info['url'], 'http_code' => $this->curl_info['http_code'], 'content_type' => $this->curl_info['content_type'], 'value' => $this->response_json];
	 }

	private $fields = ['url' => 'curl_info', 'http_code' => 'curl_info', 'content_type' => 'curl_info', 'headers' => true, 'headers_source' => true, 'value' => true];
	private $response;
	private $response_json;
	private $curl_info;
	private $headers;
}

class REST
{
	final public function __construct($url, array $data = [])
	 {
		$this->url = $url;
		$this->data = $data;
	 }

	final public function POST($endpoint, array $data)
	 {
		$this->UpdateData($data);
		$ch = $this->Init($this->GetUrl($endpoint)/* , $headers, $cookie */);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data ? http_build_query($data) : '');
		return $this->Exec($ch);
	 }

	final public function GET($endpoint, array $data)
	 {
		$this->UpdateData($data);
		$ch = $this->Init($this->GetUrl($endpoint, $data)/* , $headers, $cookie */);
		return $this->Exec($ch);
	 }

	final public function CurlHeaderFunction($ch, $headerLine)
	 {
		if(preg_match('/^Set-Cookie:\s*(.+?)=([^;]*)/i', rtrim($headerLine), $c)) $this->cookies[$c[1]] = $c[2];
		if($s = trim($headerLine)) $this->response_headers_array[] = $s;
		return strlen($headerLine);
	 }

	final protected function GetUrl($endpoint, array $data = null)
	 {
		$url = $this->url.$endpoint;
		if($data) $url .= '?'.http_build_query($data);
		return $url;
	 }

	final private function Init($url/* , array $headers, array $cookie */)
	 {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		// if($o = $this->GetOption('basic'))
		 // {
			// curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			// curl_setopt($ch, CURLOPT_USERPWD, $o);
		 // }
		// if($this->GetOption('no_ssl_verifypeer')) curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, true);
		$this->cookies = $this->response_headers_array = [];
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, [$this, 'CurlHeaderFunction']);
		// if($o = $this->GetOption('accept_encoding')) curl_setopt($ch, CURLOPT_ENCODING, $o);
		// if($o = $this->GetOption('cookies'))
		 // {
			// curl_setopt($ch, CURLOPT_COOKIEJAR, $o); 
			// curl_setopt($ch, CURLOPT_COOKIEFILE, $o);
		 // }
		// if($o = $this->GetOption('follow_location'))
		 // {
			// curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			// if(is_int($o) && 0 < $o) curl_setopt($ch, CURLOPT_MAXREDIRS, $o);
		 // }
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// if($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, self::IsAssoc($headers) ? self::TransformHdrArr($headers) : $headers);
		// if($cookie) curl_setopt($ch, CURLOPT_COOKIE, $this->ArrayToCookie($cookie));
		return $ch;
	 }

	final private function Exec($ch)
	 {
		if(($result = curl_exec($ch)) === false)
		 {
			$error = curl_error($ch);
			curl_close($ch);
			throw new Exception('Curl error: '.$error);// ??? or maybe return json?
		 }
		$hsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$headers = substr($result, 0, $hsize);
		$result = substr($result, $hsize);
		$curl_info = curl_getinfo($ch);
		curl_close($ch);
		return new RESTResponse($result, $curl_info, ['string' => $headers, 'array' => $this->response_headers_array]);
	 }

	final private function UpdateData(array &$data) { if($this->data) $data = $data ? array_merge($this->data, $data) : $this->data; }

	private $url;
	private $data;
	private $response_headers_array = [];
	private $cookies = null;
}
?>
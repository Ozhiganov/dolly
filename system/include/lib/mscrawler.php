<?php
MSConfig::RequireFile('traits');

class EMSCrawlerHTTP extends Exception {}

abstract class AMSCrawler
{
	abstract public function __construct(array $options = null);
	abstract protected function GetLinks(stdClass $url, domDocument $xml_doc);
	abstract protected function Filter(array $url, DOMNode $a);
	abstract protected function Process(stdClass $url, domDocument $xml_doc, $http_code, $content, HTTP $http);
	abstract protected function EHTTPHandler(stdClass $url, domDocument $xml_doc, $http_code, $content, HTTP $http);
	abstract protected function BeforeLoadPage(stdClass $url, array &$headers, array &$cookies);
}

abstract class MSCrawler extends AMSCrawler
{
	use TOptions;

	public function __construct(array $options = null)
	 {
		$this->options = $options;
		$this->xml_doc = new domDocument('1.0', 'UTF-8');
		$this->xml_doc->recover = false;
		$this->xml_doc->strictErrorChecking = false;
		$this->xml_doc->validateOnParse = true;
		$this->xml_doc->formatOutput = $this->xml_doc->preserveWhiteSpace = false;
		$this->http = new HTTP(['header' => true, 'user_agent' => $this->GetOption('user_agent') ?: 'Mozilla/5.0 (Windows NT 6.0; WOW64; rv:44.0) Gecko/20100101 Firefox/44.0', 'follow_location' => 1]);
		if($o = $this->GetOption('schemes')) $this->allowed_schemes = array_fill_keys(explode(',', $o), true);
		if($o = $this->GetOption('hosts')) $this->allowed_hosts = array_fill_keys(explode(',', $o), true);
	 }

	final public static function GetAllowedStatuses() { return self::$allowed_statuses; }

	final public static function NormalizeUrl($href, stdClass $owner)
	 {
		$url = parse_url($href);
		if(empty($url['scheme']))
		 {
			if(empty($url['host'])) $url['host'] = $owner->host;
			$url['scheme'] = $owner->scheme;
		 }
		if(empty($url['host']))
		 {
			if(!isset($url['path'])) $url['path'] = '';
		 }
		else
		 {
			if(!isset($url['path'])) $url['path'] = '/';
			elseif($url['path'] && '/' !== $url['path'][0])
			 {
				if(empty($owner->path) || false === ($pos = strrpos($owner->path, '/'))) $url['path'] = "/$url[path]";
				else $url['path'] = substr($owner->path, 0, $pos)."/$url[path]";
			 }
		 }
		return $url;
	 }

	final public static function UrlAsString(stdClass $row)
	 {
		$url = "$row->scheme:";
		if(!empty($row->host)) $url .= "//$row->host";
		if(!empty($row->port)) $url .= ":$row->port";
		$url .= empty($row->path) ? '/' : $row->path;
		if(!empty($row->query)) $url .= "?$row->query";
		if(!empty($row->fragment)) $url .= "#$row->fragment";
		return $url;
	 }

	final public static function Microtime()
	 {
		$t = microtime(true);
		return (new DateTime(date('Y-m-d H:i:s.'.sprintf("%06d", ($t - floor($t)) * 1000000), $t)))->format("Y-m-d H:i:s.u");
	 }

	/* final public function LoadURL($url)
	 {
		$content = $this->http->GET($url);
		print('<pre>');
		var_dump($this->http->GetResponseHeaders(true));
		print('</pre>');
		// if(200 != ($code = $this->http->GetHTTPCode()))
		 // {
			// $msg = 'Server responded: '.$code;
			// $this->Log($url, 'load', 'error', $msg);
			// $this->EHTTPHandler($url, $code);
			// throw new EMSCrawlerHTTP($msg, $code);
			print('Content length: '.strlen($content));
			if(strlen($content)) print('<pre>'.htmlspecialchars($content).'</pre>');
		 // }
		$enc_content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
		if($path = $this->GetOption('save_pages'))
		 {
			// if('/' === $path[0]) $path = "$_SERVER[DOCUMENT_ROOT]$path";
			// $name = "$path/{$this->run_id}_$url[id]";
			// file_put_contents("$name.hdrs", $this->http->GetResponseHeaders());
			// file_put_contents("$name.html", $content);
		 }
		else ;
	 } */

	final public function Run()
	 {
		$c = [];
		if($opt = $this->GetOption('condition')) $c[] = $opt;
		if($this->GetOption('revisit'))
		 {
			$ct = '`http_code` <> 0';
			if(!$this->GetOption('revisit_only')) $ct .= ' OR `http_code` IS NULL';
		 }
		else $ct = '`http_code` IS NULL';
		if($opt = $this->GetOption('reload_errors'))
		 {
			$ct .= ' OR ';
			$opt = explode(',', $opt);
			switch((int)in_array('300', $opt) + 2 * (int)in_array('400', $opt) + 4 * (int)in_array('500', $opt))
			 {
				case 1: $ct .= '(`http_code` BETWEEN 300 AND 399)'; break;
				case 2: $ct .= '(`http_code` BETWEEN 400 AND 499)'; break;
				case 3: $ct .= '(`http_code` BETWEEN 300 AND 499)'; break;
				case 4: $ct .= '(`http_code` BETWEEN 500 AND 599)'; break;
				case 5: $ct .= '(`http_code` BETWEEN 300 AND 399) OR (`http_code` BETWEEN 500 AND 599)'; break;
				case 6: $ct .= '(`http_code` BETWEEN 400 AND 599)'; break;
				case 7: $ct .= '(`http_code` BETWEEN 300 AND 599)'; break;
				default: throw new Exception("Invalid value for option 'reload_errors'!");
			 }
		 }
		$c[] = $ct;
		if($opt = $this->GetOption('no_action'))
		 {
			if('extract' === $opt) $c[] = '`extract` IS NULL AND `process` IS NOT NULL';
			elseif('process' === $opt) $c[] = '`process` IS NULL AND `extract` IS NOT NULL';
			else throw new Exception("Invalid value for option 'no_action'! Expected: extract or process; given: $opt.");
		 }
		else $c[] = '`process` IS NOT NULL OR `extract` IS NOT NULL';
		$ct = '';
		if($opt = $this->GetOption('revisit'))
		 {
			if(!$this->GetOption('revisit_only')) $ct .= '`queried` IS NULL OR ';
			$ct .= "`queried` <= DATE_SUB(NOW(), INTERVAL $opt)";
		 }
		if($ct) $c[] = $ct;
		$res = DB::Select($this->GetTblName('url'), '*', '('.implode(') AND (', $c).')', null, '`http_code` IS NULL DESC, `queried` ASC', ['limit' => $this->GetOption('limit')]);
		$this->LoadPagesFromDB($res, 'Run');
	 }

	final public function LoadId($id, array $o = null)
	 {
		static $optgr = null;
		if(!$optgr) $optgr = new OptionsGroup(__CLASS__ .'__'. __FUNCTION__, ['show_src' => false]);
		$o = $optgr($o);
		$res = $this->GetRel()->Select(null, "`id` = '$id'");
		$this->LoadPagesFromDB($res, 'LoadId');
	 }

	final public function BanUrl(stdClass $url, $msg = '')
	 {
		$this->Log($url, 'ban', 'warning', "Ссылка #$url->id исключена из обработки.".($msg ? " $msg" : ''));
		throw new EMSCrawlerHTTP($msg, 0);
	 }

	final public function ProcessFile($file_name, $http_code = 200)
	 {
		$this->run_id = time();
		$content = file_get_contents($file_name);
		@$this->xml_doc->loadHTML($content);
		$this->Process((object)['id' => null], $this->xml_doc, $http_code, $content, $this->http);
		$this->run_id = null;
	 }

	protected function GetLinks(stdClass $url, domDocument $xml_doc) { return $xml_doc->GetElementsByTagName('a'); }
	protected function Process(stdClass $url, domDocument $xml_doc, $http_code, $content, HTTP $http) {}
	protected function EHTTPHandler(stdClass $url, domDocument $xml_doc, $http_code, $content, HTTP $http) { if($callback = $this->GetOption('e_http_handler')) call_user_func($callback, $this, $url, $xml_doc, $http_code, $content, $http); }
	protected function OnLoadPage(stdClass $url) {}

	final protected function LoadPagesFromDB(IDBResult $res, $caller)
	 {
		if(false !== ($opt_log = $this->GetOption('log'))) $this->run_id = 'print' === $opt_log ? time() : DB::Insert($this->GetTblName('run'), ['caller' => $caller, 'crawler_id' => "{$this->GetOption('id')}"]);
		if($num_rows = $res->GetNumRows())
		 {
			$this->Log(null, 'select', 'success', "Получено ссылок для обработки: $num_rows.");
			$ins_func = $this->GetOption('debug') ? 'OutputUrl' : 'InsertUrl';
			$can_extract = $this->GetOption('no_action') != 'extract';
			$can_process = $this->GetOption('no_action') != 'process';
			foreach($res as $row)
			 {
				$http_code = false;
				try
				 {
					$extract = $row->extract && $can_extract;
					$process = $row->process && $can_process;
					if($extract || $process)
					 {
						$tmtr = new TimeMeter();
						$status = $this->LoadPage($row, $http_code, $content);
						$this->Log($row, 'load', 'success', "Ссылка #$row->id загружена. $tmtr");
						if('skip' === $status) $extract = $process = false;
						if($extract)
						 {
							$items = [];
							foreach($this->GetLinks($row, $this->xml_doc) as $a)
							 {
								if($href = $a->GetAttribute('href'))
								 {
									$url = $this->NormalizeUrl($href, $row);
									if($this->PreFilterURL($url) && ($s = $this->Filter($url, $a)))
									 {
										if(true === $s || 1 === $s) $s = [1, 1];
										switch((int)!empty($s[0]) + 2 * (int)!empty($s[1]))
										 {
											case 1: $url['extract'] = 1; break;
											case 2: $url['process'] = 1; break;
											case 3: $url['extract'] = $url['process'] = 1; break;
											default:
										 }
										$items[self::UrlAsString((object)$url)] = $url;
									 }
								 }
							 }
							foreach($items as $url) $this->{$ins_func}($url);
							$this->Log($row, 'extract', $items ? 'success' : 'warning', 'Извлечено ссылок: '.count($items));
						 }
						if($process) $this->Process($row, $this->xml_doc, $http_code, $content, $this->http);
					 }
				 }
				catch(EMSCrawlerHTTP $e)
				 {
					$http_code = $e->GetCode();
					MSConfig::LogException($e);
				 }
				catch(EHTTP $e)
				 {
					MSConfig::HandleException($e);
					continue;
				 }
				if(false !== $http_code && !$this->GetOption('debug')) DB::Update($this->GetTblName('url'), ['=queried' => 'NOW()', 'http_code' => $http_code, '~id' => $row->id], '`id` = :id');
			 }
		 }
		else $this->Log(null, 'select', 'warning', 'Нет ссылок для обработки.');
		$this->run_id = null;
	 }

	final protected function GetPrefix() { return $this->prefix; }
	final protected function GetTblName($name) { return $this->prefix.'_'.$name; }

	final protected function PreFilterURL(array $url)
	 {
		if($this->allowed_schemes && empty($this->allowed_schemes[$url['scheme']])) return false;
		if($this->allowed_hosts && empty($this->allowed_hosts[$url['host']])) return false;
		return true;
	 }

	final protected function Log(stdClass $url = null, $action, $status, $text)
	 {
		if(false === ($opt_log = $this->GetOption('log'))) return;
		if(!$this->run_id) throw new Exception('Can not log without running!');
		static $allowed_actions = ['extract' => 'extract', 'process' => 'process', 'select' => 'select', 'load' => 'load', 'ban' => 'ban'];
		if(!isset($allowed_actions[$action])) throw new Exception('Invalid action! Expected: '.implode(', ', $allowed_actions)."; given: $action.");
		if(!isset(self::$allowed_statuses[$status])) throw new Exception('Invalid status! Expected: '.implode(', ', array_keys(self::$allowed_statuses))."; given: $status.");
		if('print' === $opt_log) print("<div><pre>{$this->Microtime()} - run_id: $this->run_id; ".(null === $url ? 'no url' : "url_id: $url->id")."; $action: $status.</pre><div>$text</div></div>");
		else
		 {
			$data = ['run_id' => $this->run_id, 'action' => $action, 'status' => $status, 'text' => $text];
			if(null !== $url) $data['url_id'] = $url->id;
			DB::Insert($this->GetTblName('message'), $data);
		 }
	 }

	final private function InsertUrl(array $url)
	 {
		$t_name = $this->GetTblName('url');
		if(!DB::Count($t_name, self::Url2Condition($url, $p, ['extract', 'process']), $p)) DB::Insert($t_name, $url);
	 }

	final private function OutputUrl(array $url) { echo self::UrlAsString((object)$url), '<br/>'; }

	final private function Url2Condition(array $url, array &$params = null, array $skip = null)
	 {
		$c = '';
		$params = [];
		foreach($url as $k => $v)
		 {
			if($skip && in_array($k, $skip)) continue;
			if($c) $c .= ' AND ';
			if($k[0] === '=') $s = '`'.substr($k, 1)."` = $v";
			else
			 {
				$s = "`$k` = ?";
				$params[] = $v;
			 }
			$c .= "($s)";
		 }
		return $c;
	 }

	final private function LoadPage(stdClass $url, &$code, &$content)
	 {
		$headers = $cookies = [];
		$this->BeforeLoadPage($url, $headers, $cookies);
		$content = $this->http->GET(self::UrlAsString($url), [], $headers, $cookies);
		$code = $this->http->GetHTTPCode();
		if('gzip' === $this->http->GetResponseHeader('Content-Encoding')) $content = gzdecode($content);
		if($path = $this->GetOption('save_pages'))
		 {
			if('/' === $path[0]) $path = "$_SERVER[DOCUMENT_ROOT]$path";
			$name = "$path/{$this->run_id}_$url->id";
			file_put_contents("$name.hdrs", $this->http->GetResponseHeaders());
			file_put_contents("$name.html", $content);
		 }
		$enc_content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
		@$this->xml_doc->loadHTML($enc_content);
		if(200 != $code)
		 {
			$msg = 'Server responded: '.$code;
			$this->Log($url, 'load', 'error', $msg);
			$status = $this->EHTTPHandler($url, $this->xml_doc, $code, $content, $this->http);
			if(false !== $status) throw new EMSCrawlerHTTP($msg, $code);
			return $status;
		 }
	 }

	private $allowed_hosts = null;
	private $allowed_schemes = null;
	private $xml_doc;
	private $http;
	private $run_id;
	private $prefix = 'mscrawler';

	private static $allowed_statuses = ['success' => ['title' => 'Успех'], 'warning' => ['title' => 'Предупреждение'], 'error' => ['title' => 'Ошибка']];
}
?>
<?php
require_once(dirname(__FILE__).'/traits.php');

interface IFormat
{
	function __construct(array $options = null);
	function Apply(&$val);
}

class file_size
{
	final public function __construct($value, $unit)
	 {
		$this->value = $value;
		$this->unit = $unit;
	 }

	final public static function GetFileSizeUnit($index) { return @self::$file_size_units[$index]; }

	final public function __toString() { return $this->value ? $this->value.' '.self::$file_size_units[$this->unit] : '0'; }

	public $value;
	public $unit;
	private static $file_size_units = array('B' => 'Б', 'M' => 'МБ', 'K' => 'КБ', 'G' => 'ГБ');
}

abstract class Format implements IFormat
{
	use TOptions;

	final public static function AsInt($val, array $options = null) { return number_format($val, 0, '.', empty($options['separator']) ? ' ' : $options['separator']); }//&#8201;

	final public static function AsPhoneHref($val)
	 {
		$val = preg_replace('/[()\s-]+/', '', $val);
		return 'tel:'.(preg_match('/^[^8][0-9]{9}$/', $val) ? "8$val" : $val);
	 }

	final public static function AsPhoneNum($n, array $options = null)
	 {
		$n = trim($n);
		if(is_numeric($n))
		 {
			if(11 === strlen($n)) return "$n[0] $n[1]$n[2]$n[3] $n[4]$n[5]$n[6]-$n[7]$n[8]-$n[9]$n[10]";
			elseif(10 === strlen($n)) return "8 $n[0]$n[1]$n[2] $n[3]$n[4]$n[5]-$n[6]$n[7]-$n[8]$n[9]";
		 }
		if(preg_match('/^((\+?7)|8)?([0-9]{3})([0-9]{3})([0-9]{2})([0-9]{2})$/', $n, $m))
		 {
			if(empty($m[1])) $m[1] = '8';
			return "$m[1] $m[3] $m[4]-$m[5]-$m[6]";
		 }
		return $n;
	 }

	final public static function AsEmail($val, array $options = null)
	 {
		$s = 'mailto:';
		if(strpos($val, $s) === 0)
		 {
			$href = $val;
			$url = substr($val, strlen($s));
		 }
		else
		 {
			$href = "$s$val";
			$url = $val;
		 }
		switch(@$options['field'])
		 {
			case 'href': return $href;
			case 'value': return $url;
			default: return array('href' => $href, 'value' => $url);
		 }
	 }

	final public static function AsSkype($val, array $options = null)
	 {
		$s = 'skype:';
		if(strpos($val, $s) === 0)
		 {
			$href = $val;
			$url = substr($val, strlen($s));
		 }
		else
		 {
			$href = "$s$val";
			$url = $val;
		 }
		switch(@$options['field'])
		 {
			case 'href': return $href;
			case 'value': return $url;
			default: return array('href' => $href, 'value' => $url);
		 }
	 }

	final public static function AsUrl($val, array $options = null)
	 {
		$s = 'https://';
		if(strpos($val, $s) === 0)
		 {
			$href = $val;
			$url = substr($val, strlen($s));
		 }
		else
		 {
			$protocol = empty($options['protocol']) ? '//' : $options['protocol'];
			$patterns = array('/^\/\//', '/^http:\/\//');
			$url = $val;
			$href = "$protocol$val";
			foreach($patterns as $p)
			 {
				$r = preg_replace($p, '', $val, -1, $c);
				if($c)
				 {
					$href = $val;
					$url = $r;
					break;
				 }
			 }
		 }
		switch(@$options['field'])
		 {
			case 'href': return $href;
			case 'value': return $url;
			default: return array('href' => $href, 'value' => $url);
		 }
	 }

	final public static function AsFloat($val, array $options = null)
	 {
		$fval = (float)$val;
		if(!$fval) return '0';
		if(1 > abs($fval)) return str_replace('.', ',', rtrim(self::RoundByPrecision($val, $options), '0'));
		$tmp = explode('.', self::RoundByPrecision($val, $options));
		$tmp[1] = empty($tmp[1]) ? null : rtrim($tmp[1], '0');
		return self::AsInt($tmp[0], $options).($tmp[1] ? (empty($options['dec_point']) ? ',' : $options['dec_point']).$tmp[1] : '');
	 }

	final public static function IsDate($val)
	 {
		return !empty($val) && preg_match('/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/', $val);
	 }

	final public static function IsDateTime($val) { return preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $val); }

	final public static function AsDateTime($val, array $options = null)
	 {
		if(preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $val, $m))
		 {
			$month = empty($options['text']) ? ".$m[2]." : ' '.self::$months_1[$m[2]].' ';
			return "$m[3]$month$m[1], $m[4]:$m[5]";
		 }
		else return $val;
	 }

	final public static function AsDate($val, array $options = null) { return (new FormatDate($options))->Apply($val); }

	final public static function AsDateMD($val)
	 {
		if(preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $val, $m))
		 {
			return $m[3].' '.self::$months_1[$m[2]];
		 }
		else return $val;
	 }

	final public static function RoundFileSize($size, $precision = 2)
	 {
		$u = 'B';
		if($size >= 1024)
		 {
			$size /= 1024;
			$u = 'K';
			if($size >= 1024)
			 {
				$size /= 1024;
				$u = 'M';
				if($size >= 1024)
				 {
					$size /= 1024;
					$u = 'G';
				 }
			 }
		 }
		return new file_size(round($size, $precision), $u);
	 }

	final public static function GetAmountStr($num, $str1, $str2, $str3)
	 {
		$tmp = $num % 100;
		if($tmp > 4 && $tmp < 21) return $str3;
		else
		 {
			$tmp = $num % 10;
			if($tmp == 1) return $str1;
			else if($tmp > 1 && $tmp < 5) return $str2;
			else return $str3;
		 }
	 }

	final public static function AsTimeDiff($val, array $options = null)
	 {
		if(!$val) return 0;
		$s = $val % 60;
		$val = ($val - $s) / 60;
		$m = $val % 60;
		$h = ($val - $m) / 60;
		if(empty($options['format'])) $f = empty($options['separators']) ? '%02d:%02d:%02d' : "%02d{$options['separators'][0]}%02d{$options['separators'][1]}%02d{$options['separators'][2]}";
		elseif(is_callable($options['format'])) return call_user_func($options['format'], $h, $m, $s, empty($options['separators']) ? null : $options['separators']);
		else $f = $options['format'];
		return sprintf($f, $h, $m, $s);
	 }

	public function __construct(array $options = null)
	 {
		$this->AddOptionsMeta(['src' => [], 'dest' => []]);
		$this->SetOptionsData($options);
		if(!empty($options['index']))
		 {
			$this->src_index = $this->dest_index = $options['index'];
			$this->apply_func = 'ApplyRow';
		 }
		elseif(($src_i = $this->GetOption('src')) && ($dest_i = $this->GetOption('dest')))
		 {
			$this->src_index = $options['src'];
			$this->dest_index = $options['dest'];
			$this->apply_func = 'ApplyRow';
		 }
		else $this->apply_func = 'ApplyVal';
	 }

	final public function Apply(&$val)
	 {
		$func = $this->apply_func;
		return $this->$func($val);
	 }

	abstract protected function Run($val);

	final protected static function RoundByPrecision($val, array $options = null) { return isset($options['precision']) ? (false === $options['precision'] ? $val : round($val, $options['precision'])) : round($val, 2); }

	final private function ApplyRow(&$row) { $row[$this->dest_index] = $this->Run($row[$this->src_index]); }
	final private function ApplyVal($val) { return $this->Run($val); }

	private static $months_1 = [1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря',
								'01' => 'января', '02' => 'февраля', '03' => 'марта', '04' => 'апреля', '05' => 'мая', '06' => 'июня', '07' => 'июля', '08' => 'августа', '09' => 'сентября', '10' => 'октября', '11' => 'ноября', '12' => 'декабря'];

	private $apply_func;
	private $src_index;
	private $dest_index;
}
?>
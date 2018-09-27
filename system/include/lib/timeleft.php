<?php
/* options

*/
class TimeLeft extends Format
{
	final protected function Run($val)
	 {
		if(0 === strpos($val, 'i~'))
		 {
			$val = explode(':', str_replace('i~', '', $val));
			$ret_val = array();
			$map = array(0 => array('unit' => 'hour', 'words' => array('час', 'часа', 'часов')),
						 1 => array('unit' => 'minute', 'words' => array('минуту', 'минуты', 'минут')),
						 2 => array('unit' => 'second', 'words' => array('секунду', 'секунды', 'секунд')));
			$precision = $this->GetOption('precision');
			foreach($map as $key => $value)
			 {
				$v = (int)$val[$key];
				if($v) $ret_val[] = $v.' '.self::GetAmountStr($v, $value['words'][0], $value['words'][1], $value['words'][2]);
				if($value['unit'] === $precision) break;
			 }
			if($ret_val)
			 {
				$ago = $this->GetOption('ago');
				if(is_null($ago)) $ret_val[] = 'назад';
				elseif($ago) $ret_val[] = $ago;
			 }
			else
			 {
				$just = $this->GetOption('just');
				if(is_null($just)) $ret_val[] = 'только что';
				elseif($just) $ret_val[] = $just;
			 }
			return implode(' ', $ret_val);
		 }
		else return $val;
	 }

	private static $month_names = array(1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
}
?>
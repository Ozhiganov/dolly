<?php
/* options
empty - значение, возвращаемое, если дата пустая (0000-00-00)
*/
class FormatDate extends Format
{
	public function __construct(array $options = null)
	 {
		$this->AddOptionsMeta(['empty' => [], 'text' => []]);
		parent::__construct($options);
	 }

	final protected function Run($val)
	 {
		if(is_numeric($val))
		 {
			throw new Exception('Класс `FormatDate` не поддерживает работу с UNIX-датами.');
			// $d_arr = getdate($date);
			// return $d_arr['mday'].' '.$month_names[$d_arr['mon']].' '.$d_arr['year'].' года';
		 }
		$ret_val = '';
		$d = ms::SQLDateTimeToArray($val);
		$now = ms::SQLDateTimeToArray(date('Y-m-j G:i:s'));
		$diff = ms::GetDateDiffArray($d, $now);
		// if(ms::CompareDateDiff($diff, null, 0, 'day')) ;
		// if(ms::CompareDateDiff($diff, null, 1, 'day')) ;
		// if(ms::CompareDateDiff($diff, null, 2, 'day')) ;
		// else
		 // {
			$ret_val .= $d['day'].($this->GetOption('text') ? ' '.self::$month_names[(int)$d['month']].' ' : '.'.($d['month'] < 10 ? '0' : '').$d['month'].'.').$d['year'];
		 // }
		// if($d_arr[2] == '00') $month_names = array(1 => 'январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь');
		// else $ret_val = (int)$d_arr[2];
		// if($d_arr[1] != '00') $ret_val .= ($ret_val ? ' ' : '').$month_names[(int)$d_arr[1]];
		// if($d_arr[0] != '0000') $ret_val .= ($ret_val ? ' ' : '').$d_arr[0];
		return $ret_val ?: $this->GetOption('empty');
/* 		$month_names = array('января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
		if(null === $date) $date = time();
		if(is_numeric($date))
		 {
			$d_arr = getdate($date);
			return $d_arr['mday'].' '.$month_names[$d_arr['mon'] - 1].' '.$d_arr['year'].' '.($d_arr['hours'] < 10 ? 0 : '').($div ? $div : ' ').$d_arr['hours'].':'.($d_arr['minutes'] < 10 ? 0 : '').$d_arr['minutes'];
		 }
		elseif($dt_arr = explode(' ', $date))
		 {
			$d_arr = explode('-', $dt_arr[0]);
			$t_arr = explode(':', $dt_arr[1]);
			$ret_val = '';
			if($d_arr[2] != '00') $ret_val .= $d_arr[2];
			if($d_arr[1] != '00') $ret_val .= ($ret_val ? ' ' : '').$month_names[(int)$d_arr[1]];
			if($d_arr[0] != '0000') $ret_val .= ($ret_val ? ' ' : '').$d_arr[0];
			$ret_val .= ($div ? $div : ' ').$t_arr[0].':'.$t_arr[1];
			return $ret_val ? $ret_val : '<em>&mdash; не определена &mdash;</em>';
		 }
		else return ''; */
	 }

	private static $month_names = array(1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
}
?>
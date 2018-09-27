<?php
abstract class SQLExpr
{
	final public static function nl2br($fld_name, $alias = null) { return 'REPLACE('.$fld_name.', "\r\n", "<br />")'.self::HasAlias($alias, $fld_name); }
	final public static function MSContactInfoOrderBy($tbl_name = null) { return self::OrderByPosition($tbl_name); }
	final public static function MSAlbumsOrderBy($tbl_name = null) { return self::OrderByPositionAndDateTime($tbl_name); }
	final public static function MSImagesOrderBy($tbl_name = null) { return self::OrderByPositionAndDateTime($tbl_name); }
	final public static function MSResumeOrderBy() { return '`date_time` DESC, `id` DESC'; }
	final public static function MSPollQOrderBy($tbl_name = null) { return '`id` DESC'; }
	final public static function MSPollAOrderBy($tbl_name = null) { return self::OrderByPosition($tbl_name); }
	final public static function MSAppFormOrderBy($tbl_name = null) { return self::OrderByPosition($tbl_name); }
	final public static function MSAppFormAOrderBy($tbl_name = null) { return self::OrderByPosition($tbl_name); }
	final public static function MSBannersOrderBy($tbl_name = null) { return self::OrderByPosition($tbl_name); }
	final public static function MSSimpleListOrderBy($tbl_name = null) { return self::OrderByPosition($tbl_name); }
	final public static function MSFaqOrderBy() { return '`date_time` DESC, `id` DESC'; }
	final public static function MSFilesOrderBy($tbl_name = null) { return self::OrderByPosition($tbl_name); }
	final public static function MSVideosOrderBy($tbl_name = null) { return self::OrderByPosition($tbl_name); }
	final public static function NewsOrderBy() { return '`date_time` DESC, `id` DESC'; }
	final public static function MSMapsOrderBy($tbl_name = null) { return self::OrderByPosition($tbl_name); }

	final private static function FormatDateTimeIntervalCond($int, $fld_name, $day_str)
	 {
		return $int ? 'IF(TIME_TO_SEC(TIMEDIFF(NOW(), '.$fld_name.')) < 43200, CONCAT("i~", TIMEDIFF(NOW(), '.$fld_name.')), "'.$day_str.'")' : '"'.$day_str.'"';
	 }

	final public static function OrderByPosition($tbl_name = null)
	 {
		if($tbl_name) $tbl_name = '`'.$tbl_name.'`.';
		return $tbl_name.'`position` ASC, '.$tbl_name.'`id` DESC';
	 }

	final public static function FormatDateTime(array $options = array(), $fld_name = '`date_time`', $alias = 'date_time_f')
	 {
		$o = array_merge(array('format' => null, 'text' => true, 'year' => true, 'notime' => false, 'interval' => false, 'timediv' => ' в'), $options);
		/* if($o['interval'])
		 {
			$str = 'IF(TIME_TO_SEC(TIMEDIFF(NOW(), '.$fld_name.')) < 43200, CONCAT("i~", TIMEDIFF(NOW(), '.$fld_name.')), "';
			$today_str = $str.'Сегодня")';
			$yesterday_str = $str.'Вчера")';
		 }
		else
		 {
			$today_str = '"Сегодня"';
			$yesterday_str = '"Вчера"';
		 } */
		if($alias) $alias = " AS `$alias`";
		if($o['notime']) return 'CASE DATEDIFF(CURDATE(), '.$fld_name.')
	WHEN -2 THEN "Послезавтра"
	WHEN -1 THEN "Завтра"
	WHEN 0 THEN '.self::FormatDateTimeIntervalCond($o['interval'], $fld_name, 'Сегодня').'
	WHEN 1 THEN '.self::FormatDateTimeIntervalCond($o['interval'], $fld_name, 'Вчера').'
	WHEN 2 THEN "Позавчера"
	ELSE DATE_FORMAT('.$fld_name.', '.self::DateFormatStr($o).')
END'.$alias;
		else
		 {
			$time_str = $o['timediv'].' %H:%i';
			return 'DATE_FORMAT('.$fld_name.', CASE DATEDIFF(CURDATE(), '.$fld_name.')
	WHEN -2 THEN "Послезавтра'.$time_str.'"
	WHEN -1 THEN "Завтра'.$time_str.'"
	WHEN 0 THEN '.self::FormatDateTimeIntervalCond($o['interval'], $fld_name, 'Сегодня'.$time_str).'
	WHEN 1 THEN '.self::FormatDateTimeIntervalCond($o['interval'], $fld_name, 'Вчера'.$time_str).'
	WHEN 2 THEN "Позавчера'.$time_str.'"
	ELSE '.self::DateFormatStr($o, $time_str).'
END)'.$alias;
		 }
	 }

	final public static function FormatDate(array $options = array(), $fld_name = '`date`', $alias = 'date_f')
	 {
		$o = array_merge(array('format' => null, 'text' => true, 'year' => true), $options);
		return 'CASE DATEDIFF(CURDATE(), '.$fld_name.')
	WHEN -2 THEN "Послезавтра"
	WHEN -1 THEN "Завтра"
	WHEN 0 THEN "Сегодня"
	WHEN 1 THEN "Вчера"
	WHEN 2 THEN "Позавчера"
	ELSE DATE_FORMAT('.$fld_name.', '.self::DateFormatStr($o).')
END'.($alias ? " AS `$alias`" : '');
	 }

	final private static function DateFormatStr(array $o, $time_str = '')
	 {
		if($o['format']) return $o['format'];
		$dm = $o['text'] ? '%e %M' : '%e.%m';
		$y = $o['text'] ? ' %Y' : '.%Y';
		return $o['year'] ? "'$dm$y$time_str'" : "'$dm$time_str'";
	 }

	final public static function BannerUrl($dir, $tbl_name = null, $host = null)
	 {
		if($tbl_name) $tbl_name = '`'.$tbl_name.'`.';
		return self::Url(true, 'href', 'url', $tbl_name).', CONCAT("'.($tbl_name ?: 'banner').'_", `id`) AS `block_id`, CONCAT("'.$host.$dir.'/image_", '.$tbl_name.'`id`, ".", '.$tbl_name.'`ext`) AS `src`, CONCAT("'.$host.$dir.'/image_alt_", '.$tbl_name.'`id`, ".", '.$tbl_name.'`alt_ext`) AS `alt_src`, '.$tbl_name.'`width`, '.$tbl_name.'`height`';
	 }

	final public static function CheckBox($field_name, $input_name, $alias = null, $value_field_name = '`id`')
	 {
		return 'CONCAT("<input type=\"hidden\" name=\"'.$input_name.'[", '.$value_field_name.', "]\" value=\"0\" /><input type=\"checkbox\" name=\"'.$input_name.'[", '.$value_field_name.', "]\" value=\"1\"", IF('.$field_name.' = 0, "", " checked=\"checked\""), " />")'.self::HasAlias($alias, $field_name);
	 }

	final public static function Url($add_protocol = true, $alias = 'href', $field_name = 'url', $tbl_name = null)
	 {
		if($tbl_name);
		return 'IF('.$field_name.' <> "", REPLACE('.($add_protocol ? 'IF(SUBSTRING('.$field_name.', 1, 7) = "http://" OR SUBSTRING('.$field_name.', 1, 8) = "https://", '.$field_name.', CONCAT("http://", '.$field_name.'))' : 'REPLACE('.$field_name.', "http://", "")').', "&", "&amp;"), NULL)'.self::HasAlias($alias, $field_name);
	 }

	final public static function HasUrlPart($fld_alias = null, $tbl_name = null)
	 {
		if($tbl_name) $tbl_name = '`'.$tbl_name.'`.';
		return 'IF('.$tbl_name.'`url_part_name` IS NULL OR '.$tbl_name.'`url_part_name` = "", '.$tbl_name.'`id`, '.$tbl_name.'`url_part_name`)'.($fld_alias ? ' AS `'.$fld_alias.'`' : '');
	 }

	final public static function FileName($dir, $alias = 'href', $tbl_name = null)
	 {
		if($tbl_name) $tbl_name = '`'.$tbl_name.'`.';
		return 'CONCAT("'.$dir.'/file_", '.$tbl_name.'`id`, ".", '.$tbl_name.'`ext`)'.self::HasAlias($alias);
	 }

	final public static function IUrl($host, $dir, $width, $height, $fld_name = 'ext', $alias = 'image_src', $rn = false)//checked //rn - tbl_name
	 {
		$host = self::GetHost($host);
		if($rn) $ret_val = "CONCAT_WS('', '$host/', `$rn`.`icon_type`, '/w$width/h$height', IF(`$rn`.`icon_type` = 'crop', CONCAT('/left', `$rn`.`crop_left`, '/top', `$rn`.`crop_top`, '/ratio', `$rn`.`crop_ratio`), ''), '$dir/image_', `$rn`.`id`, '.', `$rn`.`$fld_name`)";
		else $ret_val = "CONCAT_WS('', '$host/', `icon_type`, '/w$width/h$height', IF(`icon_type` = 'crop', CONCAT('/left', `crop_left`, '/top', `crop_top`, '/ratio', `crop_ratio`), ''), '$dir/image_', `id`, '.', `$fld_name`)";
		return $ret_val.self::HasAlias($alias);
	 }

	final public static function IUrlEx($host, $dir, $width, $height, $fld_name = 'ext', $src_alias = 'image_src', $href_alias = 'href', $tbl_name = false)
	 {
		return self::IUrl($host, $dir, $width, $height, $fld_name, $src_alias, $tbl_name).', '.self::SrcImageUrl(self::GetHost($host).$dir, $fld_name, $href_alias, $tbl_name);
	 }

	final public static function ImageUrlEx($host, $dir, $type, $width, $height, $fld_name = 'ext', $src_alias = 'image_src', $href_alias = 'href', $tbl_name = false)
	 {
		return self::ImageUrl($host, $dir, $type, $width, $height, $fld_name, $src_alias, $tbl_name).', '.self::SrcImageUrl(self::GetHost($host).$dir, $fld_name, $href_alias, $tbl_name);
	 }

	// поставить tbl_name до fld_name - оно меняется чаще
	final public static function ImageUrl($host, $dir, $type = false, $w = false, $h = false, $fld_name = 'ext', $alias = 'image_src', $tbl_name = false, $key_fld = 'id')
	 {
		if($tbl_name) $tbl_name = '`'.$tbl_name.'`.';
		if($type)
		 {
			$type = "/$type";
			if($w) $type .= "/w$w";
			if($h) $type .= "/h$h";
		 }
		return "CONCAT('".self::GetHost($host)."$type$dir/image_', $tbl_name`$key_fld`, '.', $tbl_name`$fld_name`)".self::HasAlias($alias);
	 }

	final public static function SrcImageUrl($dir, $fld_name = 'ext', $alias = 'href', $tbl_name = false, $id_fld = 'id')
	 {
		if($tbl_name) $tbl_name = "`$tbl_name`.";
		return 'CONCAT("'.$dir.'/image_", '.$tbl_name.'`'.$id_fld.'`, ".", '.$tbl_name.'`'.$fld_name.'`)'.self::HasAlias($alias);
	 }

	final public static function CroppedImageUrl($dir, $width, $height, $fld_name = 'ext', $alias = 'image_src', $tbl_name = null)// check it!!!
	 {
		if($tbl_name) $tbl_name = '`'.$tbl_name.'`.';
		return 'CONCAT_WS("", "/crop/w'.$width.'/h'.$height.'", CONCAT("/tx", `top_x`, "/ty", `top_y`, "/bx", `bottom_x`, "/by", `bottom_y`), "'.$dir.'/image_", '.$tbl_name.'`id`, ".", '.$tbl_name.'`'.$fld_name.'`)'.self::HasAlias($alias);
	 }

	final public static function FormatInt($fld_name, $alias = null)
	 {
		return 'REPLACE(FORMAT('.$fld_name.', 0), ",", " ")'.self::HasAlias($alias, $fld_name);
	 }

	final protected static function HasAlias($alias, $field_name = null) { return $alias ? ' AS `'.(true === $alias ? $field_name : $alias).'`' : ''; }

	final protected static function OrderByPositionAndDateTime($tbl_name = null)
	 {
		if($tbl_name) $tbl_name = '`'.$tbl_name.'`.';
		return $tbl_name.'`position` ASC, '.$tbl_name.'`date_time` DESC, '.$tbl_name.'`id` DESC';
	 }

	final private static function GetHost($host) { return true === $host ? Page::GetStaticHost() : $host; }

	final private function __construct() {}
}
?>
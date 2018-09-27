<?php
abstract class ColConf
{
	final public static function LinkToFiles(MSDBColData $col, $rel_name, $file_rel_name = null, $title = 'Файлы', $ext_key = null, $key = 'id')
	 {
		if(!$file_rel_name) $file_rel_name = $rel_name.'_file';
		return self::LinkTo($col, $rel_name, $file_rel_name, 'files/?pid={id}', $title, $ext_key, $key);
	 }

	final public static function LinkToImages(MSDBColData $col, $rel_name, $file_rel_name = null, $title = 'Фото', $ext_key = null, $key = 'id')
	 {
		if(!$file_rel_name) $file_rel_name = $rel_name.'_image';
		return self::LinkTo($col, $rel_name, $file_rel_name, 'images/?pid={id}', $title, $ext_key, $key);
	 }

	final public static function HasImage(MSDBColData $col) { return $col->SetExpression('IF(`ext` IS NULL, "<i>нет</i>", "да")')->SetClass('has_image'); }

	final public static function CheckBox(MSDBColData $col, $field_name, $input_name = null, $value_field_name = '`id`')
	 {
		if(!$input_name) $input_name = $field_name;
		return $col->SetExpression(SQLExpr::CheckBox($field_name, $input_name, null, $value_field_name))->SetClass('ch_box')->SetClick('MSDBTable.CheckBoxClick');
	 }

	final public static function LinkTo(MSDBColData $col, $rel_name, $slave_rel_name, $url, $title, $ext_key = null, $key = 'id')
	 {
		if(!$ext_key) $ext_key = 'parent_id';
		return $col->SetExpression("CONCAT('$title (', (SELECT COUNT(*) FROM `$slave_rel_name` AS `__slave` WHERE `__slave`.`$ext_key` = `$rel_name`.`$key`), ')')")->SetClick("'$url'")->SetClass('link');
	 }

	final public static function Href(MSDBColData $col, $url, $title, $rel_name = null, $slave_rel_name = null, $ext_key = 'parent_id')
	 {
		if($rel_name) $col->SetExpression('(SELECT CONCAT("<a class=\"cell_wr\" href=\"'.$url.'/?id=", `'.$rel_name.'`.`id`, "\">", `'.$rel_name.'`.`'.$title.'` , "</a>") FROM `'.$rel_name.'` WHERE `'.$rel_name.'`.`id` = `'.$slave_rel_name.'`.`'.$ext_key.'`)')->SetClass('link wr');
		else return $col->SetExpression('"'.$title.'"')->SetClick('"'.$url.'"')->SetClass('link');
	 }

	final private function __construct() {}
}
?>
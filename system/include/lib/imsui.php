<?php
interface IMSUI
{
	public static function Button();
	public static function Submit();
	// public static function Reset();
	public static function Date(...$args);
	public static function Text(...$args);
	public static function Textarea();
	public static function Number();
	public static function Password();
	public static function FileInput($name, $id = null);
	public static function ImageInput($name, $id = null);
	public static function FilesInput($name, $id = null);// <input type="file" multiple="multiple" />
	public static function Progbar($percent, $precision = 2);
	public static function Form();
	public static function FormRow($label, $input, $rc = '', $lc = '');
	public static function DeleteBlock($class = '');
	public static function SuccessMsg($msg);
	public static function ErrorMsg($msg);
	public static function WarningMsg($msg);
	public static function InfoPopup($text);
	public static function PhoneHref($phone_num, $class = '');
	public static function FAction($value);
	public static function FRedirect($url = true, $params = '');
	public static function Search();
	public static function Year($start, $end, $key = false);
	public static function PNav(MSPageNav $nav);
	public static function PNavBtn(stdClass $b);
	public static function FGroupBtn(stdClass $b);
}
?>
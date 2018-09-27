<?php
abstract class MSCfg extends MSConfig
{
	final public static function IsDebug() { return null === self::$debug_mode ? function_exists('MSSMAI') && MSSMAI()->GetSUID() : (bool)self::$debug_mode; }
	final public static function SetDebug($state) { self::$debug_mode = $state; }
	final public static function GetOption($name) { return @self::$options[$name]; }
	final public static function OptionExists($name) { return isset(self::$options[$name]); }
	final public static function SetOption($name, $value) { self::$options[$name] = $value; }
	final public static function SetOptions(array $o) { foreach($o as $name => $value) self::SetOption($name, $value); }

	final public static function SetSunderOptions(array &$o)
	 {
		$o['on_invalid_fragment'] = function($exml, $layout_name, $src, $caller){
			$error = ['type' => 0, 'message' => "Layout: $layout_name
Source: $src
Caller: ".implode('', $caller)."

LibXMLError
level: $exml->level
code: $exml->code
column: $exml->column
line: $exml->line
file: $exml->file
message: $exml->message"];
			MSConfig::HandleError($error);
		};
		$o['debug'] = self::IsDebug();
		$o['html_root'] = self::GetOption('html_root');
		Sunder::RegisterFormats([
			'AsDate' => function($value){return (new FormatDate())->Apply($value);},
			'AsDateMD' => function($value){return Format::AsDateMD($value);},
			'AsDateTime' => function($value){return Format::AsDateTime($value);},
			'AsInt' => function($value){return Format::AsInt($value);},
			'AsPhoneHref' => function($value){return Format::AsPhoneHref($value);},
			'AsPhoneNum' => function($value){return Format::AsPhoneNum($value);},
			'AsFloat' => function($value){return Format::AsFloat($value);},
		]);
	 }

	private static $options;
	private static $debug_mode = null;
}
?>
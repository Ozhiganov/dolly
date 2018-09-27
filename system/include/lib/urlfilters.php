<?php
interface IURLFilter
{
	function __invoke($val);
	function GetLang();
	function GetHTMLPattern();
	function GetInfo();
}

class URLFilterDefault implements IURLFilter
{
	public function __invoke($val) { return Filter::GetValidURLPart($val); }
	public function GetLang() { return 'en'; }
	public function GetHTMLPattern() { return '^[a-z0-9-]+$'; }
	public function GetInfo() { return 'Только латинские буквы, цифры, дефис, без пробелов.'; }
}

class URLFilterRU implements IURLFilter
{
	public function __invoke($val) { if('' !== $val && preg_match('/^[0-9a-zа-яё\-\/]+$/iu', $val)) return $val; }
	public function GetLang() { return 'en,ru'; }
	public function GetHTMLPattern() { return '^[0-9a-zA-Zа-яёА-ЯЁ\-\/]+$'; }
	public function GetInfo() { return 'Латинские и русские буквы, цифры, дефис, без пробелов.'; }
}
?>
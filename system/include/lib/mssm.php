<?php
$doc_title = self::GetDocTitle();
$caption = self::GetCaption();
if($nav = $this->GetNav())
 {
	if($nav_doc_title = $nav->GetTitle()) $doc_title = $doc_title ? array_merge($nav_doc_title, $doc_title) : (array)$nav_doc_title;
	if($nav_caption = $nav->GetCaption()) $caption = $caption ? array_merge((array)$nav_caption, $caption) : (array)$nav_caption;
 }
if(empty($caption)) $caption = $bread_crumbs = '';
else
 {
	if(1 == count($caption))
	 {
		$caption = reset($caption);
		$bread_crumbs = '';
	 }
	else
	 {
		$tmp = array_pop($caption);
		$bread_crumbs = implode(self::CAPTION_DIVIDER, $caption);
		$caption = $tmp;
	 }
 }
?><!DOCTYPE html>
<html lang="<?=self::GetLang()?>">
<head>
<meta name="viewport" content="<?=self::GetViewport()?>" />
<link rel="shortcut icon" href="/system/img/ui/system.ico" />
<meta http-equiv="content-type" content="text/html; charset=<?=self::GetCharset()?>" />
<?=self::GetCSSLinksAsHTML().self::GetJSLinksAsHTML()?>
<meta name="Robots" content="noindex, nofollow" />
<title><?=implode(self::TITLE_DIVIDER, array_reverse(array_map('a::MapArray', $doc_title)))?></title>
<script type="text/javascript">/* <![CDATA[ */<?php
self::RequireFiles('js');
print(self::$js_code);
?>/* ]]> */</script>
<style type="text/css">/* <![CDATA[ */<?php
self::RequireFiles('css');
print(self::$css_code);
?>/* ]]> */</style>
</head>
<body><?php
require_once(MSConfig::GetLibDir().'/mssm_body'.(self::Discarded() ? '_nowr' : '').'.php');
?></body></html>
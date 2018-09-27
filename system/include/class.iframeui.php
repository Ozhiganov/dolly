<?php
class IframeUI
{
	final public function Show(array $data) { echo $this->Make($data); }

	final public function Make(array $data)
	 {
		$l = l10n();
		MSConfig::RequireFile('traits', 'datacontainer');
		$o = new OptionsGroup($data, [
					'body_content' => ['type' => 'string', 'value' => ''],
					'css' => ['set' => true, 'type' => 'array', 'value' => []],
					'document_title' => ['type' => 'string'],
					'js' => ['set' => true, 'type' => 'array', 'value' => []],
					'js_inline' => ['type' => 'string', 'value' => ''],
					'js_links' => ['set' => true, 'type' => 'array', 'value' => []],
					'status_type' => ['type' => 'string', 'value' => ''],
					'status_msg' => ['type' => 'string', 'value' => ''],
					'toolbar_content' => ['type' => 'string', 'value' => ''],
				]);
		array_unshift($o->js_links, IConst::JQUERY);
		array_unshift($o->js, 'iframeui');
		array_unshift($o->css, 'iframeui');
		$st = $o->status_type ? " data-status='$o->status_type'" : '';
		$html_head = '';
		foreach($o->js_links as $src) $html_head .= "<script type='text/javascript' src='$src'></script>";
		$f = [];
		foreach($o->js as $src)
		 if(empty($f[$src]))
		  {
			$html_head .= "<script type='text/javascript' src='/dolly_templates/js/$src.js'></script>";
			$f[$src] = true;
		 }
		$f = [];
		foreach($o->css as $src)
		 if(empty($f[$src]))
		  {
			$html_head .= "<link rel='stylesheet' href='/dolly_templates/css/$src.css' type='text/css' media='all' />";
			$f[$src] = true;
		 }
		if($src = trim($o->js_inline)) $html_head .= "<script type='text/javascript'>/* <![CDATA[ */ $src /* ]]> */</script>";
		$action = isset($_GET['__dolly_action']) ? $_GET['__dolly_action'] : '';
		$links_0 = $this->MakeLinksList($this->GetLinks($src, $s), ['selected' => function($k) use($action, $s){return $s.$action === $k;}]);
		$links_1 = '';//$this->MakeLinksList(['/' => 'Главная страница', '/feedback/?p1=x&p2=yy&xxxz=abcd#test-test-test' => 'Обратная связь', '#' => 'File 3'], ['class' => '_pages', 'target' => 'page_container', 'selected' => $src]);
		return "<!DOCTYPE html>
<html lang='{$l->GetLang()}' prefix='og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# article: http://ogp.me/ns/article#'>
<head>
<meta charset='utf-8' />
<title>$o->document_title</title>
$html_head
</head>
<body data-action='$action'>
<div class='toolbar _top'>
	<div class='toolbar__row _1'><a href='/admin.php?action=logout' class='toolbar__action_2 _logout' title='$l->logout'>$l->logout</a><a href='$src' class='toolbar__action_2 _close' title='$l->close'>$l->close</a>$links_0$links_1</div>
	<div class='toolbar__row _2'>$o->toolbar_content</div>
	<div class='status_msg'$st>$o->status_msg</div>
</div>
<div class='page_container'><iframe name='page_container' src='$src'></iframe></div>
$o->body_content
</body>
</html>";
	}

	final public static function MakeLinksList(array $links, array $options = null)
	 {
		$o = new OptionsGroup($options, [
						'base' => ['type' => 'string', 'value' => ''],
						'class' => ['set' => true, 'type' => 'string', 'value' => '', 'proxy' => new DataContainerElements(['glue' => ' ', 'before' => ' '])],
						'prefix' => ['type' => 'string', 'value' => ''],
						'target' => ['set' => true, 'type' => 'string', 'value' => '', 'proxy' => new DataContainerElements(['glue' => ' ', 'before' => ' target="', 'after' => '"'])],
						'selected' => ['set' => true, 'value' => false],
					]);
		$cmp = false;
		if($o->selected)
		 {
			if(is_string($o->selected)) $cmp = function($k, $o) { return $o->selected === $k; };
			else $cmp = $o->selected;
		 }
		$c = $o->prefix.'links_list';
		$nav = '';
		foreach($links as $k => $v)
		 {
			$s = ($cmp && call_user_func($cmp, $k, $o)) ? ' data-state="selected"' : '';
			$nav .= "<a class='{$c}__item' href='$o->base$k'$o->target$s>$v</a>";
		 }
		return "<div class='$c$o->class'><button type='button' class='{$c}__toggle'></button>$nav</div>";
	 }

	final public static function GetLinks(&$src = null, &$s = null)
	 {
		$src = '/';
		$url = parse_url($_SERVER['REQUEST_URI']);
		if(!empty($url['path'])) $src = $url['path'];// check url!!!
		$s = "$src?__dolly_action=";
		$links = [$s.'editor' => l10n()->wysiwyg_editor, $s.'forms' => l10n()->forms_constructor];
		foreach(Controllers::GetAdminMenu() as $key => $title) $links["/admin.php?action=admin_$key"] = $title;
		return $links;
	 }
}
?>
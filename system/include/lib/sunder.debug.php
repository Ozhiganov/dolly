<?php
namespace Sunder\Debug;

class LayoutTagGroup
{
	final public function __construct(array $data, array $o = null)
	 {
		$this->data = $data;
		$this->o = $o;
	 }

	final public function __get($name)
	 {
		if('count' === $name)
		 {
			if(null === $this->count)
			 {
				if(empty($this->o['unique_count']))
				 {
					$this->count = 0;
					foreach($this->data as $n) $this->count += $n;
				 }
				else $this->count = count($this->data);
			 }
			return $this->count;
		 }
		throw new \Exception('Undefined property: '. __CLASS__ .'::$'.$name);
	 }

	final public function __set($name, $value)
	 {
		throw new \Exception('Object of '.__CLASS__.' is read only!');
	 }

	final public function __toString()
	 {
		return $this->data ? implode(', ', array_keys($this->data)) : '';
	 }

	private $data;
	private $count = null;
	private $o;
}

class LayoutTagGroups
{
	final public function __construct(array $data)
	 {
		foreach($data as $name => $value) $this->data[$name] = 'total' === $name ? $value : new LayoutTagGroup($value, ['unique_count' => 'names' === $name]);
	 }

	final public function __get($name)
	 {
		if(isset($this->data[$name])) return $this->data[$name];
		throw new \Exception('Undefined property: '. __CLASS__ .'::$'.$name);
	 }

	final public function __set($name, $value)
	 {
		throw new \Exception('Object of '.__CLASS__.' is read only!');
	 }

	private $data = [];
}

abstract class Statistics
{
	final public static function CountLayoutTag($name, $status, \MSLayout $parent = null, \MSLayout $layout = null)
	 {
		if(true === $status) self::CountName(self::$layout_tags['replaced'], $name);
		elseif(false === $status) self::CountName(self::$layout_tags['empty'], $name);
		elseif(1 === $status) self::CountName(self::$layout_tags['default'], $name);
		elseif(null === $status) self::CountName(self::$layout_tags['removed'], $name);
		else throw new \Exception('Invalid status: '.var_export($status, true).'!');
		++self::$layout_tags['total'];
		self::CountName(self::$layout_tags['names'], $name);
	 }

	final public static function GetLayoutTagGroups()
	 {
		if(null === self::$layout_tag_groups) self::$layout_tag_groups = new LayoutTagGroups(self::$layout_tags);
		return self::$layout_tag_groups;
	 }

	final public static function GetUnusedMSLayout(array $layouts) { return array_diff_key($layouts, self::$layout_tags['names']); }

	final public static function WalkDocument(\DOMDocument $doc)
	 {
		$result = new \stdClass();
		$result->tags = [];
		$result->depth = [];
		foreach($doc->documentElement->getElementsByTagName('body')->item(0)->childNodes as $n) self::WalkNode($n, $result, 1);
		ksort($result->tags, SORT_STRING);
		$result->tags_total = 0;
		foreach($result->tags as $tag_name => $count) $result->tags_total += $count;
		return $result;
	 }

	final private static function WalkNode(\DOMNode $node, \stdClass $result, $depth)
	 {
		if(XML_ELEMENT_NODE === $node->nodeType)
		 {
			self::CountName($result->tags, $node->tagName);
		 }
		elseif(XML_TEXT_NODE === $node->nodeType)
		 {
			return;
		 }
		elseif(XML_COMMENT_NODE === $node->nodeType)
		 {
			return;
		 }
		else ;
		self::CountName($result->depth, $depth);
		if($node->hasChildNodes()) foreach($node->childNodes as $n) self::WalkNode($n, $result, $depth + 1);
		else ;
		// XML_ELEMENT_NODE				1	Node is a DOMElement
		// XML_ATTRIBUTE_NODE			2	Node is a DOMAttr
		// XML_TEXT_NODE				3	Node is a DOMText
		// XML_CDATA_SECTION_NODE		4	Node is a DOMCharacterData
		// XML_ENTITY_REF_NODE			5	Node is a DOMEntityReference
		// XML_ENTITY_NODE				6	Node is a DOMEntity
		// XML_PI_NODE					7	Node is a DOMProcessingInstruction
		// XML_COMMENT_NODE				8	Node is a DOMComment
		// XML_DOCUMENT_NODE			9	Node is a DOMDocument
		// XML_DOCUMENT_TYPE_NODE		10	Node is a DOMDocumentType
		// XML_DOCUMENT_FRAG_NODE		11	Node is a DOMDocumentFragment
		// XML_NOTATION_NODE			12	Node is a DOMNotation
		// XML_HTML_DOCUMENT_NODE		13	 
		// XML_DTD_NODE					14	 
		// XML_ELEMENT_DECL_NODE		15	 
		// XML_ATTRIBUTE_DECL_NODE		16	 
		// XML_ENTITY_DECL_NODE			17	 
		// XML_NAMESPACE_DECL_NODE		18
	 }

	final private static function CountName(array &$data, $name)
	 {
		if(isset($data[$name])) ++$data[$name];
		else $data[$name] = 1;
	 }

	private static $layout_tags = [
			'total' => 0,
			'replaced' => [],
			'empty' => [],
			'default' => [],
			'removed' => [],
			'names' => [],
		];
	private static $layout_tag_groups = null;
}

function AnchorLayout(\DOMNode $layout)
{
	return \n::Before($layout, $layout->ownerDocument->createTextNode(PHP_EOL));
}

function MarkLayout(\DOMNode $anchor = null, \DOMNode $layout, \MSLayout $l)
{
	\n::AddComment($anchor ?: $layout, " {$l->GetInfo()} ", $anchor ? 'after' : 'before', ['eol_before' => 1]);
	\n::AddComment($layout, " [{$layout->getAttribute('name')}] ", 'after', ['eol_before' => 1, 'eol_after' => 2]);
}

function MarkFragment(\DOMDocumentFragment $fragment, \DOMNode $layout, $counter)
{
	\n::AddComment($fragment, " $counter - {$layout->getAttribute('name')} ", 'first', ['eol_before' => 1]);
	\n::AddComment($fragment, " [$counter] ");
}

class Console
{
	final public static function ShowInfo(\DOMDocument $doc, array $layouts)
	 {
		$c = new \Sunder\Debug\Console($doc);
		$classes = [];
		foreach($layouts as $layout)
		 {
			$info = $layout->GetInfo();
			if(isset($classes[$info->class])) ++$classes[$info->class];
			else $classes[$info->class] = 1;
		 }
		$c->AddMsg('Всего объектов MSLayout: '.count($layouts));
		foreach($classes as $class => $count) $c->AddMsg("$count - $class");
		$tg = \Sunder\Debug\Statistics::GetLayoutTagGroups();
		$c->AddMsg('Всего тэгов &lt;'.\Sunder::LAYOUT_NODE_NAME."&gt;: $tg->total (уникальных имён: {$tg->names->count}).");
		$c->AddMsg($tg->replaced->count." - заменено данными.");
		$c->AddMsg($tg->default->count.' - заменено умолчанием, нет объекта MSLayout. '.$tg->default);
		$c->AddMsg($tg->empty->count.' - удалено, пустой набор данных. '.$tg->empty);
		$c->AddMsg($tg->removed->count.' - удалено, нет объекта MSLayout. '.$tg->removed);
		$c->AddMsg(($unused = \Sunder\Debug\Statistics::GetUnusedMSLayout($layouts)) ? 'Неиспользованные объекты MSLayout ('.count($unused).'): '.implode(', ', array_keys($unused)).'.' : 'Неиспользованных объектов MSLayout нет.');
		$c->AddHdr('Создание объектов MSLayout');
		$i = 0;
		foreach($layouts as $layout) $c->AddMsg(++$i.'. '.$layout->GetInfo());
		$c->AddHdr('Статистика DOM-узлов документа (тэга &lt;body&gt;)');
		$stats = \Sunder\Debug\Statistics::WalkDocument($doc);
		$c->AddMsg("Всего тэгов: $stats->tags_total");
		foreach($stats->tags as $tag_name => $count) $c->AddMsg("<code>$tag_name</code>: $count (".\ms::Percent($count, $stats->tags_total, 4)."%)");
		$c->AddMsg('Распределение по глубине.');
		$html = '';
		$max = max($stats->depth);
		foreach($stats->depth as $depth => $count)
		 {
			$w = 100;
			if($count != $max) $w = round($count / $max * 100, 2);
			$html .= "<div class='__mssunder_console_depth__level' style='width:$w%'><span class='__mssunder_console_depth__label _number'>#$depth</span><span class='__mssunder_console_depth__label _count'>$count</span></div>";
		 }
		$c->AddMsg("<div class='__mssunder_console_depth'>$html</div>");
		$c->Show();
	 }

	final public static function IsEnabled()
	 {
		$gn = '__mssunder_show_console';
		$cn = 'MSSunder:show_console';
		if(isset($_GET[$gn]))
		 {
			setcookie($cn, empty($_GET[$gn]) ? '0' : '1', 0, '/');
			return !empty($_GET[$gn]);
		 }
		else return !empty($_COOKIE[$cn]);
	 }

	final public function __construct(\DOMDocument $dom_doc)
	 {
		$this->dom_doc = $dom_doc;
		$this->dom_console = $this->CreateDiv(null, ['class' => '__mssunder_console', 'data-hidden' => 'false']);
		$this->CreateEl('a', 'console', ['class' => '__mssunder_console__toggle_state _close', 'href' => '?__mssunder_show_console=0']);
		$this->CreateEl('button', 'console', ['class' => '__mssunder_console__toggle_state _hidden', 'id' => '__mssunder_console__toggle_visibility']);
		$this->dom_inner = $this->CreateDiv('console', ['class' => '__mssunder_console__inner']);
	 }

	final public function Show()
	 {
		if($this->hidden)
		 {
			$body = $this->dom_doc->documentElement->getElementsByTagName('body')->item(0);
			$this->CreateEl('link', $body, ['rel' => 'stylesheet', 'href' => \Page::GetStaticHost().'/css/s_debug.css', 'type' => 'text/css', 'media' => 'all']);
			$body->appendChild($this->dom_console);
			$this->CreateEl('script', $body, ['src' => \Page::GetStaticHost().'/js/s_debug.js', 'type' => 'text/javascript']);
			$this->hidden = false;
		 }
		return $this;
	 }

	final public function AddHdr($text_xml) { return $this->AddMsg($text_xml, 'header'); }

	final public function AddMsg($text_xml, $type = false)
	 {
		$attrs = ['class' => '__mssunder_console__message'];
		if($type) $attrs['data-type'] = $type;
		$msg = $this->CreateDiv('inner', $attrs);
		$f = $this->dom_doc->createDocumentFragment();
		$f->appendXML($text_xml);
		$msg->appendChild($f);
	 }

	final protected function CreateEl($tag, $dest = null, array $attrs = null)
	 {
		$el = $this->dom_doc->createElement($tag);
		if($attrs) foreach($attrs as $a => $v) if(null !== $v) $el->setAttribute($a, $v);
		if($dest)
		 {
			if('console' === $dest) $dest = $this->dom_console;
			elseif('inner' === $dest) $dest = $this->dom_inner;
			$dest->appendChild($el);
		 }
		return $el;
	 }

	final protected function CreateDiv($dest, array $attrs = null) { return $this->CreateEl('div', $dest, $attrs); }

	private $dom_doc;
	private $dom_console;
	private $dom_inner;
	private $hidden = true;
}

function ExceptionHandler(\Exception $e)
{
	if(($e instanceof \ESunderInvalidXMLFragment) && ('On' == ini_get('display_errors')))
	 {
		$error = $e->GetXMLError();
		switch($error->level)
		 {
			case LIBXML_ERR_WARNING: $level = 'warning'; break;
			case LIBXML_ERR_ERROR: $level = 'error'; break;
			case LIBXML_ERR_FATAL: $level = 'fatal error'; break;
			default: $level = '';
		 }
		$show_trace = false;
		$l_name = $e->GetLayoutName();
		if(0 === $l_name) $l_name = '';
		elseif(1 === $l_name)
		 {
			$l_name = 'Sunder::Replace';
			$show_trace = true;
		 }
		else $l_name = "Layout: $l_name";
?><!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Sunder exception</title>
<link rel="stylesheet" type="text/css" href="<?=\IConst::MSAPIS?>/css/msse.exception-1.1.css" />
</head>
<body>
<div id="body">
<div class="sunder_e_msg">Sunder: LibXML <?=$level?> (code: <?=$error->code?>)</div>
<div class="sunder_e_msg"><?=$error->message?></div>
<div class="sunder_e_msg"><?=$e->GetXMLSrc()?> on line <?=$error->line?>, symbol <?=$error->column?></div>
<div class="sunder_e_msg"><?=$l_name?></div>
<ol class="sunder_code"><?php
		foreach(array_map(function($v){return trim($v, "\r");}, explode("\n", htmlspecialchars($e->GetXMLCode()))) as $num => $str) print("<li><div class='sunder_code__line'>$str</div></li>");
?></ol><?php
		if($show_trace)
		 {
			$c = $e->GetCaller();
			$t = $e->getTrace();
			do
			 {
				if($t[0]['class'] === $c['class'] && $t[0]['type'] === $c['type'] && $t[0]['function'] === $c['function']) break;
				array_shift($t);
			 }
			while($t);
			if($t)
			 {
?><div class="sunder_trace"><?php
				foreach($t as $key => $item)
				 {
					$caller = '';
					foreach(['class', 'type', 'function'] as $k) if(isset($item[$k])) $caller .= $item[$k];
?><div class="sunder_trace__item">
	<div class="sunder_trace__num">#<?=$key?></div>
	<div><?=$caller.(empty($item['file']) ? '' : " <div class='sunder_trace__file'>$item[file] on line $item[line]</div>")?></div>
</div><?php
				 }
?></div><?php
			 }
		 }
?></div></body></html><?php
	 }
	else throw $e;
}
?>
<?php
namespace Sunder\Processor;

function Init(...$names)
{
	foreach($names as $name)
	 {
		if(is_array($name))
		 {
			$args = $name;
			$name = array_shift($args);
			\Sunder::AddAfterBuild("\Sunder\Processor\\$name", ...$args);
		 }
		else \Sunder::AddAfterBuild("\Sunder\Processor\\$name");
	 }
}

function CreatePreview(\DOMDocument $doc)
{
	$images = (new \DOMXPath($doc))->query('//img[@data-preview]');
	if($images->length)
	 {
		// Page::RequireScript();
		foreach($images as $img)
		 {
			$t = explode('/', $img->getAttribute('data-preview'));
			$src = $img->getAttribute('src');
			$new_src = preg_replace('/^((https?:)?\/\/[a-z0-9-.]+)\/(.+)$/', "$1/$t[1]/w$t[2]/h$t[3]/$3", $src, 1);//сделать возможность задавать адрес превью явно
			switch($t[0])
			 {
				case 1:
					n::Wrap($img, $doc, 'a', ['href' => $src, 'class' => 'adaptive_preview _1']);
					$new_w = $t[2];// только если адрес превью генерируется автоматически, а не берётся из атрибута!!!
					$new_h = $t[3];// впрочем, есть вариант, что размеры для произвольного превью тоже можно задавать
					break;
			 }
			$img->setAttribute('src', $new_src);
			if($new_w) $img->setAttribute('width', $new_w);
			else $img->removeAttribute('width');
			if($new_h) $img->setAttribute('height', $new_h);
			else $img->removeAttribute('height');
			$img->removeAttribute('data-preview');
		 }
	 }
}

function ResolveHRefs(\DOMDocument $doc, $attr_id, $tbl_name, \Engine $engine)
{
	$nodes = (new \DOMXPath($doc))->query("//a[@$attr_id]");
	if($nodes->length)
	 {
		$ids = [];
		foreach($nodes as $n) if(($id = $n->getAttribute($attr_id)) && is_numeric($id)) $ids[$id] = $id;
		if($ids)
		 {
			$res = \DB::Select($tbl_name, '`id`, `sid`, `title`', '`id` IN ('.implode(', ', $ids).')');
			if(count($res))
			 {
				$links = [];
				foreach($res as $page)
				 if(empty($links[$page->id]))
				  {
					$engine->AddHref($page);
					$links[$page->id] = $page;
				  }
				foreach($nodes as $n)
				 {
					$id = $n->getAttribute($attr_id);
					if(isset($links[$id])) $n->setAttribute('href', $links[$id]->href.($n->hasAttribute('href') ? $n->getAttribute('href') : ''));
					$n->removeAttribute($attr_id);
				 }
			 }
		 }
	 }
}
?>
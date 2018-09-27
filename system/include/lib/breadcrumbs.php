<?php
class BreadCrumbsPart
{
	final public function __construct($href, $has_url, $title, $sid)
	 {
		$this->crumbs = array(array('href' => $href, 'has_url' => $has_url, 'title' => $title, 'sid' => $sid));
	 }

	final public function Push(BreadCrumbsPart $crumbs)
	 {
		$this->crumbs = array_merge($this->crumbs, $crumbs->GetRaw());
		return $this;
	 }

	final public function Unshift(BreadCrumbsPart $crumbs)
	 {
		$this->crumbs = array_merge($crumbs->GetRaw(), $this->crumbs);
		return $this;
	 }

	final public function AsString($plain = false)
	 {
		if(null === $this->cache)
		 {
			$this->cache = $this->cache_plain = $href = '';
			foreach($this->crumbs as $i => $crumb)
			 {
				$href .= $crumb['href'];
				$this->cache .= ($i ? self::DIVIDER : '').($crumb['has_url'] ? '<a href="'.($crumb['sid'] ? "/$crumb[sid]/" : Href::AddSlash($href)).'">'.$crumb['title'].'</a>' : $crumb['title']);
				$this->cache_plain .= ($i ? self::DIVIDER : '').$crumb['title'];
			 }
		 }
		return $plain ? $this->cache_plain : $this->cache;
	 }

	final protected function GetRaw() { return $this->crumbs; }

	const DIVIDER = ' &bull; ';

	private $crumbs = array();
	private $cache = null;
	private $cache_plain = null;
}

class Breadcrumbs
{
	final public function __construct(array $items, $div = ' / ', $css_class = false)
	 {
		$this->items = $items;
		$this->div = $div;
		$this->class_attr = $css_class ? " class='$css_class'" : '';
	 }

	final public function __toString()
	 {
		$ret_val = '';
		foreach($this->items as $href => $title) $ret_val .= ($ret_val ? $this->div : '')."<a href='$href'{$this->class_attr}>$title</a>";
		return $ret_val;
	 }

	private $items, $div, $class_attr;
}

class PlainBreadCrumbs
{
	use TOptions;

	final public function __construct($tbl_name, array $options = null)
	 {
		$this->tbl_name = $tbl_name;
		$this->AddOptionsMeta(['cols' => ['type' => 'string', 'value' => '`id`, `parent_id`, `title`'], 'db' => ['value' => null]]);
		$this->SetOptionsData($options);
		$opt = $this->GetOption('db');
		if(null === $opt || false === $opt) $this->db = DB();
		elseif(!DB::InstanceExists($opt, $this->db)) $this->db = DB::CloneConnection($opt);
	 }

	final public function Make(stdClass $row) { return $row->parent_id ? $this->MakeImp($row)->cached_links[$row->parent_id]['crumbs']->AsString(true) : ''; }
	final public function Add(stdClass $row) { $row->crumbs = $this->Make($row); }

	final private function MakeImp(stdClass $row)
	 {
		if(empty($this->cached_links[$row->id]))
		 {
			if($row->parent_id)
			 {
				$cols = $this->GetOption('cols');
				$r = $row;
				$chain = [];
				do
				 {
					if(isset($this->cached_links[$r->id]))
					 {
						$chain[0]['crumbs']->Unshift($this->cached_links[$r->id]['crumbs']);
						break;
					 }
					else array_unshift($chain, ['id' => $r->id, 'title' => $r->title, 'crumbs' => new BreadCrumbsPart('', false, $r->title, '')]);
				 }
				while($r->parent_id && ($r = $this->db->GetRowById($this->tbl_name, $r->parent_id, $cols)));
				$len = count($chain);
				for($i = 0; $i < $len; ++$i)
				 {
					if($i) $chain[$i]['crumbs']->Unshift($chain[$i - 1]['crumbs']);
					$this->cached_links[$chain[$i]['id']] = ['title' => $chain[$i]['title'], 'crumbs' => $chain[$i]['crumbs']];
				 }
			 }
			else $this->cached_links[$row->id] = ['title' => $row->title, 'crumbs' => new BreadCrumbsPart('', false, $row->title, '')];
		 }
		return $this;
	 }

	private $tbl_name;
	private $db = null;
	private $cached_links = [];
}
?>
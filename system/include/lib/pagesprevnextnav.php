<?php
class PagesPrevNextNav
{
	final public function __construct(\stdClass $row, array $btns, $tbl_name, $cnd, array $prm = null, $order_by)
	 {
		if(self::$instanced) throw new \Exception('Can not create another instance of class `'.get_class($this).'`');
		self::$instanced = true;
		$this->row = $row;
		$this->btns = $btns;
		$this->tbl_name = $tbl_name;
		$this->cnd = $cnd;
		$this->prm = $prm;
		$this->order_by = $order_by;
	 }

	final public function Make()
	 {
		$cnd = '`parent_id` '.($this->row->parent_id ? '= :__page_pid' : 'IS NULL');
		if($this->cnd) $cnd = "($cnd) AND ($this->cnd)";
		$prm = $this->prm ?: [];
		if($this->row->parent_id) $prm['__page_pid'] = $this->row->parent_id;
		$rows = \DB::GetPrevNextRows($this->tbl_name, 'position', $this->row->id, '`id`, `parent_id`, `title`', $cnd, $prm, $this->order_by);
		$ret_val = '';
		if(!empty($rows['prev'])) $ret_val .= $this->MakeBlock($rows['prev'], 'prev');
		if(!empty($rows['next'])) $ret_val .= $this->MakeBlock($rows['next'], 'next');
		if($ret_val)
		 {
			\ResourceManager::AddCSS('lib.nav');
			return "<div class='pages_prev_next'>$ret_val<div class='clear'></div></div>";
		 }
	 }

	final private function MakeBlock(\stdClass $page, $class)
	 {
		$links = '';
		$dir = \MSConfig::GetMSSMDir();
		$section = \MSLoader::GetId();
		$curr_link = null;
		foreach($this->btns as $name => $b)
		 if($data = call_user_func($b, $page))
		  {
			if($section === $name) $curr_link = $data;
			$links .= $this->MakeLink($data, $dir, false);
		  }
		if(null === $curr_link) $curr_link = $data;
		$curr_link['title'] = '' === $page->title ? $page->id : $page->title;
		return "<div class='pages_prev_next__block _$class'><div class='pages_prev_next__base'><div class='pages_prev_next__group'><div class='pages_prev_next__shadow _top'>$links</div></div></div><div class='pages_prev_next__crop'>{$this->MakeLink($curr_link, $dir, true)}</div></div>";
	 }

	final private function MakeLink(array $link, $dir, $main)
	 {
		$c = 'pages_prev_next__button _'.($main ? 'main' : 'other');
		if(isset($link['href'])) return "<a class='$c' href='$dir$link[href]'>$link[title]</a>";
		else return "<span class='$c _no_href'>$link[title]</span>";
	 }

	private $row;
	private $btns;
	private $tbl_name;
	private $cnd;
	private $prm;
	private $order_by;

	private static $instanced = false;
}
?>
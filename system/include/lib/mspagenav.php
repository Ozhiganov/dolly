<?php
require_once(dirname(__FILE__).'/traits.php');

class MSPageNav
{
	use TOptions;

	final public function __construct($count, $length, array $options = [])
	 {
		$this->length = $length;
		$this->AddOptionsMeta(['GET' => [], 'on_invalid_num' => [], 'tpl' => [], 'prefix' => [], 'no_wr' => [], 'prev' => [], 'next' => []]);
		$this->SetOptionsData(array_merge(['next' => '&rarr;', 'prev' => '&larr;'], $options));
		if($count)
		 {
			$this->num_of_pages = ceil($count / $length);
			$this->page_num = (int)Filter::NumFromGET($this->GetOption('GET') ?: 'page') ?: 1;
			if($this->IsOutOfBounds())
			 {
				if($func = $this->GetOption('on_invalid_num')) call_user_func($func);
			 }
			$this->link_tpl = $this->GetOption('tpl') ?: 'page-{number}.html';
		 }
		else $this->num_of_pages = 0;
		if(!$this->IsVisible()) $this->mk_btn_method = 'MakeEmptyBtn';
	 }

	final public function FetchBtn() { return $this->{$this->mk_btn_method}(); }
	final public function IsVisible() { return $this->num_of_pages > 1; }
	final public function IsOutOfBounds() { return $this->page_num < 1 || $this->page_num > $this->num_of_pages; }
	final public function GetLimit() { if($this->num_of_pages) return (($this->page_num - 1) * $this->length).', '.$this->length; }

	final public function Show()
	 {
		if($this->IsVisible())
		 {
			$lname = $this->GetOption('prefix').'page_nav';
			if($this->GetOption('no_wr')) new Layout($lname, [$this, 'FetchBtn'], '/page_nav_btn');
			else
			 {
				new SLayout($lname, null, '/page_nav');
				new Layout("$lname:button", [$this, 'FetchBtn'], null);
			 }
		 }
		return $this;
	 }

	final private function NextBtn()
	 {
		if($this->num_of_pages < 2 || $this->curr_page > $this->num_of_pages) return null;
		$ret_val = (object)array('title' => $this->curr_page, 'this_page' => $this->curr_page == $this->page_num, 'href' => $this->MakePageNavLink($this->curr_page));
		++$this->curr_page;
		return $ret_val;
	 }

	final private function MakeFirstBtn()
	 {
		$ret_val = $this->page_num == 1 ? $this->MakeBtn() : $this->MakePageNavItem($this->GetOption('prev'), 1 == $this->page_num, 'prev', $this->MakePageNavLink($this->page_num - 1));
		$this->mk_btn_method = 'MakeBtn';
		return $ret_val;
	 }

	final private function MakeLastBtn()
	 {
		if($this->page_num == $this->num_of_pages) return $this->MakeEmptyBtn();
		$ret_val = $this->MakePageNavItem($this->GetOption('next'), $this->num_of_pages == $this->page_num, 'next', $this->MakePageNavLink($this->page_num + 1));
		$this->mk_btn_method = 'MakeEmptyBtn';
		return $ret_val;
	 }

	final private function MakeBtn()
	 {
		$ret_val = $this->MakePageNavItem($this->curr_page, $this->curr_page == $this->page_num, null, $this->MakePageNavLink($this->curr_page));
		if($this->curr_page++ == $this->num_of_pages) $this->mk_btn_method = 'MakeLastBtn';
		return $ret_val;
	 }

	final private function MakeEmptyBtn()
	 {
		$this->curr_page = 1;
		$this->mk_btn_method = 'MakeFirstBtn';
		return null;
	 }

	final private function MakePageNavItem($title, $this_page, $type, $href) { return (object)array('title' => $title, 'this_page' => $this_page, 'type' => $type, 'href' => $href); }

	final private function MakePageNavLink($num) { return str_replace('{number}', $num, $this->link_tpl); }

	private $length;
	private $link_tpl;
	private $mk_btn_method = 'MakeFirstBtn';
	private $num_of_pages;
	private $page_num;
	private $curr_page = 1;
}
?>
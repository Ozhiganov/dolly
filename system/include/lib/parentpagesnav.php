<?php
class ParentPagesNav implements \IMSNav
{
	final public function __construct(\stdClass $page, array $buttons, $tbl_name)
	 {
		$this->page = $page;
		$this->buttons = $buttons;
		$this->tbl_name = $tbl_name;
	 }

	final public function GetCaption()
	 {
		if(null === $this->caption) $this->Make();
		return $this->caption;
	 }

	final public function GetTitle()
	 {
		if(null === $this->title) $this->Make();
		return $this->title;
	 }

	final public function GetItem() { return $this->page; }

	final public function Make()
	 {
		if(null === $this->caption)
		 {
			$this->caption = $this->title = [];
			\ResourceManager::AddCSS('lib.nav');
			$dir = \MSConfig::GetMSSMDir();
			$page = $this->page;
			do
			 {
				$title = $caption = ('' === $page->title ? $page->id : $page->title);
				$html = '';
				foreach($this->buttons as $name => $b) if($data = call_user_func($b, $page)) $html .= "<a href='$dir$data[href]'>$data[title]</a>";
				$caption = "<a href='$dir/pages/?page_id=$page->id'>$caption</a>";
				array_unshift($this->caption, $this->caption ? "<span class='header_nav_item'>$caption<span class='header_nav_item__links'>$html</span></span>" : $caption);
				array_unshift($this->title, $title);
			 }
			while($page->parent_id && ($page = $this->GetParent($page)));
			$page->_parent = null;
		 }
		return $this->page;
	 }

	final private function GetParent(\stdClass $page)
	 {
		$page->_parent = \DB::GetRowById($this->tbl_name, $page->parent_id);
		return $page->_parent;
	 }

	private $caption = null;
	private $title = null;
	private $page;
	private $buttons;
	private $tbl_name;
}
?>
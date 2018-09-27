<?php
trait TPagesComplexNav
{
	public function ConfigNav(stdClass &$page = null)
	 {
		if($page = $this->GetCurrentPage())
		 {
			\MSConfig::RequireFile('pagesprevnextnav', 'parentpagesnav');
			$btns = $this->GetPageButtons();
			$this->ConfigSideNav($page, $btns);
			$nav = new \ParentPagesNav($page, $btns, $this->GetPageTblName());
			$this->SetNav($nav);
			$nav->Make();
			list($cnd, $prm) = $this->GetPageWhere($page);
			$this->prev_next_nav = new \PagesPrevNextNav($page, $btns, $this->GetPageTblName(), $cnd, $prm, $this->GetPageOrderBy($page));
			$this->ResetTitle();
		 }
	 }

	public function GetCurrentPage()
	 {
		if(null === $this->current_page)
		 {
			if($id = ($this->current_page_id ?: \Filter::NumFromGET('page_id')))
			 {
				if($page = \DB::GetRowById($this->GetPageTblName(), $id))
				 {
					return ($this->current_page = $page);
				 }
				else ;// do something?...
			 }
			$this->current_page = false;
		 }
		return $this->current_page;
	 }

	final public function SetCurrentPage(\stdClass $page)
	 {
		$this->current_page = $page;
		return $this;
	 }

	final public function SetCurrentPageId($id)
	 {
		$this->current_page_id = $id;
		return $this;
	 }

	abstract protected function GetPageButtons();
	abstract protected function GetPageTblName();
	abstract protected function GetPageOrderBy(\stdClass $page);
	abstract protected function GetPageWhere(stdClass $page);

	protected function AfterConfigSideNav(\stdClass $page, $menu_items_pid)
	 {
		\MainMenu::AddExternal('view-this-page', 'Перейти на сайт', \Engine()->MakeHref($page->sid), $menu_items_pid, ['type' => 'icon screen']);
		\MainMenu::GetItem($menu_items_pid)->Show();
		\ResourceManager::AddCSS('lib.main_menu');
	 }

	final protected function GetPrevNextNav() { return $this->prev_next_nav; }
	final protected function ShowPrevNextNav() { echo $this->prev_next_nav->Make(); }

	final private function ConfigSideNav(\stdClass $page, array $btns)
	 {
		\MainMenu::AddItemsFromButtons($page, $btns, $this->menu_items_pid);
		$this->AfterConfigSideNav($page, $this->menu_items_pid);
	 }

	protected $menu_items_pid = 'edit_page';

	private $current_page = null;
	private $current_page_id = null;
	private $prev_next_nav;
}
?>
<?php
abstract class MSBreadCrumbs
{
	final public function __construct($rel_name, $id, $divider, $first_with_link, $show_root_if_empty = false, $root_url = null)
	 {
		$this->rel_name = $rel_name;
		$this->divider = $divider;
		$this->first_with_link = $first_with_link;
		$this->root_url = $root_url;
		if($row = Relation::Get($this->rel_name)->GetAssocById($id))
		 {
			$this->first_item = $row;
			do
			 {
				$this->AddItemToList($row);
				$row = Relation::Get($this->rel_name)->GetAssocById($row['parent_id']);
			 }
			while($row);
			$this->level = count($this->items);
			foreach($this->items as $key => $item) $this->html .= ($this->html ? $this->divider : '').(!$this->first_with_link && $this->level - 1 == $key ? $this->MakeNoLinkItem($item, $key + 1) : $this->MakeItem($item, $key + 1));
			if($root = $this->GetRootLink()) $this->html = $root.$this->divider.$this->html;
		 }
		elseif($show_root_if_empty) $this->html = $this->GetRootLink();
	 }

	final public function GetFirstItem() { return $this->first_item; }
	final public function GetHTML() { return $this->html; }
	final public function GetLevel() { return $this->level; }
	final public function GetItems() { return $this->items; }
	final public function GetItemIds() { return $this->item_ids; }

	final protected function GetRootUrl() { return $this->root_url; }

	protected function MakeNoLinkItem($row, $level) { return $row['title']; }
	protected function GetRootLink() { return ''; }

	abstract protected function MakeItem($row, $level);

	final private function AddItemToList($item)
	 {
		array_unshift($this->items, $item);
		array_unshift($this->item_ids, $item['id']);
	 }

	private $rel_name;
	private $first_with_link;
	private $divider;
	private $first_item;
	private $root_url;
	private $html;
	private $level = 0;
	private $items = array();
	private $item_ids = array();
}
?>
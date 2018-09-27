<?php
MSConfig::RequireFile('tpagegroup');

// buttons = array('name' => function($row) { return ['href', 'title']; })
// actions = array('name' => ['href', 'title', 'hidden'])
// options:
// href_base, order_by, no_nodes_state, is_async
class PageTree
{
	use TPageGroup;

	public function __construct($tbl_name, $fields, array $options = null, array $buttons = [], array $actions = [])
	 {
		$this->AddOptionsMeta(['btn_delete' => ['type' => 'bool', 'value' => true], 'btn_delete_title' => ['type' => 'string', 'value' => 'Удалить страницу'], 'callback' => ['type' => 'callback,null'], 'dragndrop' => ['type' => 'bool', 'value' => true], 'msg_confirm' => ['type' => 'string', 'value' => ''], 'trash' => ['type' => 'bool', 'value' => false]]);
		$this->InitPageGroup($tbl_name, $fields, $options, $buttons, $actions);
		if(false !== $this->GetOption('btn_delete')) $this->AddBtn('delete', function($row){ return ['title' => $this->GetOption('btn_delete_title')]; });
	 }

	final public function Make(stdClass $parent = null, &$has_pages = null)
	 {
		if($this->GetOption('dragndrop')) ResourceManager::AddJS('lib.msdndmanager');
		ResourceManager::AddCSS('lib.pagetree', 'lib.pagetree_actions');
		ResourceManager::AddJS('lib.pagetree');
		$html = $this->GetLinksToPages($parent, 0, $this->IsAsync());
		$has_pages = (bool)$html;
		return $this->GetActionsPanel().$html;
	 }

	final public function GetBranch()
	 {
		if(($id = Filter::NumFromGET('id')) && ($page = DB::GetRowById($this->GetTblName(), $id)))
		 {
			$level = Filter::NumFromGET('level');
			if(null === $level) throw new EDocumentHandle('Неправильно указан уровень ветви страниц!');
			MSDocument::SendHTML($this->GetLinksToPages($page, $level + 1, true));
		 }
		else throw new EDocumentHandle('Неправильный идентификатор страницы!');
	 }

	final private function GetLinksToPages(stdClass $parent = null, $level, $load_only_one_level = false)
	 {
		$res = DB::Select($this->GetTblName(), $this->GetFields(), $this->GetCondition($parent ? $parent->id : false, $params), $params, $this->GetOrderBy($parent, $level, $order_by));
		if(count($res))
		 {
			$ret_val = '';
			foreach($res as $row)
			 {
				$attrs = " data-id='$row->id'";
				if('' === $row->title)
				 {
					$row->title = $row->id;
					$attrs .= ' data-title=""';
				 }
				if(!empty($row->hidden)) $attrs .= " data-hidden='$row->hidden'";
				if('' === $row->sid) $attrs .= ' data-sid=""';
				$pt_1 = "<li class='pagetree__leaf'$attrs><span class='pagetree__title'>";
				$pt_2 = "</span>{$this->MakeActionsBlock($row)}</li>";
				if($load_only_one_level)
				 {
					if(DB::Exists($this->GetTblName(), $this->GetCondition($row->id, $params), $params)) $pt_2 .= $this->MkNodeHTML('<ul class="pagetree__branch"></ul>');
				 }
				elseif($content = $this->GetLinksToPages($row, $level + 1)) $pt_2 .= $this->MkNodeHTML($content);
				if($c = $this->GetOption('callback')) call_user_func($c, $row);
				$ret_val .= "$pt_1$row->title$pt_2";
			 }
			$html = "<ul class='pagetree__".($level ? 'branch' : 'root')."' data-level='$level' data-order-by='$order_by'>$ret_val</ul>";
			return $level ? $html : "<div class='pagetree'{$this->MkRootNodeAttrs()}>$html</div>";
		 }
	 }

	final private function MkRootNodeAttrs()
	 {
		$s = '';
		foreach(['dragndrop', 'trash'] as $k) $s .= " data-$k='".($this->GetOption($k) ? 'true' : 'false')."'";
		foreach(['msg_confirm'] as $k) $s .= " data-$k='{$this->GetOption($k)}'";
		return $s;
	 }

	final private function MkNodeHTML($content) { return '<li class="pagetree__node"><input type="button" class="pagetree__node_state _expand" value="" />'.$content.'</li>'; }
}
?>
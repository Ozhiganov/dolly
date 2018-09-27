<?php
MSConfig::RequireFile('traits');

trait TPageGroup
{
	use TOptions;

	abstract public function Make();

	final public function AddBtn($name, $btn)
	 {
		if(isset($this->buttons[$name])) throw new Exception("Button with name `$name` already exists!");
		$this->buttons[$name] = $btn;
		return $this;
	 }

	final public function AddAct($name, $btn)
	 {
		if(isset($this->actions[$name])) throw new Exception("Button with name `$name` already exists!");
		$this->actions[$name] = $btn;
		return $this;
	 }

	final public function MakeActionsBlock(stdClass $row, array $o = null)
	 {
		$html = '';
		foreach($this->GetButtons() as $name => $b) $html .= $this->MkBtn($row->id, $name, call_user_func($b, $row));
		if(!$this->GetOption('no_check') && empty($o['no_check'])) $html .= "<label class='pagetree__action _check'><input type='checkbox' name='item[]' value='$row->id' /></label>";
		return "<span class='pagetree__actions'>$html</span>";
	 }

	final public function GetActionsPanel()
	 {
		$html = '';
		if(!$this->IsAsync() && !$this->GetOption('no_nodes_state')) $html .= '<input type="button" class="nodes_state _collapse_all" title="Свернуть все" value="" /><input type="button" class="nodes_state _expand_all" title="Раскрыть все" value=""'.($this->GetOption('expand_tree') ? ' data-default="true"' : '').' />';
		foreach($this->GetActions() as $name => $act) $html .= $this->MkActBtn($name, $act);
		if($html)
		 {
			ResourceManager::AddJS('lib.pagetree_panel', 'lib.pagetree_delete');
			return "<div class='pagetree_actions_wr'><div class='pagetree_actions'><div class='pagetree_actions__inner'><span class='pagetree_actions__n_sel _hidden'></span>$html</div></div></div>";
		 }
		else return '';
	 }

	final public function GetOrderBy(stdClass $parent = null, $level, &$order_by = null)
	 {
		if(null === $this->get_order_by)
		 {
			$order_by = $this->order_by[0];
			return $this->order_by[1];
		 }
		else
		 {
			$val = list($order_by, $expr) = call_user_func($this->get_order_by, $parent, $level, $this->order_by, $this);
			if(null === $order_by || null === $expr) throw new Exception('`order_by` callback must return an array with two non-empty values ('.gettype($val).' given)!');
			return $expr;
		 }
	 }

	final public function Output(stdClass $parent = null, &$has_pages = null) { echo $this->Make($parent, $has_pages); }
	final public function IsAsync() { return $this->is_async; }

	final protected function GetActions() { return $this->actions; }
	final protected function GetButtons() { return $this->buttons; }
	final protected function GetFields() { return $this->fields; }
	final protected function GetTblName() { return $this->tbl_name; }

	final protected function InitPageGroup($tbl_name, $fields, array $options = null, array $buttons = [], array $actions = [])
	 {
		$this->tbl_name = $tbl_name;
		$this->fields = $fields;
		$this->AddOptionsMeta(['order_by' => [], 'condition' => [], 'is_async' => [], 'no_check' => [], 'href_base' => [], 'no_nodes_state' => [], 'expand_tree' => []]);
		$this->SetOptionsData($options);
		$this->buttons = $buttons;
		$this->actions = $actions;
		$this->href_base = $this->GetOption('href_base');
		if($opt = $this->GetOption('order_by')) $this->get_order_by = is_callable($opt) ? $opt : function() use($opt){return $opt;};
		if($cnd = $this->GetOption('condition'))
		 {
			// if(is_callable($cnd));
			// else
			 // {
				$this->condition[0] .= " AND ($cnd)";
				$this->condition[1] .= " AND ($cnd)";
			 // }
		 }
		$opt = $this->GetOption('is_async');
		$this->is_async = is_int($opt) && $opt > 0 ? \DB::Count($this->tbl_name, $cnd) >= $opt : $opt;
	 }

	final protected function GetCondition($parent_id, &$params)
	 {
		if($parent_id)
		 {
			$params = ['parent_id' => $parent_id];
			return $this->condition[1];
		 }
		else
		 {
			$params = null;
			return $this->condition[0];
		 }
	 }

	final protected function MkActBtn($name, array $data = null)
	 {
		$class = "global_action _$name";
		if(!empty($data['hidden'])) $class .= ' _hidden';
		if(empty($data['href']))
		 {
			$i = html::Button('class', $class, 'title', $data['title'], 'value', '');
			if(!empty($data['data'])) foreach($data['data'] as $k => $v) $i->SetData($k, $v);
			return "$i";
		 }
		else return "<a href='{$this->href_base}$data[href]' class='$class' title='$data[title]'></a>";
	 }

	final protected function MkBtn($id, $name, array $data = null)
	 {
		if($data)
		 {
			$class = 'pagetree__action _'.(empty($data['class']) ? $name : $data['class']);
			$title = $data['title'];
			if(!empty($data['info'])) $title .= PHP_EOL.Filter::TextAttribute($data['info']);
			return empty($data['href']) ? "<input type='button' value='' class='$class' title='$data[title]' data-id='$id' />" : "<a href='{$this->href_base}$data[href]' class='$class' title='$data[title]'></a>";
		 }
		elseif(null === $data) return '';
		else return '<span class="pagetree__action _dummy"></span>';
	 }

	private $tbl_name;
	private $fields;
	private $is_async;
	private $buttons;
	private $actions;
	private $href_base;
	private $condition = ['(`parent_id` IS NULL)', '(`parent_id` = :parent_id)'];
	private $order_by = ['position', '`position` DESC, `id` ASC'];
	private $get_order_by = null;
}
?>
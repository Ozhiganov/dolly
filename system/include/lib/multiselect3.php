<?php
class MultiSelect3 extends DropDown
{
	final public function Make()
	 {
		if($opt = $this->GetOption('check_fld')) $this->check = is_string($opt) ? function(stdClass $row = null) use($opt) { return null !== $row && !empty($row->$opt); } : $opt;
		$inner_html = '';
		$this->prev_group = false;
		$data = $this->GetData();
		if(!($data['source'] instanceof Iterator) && is_callable($data['source'], false)) while($row = call_user_func($data['source'])) $inner_html .= $this->MakeOptionM($row, $data);
		elseif(null !== $data['f_value'] && null !== $data['f_title']) foreach($data['source'] as $row) $inner_html .= $this->MakeOptionM($row, $data);
		else foreach($data['source'] as $key => $value) $inner_html .= $this->MakeOption($key, $value);
		$collapse = $this->GetOption('collapse');
		if(null === $collapse) $collapse = true;
		$tag = html::div('id', $this->GetId(), 'class', 'multiselect3'.(($c = $this->GetClassName()) ? " $c" : ''))
					->SetData('enabled', $this->IsDisabled() || !$inner_html ? 'false' : 'true', 'collapse', $collapse ? ('uncheck' === $collapse ? $collapse : 'true') : 'false');
		// if($this->prev_group !== false) $inner_html .= '</optgroup>';
		if($inner_html && ($o = $this->GetDefaultOption())) $inner_html = $this->MakeOption($o['value'], $o['title'], $o['data']).$inner_html;
		if($this->GetOption('init')) $tag->SetData('init', 'auto');
		if($v = $this->GetOption('title')) $tag->SetAttr('title', $v);
		if($collapse)
		 {
			$sep = '&thinsp;/&thinsp;';
			return $tag->SetHTML("<input type='button' class='multiselect3__header msui_toggle2 _collapsed' value='$this->qty_selected$sep$this->qty_total' data-separator='$sep' />$inner_html");
		 }
		else return $tag->SetHTML($inner_html);
	 }

	final public function SetGroups($field, $groups)
	 {
		$this->group_field = $field;
		$this->groups = $groups;
		return $this;
	 }

	protected function OnCreate()
	 {
		$this->AddOptionsMeta(['check_fld' => [], 'collapse' => [], 'init' => []]);
		parent::OnCreate();
	 }

	// final private function MakeOptionMArr(array $row, array $meta)// - ???

	final private function MakeOptionM(stdClass $row, array $meta)
	 {
		$s = '';
		// if($this->groups)
		 // {
			// if($this->prev_group !== $row[$this->group_field])
			 // {
				// if($this->prev_group !== false) $s .= '</optgroup>';
				// $s .= "<optgroup label='{$this->groups[$row[$this->group_field]]}'>";
			 // }
			// $this->prev_group = $row[$this->group_field];
		 // }
		return $s.$this->MakeOption($row->{$meta['f_value']}, $row->{$meta['f_title']}, $meta['data_attrs'], $row);
	 }

	final private function MakeOption($value, $text, array $data = null, stdClass $row = null)
	 {
		++$this->qty_total;
		$fv = Filter::TextAttribute($value);
		$name = $this->GetName()."[$fv]";
		$chbox = html::CheckBox('name', $name, 'value', 1);
		$ihdn = html::Hidden('name', $name, 'value', 0);
		if(null === $this->check ? $this->IsSelected($value) : $this->check->__invoke($row))
		 {
			$chbox->SetAttr('checked', true);
			++$this->qty_selected;
			$c = ' _checked';
		 }
		else $c = '';
		if($this->IsDisabled()) $chbox->SetAttr('disabled', true);
		if($data)
		 {
			if(null === $row) $chbox->SetData($data);
			else foreach($data as $k => $v) $chbox->SetData($v, $row->$k);
		 }
		return "<label class='multiselect3__option$c'>$ihdn$chbox$text</label>";
	 }

	private $groups;
	private $group_field;
	private $prev_group = false;
	private $qty_total = 0;
	private $qty_selected = 0;
	private $check = null;
}
?>
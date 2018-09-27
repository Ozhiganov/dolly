<?php
class Select extends DropDown
{
	final public function Make()
	 {
		$tag = html::Select('id', $this->GetId(), 'name', $this->GetName(), 'class', $this->GetClassName(), 'multiple', $this->IsMultiple(), 'title', $this->GetOption('title'));
		if($this->size > 1) $tag->SetAttr('size', $this->size);
		$inner_html = '';
		$this->prev_group = false;
		$data = $this->GetData();
		if(!($data['source'] instanceof Iterator) && is_callable($data['source'], false)) while($row = call_user_func($data['source'])) $inner_html .= $this->MakeOptionM($row, $data);
		elseif(null !== $data['f_value'] && null !== $data['f_title']) foreach($data['source'] as $row) $inner_html .= $this->MakeOptionM($row, $data);
		else foreach($data['source'] as $key => $value) $inner_html .= $this->MakeOption($key, $value);
		if($this->IsDisabled() || !$inner_html) $tag->SetAttr('disabled', true);
		if($this->prev_group !== false) $inner_html .= '</optgroup>';
		if($inner_html && ($o = $this->GetDefaultOption())) $inner_html = $this->MakeOption($o['value'], $o['title'], $o['data']).$inner_html;
		return $tag->SetHTML($inner_html);
	 }

	final public function GetSize() { return $this->size; }
	final public function IsMultiple() { return $this->GetOption('multiple'); }

	final public function SetGroups($field, $groups)
	 {
		$this->group_field = $field;
		$this->groups = $groups;
		return $this;
	 }

	final public function SetSize($val)
	 {
		$this->size = $val;
		return $this;
	 }

	protected function OnCreate()
	 {
		$this->AddOptionsMeta(['multiple' => ['type' => 'bool', 'value' => false]]);
		parent::OnCreate();
	 }

	final private function MakeOption($value, $text, array $data = null)
	 {
		$attrs = '';
		if($this->IsSelected($value)) $attrs .= ' selected="selected"';
		if($data) foreach($data as $k => $v) $attrs .= " data-$k='$v'";
		return "<option value='".Filter::TextAttribute($value)."'$attrs>$text</option>";
	 }

/* 	final private function MakeOptionMArr(array $row, array $meta)
	 {
		$s = $a = '';
		if($meta['data_attrs']) foreach($meta['data_attrs'] as $k => $v) $a .= " data-$v='$row[$k]'";
		if($this->IsSelected($row[$meta['f_value']])) $a .= ' selected="selected"';
		if($this->groups)
		 {
			if($this->prev_group !== $row[$this->group_field])
			 {
				if($this->prev_group !== false) $s .= '</optgroup>';
				$s .= "<optgroup label='{$this->groups[$row[$this->group_field]]}'>";
			 }
			$this->prev_group = $row[$this->group_field];
		 }
		return $s.'<option value="'.Filter::TextAttribute($row[$meta['f_value']]).'"'.$a.'>'.$row[$meta['f_title']].'</option>';
	 } */

	final private function MakeOptionM(stdClass $row, array $meta)
	 {
		$s = $a = '';
		if($meta['data_attrs']) foreach($meta['data_attrs'] as $k => $v) $a .= " data-$v='{$row->$k}'";
		if($this->IsSelected($row->{$meta['f_value']})) $a .= ' selected="selected"';
		if($this->groups)
		 {
			if($this->prev_group !== $row->{$this->group_field})
			 {
				if($this->prev_group !== false) $s .= '</optgroup>';
				$s .= "<optgroup label='{$this->groups[$row->{$this->group_field}]}'>";
			 }
			$this->prev_group = $row->{$this->group_field};
		 }
		return $s.'<option value="'.Filter::TextAttribute($row->{$meta['f_value']}).'"'.$a.'>'.$row->{$meta['f_title']}.'</option>';
	 }

	private $size = 1;
	private $groups;
	private $group_field;
	private $prev_group = false;
}
?>
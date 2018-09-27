<?php
class DropDownList extends DropDown
{
	final public function Make()
	 {
		$inner_html = '';
		$index = 0;
		$first_option = null;
		$data = $this->GetData();
		if($data['method'])
		 {
			$func = $data['source'] ? array($data['source'], $data['method']) : $data['method'];
			while($row = call_user_func($func))
			 {
				$inner_html .= $this->MakeOption($row[$data['value_fld']], $row[$data['title_fld']]);
				if(!$index++) $first_option = array('value' => $row[$data['value_fld']], 'title' => $row[$data['title_fld']]);
			 }
		 }
		else
		 {
			if(null !== $data['value_fld'] && null !== $data['title_fld'])
			 foreach($data['source'] as $row)
			  {
				$inner_html .= $this->MakeOption($row[$data['value_fld']], $row[$data['title_fld']]);
				if(!$index++) $first_option = array('value' => $row[$data['value_fld']], 'title' => $row[$data['title_fld']]);
			  }
			else
			 foreach($data['source'] as $value => $title)
			  {
				$inner_html .= $this->MakeOption($value, $title);
				if(!$index++) $first_option = array('value' => $value, 'title' => $title);
			  }
		 }
		$disabled = $this->IsDisabled() || !$inner_html ? ' data-disabled="true"' : '';
		if($inner_html && ($o = $this->GetDefaultOption())) $inner_html = $this->MakeOption($o['value'], $o['title']).$inner_html;
		if($this->selected_option) $s = array('title' => $this->selected_option['title'], 'value' => $this->selected_option['value']);
		elseif($o = $this->GetDefaultOption()) $s = array('title' => $o['title'], 'value' => $o['value']);
		elseif($first_option) $s = array('title' => $first_option['title'], 'value' => $first_option['value']);
		else $s = array('title' => '&mdash;', 'value' => '');
		return "<div class='dropdown_list {$this->GetClassName()}'$disabled><span class='dropdown_list__value _image_dropdown'><span class='dropdown_list__value_text'>{$s['title']}</span></span><ul class='dropdown_list__list'>$inner_html</ul><input type='hidden' value='{$s['value']}'".($this->GetId() ? " id='{$this->GetId()}'" : '').($this->GetName() ? " name='{$this->GetName()}'" : '').' /></div>';
	 }

	final public function SetSelected($val)
	 {
		$this->selected = $val;
		return $this;
	 }

	final private function MakeOption($value, $title)
	 {
		$class = '';
		if($this->selected !== null && $value == $this->selected)
		 {
			$class = ' _st_selected';
			$this->selected_option = array('value' => $value, 'title' => $title);
		 }
		return '<li class="dropdown_list__item'.$class.'" data-value="'.Filter::TextAttribute($value).'">'.$title.'</li>';
	 }

	private $selected = null;
	private $selected_option;
	private $default_option;
}
?>
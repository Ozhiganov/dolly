<?php
class Radio extends DropDown
{
	final public function Make()
	 {
		$html = '';
		$index = 0;
		$data = $this->GetData();
		if(!($data['source'] instanceof Iterator) && is_callable($data['source'], false)) while($row = call_user_func($data['source'])) $html .= $this->MakeBlock($row[$data['value_fld']], $row[$data['title_fld']], $index++);
		elseif(null !== $data['f_value'] && null !== $data['f_title']) foreach($data['source'] as $row) $html .= $this->MakeBlock($row[$data['value_fld']], $row[$data['title_fld']], $index++);
		else foreach($data['source'] as $key => $value) $html .= $this->MakeBlock($key, $value, $index++);
		$class = $this->GetClassName();
		if($class) $class = " class='$class'";
		elseif(false !== $class) $class = ' class="form__radio_group"';
		else $class = '';
		if($id = $this->GetId()) $id = " id='$id'";
		return "<span$class$id>$html</span>";
	 }

	final private function MakeBlock($value, $text, $index)
	 {
		$tag = html::radio('name', $this->GetName(), 'value', $value);
		if($this->IsDisabled()) $tag->SetAttribute('disabled', true);
		$selected = $this->GetSelected();
		if(null === $selected)
		 {
			if(!$index) $tag->SetAttr('checked', true);
		 }
		elseif($value == $selected) $tag->SetAttr('checked', true);
		$index .= ' _'.(preg_match('/^[a-z0-9_-]+$/i', $value) ? $value : hash('crc32', $value));
		return "<label class='form__box_label _n$index'>$tag$text</label>";
	 }
}
?>
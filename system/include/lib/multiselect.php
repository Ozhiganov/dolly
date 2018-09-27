<?php
class iMultiSelect
{
	use TOptions;

	public function __construct(array $options)
	 {
		$this->AddOptionsMeta(['delete' => ['type' => 'bool', 'value' => true], 'id' => ['type' => 'string', 'value' => ''], 'name' => ['type' => 'string'], 'title' => ['type' => 'string,null', 'value' => 'Указать страницу...'], 'update' => ['type' => 'bool', 'value' => true], 'value' => []]);
		$this->SetOptionsData($options);
	 }

	public function Make()
	 {
		$html = '';
		if($opt = $this->GetOption('value'))
		 {
			foreach($opt as $v) $html .= "<span class='imultiselect__title' data-id='$v->id'>$v->title</span> ";
			$v = $v->id;
		 }
		else $v = null;
		$name = $this->GetOption('name');
		if($has_del = $this->GetOption('delete'))
		 {
			$cl = ' _to_delete';
			$del = \html::Hidden('name', $name.'[d][]', 'value', $v, 'disabled', true, 'class', 'imultiselect__hdel').\ui::DeleteBlock('imultiselect__delete');
		 }
		else $cl = $del = '';
		return "<div class='imultiselect$cl'>".(($has_upd = $this->GetOption('update')) ? \html::Hidden('name', $name.'[i][]', 'value', $v) : '').\html::Hidden('name', $name.($has_del || $has_upd ? '[v]' : '').'[]', 'value', $v, 'class', 'imultiselect__hval').\html::Button('class', 'imultiselect__show_tree show_simplepagetree _light', 'title', $this->GetOption('title'), 'value', '')."<div>$html</div>$del</div>";
	 }
}
?>
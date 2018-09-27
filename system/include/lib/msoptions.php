<?php
MSConfig::RequireFile('form', 'optformfields');

class MSOptionsForm extends MSFormFieldSet
{
	final public static function CheckField(\MSFieldSet\Field $f)
	 {
		if(!($f instanceof \MSFieldSet\IIgnoreValue))
		 {
			$n = array_filter(explode(':', $f->GetName(), 2), function($v){return '' !== $v;});
			if(2 === count($n)) return $n;
		 }
		return false;
	 }

	final public function GetTblName() { return 'registry_value'; }

	final public function Make($title = null)
	 {
		$this->DispatchEvent('before_make', false, ['target' => $this]);
		foreach($this->AsIFields() as $item)
		 {
			if($n = $this->CheckField($item)) $item->SetOption('value', Registry::GetValue($n[0], $n[1]));
		 }
		$content = $this->GetContent();
		$fs_data = $this->GetData();
		switch($fs_data->status_type)
		 {
			case 'success': if($fs_data->status_msg) MSDocument::AddSuccessMsg($fs_data->status_msg); break;
			case 'error': MSDocument::AddErrorMsg($fs_data->status_msg ?: 'Произошла ошибка!'); break;
		 }
		$mssm_dir = MSConfig::GetMSSMDir();
		$form = ui::Form('class', 'form msoptions'.(($opt = $this->GetOption('class')) ? " $opt" : ''), 'id', $this->GetId().'_form', 'action', $this->GetAction());
		if($this->OptionExists('autocomplete', $opt)) $form->SetAttr('autocomplete', $opt);
		if(false !== $title) $form->SetCaption($title ?: 'Редактирование настроек');
		$form->SetMiddle($content)->SetBottom(ui::FRedirect($this->GetRedirect() ?: true).ui::FAction('save').ui::Submit('value', 'Сохранить').$this->GetHiddenField());
		if($this->IsMultipart()) $form->SetAttr('enctype', 'multipart/form-data');
		return $form;
	 }

	final protected function Action(...$args)
	 {
		if(!empty($_POST['__redirect'])) $this->SetRedirect($_POST['__redirect']);
		$data = [];
		$i = 0;
		foreach($this->AsIFields() as $fld)
		 {
			if($n = $this->CheckField($fld)) $data[$fld->GetName()] = [$n[0], $n[1], $args[$i]];
			++$i;
		 }
		$this->DispatchEvent('before_update', false, ['data' => &$data, 'form' => $this], ['data' => ['set' => true, 'type' => 'array']]);
		foreach($data as $args) Registry::SetValue(...$args);
		$d = $this->DispatchEvent('after_update', false, ['data' => $data, 'form' => $this, 'status_msg' => 'Изменения сохранены.'], ['status_msg' => ['set' => true, 'type' => 'string']]);
		$this->SetMsg($d->status_msg);
	 }

	final protected function OnCreate()
	 {
		$this->RegisterEvents('before_update', 'after_update');
		parent::OnCreate();
	 }

	final protected function GetDefaultFType($name, array &$o) { return 'TextInput'; }

	protected $ns = ['MSOptionsForm', 'MSForm', 'MSFieldSet'];
}

class MSOptions extends MSDocument
{
	use TEvents;

	final public function __construct(array $options = [])
	 {
		$options['disable_auto_redirect'] = true;
		$this->form = new MSOptionsForm('msoptions', $options);
	 }

	final public function Add($group, $name, $title = null, array $o = [])
	 {
		$this->form->AddField("$group:$name", $title, $o);
		return $this;
	 }

	final public function OpenGroup($name, $title = null, $state = null)
	 {
		$this->form->OpenGroup($name, $title, $state);
		return $this;
	 }

	final public function CloseGroup()
	 {
		$this->form->CloseGroup();
		return $this;
	 }

	final public function Show()
	 {
		$this->DispatchEvent('on_show', false, []);
		print($this->form->Make());
	 }

	final public function Handle()
	 {
		MSFieldSet::Handle();
	 }

	final public function SetAction($val)
	 {
		$this->form->SetAction($val);
		return $this;
	 }

	final public function SetRedirect($val)
	 {
		$this->form->SetRedirect($val);
		return $this;
	 }

	private $form;
}
?>
<?php
interface IUIWizardStep
{
	public function Run(DataContainer $form, DataContainer $o);
	public function __invoke(DataContainer $form, DataContainer $o);
}

class UIWizardDataContainer extends DataContainer
{
	final public function AddBtn(array $data, array $o = null)
	 {
		if($o)
		 {
			$o = new \OptionsGroup($o, ['step' => ['type' => 'string,number', 'value' => ''], 'action' => ['type' => 'string', 'value' => '']]);
			if('' !== $o->step)
			 {
				if('' !== $o->action) throw new Exception();
				$data['name'] = '_step';
				if(is_numeric($o->step)) $data['value'] = $o->step;
				else
				 {
					$data['value'] = $this->step_number;
					if('next' === $o->step) ++$data['value'];
					elseif('this' !== $o->step) throw new Exception('Invalid value for `step`: '.var_export($o->step, true).'!');
				 }
			 }
			elseif('' !== $o->action)
			 {
				$data['name'] = '_action';
				$data['value'] = $o->action;
			 }
		 }
		$b = new \OptionsGroup($data, self::GetBtnMeta());
		$this->buttons[] = $b;
		return $b;
	 }

	final public function GetButtons() { return $this->buttons; }

	final public static function GetBtnMeta()
	 {
		return [
			'disabled' => ['type' => 'bool', 'value' => false, 'set' => true],
			'formaction' => ['type' => 'string', 'value' => '', 'set' => true],
			'formmethod' => ['type' => 'string', 'value' => '', 'set' => true],
			'name' => ['type' => 'string', 'value' => '', 'set' => true],
			'caption' => ['type' => 'string', 'value' => '', 'set' => true],
			'value' => ['type' => 'string,number', 'value' => '', 'set' => true],
		];
	 }

	private $buttons = [];
}

class UIWizard implements Countable
{
	public function __construct(IUIWizardStep ...$steps)
	 {
		$this->steps = $steps;
		$this->GetStep();
		$this->form_data = new UIWizardDataContainer($this->GetFormMeta());
	 }

	final public function GetStep(&$step = null)
	 {
		if(null === $this->n)
		 {
			$this->n = 0;
			if($n = \Filter::NumFromREQUEST('_step', 'gt0')) if($n < count($this->steps)) $this->n = (int)$n;
		 }
		$step = $this->steps[$this->n];
		return $this->n;
	 }

	final public function Run()
	 {
		if(isset($_POST['_step']))
		 {
			$method = 'post';
			$action = isset($_POST['_action']) ? $_POST['_action'] : '';
		 }
		else
		 {
			$method = 'get';
			$action = isset($_GET['_action']) ? $_GET['_action'] : '';
		 }
		$o = new DataContainer(['method' => ['type' => 'string', 'value' => $method], 'action' => ['type' => 'string', 'value' => $action]]);
		$n = $this->GetStep($step);
		for($i = 0; $i <= $n; ++$i) $this->steps[$i]->Run($this->form_data, $o);
		$step($this->form_data, $o);
		return $this->form_data;
	 }

	final public function count() { return count($this->steps); }

	final public function GetFormData() { return $this->form_data; }

	protected function GetFormMeta()
	 {
		return [
				'action' => ['type' => 'string', 'value' => 'core.php', 'set' => true],
				'method' => ['type' => 'string', 'value' => 'post', 'set' => true],
				'step_number' => ['type' => 'int', 'value' => $this->n],
				'master' => ['value' => $this],
				'b_submit' => ['type' => 'container', 'value' => new \OptionsGroup(['caption' => 'Далее'], UIWizardDataContainer::GetBtnMeta())],
				'b_back' => ['type' => 'container', 'value' => new \OptionsGroup(['caption' => 'Назад', 'name' => '_step', 'value' => $this->n - 1], UIWizardDataContainer::GetBtnMeta())],
			];
	 }

	private $steps;
	private $n = null;
	private $form_data;
}

class MSUIWizard extends UIWizard
{
	protected function GetFormMeta()
	 {
		$m = parent::GetFormMeta();
		$m['content'] = ['set' => true, 'value' => '', 'type' => 'string', 'proxy' => new DataContainerElements(['glue' => '', 'before' => '', 'after' => ''])];
		return $m;
	 }

	public function GetFormHTML()
	 {
		$form = $this->Run();
		$html = \html::Hidden('name', '_step', 'value', $form->step_number);
		if($form->step_number) $html .= self::Button2HTML($form->b_back);
		foreach($form->GetButtons() as $b) $html .= self::Button2HTML($b);
		$form->b_submit->name = '_step';
		$form->b_submit->value = $form->step_number + 1;
		$html .= self::Button2HTML($form->b_submit);
		return \ui::Form('class', 'form _wizard', 'method', $form->method, 'action', $form->action)->SetCaption('Шаг '.($form->step_number + 1).' из '.count($this))->SetMiddle($form->content)->SetBottom($html);
	 }

	final public static function Button2HTML(DataContainer $b)
	 {
		$a = " class='msui_button' type='submit'";
		foreach($b->__debugInfo() as $k => $f)
		 {
			if('caption' === $k) continue;
			if('string' === $f['type'])
			 {
				if('' !== $b->$k) $a .= " $k='{$b->$k}'";
			 }
			elseif('bool' === $f['type'])
			 {
				if($b->$k) $a .= " $k='$k'";
			 }
			elseif('string,number' === $f['type'])
			 {
				$s = (string)$b->$k;
				if('' !== $s) $a .= " $k='$s'";
			 }
			else throw new Exception('Invalid type!');
		 }
		return "<button$a>$b->caption</button>";
	 }
}
?>
<?php
class MSMessageFieldSet extends MSFieldSet
{
	protected function OnCreate()
	 {
		$this->AddOptionsMeta(['_sender_id' => [], 'filter' => ['type' => 'callback,null'], 'msg' => ['type' => 'string', 'value' => ''], 'subject' => ['type' => 'string', 'value' => ''], 'tpl' => ['type' => 'array,null', 'value' => null]]);
		parent::OnCreate();
	 }

	final protected function Action(...$args)
	 {
		// if($callback = $this->GetOption('on_action'))
		 // {
			// array_unshift($args, $this);
			// call_user_func_array($callback, $args);
		 // }
		$o_tpl = $this->GetOption('tpl');
		$strs = '';
		$index = 0;
		foreach($this->AsIFields() as $fld)
		 {
			// if($fld instanceof IFSFile)
			 // {
				// $upl = new StreamUploader($fld->GetInputName());
				// if($file = $upl->LoadFile()) foreach($this->tpls as $tpl) $tpl->AddFileContent($fld->GetName(), $file['data'], $file['name']);
			 // }
			// else
			if(!($fld instanceof \MSFieldSet\IIgnoreValue))
			 {
				$n = $fld->GetName();
				$v = [];
				if(isset($o_tpl[$n]))
				 {
					if(is_string($o_tpl[$n])) $v['label'] = $o_tpl[$n];
					else $v = $o_tpl[$n];
				 }
				if(!isset($v['label'])) $v['label'] = $fld->GetTitle();
				$t = new OptionsGroup($v, $this->t_opts);
				if('' !== $t->label) $strs .= $t->label.$t->lbl_sep;
				$strs .= htmlspecialchars(trim($args[$index])).$t->eol;
			 }
			++$index;
		 }
		(new MSNotifications($this->GetOption('_sender_id')))->Add('', $this->GetOption('subject') ?: 'Поступило сообщение', $strs, ['filter' => $this->GetOption('filter')]);
		if(!$this->HasMsg()) $this->SetMsg($this->GetOption('msg') ?: 'Ваше сообщение отправлено. Благодарим за обращение!');
	 }

	private $t_opts = ['label' => ['type' => 'string'], 'lbl_sep' => ['type' => 'string', 'value' => ': '], 'eol' => ['type' => 'string', 'value' => PHP_EOL]];
}
?>
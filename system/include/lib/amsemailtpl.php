<?php
require_once(dirname(__FILE__).'/traits.php');

abstract class AMSEmailTpl
{
	use TOptions, TCallbacks;

	abstract public function Run();

	protected function ProcessData(array &$data) { if($callback = $this->GetOption('process_data')) $this->CallbackThis1($callback, $data); }

	final public function __construct($id, $to = null, $from = null, array $options = null)
	 {
		$this->id = $id;
		$this->to = $to;
		$this->from = $from;
		$this->options = $options;
		$this->check_file = array($this, 'CheckFile');
		if($fields = $this->GetOption('disable_field_check')) $this->DisableFieldCheck($fields);
	 }

	final public function GetToType() { return $this->to ? (strpos($this->to, '@') === false ? 'field' : 'email') : 'template'; }
	final public function GetFromType() { return $this->from ? (strpos($this->from, '@') === false ? 'field' : 'email') : 'template'; }
	final public function GetTo() { return $this->to; }
	final public function GetFrom() { return $this->from; }
	final public function GetFS() { return $this->fs; }
	final public function AddFile($input_name, $src_file_name, $file_name) { return $this->AddFileContent($input_name, file_get_contents($src_file_name), $file_name); }

	final public function SetTo($val)
	 {
		$this->to = $val;
		return $this;
	 }

	final public function SetReplyTo($val)
	 {
		$this->options['reply_to'] = $val;
		return $this;
	 }

	final public function AddTo($val)
	 {
		$args = is_array($val) ? $val : func_get_args();
		foreach($args as $arg) $this->to .= ','.$arg;
		return $this;
	 }

	final public function EnableStorage()
	 {
		$this->enable_storage = true;
		return $this;
	 }


	final public function SetData(array $data)
	 {
		$this->data = $data;
		return $this;
	 }

	final public function SetSubject($val)
	 {
		$this->subject = $val;
		return $this;
	 }

	final public function DisableFieldCheck($fld)
	 {
		if(true === $fld) $this->enable_field_check = false;
		elseif(is_array($fld)) $this->skipped_flds = array_fill_keys($fld, true);
		else $this->skipped_flds = array_fill_keys(func_get_args(), true);
		return $this;
	 }

	final public function AllowEmptyTo()
	 {
		$this->allow_empty_to = true;
		return $this;
	 }

	final public function CatchEMailError()
	 {
		$this->catch_email_error = true;
		return $this;
	 }

	final public function AddFileContent($input_name, $data, $file_name)
	 {
		if(call_user_func($this->check_file, $input_name, $data, $file_name)) $this->files[$input_name] = ['data' => $data, 'name' => $file_name];
		return $this;
	 }

	final public function SetFS(MSEMailFieldSet $fs)
	 {
		$this->fs = $fs;
		return $this;
	 }

	final public function SetCheckFile($callback, $method = null)
	 {
		$this->check_file = $method ? [$callback, $method] : $callback;
		return $this;
	 }

	protected function CheckFile($input_name, $data, $file_name) { return true; }

	final protected function ReplaceData($name, $value, &$tpl)
	 {
		$num = null;
		$tpl = preg_replace('/<data\s+name="'.$name.'"\s*\/>/u', $value, $tpl, -1, $num);
		if(!$num && $this->enable_field_check && !isset($this->skipped_flds[$name])) $this->missing_flds[] = $name;
	 }

	final protected function ProcessTpl(array $data, &$text)
	 {
		$this->missing_flds = [];
		foreach($data as $key => $value) $this->ReplaceData($key, $value ?: '—', $text);
		switch(count($this->missing_flds))
		 {
			case 0: break;
			case 1: throw new Exception('Поле `'.$this->missing_flds[0].'` отсутствует в шаблоне письма `'.$this->GetId().'`.');
			default: throw new Exception('Поля `'.implode('`, `', $this->missing_flds).'` отсутствуют в шаблоне письма.');
		 }
	 }

	final protected function SendEmail(MSMail $mail, $to, $from, $subject, $text, array $data)
	 {
		if(strpos($to, '@') === false) $to = @$data[$to];
		if(!$to && !$this->GetOption('ignore_empty_email'))
		 {
			if($this->IsEmptyToAllowed()) continue;
			else throw new Exception("Не указан e-mail получателя в шаблоне письма `{$this->GetId()}`.");
		 }
		if($mail->SetTo($to)->SetFrom($from)->SetSubject($subject)->SetText($text)->Send());
		elseif($this->IsEMailErrorCatched()) throw new Exception('Произошла ошибка при отправке письма.');
	 }

	final protected function ProcessReplTo(MSMail $mail, array $data)
	 {
		if($reply_to = $this->GetOption('reply_to'))
		 {
			if(strpos($reply_to, '@') === false) $reply_to = @$data[$reply_to];
			$mail->SetReplyTo($reply_to);
		 }
		return (string)$reply_to;
	 }

	final protected function ProcessSubj()
	 {
		if(!($subject = $this->GetSubject()))// если не пустой, обрабатывать шаблон? например, подставлять имя сайта
		 {
			$idn = new idna_convert();
			$subject = 'Письмо от посетителя сайта «'.$idn->decode($_SERVER['HTTP_HOST']).'»';
		 }
		return $subject;
	 }

	final protected function SetTypeId($val)
	 {
		$this->type_id = $val;
		return $this;
	 }

	final protected function SetText($val)
	 {
		$this->text = $val;
		return $this;
	 }

	final protected function IsEMailErrorCatched() { return $this->catch_email_error; }
	final protected function IsEmptyToAllowed() { return $this->allow_empty_to; }
	final protected function GetId() { return $this->id; }
	final protected function GetTypeId() { return $this->type_id; }
	final protected function GetSubject() { return $this->subject; }
	final protected function GetText() { return $this->text; }
	final protected function GetData() { return $this->data; }
	final protected function GetFiles() { return $this->files; }
	final protected function UseStorage() { return $this->enable_storage; }

	private $id;
	private $to;
	private $type_id;
	private $subject;
	private $from;
	private $text;
	private $data;
	private $files = [];
	private $missing_flds;
	private $skipped_flds = [];
	private $enable_field_check = true;
	private $catch_email_error = false;
	private $allow_empty_to = false;
	private $fs;
	private $enable_storage = false;
	private $check_file;
}
?>
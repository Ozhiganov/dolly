<?php
require_once(MSSE_LIB_DIR.'/traits.php');
require_once(MSSE_LIB_DIR.'/events.php');
require_once(MSSE_LIB_DIR.'/fsfield.php');
require_once(MSSE_LIB_DIR.'/fscheck.php');

class EFieldSet extends Exception {}
	class EFSAction extends EFieldSet {}
	class EFSEmptyFieldName extends EFieldSet {}
	class EFSDuplicateFieldName extends EFieldSet {}
	class EFSDuplicateGroupName extends EFieldSet {}
	class EFSDuplicateId extends EFieldSet {}
	class EFSCheckFailed extends EFieldSet {}
		class ЕFSFieldOmitted extends EFSCheckFailed {}
	class EFSInvalidFieldID extends EFieldSet {}
	class EFSCheckIsSet extends EFieldSet {}
	class EFSForbiddenDataKey extends EFieldSet {}

interface IFieldSet extends Iterator
{
	public function AddField($name, $title = null, array $o = []);
	public function OpenGroup($name, $title = null, $state = null);
	public function CloseGroup();
}

abstract class AMSFieldSet implements IFieldSet
{
	abstract protected function OnCreate();
	abstract protected function OnError(Exception $e);
	abstract protected function Action(...$args);
	abstract protected function GetDefaultFType($name, array &$o);
	abstract protected function OnAddField($name, array &$o);
}

abstract class msfs_iterator implements Iterator
{
	final public function __construct(MSFieldSet $fs)
	 {
		$this->fs = $fs;
	 }

	final public function Key() { return $this->fs->Key(); }
	final public function Next() { $this->fs->Next(); }
	final public function Rewind() { $this->fs->Rewind(); }
	final public function Valid() { return $this->fs->Valid(); }

	protected $fs;
}

class msfs_field_iterator extends msfs_iterator
{
	final public function Current() { return $this->fs->CurrentField(); }
}

class msfs_field_data_iterator extends msfs_iterator
{
	final public function Current()
	 {
		if($f = $this->fs->CurrentField())
		 {
			$ret_val = ['title' => $f->GetTitle(), 'name' => $f->GetName(), 'input_name' => $f->GetInputName(), 'id' => $f->GetId(), 'type' => $f->GetType(), 'required' => false];
			if($check = $f->GetCheck()) $ret_val['required'] = $check->IsRequired();
			return $ret_val;
		 }
		else return $f;
	 }
}

class MSFieldPlainObject extends stdClass
{
	final public function __construct(\MSFieldSet\Field $f, $group)
	 {
		$msg = $f->GetErrMsg();
		$this->data = ['class' => $f->GetType(), 'group' => $group, 'id' => $f->GetId(), 'input' => $f->MakeInput(), 'input_name' => $f->GetInputName(), 'name' => $f->GetName(), 'title' => $f->GetTitle(), 'type' => $f->GetType(false), 'msg' => $msg, 'object' => $f, 'required' => false, 'state' => null === $msg ? null : 'error', 'value' => $f->GetInputValue()];
		if($check = $f->GetCheck()) $this->data['required'] = $check->IsRequired();
		if($data = $f->GetOption('__data'))
		 {
			foreach($data as $k => $d)
			 {
				if(array_key_exists($k, $this->data)) throw new EFSForbiddenDataKey("Ключ [$k] недопустим для опции `__data`!");
				$this->data[$k] = $d;
			 }
		 }
	 }

	final public function __set($name, $value)
	 {
		if(array_key_exists($name, $this->data)) throw new Exception('Property '. __CLASS__ ."::$$name is read only!");
		$this->$name = $value;
	 }

	final public function __get($name)
	 {
		if(array_key_exists($name, $this->data)) return $this->data[$name];
        throw new Exception('Undefined property: '. __CLASS__ ."::$$name");
	 }

	final public function __isset($name)
	 {
		return isset($this->data[$name]);
	 }

	final public function __debugInfo()
	 {
		$r = $this->data;
		$r['object'] = get_class($r['object']);
		return $r;
	 }

	private $data = [];
}

class FSVoidValue
{
	public function __toString() { return ''; }
}

class MSFieldSet extends AMSFieldSet
{
	use TOptions, TCallbacks, TEvents;

	final public function __construct($id, array $options = null)
	 {
		if(isset(self::$field_sets[$id])) throw new EFSDuplicateId('Набор полей с идентификатором `'.$id.'` уже существует.');
		self::$field_sets[$id] = $this;
		$this->id = $id;
		if(!isset($options['split_groups'])) $options['split_groups'] = true;
		$this->AddOptionsMeta(['action' => ['type' => 'callback,null'], 'disable_auto_redirect' => ['type' => 'bool', 'value' => false], 'field_omission' => ['type' => 'array', 'value' => []], 'field_omission_allowed' => ['type' => 'callback,bool', 'value' => false], 'log_exception' => ['type' => 'callback,bool,null', 'value' => true], 'redirect_base' => ['type' => 'string', 'value' => ''], 'show_e_msg' => ['type' => 'bool', 'value' => false], 'split_groups' => []]);
		$this->SetOptionsData($options);
		$this->RegisterEvents('before_run');
		$this->OnCreate();
		$this->fetch_field = $this->SplitGroups() ? 'FetchFieldSplit' : 'FetchFieldNoSplit';
		$this->default_e_msg = 'Произошла ошибка при отправке формы!';
	 }

	final public static function Get($id) { return self::$field_sets[$id]; }

	final public static function Handle()
	 {
		if(!empty($_REQUEST['__fs_id']) && isset(self::$field_sets[$_REQUEST['__fs_id']]))// модифицировать для обработки группы наборов полей (мультиформ)!
		 {
			$obj = self::$field_sets[$_REQUEST['__fs_id']];
			if(!empty($_REQUEST['__get_field_data']))
			 {
				$field = null;
				if($obj->FieldExists($_REQUEST['__get_field_data'], $field) && ($field instanceof \MSFieldSet\IFieldAsync)) $field->GetData();
				exit;
			 }
			try
			 {
				$obj->Run();
			 }
			catch(EFSCheckFailed $e)
			 {
				$obj->OnException($e);
			 }
			catch(EFSAction $e)
			 {
				$obj->SetError($e->GetMessage() ?: $obj->default_e_msg);
				$obj->OnException($e);
			 }
			catch(Exception $e)
			 {
				$callback = $obj->GetOption('log_exception');
				if(true === $callback) MSConfig::HandleException($e, false);
				elseif(null === $callback) throw $e;
				elseif(false !== $callback) call_user_func($callback, $e, $this);
				$obj->SetError(false === $obj->GetOption('show_e_msg') ? $obj->default_e_msg : get_class($e).", code: {$e->GetCode()}, file: {$e->GetFile()}, line: {$e->GetLine()}. ".$e->GetMessage());
				$obj->OnException($e);
			 }
		 }
	 }

	final public function Current()
	 {
		if(null === $this->current && ($f = current($this->fields))) $this->current = $this->Field2PlainObject($f);
		return $this->current;
	 }

	final public function Key() { return key($this->fields); }

	final public function Next()
	 {
		++$this->num_fetched;
		$this->current = null;
		next($this->fields);
	 }

	final public function Rewind()
	 {
		$this->num_fetched = 0;
		$this->current = null;
		reset($this->fields);
	 }

	final public function Valid() { return null !== key($this->fields); }

	final public function CurrentField() { return current($this->fields); }
	final public function GetId() { return $this->id; }
	final public function GetRedirect() { return $this->redirect; }
	final public function GetSessionId() { return '__msfs_'.$this->GetId(); }
	final public function GetHiddenField() { return '<input type="hidden" name="__fs_id" value="'.$this->id.'" />'; }
	final public function IsRunning() { return $this->running; }
	final public function Crashed() { return $this->crashed; }
	final public function Succeeded() { return 'success' === @$_SESSION[$this->GetSessionId()]['status']['type']; }
	final public function IsAsync() { return !empty($_POST['__disable_redirect']); }

	final public function GetData(stdClass $data = null, $prefix = '', $inc_fields = false)
	 {
		if(null === $data) $data = new stdClass;
		$data->{$prefix.'hidden_field'} = $this->GetHiddenField();
		$data->{$prefix.'fsid'} = $this->GetId();
		$data->{$prefix.'status_msg'} = $this->GetStatusMsg();
		$data->{$prefix.'status_type'} = $this->GetStatusType();
		if($inc_fields)
		 {
			$attrs = ['name', 'type', 'id', 'input_name', 'title', 'value', 'input', 'required', 'state', 'msg'];
			foreach($this as $name => $f)
			 {
				foreach($attrs as $a) $data->{$prefix.'fld_'.$name.'__'.$a} = $f->$a;
			 }
		 }
		return $data;
	 }

	final public function AddField($name, $title = null, array $o = [])
	 {
		$name = "$name";
		if('' === $name) throw new EFSEmptyFieldName();
		if(isset($this->fields[$name])) throw new EFSDuplicateFieldName();
		$this->OnAddField($name, $o);
		$type = empty($o['type']) ? $this->GetDefaultFType($name, $o) : $o['type'];
		if('\\' !== $type[0])
		 {
			foreach($this->ns as $ns)
			 {
				$c = "\\$ns\\$type";
				if(class_exists($c, false)) break;
			 }
		 }
		else $c = $type;
		if(isset($o['__before']))
		 {
			$before = $o['__before'];
			unset($o['__before']);
		 }
		else $before = false;
		$this->InitField(new $c($this, $name, $title, $o), $before);
		if(null !== $this->curr_group)
		 {
			$this->groups_opened[$name] = $this->curr_group;
			$this->curr_group = null;
		 }
		$this->last_added = $name;
		return $this->fields[$name];
	 }

	final public function GetField($name)
	 {
		if(isset($this->fields[$name])) return $this->fields[$name];
		else throw new EFSInvalidFieldID();
	 }

	final public function FieldExists($name, \MSFieldSet\Field &$field = null)
	 {
		if(isset($this->fields[$name]))
		 {
			$field = $this->fields[$name];
			return true;
		 }
		else
		 {
			$field = null;
			return false;
		 }
	 }

	final public function HasFields() { return !empty($this->fields); }

	final public function FieldOmissionAllowed($name)
	 {
		if(null !== $name && empty($this->fields[$name])) throw new EFSInvalidFieldID();
		if(null === $this->field_omission_allowed)
		 {
			$flag = $this->GetOption('field_omission_allowed');
			$flds = $this->GetOption('field_omission');
			if(is_callable($flag))
			 {
				$flag = $this->CreateCallbackArgs($flag, false);
				$this->field_omission_allowed = $flds ? function($name, $fs) use($flag, $flds){return isset($flds[$name]) ? $flds[$name] && $flag($name, $fs) : $flag($name, $fs);} : $flag;
			 }
			else
			 {
				$flag = (bool)$flag;
				$this->field_omission_allowed = $flds ? function($name) use($flag, $flds){return isset($flds[$name]) ? $flds[$name] : $flag;} : function() use($flag){return $flag;};
			 }
		 }
		return $this->field_omission_allowed->__invoke($name, $this);
	 }

	final public function FetchField()
	 {
		$this->current = null;
		return $this->{$this->fetch_field}();
	 }

	final public function AsIFields()
	 {
		if(!$this->field_iterator) $this->field_iterator = new msfs_field_iterator($this);
		return $this->field_iterator;
	 }

	final public function AsIFieldsData()
	 {
		if(!$this->field_data_iterator) $this->field_data_iterator = new msfs_field_data_iterator($this);
		return $this->field_data_iterator;
	 }

	final public function OpenGroup($name, $title = null, $state = null)
	 {
		settype($name, 'string');
		if(isset($this->groups[$name])) throw new EFSDuplicateGroupName();
		$this->groups[$name] = ['title' => $title, 'state' => $state];
		$this->curr_group = $name;
		return $this;
	 }

	final public function CloseGroup()
	 {
		$this->curr_group = false;
		return $this;
	 }

	final public function HasStatusMsg(&$msg = '')
	 {
		if(isset($_SESSION[$this->GetSessionId()]['status']['text']))
		 {
			$msg = $_SESSION[$this->GetSessionId()]['status']['text'];
			return true;
		 }
		else
		 {
			$msg = null;
			return false;
		 }
	 }

	final public function GetStatusMsg()
	 {
		if(isset($_SESSION[$this->GetSessionId()]['status']['text']))
		 {
			$ret_val = $_SESSION[$this->GetSessionId()]['status']['text'];
			unset($_SESSION[$this->GetSessionId()]['status']['text']);
			return $ret_val;
		 }
	 }

	final public function GetStatusType()
	 {
		if(isset($_SESSION[$this->GetSessionId()]['status']['type']))
		 {
			$ret_val = $_SESSION[$this->GetSessionId()]['status']['type'];
			unset($_SESSION[$this->GetSessionId()]['status']['type']);
			return $ret_val;
		 }
	 }

	final public function SetRedirect($on_success, $on_error = null)
	 {
		if($on_success) $this->redirect = $on_success;
		if($on_error) $this->redirect_on_error = $on_error;
		return $this;
	 }

	final public function SetMsg($text)
	 {
		$this->msg = $text;
		return $this;
	 }

	public function __debugInfo()
	 {
		return [];
	 }

	protected function OnCreate() {}
	protected function OnError(Exception $e) {}
	protected function Action(...$args) {}
	protected function OnAddField($name, array &$o) {}
	protected function GetDefaultFType($name, array &$o) { return 'TextInput'; }
	protected function SplitGroups() { return $this->GetOption('split_groups'); }

	final protected function GetGroup($name) { return $this->groups[$name]; }
	final protected function GetLastAddedField() { return $this->last_added; }
	final protected function HasMsg() { return null !== $this->msg; }
	final protected function GetRedirectBase() { return $this->GetOption('redirect_base') ?: $this->redirect_base; }

	final protected function Redirect($redirect = null)
	 {
		if($this->IsAsync())
		 {
			$data = ['__status' => $this->GetStatusType(), '__message' => $this->GetStatusMsg(), '__invalid' => []];
			if(null === $data['__status'] && $this->Crashed())
			 {
				foreach($this as $k => $f) if('error' === $f->state) $data['__invalid'][] = ['name' => $k, 'msg' => $f->msg];
			 }
			header('Content-Type: application/json');
			die(json_encode($data));
		 }
		elseif(!$this->GetOption('disable_auto_redirect')) ms::Redirect($this->GetRedirectBase().(null === $redirect ? $this->redirect : $redirect));
	 }

	final protected function ERedirect()
	 {
		$this->Redirect($this->redirect_on_error);
	 }

	final protected function SetRedirectBase($val)
	 {
		$this->redirect_base = $val;
		return $this;
	 }

	final protected function TriggerAction(array $fields)
	 {
		if($action = $this->GetOption('action'))
		 {
			array_unshift($fields, $this);
			call_user_func_array($action, $fields);
		 }
		else call_user_func_array([$this, 'Action'], $fields);
		$this->SetSuccess($this->msg);
		$this->Redirect();
	 }

	final private function Field2PlainObject(\MSFieldSet\Field $f)
	 {
		$group = null;
		if(isset($this->groups_opened[$f->GetName()])) $this->group = $this->groups_opened[$f->GetName()];
		if(null !== $this->group) $group = false === $this->group ? null : $this->group;
		return new MSFieldPlainObject($f, $group);
	 }

	final private function FetchFieldSplit()
	 {
		if($this->tmp_fetched)
		 {
			$ret_val = $this->tmp_fetched;
			$this->tmp_fetched = false;
			return $ret_val;
		 }
		if(list($k, $f) = each($this->fields))
		 {
			$ret_val = $this->Field2PlainObject($f);
			if(isset($this->groups_opened[$f->GetName()]) && $this->num_fetched)
			 {
				$this->tmp_fetched = $ret_val;
				$ret_val = false;
			 }
			++$this->num_fetched;
			return $ret_val;
		 }
		else return false;
	 }

	final private function FetchFieldNoSplit()
	 {
		if(list($k, $f) = each($this->fields))
		 {
			$ret_val = $this->Field2PlainObject($f);
			++$this->num_fetched;
			return $ret_val;
		 }
		else return false;
	 }

	final private function InitField(\MSFieldSet\Field $obj, $before)
	 {
		if($before)
		 {
			if(empty($this->fields[$before])) throw new EFSInvalidFieldID();
			$tmp = [];
			foreach($this->fields as $k => $v)
			 {
				if($k === $before) $tmp[$obj->GetName()] = $obj;
				$tmp[$k] = $v;
			 }
			$this->fields = $tmp;
		 }
		else $this->fields[$obj->GetName()] = $obj;
	 }

	final private function SetStatus($text, $type) { $_SESSION[$this->GetSessionId()]['status'] = ['type' => $type, 'text' => $text]; }
	final private function SetError($text) { $this->SetStatus($text, 'error'); }
	final private function SetSuccess($text) { $this->SetStatus($text, 'success'); }

	final private function OnException(Exception $e)
	 {
		$this->crashed = true;
		$this->OnError($e);
		$this->ERedirect();
	 }

	final private function Run()
	 {
		$this->running = true;
		$this->DispatchEvent('before_run', false, ['target' => $this]);
		$fields = [];
		$invalid = false;
		foreach($this->fields as $name => $field)
		 {
			try
			 {
				$fields[$name] = $field->GetValue();
			 }
			catch(EFSCheckFailed $e)
			 {
				$invalid = true;
			 }
		 }
		if($invalid) throw new EFSCheckFailed();
		$this->TriggerAction($fields);
	 }

	protected $ns = ['MSFieldSet'];

	private static $field_sets = [];

	private $fields = [];
	private $last_added = null;
	private $id;
	private $redirect_base = '';
	private $redirect;
	private $redirect_on_error;
	private $curr_group = null;
	private $group = null;
	private $groups = [];
	private $groups_opened = [];
	private $msg = null;
	private $tmp_fetched = false;
	private $num_fetched = 0;
	private $running = false;
	private $crashed = false;
	private $fetch_field;
	private $current = null;
	private $field_iterator;
	private $field_data_iterator;
	private $default_e_msg;
	private $field_omission_allowed = null;
}
?>
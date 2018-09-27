<?php
trait TSystemMessages
{
	final public static function AddErrorMsg($text, $name = '') { self::AddMessage($text, 'error', "$name"); }
	final public static function AddSuccessMsg($text, $name = '') { self::AddMessage($text, 'success', "$name"); }
	final public static function AddWarningMsg($text, $name = '') { self::AddMessage($text, 'warning', "$name"); }

	final public static function AddSuccessMsgStats(array $stats)
	 {
		if(array_filter($stats))
		 {
			$labels = ['ins' => 'добавлено', 'upd' => 'обновлено', 'del' => 'удалено'];
			$msg = '';
			foreach($stats as $key => $value) if($value) $msg .= ($msg ? ', ' : '')."$labels[$key]: $value";
			self::AddSuccessMsg("Изменения сохранены ($msg).");
		 }
	 }

	// $options
	// root tag: убирать совсем либо менять название: 'root' => false \ 'new_tag'
	final public static function ToXML($val, array $options = null)
	 {
		$tag_name = 'value';
		if(isset($options['root']))
		 {
			if($options['root']) $tag_name = $options['root'];
			elseif(false === $options['root']) $tag_name = false;
		 }
		MSConfig::RequireFile('xmlbuilder');
		return \XMLBuilder\Node::ToXML($val, $tag_name);
	 }

	final public static function SendTextXML($code, $status_text = '', $status = 'success')
	 {
		return self::SendXMLData(function($code, $root) { return (new \XMLBuilder\Tag('data'))->Append(new \XMLBuilder\XMLCode($code)); }, $code, $status_text, $status, __METHOD__);
	 }

	final public static function SendXML($data, $status_text = '', $status = 'success')
	 {
		return self::SendXMLData(function($data, $root) { return \XMLBuilder\Node::ToXML($data, 'data'); }, $data, $status_text, $status, __METHOD__);
	 }

	final private static function SendXMLData($callback, $data, $status_text, $status, $method)
	 {
		self::CheckStatus($status, $method);
		MSConfig::RequireFile('xmlbuilder');
		$root = new \XMLBuilder\Tag('root');
		$root->status = $status;
		if($status_text)
		 {
			$txt = new \XMLBuilder\Tag('status_text');
			$root->Append($txt);
			$txt->AppendText($status_text);
		 }
		self::EachMessage(function($msg, $class) use(&$root){
			$txt = new \XMLBuilder\Tag('message');
			$root->Append($txt);
			$txt->AppendText($msg['text']);
			$txt->type = $msg['type'];
			$txt->css_class = "$class _$msg[type]";
			if($msg['name']) $txt->name = $msg['name'];
		});
		if(null !== $data) $root->Append($callback($data, $root));
		header('Content-Type: text/xml; charset=utf-8');
		die("<?xml version='1.0'?>$root");
	 }

	final public static function SendJSON($data, $status_text = '', $status = 'success')
	 {
		self::CheckStatus($status, __METHOD__);
		$root = new stdClass;
		$root->status = $status;
		if($status_text) $root->status_text = $status_text;
		$root->messages = [];
		self::EachMessage(function($msg, $class) use(&$root){
			$msg['css_class'] = "$class _$msg[type]";
			array_unshift($root->messages, $msg);
		});
		$root->data = $data;
		header('Content-Type: application/json');
		die(json_encode($root));
	 }

	final public static function SendHTML($content)
	 {
		header('Content-Type: text/html; charset=utf-8');
		die($content);
	 }

	final public static function SendText($content)
	 {
		header('Content-Type: text/plain; charset=utf-8');
		die($content);
	 }

	final protected static function GetMessagesByName($name, $class = '', $has_btn = false) { return self::GetMessages($name, $class, $has_btn); }

	final private static function ClearMessages() { unset($_SESSION['__mssm_messages']); }

	final private static function CheckStatus(&$status, $method)
	 {
		static $st = ['success' => true, 'warning' => true, 'error' => true];
		if(true === $status) $status = 'success';
		elseif(false === $status) $status = 'error';
		if(!is_scalar($status) || (null !== $status && !isset($st[$status])))
		 {
			$t = gettype($status);
			if('string' === $t) $t .= ' ('.var_export($status, true).')';
			throw new InvalidArgumentException("$method expects parameter 3 to be boolean or string ('success', 'warning', 'error'), $t given.");
		 }
	 }

	final private static function AddMessage($text, $type, $name)
	 {
		if(!isset($_SESSION['__mssm_messages'])) $_SESSION['__mssm_messages'] = [];
		array_unshift($_SESSION['__mssm_messages'], ['text' => $text, 'type' => $type, 'name' => $name]);
	 }

	final private static function EachMessage($callback, $name = '', $class = '')
	 {
		if(!empty($_SESSION['__mssm_messages']) && is_array($_SESSION['__mssm_messages']))
		 {
			$c = 'status_msg';
			if('' !== $class) $c = '!' === $class[0] ? substr($class, 1) : "$class $c";
			if('' === $name)
			 {
				foreach($_SESSION['__mssm_messages'] as $key => $msg)
				 {
					call_user_func($callback, $msg, $c);
					unset($_SESSION['__mssm_messages'][$key]);
				 }
			 }
			else
			 {
				foreach($_SESSION['__mssm_messages'] as $key => $msg)
				 if($name === $msg['name'])
				  {
					call_user_func($callback, $msg, $c);
					unset($_SESSION['__mssm_messages'][$key]);
				  }
			 }
		 }
	 }

	final private static function GetMessages($name = '', $class = '', $has_btn = true)
	 {
		$ret_val = '';
		self::EachMessage(function($msg, $class) use(&$ret_val, $has_btn){
			if('' !== $msg['text'])
			 {
				$ret_val .= "<div class='$class _$msg[type]'>$msg[text]";
				if($has_btn) $ret_val .= "<input type='button' value='×' title='Закрыть сообщение' class='{$class}__close' />";
				$ret_val .= '</div>';
			 }
		}, $name, $class);
		return $ret_val;
	 }
}

class SystemMessageContainer extends StdClassProxy
{
	public function __construct($data, array $options = null)
	 {
		$this->options = new OptionsGroup(...OptionsGroup::CutOptions($options, ['root' => ['type' => 'string', 'value' => '']], true));
		if(is_string($data)) $data = json_decode($data, false);
		$all = $data;
		if($this->options->root)
		 {
			if(isset($data->{$this->options->root}))
			 {
				$data = $data->{$this->options->root};
				if(is_array($data)) $data = (object)$data;
				elseif(is_scalar($data)) $data = null;
			 }
			else $data = null;
		 }
		$this->OnCreate($all, $data);
		parent::__construct($data, $options);
	 }

	protected function OnCreate(stdClass $all = null, $root) {}

	private $options;
}

class SystemMessage extends SystemMessageContainer
{
	public function __construct($data, array $options = null)
	 {
		parent::__construct($data, ['root' => 'data', 'stdclass' => get_parent_class($this)]);
	 }

	protected function OnCreate(stdClass $all = null, $root)
	 {
		$this->data = new DataContainer([
			'status' => ['type' => 'string', 'value' => isset($all->status) ? "$all->status" : ''],
			'status_text' => ['type' => 'string', 'value' => isset($all->status_text) ? "$all->status_text" : ''],
			'messages' => ['type' => 'array', 'value' => isset($all->messages) ? $all->messages : []],
			'data' => ['value' => $root],
		]);
	 }

	final public function SendJSON()
	 {
		header('Content-Type: application/json');
		die(json_encode($this->data));
	 }

	final public function IsSuccess() { return 'success' === $this->data->status; }
	final public function IsWarning() { return 'warning' === $this->data->status; }
	final public function IsError() { return 'error' === $this->data->status; }
	final public function GetStatus() { return $this->data->status; }
	final public function GetStatusText() { return $this->data->status_text; }
	final public function GetMessages() { return $this->data->messages; }
	final public function __toString() { return $this->data->status_text; }

	private $data;
}
?>
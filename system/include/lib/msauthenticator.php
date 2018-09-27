<?php
function MSAI($index = 0) { return MSAuthenticator::InstanceExists($index) ? MSAuthenticator::Instance($index) : MSAuthenticatorDummy::Instance(); }

class EAuthentication extends Exception {}
	class EAuthenticationInvalidGroupName extends EAuthentication {}
	class EAuthenticationFailed extends EAuthentication {}
	class EAuthenticationInvalidData extends EAuthentication {}
		class EAuthenticationInvalidUID extends EAuthenticationInvalidData {}
		class EAuthenticationInvalidPassword extends EAuthenticationInvalidData {}
		class EAuthenticationUnsafeString extends EAuthenticationInvalidData {}
	class EAuthenticationInvalidKey extends EAuthentication {}
	class EAuthenticationInvalidSID extends EAuthentication {}
	class EAuthenticationSessionExpired extends EAuthentication {}
	class EAuthenticationUserCheckFailed extends EAuthentication {}
	class EAuthenticationPreCheckFailed extends EAuthentication {}
	class EAuthenticationDuplicateEntries extends EAuthentication {}

abstract class AuthenticationData
{
	abstract public function GetInstanceByUID($uid);
	abstract public function GetInstanceByKey($value, $field);
	abstract public function GetInstanceBySID($sid);
	abstract public function GetDenial($ip);
	abstract public function AddDenial($ip);

	public function __construct(MSAuthenticator $owner)
	 {
		$this->owner = $owner;
	 }

	final protected function GetOwner() { return $this->owner; }

	private $owner;
}

class DBAuthenticationData extends AuthenticationData
{
	final public function GetInstanceByUID($uid)
	 {
		return ($row = DB::GetRowByKey($this->GetDataTName(), 'uid', $uid)) ? $this->Init($row) : null;
	 }

	final public function GetInstanceByKey($value, $field)
	 {
		$res = DB::Select($this->GetDataTName(), '*', "`$field` = ?", [$value]);
		$n = count($res);
		if(1 === $n) return $this->Init($res->Fetch());
		elseif(1 < $n) throw new EAuthenticationDuplicateEntries();
	 }

	final public function GetInstanceBySID($sid)
	 {
		if($session = DB::GetRowById($this->GetSessTName(), $sid))
		 {
			if($row = DB::GetRowById($this->GetDataTName(), $session->suid))
			 {
				$row->last_visit = $session->last_visit;
				$row->sid = $session->id;
				$row->length = (int)$session->length;
				return $this->Init($row);
			 }
		 }
		return null;
	 }

	final public function GetDenial($ip) { return DB::GetRowById($this->GetOwner()->GetPrefix().'_denial', $ip); }
	final public function AddDenial($ip) { DB::InsertUpdate($this->GetOwner()->GetPrefix().'_denial', ['ip' => $ip, '=date_time' => 'NOW()'], ['=count' => 'IF(TIME_TO_SEC(TIMEDIFF(NOW(), `date_time`)) < 600, `count` + 1, 0)', '=date_time' => true]); }
	final public function GetPasswordHash() { return $this->password_hash; }
	final public function GetUID() { return $this->uid; }
	final public function GetSUID() { return $this->suid; }
	final public function GetSID() { return $this->sid; }
	final public function GetNumOfDenials() { return $this->num_of_denials; }
	final public function GetSessionLength() { return $this->length; }
	final public function SetSessionLength($value) { $this->length = $value; }
	final public function GetSessionDateTime() { return $this->last_visit_date_time; }
	final public function SetSID($value) { $this->sid = $value; }
	final public function DeleteSession() { DB::Delete($this->GetSessTName(), '`id` = ?', [$this->GetSID()]); }
	final public function ClearDeadSessions() { DB::Delete($this->GetSessTName(), '`suid` = :suid AND `id` <> :id AND `length` = 0', ['suid' => $this->GetSUID(), 'id' => $this->GetSID()]); }
	final public function GetUserData($fld = null) { return $fld ? $this->user_data->$fld : $this->user_data; }

	final public function Update(array $values)
	 {
		$values['~suid'] = $this->GetSUID();
		return DB::Update($this->GetDataTName(), $values, '`suid` = :suid');
	 }

	final public function IncNumOfDenials()
	 {
		$this->last_denial_date_time = DB::Now();
		return ++$this->num_of_denials;
	 }

	final public function ResetNumOfDenials()
	 {
		$this->last_denial_date_time = null;
		$this->num_of_denials = 0;
	 }

	final public function Save()
	 {
		DB::Update($this->GetDataTName(), ['num_of_denials' => $this->num_of_denials, 'last_denial_date_time' => $this->last_denial_date_time, '~uid' => $this->GetUID()], '`uid` = :uid');
		if($this->sid) DB::Replace($this->GetSessTName(), ['id' => $this->sid, 'suid' => $this->GetSUID(), 'length' => $this->length, '=last_visit' => 'NOW()']);
	 }

	final protected function GetDataTName() { return $this->GetOwner()->GetPrefix().'_data'; }
	final protected function GetSessTName() { return $this->GetOwner()->GetPrefix().'_session'; }

	final private function Init(stdClass $row)
	 {
		$this->password_hash = $row->password_hash;
		$this->uid = $row->uid;
		$this->suid = $row->suid;
		$this->num_of_denials = (int)$row->num_of_denials;
		unset($row->num_of_denials);
		$this->last_denial_date_time = $row->last_denial_date_time;
		unset($row->last_denial_date_time);
		if(isset($row->sid))
		 {
			$this->length = $row->length;
			unset($row->length);
			$this->sid = $row->sid;
			unset($row->sid);
			$this->last_visit_date_time = $row->last_visit;
		 }
		$this->user_data = $row;
		return $this;
	 }

	private $password_hash = null;
	private $uid = null;
	private $sid = null;
	private $suid = null;
	private $num_of_denials = null;
	private $last_denial_date_time = null;
	private $last_visit_date_time = null;
	private $length = 0;
	private $user_data = null;
}

class FileSystemAuthenticationData extends AuthenticationData
{
	final public function __construct(MSAuthenticator $owner)
	 {
		parent::__construct($owner);
		MSConfig::RequireFile('filesystemstorage');
		$this->user_data = new FileSystemStorage("/system/include/storage/{$this->GetOwner()->GetPrefix()}_data.php");
		// $this->storage = new FileSystemStorage('system/include/inc.auth_data.php', ['denial' => []]);
		// $this->storage = new FileSystemStorage('system/include/inc.auth_data.php', ['visit' => []]);
	 }

	final public function GetInstanceByUID($uid)
	 {
		// var_dump(isset($this->user_data->$uid));// return ($row = DB::GetRowByKey(, 'uid', $uid)) ? $this->Init($row) : null;
	 }

	final public function GetInstanceByKey($value, $field)
	 {
		// $res = DB::Select($this->owner->GetDataTName(), '*', "`$field` = ?", [$value]);
		// $n = $res);
		// if(1 === $n) return $this->Init($res->Fetch());
		// elseif(1 < $n) throw new EAuthenticationDuplicateEntries();
	 }

	final public function GetInstanceBySID($sid)
	 {
		// if($session = DB::GetRowById($this->GetSessTName(), $sid))
		 // {
			// if($row = DB::GetRowById($this->GetDataTName(), $session->suid))
			 // {
				// $row->last_visit = $session->last_visit;
				// $row->sid = $session->id;
				// $row->length = (int)$session->length;
				// return $this->Init($row);
			 // }
		 // }
		// return null;
	 }

	final public function GetDenial($ip) { return ; }

	final public function AddDenial($ip) { }

	private $user_data;
}

interface IMSAuthenticator
{
	static function CheckUID($str);
	static function CheckPassword($str);
}

abstract class AMSAuthenticator implements IMSAuthenticator
{
	use TInstances;

	abstract protected function GetDenial($ip);
	abstract protected function PreCheck();
	abstract protected function CheckUser();
	abstract protected function OnLogIn($forced = false);
	abstract protected function OnLogOut();
}

class MSAuthenticatorDummy
{
	final public static function Instance()
	 {
		static $instance = null;
		if(null === $instance) $instance = new MSAuthenticatorDummy();
		return $instance;
	 }

	final public function GetUID() {}
	final public function GetSUID() {}

	final private function __construct() {}
}

class MSAuthenticator extends AMSAuthenticator
{
	use TOptions;

	final public function __construct($prefix, $group_name = null, array $options = null)
	 {
		$this->AddOptionsMeta([
			'auth_data' => ['type' => 'string', 'value' => self::$auth_data_class],
			'check_user' => ['type' => 'callback,null'],
			'onlogin' => ['type' => 'callback,null'],
			'onlogout' => ['type' => 'callback,null'],
			'pre_check' => ['type' => 'callback,null'],
			'time_unit' => ['type' => 'number', 'value' => 86400],
		]);
		$this->SetOptionsData($options);
		if($prefix) $this->prefix = $prefix;
		if(null === $group_name) $index = 0;
		else
		 {
			if(!is_string($group_name)) throw new EAuthenticationInvalidGroupName('Group name must be a string ('.gettype($group_name).' given).');
			if(strlen($group_name) < 4) throw new EAuthenticationInvalidGroupName('Group name must be at least 4 characters long.');
			$this->group_name = "__{$group_name}_sid_";
			$index = $group_name;
		 }
		$class = $this->GetOption('auth_data');
		$this->SetAuthDataObject(new $class($this));
		self::SetInstance($index, $this, get_class($this).': ');
	 }

	final public static function CheckString($str) { if(preg_match('/[";\'=[:space:]]/', $str)) throw new EAuthenticationUnsafeString($str); }
	final public static function Encrypt($str) { return sha1('1173e9ca6c8e185d59f33b3ceace65cd83b79'.$str.'5b4a20cb20ffe17faed23fd3dec'); }
	final public static function SetDataClass($val) { self::$auth_data_class = $val; }

	final public function GetUID() { return $this->data ? $this->data->GetUID() : null; }
	final public function GetSUID() { return $this->data ? $this->data->GetSUID() : null; }
	final public function GetSID() { return $this->data ? $this->data->GetSID() : null; }
	final public function GetDenial($ip) { return $this->auth_obj->GetDenial($ip) ?: (object)['date_time' => null, 'count' => null]; }
	final public function GetUserData($fld = null) { return $this->data->GetUserData($fld); }
	final public function Update($attrs) { return $this->data->Update($attrs); }
	final public function GetPrefix() { return $this->prefix; }

	final public function SetSessionInitLength($value)
	 {
		$this->session_init_length = $value === null ? -1 : (int)$value;
		return $this;
	 }

	final public function SetPassword($new_password, $old_password)
	 {
		if($this->data->GetPasswordHash() != MSAuthenticator::Encrypt($old_password)) throw new EAuthenticationInvalidPassword('Неправильный текущий пароль');
		$this->data->Update(array('password_hash' => MSAuthenticator::Encrypt($new_password)));
		return $this;
	 }

	final public function LogOut()
	 {
		$this->LogOutImplementation();
		$this->OnLogOut();
	 }

	public static function CheckUID($str) {}
	public static function CheckPassword($str) {}

	protected function PreCheck() { return ($c = $this->GetOption('pre_check')) ? call_user_func($c, $this) : true; }
	protected function CheckUser() { return ($c = $this->GetOption('check_user')) ? call_user_func($c, $this) : true; }
	protected function OnLogIn($forced = false) { if($c = $this->GetOption('onlogin')) call_user_func($c, $this, $forced); }
	protected function OnLogOut() { if($c = $this->GetOption('onlogout')) call_user_func($c, $this); }

	final public function Run()
	 {
		if(isset($_COOKIE[$this->group_name]))
		 {
			self::CheckString($_COOKIE[$this->group_name]);
			if($this->data = $this->auth_obj->GetInstanceBySID($_COOKIE[$this->group_name]))
			 {
				if(!$this->CheckUser())
				 {
					$this->data = null;
					throw new EAuthenticationUserCheckFailed();
				 }
				$length = $this->data->GetSessionLength();
				if($length > 0 && time() > $length * 60 + strtotime($this->data->GetSessionDateTime())) throw new EAuthenticationSessionExpired();
				$this->data->Save();
			 }
			else throw new EAuthenticationInvalidSID();
		 }
		else throw new EAuthenticationFailed();
	 }

	final public function LogIn($uid, $password)
	 {
		if(!$this->PreCheck()) throw new EAuthenticationPreCheckFailed();
		self::CheckString($uid);
		$this->CheckUID($uid);
		$this->CheckPassword($password);
		$this->LogOutImplementation();
		if($data = $this->auth_obj->GetInstanceByUID($uid))
		 {
			if(self::Encrypt($password) === $data->GetPasswordHash())
			 {
				$this->SetDataAndCookie($data);
				return $data->GetUserData();
			 }
			$data->IncNumOfDenials();
			$data->Save();
			$e = 'EAuthenticationInvalidPassword';
		 }
		else $e = 'EAuthenticationInvalidUID';
		$this->auth_obj->AddDenial(MSConfig::GetIP());
		throw new $e();
	 }

	final public function ForceLogIn($uid, $field = null)
	 {
		self::CheckString($uid);
		$this->CheckUID($uid);
		$this->LogOutImplementation();
		if(null === $field)
		 {
			if(!($data = $this->auth_obj->GetInstanceByUID($uid))) throw new EAuthenticationInvalidUID();
		 }
		elseif(!($data = $this->auth_obj->GetInstanceByKey($uid, $field))) throw new EAuthenticationInvalidKey();
		$this->SetDataAndCookie($data, true);
	 }

	final private function SetDataAndCookie($data, $forced = false)
	 {
		$this->data = $data;
		if(!$this->CheckUser())
		 {
			$this->data = null;
			throw new EAuthenticationUserCheckFailed();
		 }
		$this->data = null;
		$sid = self::MakeSID();
		$data->ResetNumOfDenials();
		$data->SetSID($sid);
		$data->SetSessionLength($this->session_init_length);
		$data->Save();
		setcookie($this->group_name, $sid, 0 == $this->session_init_length ? 0 : time() + ($this->session_init_length == -1 ? 1209600 : $this->session_init_length * $this->GetTimeUnit()), '/', '', '', true);
		$data->ClearDeadSessions();
		$this->OnLogIn($forced);
	 }

	final private function GetTimeUnit() { return $this->GetOption('time_unit'); }

	final private function LogOutImplementation()
	 {
		if($this->data) $this->data->DeleteSession();
		setcookie($this->group_name, '', time() - 3600, '/');
		unset($_COOKIE[$this->group_name]);
	 }

	final private function SetAuthDataObject(AuthenticationData $obj) { $this->auth_obj = $obj; }

	final private static function MakeSID() { return sha1(uniqid(mt_rand(0, 5000000000), true)).sha1(uniqid(mt_rand(0, 5000000000), true)).sha1(uniqid(mt_rand(0, 5000000000), true)); }

	private $data = null;
	private $session_init_length = 0;
	private $group_name = '__mssid_';
	private $prefix = 'user';
	private $auth_obj;

	private static $auth_data_class = 'DBAuthenticationData';
}
?>
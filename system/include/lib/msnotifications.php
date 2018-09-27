<?php
require_once(dirname(__FILE__).'/datacontainer.php');

class MSNotifications
{
	final public function __construct($sender_id)
	 {
		$this->sender_id = $sender_id;
	 }

	final public function Add($link, $subject, $text, array $o = null)
	 {
		$o = new OptionsGroup($o, ['type' => ['type' => 'string', 'value' => ''], 'exclude' => ['type' => 'string', 'value' => ''], 'filter' => ['type' => 'callback,null']]);
		$exclude = $o->exclude ? false : explode(',', $o->exclude);
		$sent_at = DB::Now();
		DB::Insert(self::$tbl_name, ['sender_id' => $this->sender_id, 'sent_at' => $sent_at, 'link' => $link, 'subject' => $subject, 'text' => $text, 'type' => $o->type]);
		foreach(self::$handlers as $key => $group)
		 if(!$exclude || !in_array($key, $exclude))
		  foreach($group as $func) call_user_func($func, $this->sender_id, $link, $subject, $text, $sent_at, $o);
	 }

	final public static function Attach($callback, $pipe)
	 {
		self::$handlers[$pipe ?: '0'][] = $callback;
	 }

	final public static function GetList($viewed)
	 {
		return DB::Select(self::$tbl_name, '*', false === $viewed ? '`viewed_at` IS NULL' : (true === $viewed ? '`viewed_at` IS NOT NULL' : false), null, '`sent_at` DESC');
	 }

	final public static function GetUnreadCount()
	 {
		return DB::Count(self::$tbl_name, '`viewed_at` IS NULL');
	 }

	final public static function Delete(array $ids)
	 {
		return DB::Delete(self::$tbl_name, '`id` IN ('.implode(', ', array_fill(0, count($ids), '?')).')', $ids);
	 }

	private $sender_id;

	private static $handlers = [];
	private static $tbl_name = 'sys_notification';
}

trait TMSNoticeUserData
{
	protected function GetRes($cols, $condition, array $prm, DataContainer $o)
	 {
		$cnd = '`banned` = 0';
		if($o->filter)
		 {
			if($v = call_user_func($o->filter))
			 {
				if(count($v) > 1)
				 {
					\MSConfig::RequireFile('msdb.sql');
					$cnd = new \MSDB\SQL\IN($v, ['indexes' => 'to_string', 'expr' => "$cnd AND `suid`", 'not' => true], $prm);
				 }
				else
				 {
					$cnd .= " AND `suid` <> :suid";
					$prm['suid'] = reset($v);
				 }
			 }
		 }
		return DB::Select('user_data', $cols, "$cnd AND ($condition)", $prm);
	 }
}

class MSNoticeSMS
{
	use TMSNoticeUserData;

	final public function __construct(SMS $sms)
	 {
		$this->sms = $sms;
	 }

	final public function __invoke($sender_id, $link, $subject, $text, $sent_at, DataContainer $o)
	 {
		$res = $this->GetRes('CONCAT("7", `phone_num`) AS `num`', '`phone_num` <> "" AND `notice_phone` <> 0', [], $o);
		foreach($res as $user) $this->sms->Send($user->num, $subject);
	 }

	private $sms;
}

class MSNoticeEmail
{
	use TMSNoticeUserData;

	final public function __construct($from)
	 {
		$this->from = $from;
	 }

	final public function __invoke($sender_id, $link, $subject, $text, $sent_at, DataContainer $o)
	 {
		$res = $this->GetRes('email', '`email` <> "" AND `notice_email` <> 0', [], $o);
		if(count($res))
		 {
			$mail = (new MSMail())->SetHTMLType()->SetSubject('Уведомление с сайта '.(new idna_convert())->decode($_SERVER['HTTP_HOST']))->SetText($subject.'<br/><br/>'.nl2br($text).($link ? "<br/></br><a href='$link'>Посмотреть в системе администрирования</a>" : ''));
			if($this->from) $mail->SetFrom($this->from);
			foreach($res as $user) $mail->SetTo($user->email)->Send();
		 }
	 }

	private $from;
}
?>
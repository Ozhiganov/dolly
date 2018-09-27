<?php
require_once(dirname(__FILE__).'/traits.php');

class MSSMUsers extends MSDocument
{
	use TOptions;

	final public function __construct(array $options = null)
	 {
		$this->AddOptionsMeta(['visits' => ['type' => 'string', 'value' => 'user_visit'], 'users' => ['type' => 'string', 'value' => 'user_data'], 'config_form' => ['type' => 'callback,null'], 'on_handle' => ['type' => 'callback,null']]);
		$this->SetOptionsData($options);
	 }

	final public function Show()
	 {
	 	$this->AddCSS('lib.mssmusers');
		$type = @$_GET['type'];
		$nav = '';
		if('show404' == $type)
		 {
			$nav .= '<a class="secondary_menu__item" href="'.MSLoader::GetUrl().'">Статистика пользователей</a>';
			$tbl = new DBTable('tbl404', $this->GetVisitTblName(), '`suid`, `uid`, DATE_FORMAT(`date_time`, "'.self::TIME_FORMAT.'") AS `date_time_f`', '`class` = "MSDocument404"', null, '`date_time` DESC');
			$tbl->AddCol('user', 'Пользователь [suid]', 20)->SetCallback(function($val, $row){return "$row->uid <span class='user__suid'>[$row->suid]</span>";});
			$tbl->AddCol('section_id', 'Раздел', 27);
			$tbl->AddCol('request_uri', 'URI', 37)->SetClass('uri_col');
			$tbl->AddCol('date_time_f', 'Дата и время', 16);
		 }
		else
		 {
			if($id = Filter::NumFromGET('pid'))
			 {
				if($user = DB::GetRowById($this->GetUserTblName(), $id)) $this->PushTitleItem($user->uid);
				else return print(ui::ErrorMsg("Пользователь с идентификатором `$id` не существует."));
				$sections = ['' => array('title' => 'Последние посещения (разделы)', 'group_by' => 'section_id', 'col_w' => 65), 
							 'uri' => array('title' => 'Последние посещения (URI)', 'group_by' => 'request_uri', 'col_w' => 30, 'tbl_conf' => 'AddURICol'), 
							 'all' => array('title' => 'Все посещения', 'col_w' => 20, 'tbl_conf' => 'AddURICol')];
				if(empty($sections[$type])) $type = '';
				foreach($sections as $name => $data) $nav .= ($name == $type ? "<span class='secondary_menu__item _selected'>$data[title]</span>" : '<a class="secondary_menu__item" href="'.MSLoader::GetUrl().'?pid='.$id.($name ? "&type=$name" : '').'">'.$data['title'].'</a>');
				if(empty($sections[$type]['group_by']))
				 {
					$fields = '`date_time` AS `last_date`';
					$group_by = '';
				 }
				else
				 {
					$fields = 'MAX(`date_time`) as `last_date`';
					$group_by = "`{$sections[$type]['group_by']}`";
				 }
				$tbl = new DBTable('uri_tbl', $this->GetVisitTblName(), "`suid`, `remote_addr`, $fields", "`suid` = '$id'", null, '`last_date` DESC', ['group_by' => $group_by]);
				if(isset($sections[$type]['tbl_conf'])) $this->{$sections[$type]['tbl_conf']}($tbl);
				$tbl->AddCol('section_id', 'Раздел', $sections[$type]['col_w']);
				$tbl->AddCol('class', 'Документ', 20);
				$tbl->AddCol('last_date', 'Дата и время', 15)->SetCallback([$this, 'FormatDate']);
				if(empty($sections[$type]['group_by'])) $tbl->AddCol('remote_addr', '', 10);
			 }
			else
			 {
				if($id = Filter::NumFromGET('id'))
				 {
					if($user = DB::GetRowById($this->GetUserTblName(), $id)) $this->PushTitleItem($user->uid);
					else return print(ui::ErrorMsg("Пользователь с идентификатором `$id` не существует."));
				 }
				$form = $this->CreateForm();
				$tbl = new DBTable('main_tbl', [
							'u' => [$this->GetUserTblName(), 'suid,uid,banned'],
							'v' => [$this->GetVisitTblName(), '', '`u`.`suid` = `v`.`suid`'],
						], '`u`.`suid` as `id`, MAX(`v`.`date_time`) as `last_date`', false, null, '`banned` ASC, `u`.`uid` ASC', ['group_by' => '`u`.`suid`']);
				$tbl->AddCol('id')->SetType('IdTableCell');
				$tbl->AddCol('user', 'Пользователь [suid]', 65)->SetCallback(function($val, $row){
					$s = "$row->uid <span class='user__suid'>[$row->suid]</span>";
					if($row->banned) $s = "<span class='user__banned'>$s</span>";
					return $s;
				});
				$tbl->AddCol('set_permissions', '', 7)->SetClick('"'.MSConfig::GetMSSMDir().'/permissions/?id={id}"')->SetClass('user__permissions');
				$tbl->AddCol('last_date', 'Последнее посещение', 28)->SetClick('"?pid={id}"')->SetCallback([$this, 'FormatDate'])->SetClass('link');
				$tbl->SetRowClick();
				$tbl->SetEmptyContent('Активных пользователей нет.');
				$tbl2 = new DBTable('deleted_tbl', $this->GetVisitTblName(), 'MAX(`date_time`) as `last_date`', 'NOT EXISTS (SELECT * FROM '.DB::TName($this->GetUserTblName()).' AS `user` WHERE '.DB::TName($this->GetVisitTblName()).'.`suid` = `user`.`suid`)', null, false, ['group_by' => '`suid`']);
				$tbl2->SetCaption('Удалённые пользователи');
				$tbl2->AddCol('suid', 'suid', 10);
				$tbl2->AddCol('uid', 'Пользователь', 64);
				$tbl2->AddCol('last_date', 'Последнее посещение', 26)->SetCallback([$this, 'FormatDate']);
				$tbl2->SetEmptyContent(false);
			 }
			$nav .= '<a class="secondary_menu__item" href="'.MSLoader::GetUrl().'?type=show404">404</a>';
		 }
		$tbl->SetPageLength(100);
		print("<div class='secondary_menu'>$nav</div>".(empty($form) ? '' : $form->Make(array('Добавить пользователя', 'Редактировать пользователя'))).$tbl->Make());
		if(!empty($tbl2)) print($tbl2->Make());
	 }

	final public function ProccessData(EventData $evt, $new_user)
	 {
		if(!($evt->data['uid'] = trim($evt->data['uid']))) throw new EFSAction('Укажите логин!');
		if(DB::ValueExists($this->GetUserTblName(), 'uid', $evt->data['uid'], $new_user ? null : "`suid` <> '$evt->id'")) throw new EFSAction('Выберите другой логин!');
		if($evt->data['password_hash']) $evt->data['password_hash'] = MSAuthenticator::Encrypt($evt->data['password_hash']);
		else
		 {
			if($new_user) throw new EFSAction('Укажите пароль!');
			unset($evt->data['password_hash']);
		 }
		return $evt->data;
	 }

	final public function FormatDate($date) { return ($date ? Format::AsDateTime($date) : "не было"); }
	final public function AddURICol(DBTable $tbl) { $tbl->AddCol('request_uri', 'URI', 35)->SetCallback([$this, 'GetRef'])->SetClass('uri_col'); }
	final public function BeforeInsert(EventData $evt) { return $this->ProccessData($evt, true); }
	final public function BeforeUpdate(EventData $evt) { return $this->ProccessData($evt, false); }
	final public function GetRef($href) { return "<a href='$href'>$href</a>"; }
	final public function GetVisitTblName() { return $this->GetOption('visits'); }
	final public function GetUserTblName() { return $this->GetOption('users'); }

	final public function Handle()
	 {
		$this->OnHandle();
		$this->CreateForm();
		Form::Handle();
	 }

	protected function ConfigForm(Form $form) { if($callback = $this->GetOption('config_form')) call_user_func($callback, $form, $this); }
	protected function OnHandle() { if($callback = $this->GetOption('on_handle')) call_user_func($callback, $this); }

	final private function CreateForm()
	 {
		$form = new Form($this->GetUserTblName(), ['status_msgs' => ['inserted' => 'Пользователь добавлен.', 'updated' => 'Информация о пользователе обновлена.', 'deleted' => 'Пользователь удален.'], 'use_transaction' => true]);
		$form->AddField('uid', 'Логин', ['type' => 'TextInputUnique', 'required' => true]);
		$form->AddField('password_hash', 'Пароль', ['type' => 'SetPassword']);
		$form->AddField('banned', 'Заблокирован', ['type' => 'Checkbox']);
		$this->ConfigForm($form);
		$form->BindToEvent('before_insert', [$this, 'BeforeInsert']);
		$form->BindToEvent('before_update', [$this, 'BeforeUpdate']);
		return $form;
	 }

	const TIME_FORMAT = '%e.%m.%Y %H:%i';
}
?>
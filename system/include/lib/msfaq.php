<?php
class MSFaq extends MSDocument
{
	final public function __construct($tbl_name = 'faq', $email_tpl = null, $read_only_date = true)
	 {
		$this->tbl_name = $tbl_name;
		$this->email_tpl = $email_tpl ? $email_tpl : $tbl_name.'_answer';
		$this->read_only_date = $read_only_date;
		$this->order = SQLExpr::MSFaqOrderBy();
	 }

	final public function AddField()
	 {
		$this->addit_flds[] = func_get_args();
		return $this;
	 }

	final public function Show()
	 {
		$row = $this->GetRel()->GetAssocById(Filter::NumFromGET('id'));
		$form = Form::Get($this->tbl_name);
		$form->AddField('name', 'Имя', $row ? 'read_only' : null);
		$form->AddField('phone_num', 'Номер телефона', $row ? 'read_only' : null);
		$form->AddField('email', 'Электропочта', $row ? 'read_only' : null);
		$form->AddField('date_time', 'Дата', $row && $this->read_only_date ? array($this, 'FormatDate') : null);
		$form->AddField('text', 'Вопрос');
		foreach($this->addit_flds as $args) call_user_func_array(array($form, 'AddField'), $args);
		$form->AddField('answerer', 'Кто отвечает');
		$form->AddField('answer', 'Ответ');
		if($row)
		 {
			$form->AddField('not_show', 'Не публиковать ответ на сайте.'.(is_null($row['not_show']) ? ' Отправить на указанный e-mail адрес.' : ''));
			$form->AddField('sent', '', array($this, 'IsSent'));
		 }
		else $form->AddField('not_show', '', 'hidden', 0);
		$this->AddCSS('lib.faq');
		$new_html = $this->MakeTable(false);
		$old_html = $this->MakeTable(true);
		print($form->Make(array('Добавить', 'Редактировать')).($new_html || $old_html ? $new_html.$old_html : ui::WarningMsg('Список пуст.')));
	 }

	final public function Handle()
	 {
		Form::SetAfterInsertHdl($this->tbl_name, array($this, 'PrivateAnswer'));
		Form::SetAfterUpdateHdl($this->tbl_name, array($this, 'PrivateAnswer'));
		Form::Handle(array($this, 'AddMsgs'));
	 }

	final public function AddMsgs()
	 {
		switch(Form::GetStatus())
		 {
			case 'inserted': $this->AddSuccessMsg('Запись добавлена.'); break;
			case 'updated': $this->AddSuccessMsg('Данные обновлены.'); break;
			case 'deleted': $this->AddSuccessMsg('Данные удалены.'); break;
			case 'error': $this->AddErrorMsg(Form::GetErrorMsg());
		 }
	 }

	final public function PrivateAnswer($id)
	 {
		$row = $this->GetRel()->GetAssocById($id);
		if(0 == @$_POST['sent'] && $row['email'] && ($answer = trim($row['answer'])))
		 {
			try
			 {
				(new MSEMailTpl($this->email_tpl, $row['email']))->SetData(['name' => $row['name'], 'text' => $row['text'], 'answerer' => $row['answerer'], 'answer' => $answer])->Run();
				$this->AddSuccessMsg('Ответ отправлен.');
			 }
			catch(Exception $e)
			 {
				$msg = $e->GetMessage();
				$this->AddErrorMsg('Произошла ошибка при отправке письма.'.($msg ? '<br />'.$msg : ''));
				$this->GetRel()->Update(array('not_show' => null), '`id` = "'.$id.'"');
			 }
		 }
	 }

	final public function FormatDate($row, $input_name, $field_name) { return ms::DateStr($row[$field_name]); }
	final public function IsSent($row) { return '<input type="hidden" name="sent" value="'.(int)!$this->GetRel()->Select(null, '`id` = '.$row['id'].' AND `not_show` IS NULL')->FetchAssoc().'" />'; }
	final public function GetNewCount() { return DB::COUNT($this->tbl_name, '`not_show` IS NULL'); }
	final public function GetNewCountF() { return ($count = $this->GetNewCount()) ? " <span class='main_menu__count'>$count</span>" : ''; }

	final private function MakeTable($answered)
	 {
		$tbl = new DBTable(($answered ? 'old' : 'new').'_list', $this->tbl_name, true, '`not_show` IS '.($answered ? 'NOT ' : '').'NULL', $this->order);
		$tbl->SetCaption($answered ? 'С ответами' : 'Для проверки');
		$tbl->EnableDeleting($this->tbl_name);
		$tbl->AddCol('sender', 'Отправитель', 25)->SetExpression('CONCAT_WS("<br />", `name`, `phone_num`, CONCAT("<a href=\"mailto:", `email`, "\" title=\"Написать письмо\">", `email`, "</a>"), '.SQLExpr::FormatDateTime([], 'date_time', false).')');
		$tbl->AddCol('text', 'Вопрос', $answered ? 67 : 72)->SetExpression(SQLExpr::nl2br('`text`'))->SetClick('""');
		if($answered) $tbl->AddCol('not_show', '<img title="Показывать ответ на сайте" src="/system/img/visible.png" width="16" height="16" />', 5)->SetExpression('IF(`not_show`, "<em>нет</em>", "да")')->SetClick('""');
		return $tbl->SetRedirect(MSLoader::GetUrl(false))->SetPageLength(20)->SetEmptyContent(false)->Make($obj);
	 }

	private $tbl_name;
	private $email_tpl;
	private $order;
	private $addit_flds = [];
	private $read_only_date = false;
}
?>
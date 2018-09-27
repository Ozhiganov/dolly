<?php
class MS4xxLog extends MSDocument
{
	final public function Show()
	 {
		$this->AddCSS('lib.ms4xxlog');
		$tbl = new DBTable('errors', self::TBL_NAME, '`https`, `host`, `uri`, `referer`, `remote_addr`, `remote_host`, `user_agent`, '.SQLExpr::FormatDateTime(array('text' => false)), false, null, '`date_time` DESC, `id` DESC');
		$tbl->EnableDeleting();
		$tbl->AddCol('status', '', 5);
		$tbl->AddCol('info', '', 77)->SetClass('error_info')->SetCallback($this, 'ShowInfo');
		$tbl->AddCol('date_time_f', 'Дата', 15);
		$tbl->SetPageLength(100);
		$tbl->SetEmptyContent('Журнал ошибок пуст.');
		print($tbl->Make());
	 }

	final public function ShowInfo($val, $row)
	 {
		$url = $row->host.$row->uri;
		return "<div class='error_info__block'><a href='http".($row->https ? 's' : '')."://$url'>$url</a></div>".($row->referer ? "<div class='error_info__block'>Источник перехода: <a href='$row->referer'>$row->referer</a></div>" : '').'<div class="error_info__block">'.htmlspecialchars($row->user_agent).'</div> <span class="error_info__label">REMOTE_ADDR:</span> '.$row->remote_addr.($row->remote_host ? ' <span class="error_info__label _remote_host">REMOTE_HOST:</span> '.$row->remote_host : '');
	 }

	final public function Handle()
	 {
		DBTable::Handle(self::TBL_NAME);
	 }

	const TBL_NAME = 'sys_server_error';
}
?>
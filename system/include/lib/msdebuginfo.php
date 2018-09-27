<?php
class MSDebugInfo extends MSDocument
{
	final public function __construct(array $options = null)
	 {
		$this->options = new OptionsGroup($options, ['editable' => ['type' => 'bool', 'value' => false], 'prefix' => ['type' => 'string', 'value' => 'sys'], 'items' => ['type' => 'string', 'value' => ''], 'db' => ['type' => 'array,string,int', 'value' => 0]]);
	 }

	final public function Show()
	 {
		$this->AddCSS('lib.msdebuginfo');
		$items = [];
		$dbi = $this->InitDB();
		$add = function($k, $title) use(&$items) { $items[$k] = ['selected' => empty($_GET['page']) ? !$items : $k === $_GET['page'], 'title' => $title.' ('.DB::COUNT($this->TName($k)).')', 'href' => "?page=$k"];};
		if($this->options->items)
		 {
			$i = Filter::GetEnum($this->options->items, $this->items, $diff);
			if($diff) throw new EDataContainerInvalidMeta('Option `items` has invalid value'.(count($diff) > 1 ? 's' : '').': '.implode(', ', $diff));
			foreach($this->items as $k => $title) if(isset($i[$k])) $add($k, $title);
		 }
		else foreach($this->items as $k => $title) $add($k, $title);
?><div class="secondary_menu"><?php
		$url = MSLoader::GetUrl();
		foreach($items as $item) print("<a href='$url$item[href]' class='secondary_menu__item".($item['selected'] ? ' _selected' : '')."'>$item[title]</a>");
?></div><?php
		foreach($items as $key => $item)
		 if($item['selected'])
		  {
			$this->ShowClearForm($key)->{"ShowPage_$key"}();
			break;
		  }
		DB::SetIndex($dbi);
	 }

	final public function Handle()
	 {
		if(!$this->IsEditable()) return;
		$dbi = $this->InitDB();
		if(isset($_POST['clear_log']) && !empty($_POST['date']))
		 {
			if(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $_POST['date']))
			 {
				switch($_POST['clear_log'])
				 {
					case 'profile':
					case 'error':
					case 'message': $tbl_name = $_POST['clear_log']; break;
					default: $tbl_name = 'exception';
				 }
				DB::Delete($this->TName($tbl_name), 'DATE(`date_time`) <= ?', [$_POST['date']]);
				$this->AddSuccessMsg('Журнал очищен.');
			 }
			else $this->AddErrorMsg('Неправильный формат даты: '.$_POST['date']);
		 }
		else
		 {
			DBTable::Handle($this->TName('error'));
			DBTable::Handle($this->TName('exception'));
		 }
		DB::SetIndex($dbi);
	 }

	final public function ShowProfileInfo($val, $row)
	 {
		return '<div class="msprofiles__info_wr">
	<div class="msprofiles__info">
		<span class="msprofiles__label">SUID:</span> '.$row['suid'].'<br/>
		<span class="msprofiles__label">UID:</span> '.$row['uid'].'<br/>
		<span class="msprofiles__label">Раздел:</span> '.$row['section_id'].'<br/>
		<span class="msprofiles__label">Класс документа:</span> '.$row['class'].'<br/>
		<span class="msprofiles__label">Действие:</span> '.$row['action'].'<br/>
		'.($row['message'] ? '<div class="msprofiles__message"><span class="msprofiles__label">Сообщение:</span> '.htmlspecialchars($row['message']).'</div>' : '').'
	</div>
</div>';
	 }

	final public function ShowErrInfo($val, $row)
	 {
		if($href = $row->host.$row->uri) $href = " <a href='$row->protocol://$href' class='error_href _url'>$href</a>";
		$referer = $row->referer ? " <span class='lbl_referer'>referer:</span>&nbsp;<a href='$row->referer' class='error_href _referer'>$row->referer</a>" : '';
		return "<span class='exception_time'>$row->date_time_f</span> <span class='exception_info'>REMOTE_ADDR:</span> <code>$row->remote_addr</code><br/>
$href$referer<div class='error__msg'>".htmlspecialchars($row->message)."</div>
<span class='exception_info'>в файле </span> <code>$row->file</code> <span class='exception_info _line'>на строке номер</span>&nbsp;$row->line";
	 }

	final public function ShowErrType($val)
	 {
		switch($val)
		 {
			case E_ERROR: return 'E_ERROR';
			case E_WARNING: return 'E_WARNING';
			case E_PARSE: return 'E_PARSE';
			case E_NOTICE: return 'E_NOTICE';
			case E_CORE_ERROR: return 'E_CORE_ERROR';
			case E_CORE_WARNING: return 'E_CORE_WARNING';
			case E_COMPILE_ERROR: return 'E_COMPILE_ERROR';
			case E_COMPILE_WARNING: return 'E_COMPILE_WARNING';
			case E_USER_ERROR: return 'E_USER_ERROR';
			case E_USER_WARNING: return 'E_USER_WARNING';
			case E_USER_NOTICE: return 'E_USER_NOTICE';
			case E_STRICT: return 'E_STRICT';
			case E_RECOVERABLE_ERROR: return 'E_RECOVERABLE_ERROR';
			case E_DEPRECATED: return 'E_DEPRECATED';
			case E_USER_DEPRECATED: return 'E_USER_DEPRECATED';
			default: return $val;
		 }
	 }

	final public function ShowEInfo($val, $row)
	 {
		if($href = $row->host.$row->uri) $href = " <code class='exception_href _url'>$href</code>";
		if($row->referer) $href .= " <code class='exception_href _referer'>$row->referer</code>";
		return "<span class='exception_time'>$row->date_time_f</span> <span class='exception_info'>REMOTE_ADDR:</span> <code>$row->remote_addr</code><br />
$href<br />
<span class='exception_info'>Выброшено в файле</span> <code>$row->file</code> <span class='exception_info _line'>на строке номер</span>&nbsp;$row->line<br />
<span class='exception_info'>Класс:</span> $row->class<br />
<span class='exception_info'>Сообщение:</span> ".htmlspecialchars($row->message)."<br />
<span class='exception_info'>Код:</span> $row->code";
	 }

	final public function ShowMemSize($val) { return '<span title="'.$val.'">'.Format::RoundFileSize($val).'</span>'; }

	final private function InitDB()
	 {
		if(is_array($this->options->db))
		 {
			$dbc = new MYSQLDB(...$this->options->db);
			$i = $dbc->GetIndex();
		 }
		else $i = $this->options->db;
		return DB::SetIndex($i);
	 }

	final private function ShowClearForm($tbl_name)
	 {
		if($this->IsEditable())
		 {
			$row = DB::Select($this->TName($tbl_name), 'DATE(MIN(`date_time`)) AS `min`, DATE(MAX(`date_time`)) AS `max`')->Fetch();
			if($row->min || $row->max)
			 {
?><form class="msdebuginfo_clear" action="core.php" method="post">
	<input type="hidden" value="<?=MSLoader::GetUrl(false).(empty($_GET['page']) ? '' : '?page='.$_GET['page'])?>" name="__redirect" />
	<input type="hidden" name="clear_log" value="<?=@$_GET['page']?>" />
	<input type="date" required="required" name="date" min="<?=$row->min?>" max="<?=$row->max?>" />
	<input type="submit" value="Очистить" />
</form><?php
			 }
		 }
		return $this;
	 }

	final private function ShowPage_profile()
	 {
		$tbl = new DBTable('profiles', $this->TName('profile'), SQLExpr::FormatDateTime(array('text' => false)), false, null, '`date_time` DESC, `id` DESC');
		if($this->IsEditable()) $tbl->EnableDeleting();
		$tbl->AddCol('date_time_f', 'Дата', 15);
		$tbl->AddCol('request_uri', 'URL', $this->IsEditable() ? 51 : 54);
		$tbl->AddCol('time', 'Время', 13);
		$tbl->AddCol('memory', 'Память', 13)->SetCallback($this, 'ShowMemSize');
		$tbl->AddCol('info', '', 5)->SetClass('info')->SetCallback($this, 'ShowProfileInfo');
		$tbl->SetPageLength(100);
		print($tbl->Make());
	 }

	final private function ShowPage_message()
	 {
		$tbl = new DBTable('messages', $this->TName('message'), SQLExpr::FormatDateTime(array('text' => false)), false, null, '`date_time` DESC, `id` DESC');
		if($this->IsEditable()) $tbl->EnableDeleting();
		$tbl->AddCol('date_time_f', 'Дата', 16);
		$tbl->AddCol('group', 'Группа', 16);
		$tbl->AddCol('text', 'Текст', $this->IsEditable() ? 65 : 68)->SetCallback(function($val){return nl2br($val);});
		$tbl->SetPageLength(100);
		print($tbl->Make());
	 }

	final private function ShowPage_error()
	 {
		$tbl = new DBTable('errors', $this->TName('error'), '`protocol`, `host`, `uri`, `referer`, `remote_addr`, `message`, `file`, `line`, '.SQLExpr::FormatDateTime(), false, null, '`date_time` DESC, `id` DESC');
		if($this->IsEditable()) $tbl->EnableDeleting();
		$tbl->AddCol('type', 'Тип', 20)->SetCallback($this, 'ShowErrType')->SetClass('type');
		$tbl->AddCol('info', 'Ошибка', $this->IsEditable() ? 77 : 80)->SetCallback($this, 'ShowErrInfo')->SetClass('info');
		$tbl->SetPageLength(50);
		print($tbl->Make());
	 }

	final private function ShowShortEInfo(stdClass $row)
	 {
?><table class="exception"><?php
		if($href = $row->host.$row->uri) print("<tr><th></th><td><a href='$row->protocol://$href'>$href</a></td></tr>");
?><tr><th>Выброшено в файле</th><td><?=$row->file?></td></tr>
<tr><th>на строке номер</th><td><?=$row->line?></td></tr>
<tr><th>Класс</th><td><?=$row->class?></td></tr>
<tr><th>Сообщение</th><td><?=$row->message?></td></tr>
<tr><th>Код</th><td><?=$row->code?></td></tr>
</table><?php
	 }

	final private function ShowPage_exception()
	 {
		if(($id = Filter::NumFromGET('id')) && ($row = DB::GetRowById($this->TName('exception'), $id)))
		 {
?><div class="nav"><?php
			if($href = $row->host.$row->uri) print("<a href='$row->protocol://$href' class='host'>$href</a>");
			if($row->referer) print(" <span class='lbl_referer'>referer:</span>&nbsp;<a href='$row->referer' class='host'>$row->referer</a>");
?><code><?=$row->remote_addr?></code></div>
<table class="exception">
<tr><th>Выброшено в файле</th><td><?=$row->file?></td></tr>
<tr><th>на строке номер</th><td><?=$row->line?></td></tr>
<tr><th>Класс</th><td><?=$row->class?></td></tr>
<tr><th>Сообщение</th><td><?=$row->message?></td></tr>
<tr><th>Код</th><td><?=$row->code?></td></tr>
</table><?php
			if($row->dump)
			 {
				$dump = base64_decode($row->dump);
				try
				 {
					$dump = unserialize($dump);
				 }
				catch(Exception $e)
				 {
					$this->ShowShortEInfo($row);
					print(ui::ErrorMsg('Не удалось восстановить трассировку из дампа.'));
					MSConfig::ShowException($e);
					return;
				 }
				MSConfig::ShowTrace($dump);
			 }
			else print(ui::WarningMsg('Дамп трассировки недоступен.'.($row->no_dump_message ? "<br />$row->no_dump_message" : '')));
		 }
		else
		 {
			$tbl = new DBTable('e_table', $this->TName('exception'), '`host`, `uri`, `referer`, `remote_addr`, `file`, `line`, `class`, `message`, `code`, '.SQLExpr::FormatDateTime(), false, null, '`date_time` DESC, `id` DESC');
			if($this->IsEditable()) $tbl->EnableDeleting();
			$tbl->AddCol('info', 'Исключение', $this->IsEditable() ? 97 : 100)->SetCallback($this, 'ShowEInfo');
			$tbl->SetRowClick();
			$tbl->SetPageLength(100);
			print($tbl->Make());
		 }
	 }

	final private function IsEditable() { return $this->options->editable; }
	final private function TName($name) { return $this->options->prefix."_$name"; }

	private $options;
	private $items = ['exception' => 'Исключения', 'error' => 'Ошибки', 'profile' => 'Профайлы', 'message' => 'Сообщения'];
}
?>
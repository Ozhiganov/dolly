<?php
require_once(dirname(__FILE__).'/traits.php');

// options:
// config_table - функция обратного вызова для настройки колонок таблицы; принимает 1 аргумент - таблицу.
// before_handle - функция обратного вызова, которая вызывается внутри MSSimpleList::Handle; принимает 1 аргумент - $this.
// order_by - SQL ORDER BY. Если он указан, то ручная сортировка убирается автоматически.
// where - SQL WHERE, массив из двух элементов, соответствующих аргументам №4 и №5 конструктора DBTable.
// config_form - функция обратного вызова для настройки полей формы; принимает 1 аргумент - форму.
// ff_title - подставлять ли в начало формы заголовок ('title', 'Название'); по умолчанию - подставлять. От form field
class MSSimpleList extends MSDocument
{
	use TOptions;

	final public function __construct($tbl_name, array $options = null)
	 {
		$this->tbl_name = $tbl_name;
		$this->AddOptionsMeta(['fs' => ['type' => 'array', 'value' => []], 'before_handle' => [], 'before_show' => [], 'config_form' => [], 'config_table' => [], 'delete' => [], 'filter_ids' => ['type' => 'bool', 'value' => true], 'ff_title' => [], 'on_delete' => [], 'order_by' => [], 'where' => ['type' => 'array,callback,null'], 'cols' => ['type' => 'string,false', 'value' => false]]);
		$this->SetOptionsData($options);
		$form = new Form($this->GetTblName(), $this->GetOption('fs'));
		if(!$this->OptionExists('ff_title') || $this->GetOption('ff_title')) $form->AddField('title', 'Название', ['required' => true]);
		if($callback = $this->GetOption('config_form')) call_user_func($callback, $form, $this);
	 }

	final public function Show()
	 {
		$this->BeforeShow();
		echo Form::Get($this->GetTblName())->Make(['Добавление', 'Редактирование']);
		/* if($id = Filter::NumFromGET('id'))
		 {
			// MSConfig::RequireFile('prevnext');
			$f = $this->GetTitleField();
			if($rows = DB::GetPrevNextRows($this->GetTblName(), $this->IsCustomOrdered() ? 0 : 'position', $id, "`id`, `$f[0]`", false, null, $this->GetOrderBy()))
			 {
				$this->AddCSS('lib.prevnext');
				$url = MSLoader::GetUrl();
?><div class="prev_next_nav"><?php
				foreach($rows as $type => $row) print("<a href='$url?id=$row[id]' class='prev_next_nav__btn _$type'>{$row[$f[0]]}</a>");
?></div><?php
			 }
		 }*/
		list($condition, $params) = $this->GetTableWhere();
		if($remove = false !== ($opt = $this->GetOption('delete')))
		 {
			if($trash = 'trash' === $opt)
			 {
				$c = '`hidden` < :__hidden';
				$condition = $condition ? "($condition) AND ($c)" : $c;
				$params['__hidden'] = 2;
			 }
		 }
		$tbl = new DBTable('list', $this->GetTblName(), $this->GetOption('cols'), $condition, $params, $this->GetOrderBy());
		if($remove) $tbl->EnableDeleting($trash ? ['btn_class' => ':remove', 'btn_caption' => 'Отправить в корзину'] : null);
		$this->ConfigTable($tbl);
		if(!$this->HasCustomOrderBy()) $tbl->EnableOrdering();
		$tbl->SetRowClick();
		$tbl->SetPageLength(50);
		$tbl->SetRedirect(MSLoader::GetUrl(false));
		echo $tbl->Make();
	 }

	final public function Handle()
	 {
		$this->BeforeHandle();
		if(!$this->HasCustomOrderBy()) ms::UpdatePos($this->GetTblName());
		if(false !== $this->GetOption('delete') && isset($_POST['delete'][$this->GetTblName()]))
		 {
			if($ids = $this->GetOption('filter_ids') ? \Filter::NumArrFromPOST('ids') : $_POST['ids']) $this->OnDelete($ids);
			else $this->AddErrorMsg('Не указаны идентификаторы записей!');
		 }
		Form::Handle();
	 }

	final public function GetTblName() { return $this->tbl_name; }
	final public function GetOrderBy() { return null === ($order_by = $this->GetOption('order_by')) ? SQLExpr::MSSimpleListOrderBy() : $order_by; }
	final public function HasCustomOrderBy() { return null !== $this->GetOption('order_by'); }

	protected function BeforeShow() { if($callback = $this->GetOption('before_show')) call_user_func($callback, $this); }
	protected function BeforeHandle() { if($callback = $this->GetOption('before_handle')) call_user_func($callback, $this); }
	protected function GetTableWhere() { if($opt = $this->GetOption('where')) return is_callable($opt) ? call_user_func($opt, $this) : $opt; }

	protected function OnDelete(array $ids)
	 {
		$is_trash = 'trash' === $this->GetOption('delete');
		if($callback = $this->GetOption('on_delete')) call_user_func($callback, $this, $ids, $is_trash);
		elseif($is_trash)
		 {
			\MSConfig::RequireFile('msdb.sql');
			$p = ['hidden' => 2];
			$c = new \MSDB\SQL\IN($ids, ['use_keys' => false, 'indexes' => 'to_string', 'prefix' => 'idx', 'update' => true, 'expr' => '`id`'], $p);
			$count = \DB::Update($this->GetTblName(), $p, $c);
			if($count)
			 {
				$s = \Format::GetAmountStr($count, ['', ''], ['ы', 'а'], ['ы', 'ов']);
				$this->AddSuccessMsg("$count элемент{$s[1]} отправлен{$s[0]} в <a href='{$this->GetURL('trash/')}'>корзину</a>.");
			 }
			else $this->AddWarningMsg('Ничего не удалено.');
		 }
		else
		 {
			MSConfig::RequireFile('msdb.sql');
			$p = null;
			if($del = DB::Delete($this->GetTblName(), new \MSDB\SQL\IN($ids, ['indexes' => 'to_string', 'expr' => '`id`'], $p), $p))
			 {
				$s = Format::GetAmountStr($del, ['ён', ''], ['ы', 'а'], ['ы', 'ов']);
				$this->AddSuccessMsg("Удал{$s[0]} $del элемент{$s[1]}.");
				return;
			 }
		 }
	 }

	protected function ConfigTable($tbl)
	 {
		if($callback = $this->GetOption('config_table')) call_user_func($callback, $tbl, $this);
		else
		 {
			$w = 100;
			if(!$this->HasCustomOrderBy()) $w -= 3;
			if(false !== $this->GetOption('delete')) $w -= 3;
			$tbl->AddCol('title', 'Название', $w);
		 }
	 }

	private $tbl_name;
	private $has_default_config = null;
}
?>
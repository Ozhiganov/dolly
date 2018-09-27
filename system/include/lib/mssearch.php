<?php
require_once(dirname(__FILE__).'/traits.php');

class MSSearch extends MSDocument
{
	use TOptions;

	public function __construct($tbl_name, array $fields, array $options = null)
	 {
		$this->tbl_name = $tbl_name;
		$this->fields = $fields;
		$this->AddOptionsMeta(['bread_crumbs' => [], 'css' => [], 'js' => [], 'fields' => [], 'filters' => [], 'after_form_show' => [], 'trash' => [], 'callback' => [], 'actions' => []]);
		$this->SetOptionsData($options);
	 }

	public function Show()
	 {
		$this->AddCSS('lib.mssearch')->AddJS('lib.mssearch');
		foreach(['css', 'js'] as $t)
		 {
			if($files = $this->GetOption($t))
			 if(is_array($files)) foreach($files as $file) $this->AddFile($t, $file);
			 else $this->AddFile($t, $files);
		 }
		$html = '';
		foreach($this->fields as $key => $fld) $html .= ($html ? ', ' : '').'<label><input type="checkbox"'.($html ? '' : ' checked="checked"').' value="'.$key.'" name="types[]" />&thinsp;'.$fld['label'].'</label>';
?><form method='get' action='core.php' class="search_form" data-url='/<?=MSLoader::GetId()?>/'>
	<div class="search_form__fields">Искать по <?=$html?>.</div><?php
		if($filters = $this->GetOption('filters'))
		 {
			$html = '';
			$add_label = function($input, $name){return "<label data-name='$name'>$input</label>";};
			foreach($filters as $key => $filter)
			 {
				$i_name = "filters[$key]";
				if($html) $html .= ', ';
				if(empty($filter['input'])) $html .= $add_label("<input type='checkbox' name='$i_name' value='1' />&thinsp;$filter[title]", $key);
				else
				 {
					$input = call_user_func($filter['input'], $i_name, $key);
					$html .= $add_label("$filter[title]: $input", $key);
				 }
			 }
?>	<div class="search_form__fields">Фильтры: <?=$html?>.</div><?php
		 }
?>	<div class="search_form__inputs"><input type="search" class="msui_input search_form__text" autocapitalize="off" autocomplete="off" maxlength="100" name='text' /><input type="submit" value="" class="submit_search" /></div>
	<span class="search_form__found"></span>
</form><?php
		if($f = $this->GetOption('after_form_show')) call_user_func($f);
		print('<div class="search_results"'.($this->GetOption('trash') ? ' data-trash="true"' : '').'></div>');
	 }

	public function Handle()
	 {
		switch($this->ActionGET())
		 {
			case 'search':
				$fq = $params = [];
				if(($filters = $this->GetOption('filters')) && !empty($_GET['filters']))
				 {
					foreach($_GET['filters'] as $name => $value)
					 if(isset($filters[$name]))
					  {
						if($filters[$name]['filter'])
						 {
							$v = call_user_func($filters[$name]['filter'], $value);
							if(null === $v) continue;
							$params[$name] = $v;
						 }
						$fq[] = "({$filters[$name]['condition']})";
					  }
				 }
				if(!($text = $this->GetText()) && !$fq) self::SendText('Укажите фразу для поиска!');
				$types = [];
				if(!empty($_GET['types'])) foreach($_GET['types'] as $type) if(isset($this->fields[$type])) $types[$type] = $type;
				if(!$text && !$types && !$fq) self::SendText('Укажите поля для поиска!');
				$res = new UnifiedResult();
				if($types)
				 {
					$p1 = $p2 = $params;
					foreach($types as $type)
					 {
						$q = "`$type` LIKE :_search_text";
						if($fq) $q .= ' AND ('.implode(' AND ', $fq).')';
						$p1['_search_text'] = "%$text[filter]%";
						$p2['_search_text'] = "%{$text['filter%']}%";
						$res->Attach($this->GetRes($q, $p1), $this->GetRes($q, $p2));
					 }
				 }
				else $res->Attach($this->GetRes(implode(' AND ', $fq), $params));
				if(count($res))
				 {
					if($callback = $this->GetOption('callback')) $res->SetCallback($callback);
					$act_callback = $this->GetOption('actions');
					$ret_val = '';
					foreach($res as $row)
					 if(!$this->FetchedRow($row))
					  {
						$ret_val .= '<div class="result">';
						if($row->ext) $ret_val .= '<div class="result__icon_wr"><div class="result__icon"><img alt="" src="'.$row->image_src.'" width="'.$row->width.'" height="'.$row->height.'" /></div></div>';
						$ret_val .= '<div class="result__title">'.$this->CorrectTitle($row->title).'</div>';
						if(isset($row->parent_id)) $ret_val .= $this->GetCrumbs($row->parent_id);
						if($act_callback) $ret_val .= call_user_func($act_callback, $row);
						$ret_val .= '<div class="clear"></div></div>';
					  }
					self::SendHTML($ret_val);
				 }
				else self::SendText('empty');
		 }
	 }

	final public static function CorrectTitle($title) { return $title ?: '&bull;&nbsp;<i>страница без названия</i>'; }

	final public static function GetText()
	 {
		if($text = trim($_REQUEST['text']))
		 {
			// (mb_strlen($text, 'utf-8') > 2)
			$text = str_replace(' ', ' ', $text);// nbsp -> space
			$text = preg_replace('/\s{2,}/', ' ', $text);// several spaces -> one space
			$t = str_replace(['"', '\'', '«', '»'], '', $text);
			return ['text' => $text, 'filter' => $t, 'filter%' => str_replace(' ', '%', $t), 'htmlsc' => htmlspecialchars($text)];
		 }
	 }

	final private function GetRes($where, array $params = null) { return DB::Select($this->tbl_name, $this->GetOption('fields'), $where, $params); }

	final private function AddFile($type, $name)
	 {
		$m = "Add$type";
		$this->$m($name);
	 }

	final private function GetCrumbs($parent_id)// можно оптимизировать, если будет выявлено сильное влияние на производительность
	 {
		if(($c = $this->GetOption('bread_crumbs')) && ($bcr = call_user_func($c, $parent_id))) return '<div class="result__crumbs">'.(is_array($bcr) ? implode(' &rarr; ', array_map(['MSSearch', 'CorrectTitle'], array_reverse($bcr))) : $bcr).'</div>';
	 }

	final private function FetchedRow(stdClass $row)
	 {
		if(isset($this->fetched[$row->id])) return true;
		$this->fetched[$row->id] = $row->id;
		return false;
	 }

	private $fetched = [];
	private $tbl_name;
	private $fields;
}
?>
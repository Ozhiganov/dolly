<?php
abstract class MSDataLoader
{
	final public static function Handle()
	 {
		if(isset($_GET['thread']) && isset(self::$instances[$_GET['thread']]))
		 {
			MSPage::AsXML();
			self::$instances[$_GET['thread']]->OnLoad();
			header('Content-Type: text/html; charset=utf-8');
			die(MSPage::Make('<root last="'.(self::$instances[$_GET['thread']]->IsLast() ? 'true' : 'false').'">'.self::$instances[$_GET['thread']]->GetRootTpl().'</root>'));
		 }
	 }

	public function __construct($id, $page_length, $root_tpl = '<layout name="root" />')
	 {
		$this->id = $id;
		$this->page_length = $page_length;
		$this->root_tpl = $root_tpl;
		if(isset(self::$instances[$id])) throw new Exception('Объект класса MSDataLoader с идентификатором `'.$id.'` уже инициализирован.');
		self::$instances[$id] = $this;
	 }

	abstract protected function OnLoad();

	final protected function Run(IDBResult $res)
	 {
		$this->last = false;
		if($num_rows = count($res))
		 {
			$id = Filter::NumFromGET('id');
			$before = !empty($_GET['before']);
			if($id)
			 {
				$index = 0;
				while($row = $res->FetchAssoc())
				 {
					++$index;
					if($row['id'] == $id) break;
				 }
				if($before)
				 {
					$offset = $index - $this->page_length - 1;
					if($offset >= 0)
					 {
						$res->SetLimit($this->page_length)->SetOffset($offset);
						if(!$offset) $this->last = true;
					 }
					else
					 {
						if(--$index > 0) $res->SetLimit($index)->SetOffset(0);
						else $res = new EmptyResult();
						$this->last = true;
					 }
				 }
				else
				 {
					if($index < $num_rows)
					 {
						$res->SetLimit($this->page_length)->SetOffset($index);
						if($index + $this->page_length >= $num_rows) $this->last = true;
					 }
					else $this->last = true;
				 }
			 }
			else
			 {
				if($before) $res->SetOffset($num_rows - $this->page_length);
				else $res->SetLimit($this->page_length);
			 }
		 }
		return $res;
	 }

	final private function IsLast() { return $this->last; }
	final private function GetRootTpl() { return $this->root_tpl; }

	private static $instances = array();
	private $id;
	private $root_tpl;
	private $page_length;
	private $last;
}
?>
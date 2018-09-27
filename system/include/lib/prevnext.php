<?php
class MSSMPrevNext
{
	final public function __construct($rel_name, $tpl, array $titles, $condition = null)
	 {
		$this->rel_name = $rel_name;
		$this->tpl = $tpl;
		$this->titles = $titles;
		$this->condition = $condition;
	 }

	final public function Show($id, $top_fields = false)
	 {
		ResourceManager::AddCSS('lib.prevnext');
?><div class="prev_next_nav"><?php
		$res = Relation::Query("({$this->GetSQLPrevNext(true, $id)}) ".($top_fields ? "UNION (SELECT $top_fields, 'all' AS `type`)" : '')." UNION ({$this->GetSQLPrevNext(false, $id)})");
		while($row = $res->FetchAssoc()) print("<a href='$row[url]' class='prev_next_nav__btn _$row[type]'>$row[title]</a>");
?></div><?php
	 }

	final protected function GetSQLPrevNext($next, $id)
	 {
		if($next)
		 {
			$sign = '>';
			$type = 'next';
			$title = $this->titles[1];
		 }
		else
		 {
			$sign = '<';
			$type = 'prev';
			$title = $this->titles[0];
		 }
		return "SELECT CONCAT({$this->tpl}) AS `url`, ".(stripos($title, 'CONCAT') === 0 ? $title : '"'.mysql_real_escape_string($title).'"')." AS `title`, '$type' AS `type` FROM `{$this->GetRelName()}` WHERE ".($this->condition ? "({$this->condition}) AND " : '')." `id` $sign '$id' ORDER BY `id` DESC LIMIT 1";
	 }

	final protected function GetRelName() { return $this->rel_name; }

	private $rel_name;
	private $tpl;
	private $condition;
	private $titles;
}
?>
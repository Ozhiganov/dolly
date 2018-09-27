<?php
MSConfig::RequireFile('traits');

class EMSTable extends Exception {}
	class EMSTableDuplicateId extends EMSTable {}
	class EMSTableDuplicateColName extends EMSTable {}

interface IMSTableCell
{
	public function __construct(MSTableRow $row, array $o = null);
	public function Make($value, stdClass $row);
	public function SetClass($value);
}

interface IMSTableCellInvisible extends IMSTableCell {}

abstract class MSTableCell implements IMSTableCell
{
	use TOptions;

	public function __construct(MSTableRow $row, array $o = null)
	 {
		$this->row = $row;
		$this->SetOptionsData($o);
		$this->AddOptionsMeta(['before_make' => []]);
		$this->before_make = $this->GetOption('before_make');
	 }

	public function SetClass($value)
	 {
		$this->class = [];
		return $this->AddClass($value);
	 }

	public function AddClass($value)
	 {
		if($value = explode(' ', $value)) foreach(array_filter($value) as $v) $this->class[$v] = $v;
		return $this;
	 }

	public function RemoveClass($value)
	 {
		unset($this->class[$value]);
		return $this;
	 }

	public function GetClass() { if($this->class) return implode(' ', $this->class); }

	final protected function GetRow() { return $this->row; }

	private $row;
	private $class = [];

	protected $before_make;
}

class TableCell extends MSTableCell
{
	public function Make($value, stdClass $row)
	 {
		if($this->before_make) call_user_func($this->before_make, $value, $row, $this);
		return '<td'.(($class = $this->GetClass()) ? ' class="'.$class.'"' : '').'>'.$value.'</td>';
	 }
}

class TableCellId extends TableCell
{
	public function Make($value, stdClass $row)
	 {
		$this->GetRow()->SetId($value);
		return parent::Make($value, $row);
	 }
}

class EmptyTableCell extends MSTableCell implements IMSTableCellInvisible
{
	public function Make($value, stdClass $row) { return ''; }
}

class KeyValueTCell extends MSTableCell
{
	public function __construct(MSTableRow $row, array $o = null)
	 {
		$this->AddOptionsMeta(['use_id' => [], 'attr' => [], 'data' => []]);
		parent::__construct($row, $o);
	 }

	public function Make($value, stdClass $row)
	 {
		if($this->before_make) call_user_func($this->before_make, $value, $row, $this);
		return '<td'.(($c = $this->GetClass()) ? " class='$c'" : '').($this->GetOption('use_id') ? " data-id='{$this->GetRow()->GetId()}'" : '')." data-{$this->GetOption('attr')}='$value'>{$this->GetOption('data')[$value]}</td>";
	 }
}

class IdTableCell extends MSTableCell implements IMSTableCellInvisible
{
	public function Make($value, stdClass $row)
	 {
		$this->GetRow()->SetId($value);
		return '';
	 }
}

class CheckBoxTCell extends MSTableCell
{
	public function Make($value, stdClass $row) { return $this->MakeCell($value); }

	final public function SetName($val)
	 {
		$this->name = $val;
		return $this;
	 }

	final protected function MakeCell($value) { return '<td class="ch_box'.(($class = $this->GetClass()) ? ' '.$class : '').'"><input type="checkbox" value="'.$value.'" name="'.$this->name.'" /></td>'; }

	protected $name;
}

class IdCheckBoxTCell extends CheckBoxTCell
{
	public function Make($value, stdClass $row)
	 {
		$this->GetRow()->SetId($value);
		return $this->MakeCell($value);
	 }

	protected $name = 'ids[]';
}

interface IMSTableRow
{
	public function __construct(MSTable $table);
	public function AttachCell($id, $cell_name);
	public function GetCode();
	public function MakeCell($id, $value, stdClass $row);
	public function SetId($value);
	public function GetId();
}

abstract class MSTableRow implements IMSTableRow
{
	final public function __construct(MSTable $table) { $this->table = $table; }
	final public function AttachCell($id, $cell_name) { return $this->InitCell($id, is_array($cell_name) ? new $cell_name[0]($this, $cell_name[1]) : new $cell_name($this)); }
	final public function GetTable() { return $this->table; }

	final protected function GetCell($id) { return $this->cells[$id]; }

	final private function InitCell($id, MSTableCell $cell)
	 {
		if(isset($this->cells[$id])) throw new EMSTableDuplicateColName();
		return $this->cells[$id] = $cell;
	 }

	private $cells;
	private $table;
}

class TableRow extends MSTableRow
{
	public function GetCode()
	 {
		$ret_val = $this->cells_code;
		$this->cells_code = '';
		return '<tr'.($this->GetId() ? ' id="'.$this->GetHTMLId().'"' : '').'>'.$ret_val.'</tr>';
	 }

	public function MakeCell($id, $value, stdClass $row)
	 {
		$cell = clone $this->GetCell($id);
		$this->cells_code .= $cell->Make($value, $row);
	 }

	public function SetId($value)
	 {
		$this->id = $value;
		return $this;
	 }

	public function GetHTMLId() { return $this->GetTable()->GetId().'_row_'.$this->GetId(); }
	public function GetId() { return $this->id; }

	private $cells_code = '';
	private $id;
}

class MSTableButton
{
	final public function __construct($title, $submit = false, $position = null)
	 {
		$this->title = $title;
		$this->submit = $submit;
		$this->position = $position;
	 }

	final public function SetId($val) { $this->id = $val; return $this; }
	final public function SetName($val) { $this->name = $val; return $this; }
	final public function SetClass($val) { $this->class = $val; return $this; }
	final public function Enable() { $this->disabled = false; return $this; }
	final public function SetClick($val) { $this->click = $val; return $this; }

	final public function GetTitle() { return $this->title; }
	final public function GetId() { return $this->id; }
	final public function GetName() { return $this->name; }
	final public function GetClass() { return $this->class; }
	final public function GetPosition() { return $this->position; }
	final public function IsSubmit() { return $this->submit; }
	final public function IsDisabled() { return $this->disabled; }
	final public function GetClick() { return $this->click; }

	private $title;
	private $position;
	private $id;
	private $name;
	private $class;
	private $disabled = true;
	private $click;
	private $submit;
}

class MSTable implements Countable
{
	protected function MakeHeader($name, $caption, $colspan) { return "<th class='$name'".($colspan ? " colspan='$colspan'" : '').">$caption</th>"; }
	protected function MakeCaption($value) { return "<caption><span>$value</span></caption>"; }

	final public function __construct($id, $row_prototype = 'TableRow')
	 {
		if(isset(self::$tables[$id])) throw new EMSTableDuplicateId();
		$this->id = $id;
		self::$tables[$id] = $this;
		$this->InitRowPrototype(new $row_prototype($this));
	 }

	final public function GetCaption() { return $this->caption; }
	final public function GetId() { return $this->id; }
	final public function count() { return $this->row_num; }

	final public function GetProcessedURIForPageNavigator($num)
	 {
		$uri_part = '__mstable_page['.$this->GetId().']=';
		$base = preg_replace('/&?'.str_replace(array('[', ']'), array('\[', '\]'), $uri_part).'[0-9]+/ui', '', $_SERVER['REQUEST_URI']);
		return $base.(strpos($_SERVER['REQUEST_URI'], '?') === false ? '?' : (in_array($base[mb_strlen($base) - 1], array('&', '?')) ? '' : '&')).$uri_part.$num;
	 }

	final public function SetData($data, $data_method = null)
	 {
		$this->data = $data;
		$this->data_method = $data_method;
		return $this;
	 }

	final public function AddCol($id, $caption, $width, $colspan = 0, $cell_class = 'TableCell')
	 {
		if(isset($this->cols[$id])) throw new EMSTableDuplicateColName();
		$col = $this->row_prototype->AttachCell($id, $cell_class);
		$this->cols[$id] = ['id' => $id, 'caption' => $caption, 'width' => $width, 'colspan' => $colspan, 'visible' => !($col instanceof IMSTableCellInvisible)];
		return $col;
	 }

	final public function SetCaption($value)
	 {
		$this->caption = $value;
		return $this;
	 }

	final public function SetAction($value)
	 {
		$this->action = $value;
		return $this;
	 }

	final public function SetClass($value)
	 {
		$this->class = $value;
		return $this;
	 }

	final public function AddBtn($title, $submit = false, $position = null)
	 {
		$btn = new MSTableButton($title, $submit, $position);
		$this->AddBtnObj($btn);
		return $btn;
	 }

	final public function AddBtnObj(MSTableButton $obj)
	 {
		if(null === $obj->GetPosition()) $this->btns[] = $obj;
		else array_splice($this->btns, $obj->GetPosition(), 0, array($obj));
		return $this;
	 }

	final public function SetPageLength($val)
	 {
		$this->page_length = $val;
		return $this;
	 }

	final public function SetPageNum($val)
	 {
		$this->page_num = $val;
		return $this;
	 }

	final public function HideHeader()
	 {
		$this->show_header = false;
		return $this;
	 }

	final public function Make()
	 {
		$ret_val = $tbody = '';
		if($this->caption) $ret_val .= $this->MakeCaption($this->caption);
		$col_str = $hdr_str = '';
		$col_count = 0;
		foreach($this->cols as $key => $col)
		 {
			if($col['visible']) ++$col_count;
			if($col['width'])
			 {
				$col_str .= "<col class='col_$key' />";
				if($this->show_header && $col['caption'] !== null) $hdr_str .= $this->MakeHeader($key, $col['caption'], $col['colspan']);
			 }
		 }
		$ret_val .= "<colgroup>$col_str</colgroup>";
		if($hdr_str) $ret_val .= "<thead><tr>$hdr_str</tr></thead>";
		if($this->HasFooter())
		 {
			$ret_val .= '<tfoot>';
			if($this->HasNavigator())
			 {
				$html = '';
				for($i = 1; $i <= $this->page_length; ++$i) $html .= $this->{$i == $this->page_num ? 'MakeInactiveNavBtn' : 'MakeActiveNavBtn'}($i);
				$ret_val .= $this->MakeFooterRow($html, $col_count, 'page_nav');
			 }
			if($this->HasButtons())
			 {
				$html = '';
				foreach($this->btns as $btn) $html .= html::{$btn->IsSubmit() ? 'Submit' : 'Button'}('value', $btn->GetTitle(), 'class', $btn->GetClass(), 'id', $btn->GetId(), 'name', $btn->GetName(), 'disabled', $btn->IsDisabled());
				$ret_val .= $this->MakeFooterRow($html, $col_count);
			 }
			$ret_val .= '</tfoot>';
		 }
		$this->row_num = 0;
		if($this->data)
		 {
			if($this->data instanceof Iterator) foreach($this->data as $row) $tbody .= $this->MakeRow($row);
			elseif($this->data_method)
			 {
				if(is_object($this->data)) while($row = $this->data->{$this->data_method}()) $tbody .= $this->MakeRow($row);
				else
				 {
					$data = $this->data;
					$method = $this->data_method;
					while($row = $data::$method()) $tbody .= $this->MakeRow($row);
				 }
			 }
			elseif(is_callable($this->data)) while($row = call_user_func($this->data)) $tbody .= $this->MakeRow($row);
			else foreach($this->data as $row) $tbody .= $this->MakeRow($row);
		 }
		return "<table id='{$this->id}'".($this->class ? " class='{$this->class}'" : '').">$ret_val<tbody>$tbody</tbody></table>";
	 }

	final private function HasFooter() { return $this->HasButtons() || $this->HasNavigator(); }
	final private function HasButtons() { return (bool)$this->btns; }
	final private function HasNavigator() { return $this->page_length > 1; }
	final private function MakeFooterRow($html, $colspan, $class = '') { return '<tr><td'.($colspan > 1 ? " colspan='$colspan'" : '').'><div class="btns'.($class ? " $class" : '').'">'.$html.'</div></td></tr>'; }
	final private function MakeActiveNavBtn($num) { return "<a href='{$this->GetProcessedURIForPageNavigator($num)}'>$num</a> "; }
	final private function MakeInactiveNavBtn($num) { return "<span>$num</span> "; }
	final private function InitRowPrototype(IMSTableRow $row) { $this->row_prototype = $row; }

	final private function MakeRow(stdClass $values)
	 {
		++$this->row_num;
		foreach($this->cols as $col) $this->row_prototype->MakeCell($col['id'], isset($values->{$col['id']}) ? $values->{$col['id']} : null, $values);// значение может отсутствовать (например, колонка для перетаскивания строки)
		return $this->row_prototype->GetCode();
	 }

	private static $tables = [];

	private $data = null;
	private $data_method = null;
	private $id;
	private $class;
	private $caption;
	private $cols = [];
	private $action;
	private $row_prototype;
	private $btns = [];
	private $page_length = 1;
	private $page_num = 0;
	private $row_num;
	private $show_header = true;
}
?>
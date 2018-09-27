<?php
class FileSizeProxy
{
	final public function __construct($field = null, $alias = null, $root = null, $precision = 2)
	 {
		$this->field = $field ? $field : 'href';
		$this->alias = $alias ? $alias : 'size';
		$this->root = $root ? $root : $_SERVER['DOCUMENT_ROOT'];
		$this->precision = $precision;
	 }

	final public function Run(stdClass $row)
	 {
		$size = ms::GetFileSize($this->root.$row->{$this->field});
		$row->{$this->alias.'_value'} = $size->value;
		$row->{$this->alias.'_unit'} = $size->unit;
		$row->{$this->alias} = $size->__toString();
	 }

	private $field;
	private $alias;
	private $root;
	private $precision;
}
?>
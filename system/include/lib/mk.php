<?php
abstract class mk
{
	final public static function array_kv(...$args) { return array_combine($args, $args); }
}
?>
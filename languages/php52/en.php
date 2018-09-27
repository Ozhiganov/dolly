<?php
return array(
'php_ver_lower' => create_function('$val, $min', 'return "Current PHP version is outdated: $val. At least PHP $min required to run this sript.";'),
'php_ver_higher' => create_function('$val, $max', 'return "Current PHP version is too new: $val. PHP version must be lower than $max.";'),
'php_extensions_missing' => create_function('$items', 'return "Required PHP extensions missing: $items";'),
'apache_modules_missing' => create_function('$items', 'return "Required Apache modules missing: $items";'),
);
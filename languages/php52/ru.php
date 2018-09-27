<?php
return array(
'php_ver_lower' => create_function('$val, $min', 'return "Ваша версия PHP безнадёжно устарела: $val. Для запуска этого скрипта требуется версия не ниже $min.";'),
'php_ver_higher' => create_function('$val, $max', 'return "Ваша версия PHP слишком новая: $val. Для запуска этого скрипта требуется версия ниже $max.";'),
'php_extensions_missing' => create_function('$items', 'return "Отсутствуют необходимые расширения PHP: $items";'),
'apache_modules_missing' => create_function('$items', 'return "Отсутствуют необходимые модули Apache: $items";'),
);
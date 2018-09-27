<?php
error_reporting(E_ALL);

function __autoload($class_name)
{
	$name = strtolower($class_name);
	if(MSConfig::HasRequiredFile($name)) return MSConfig::RequireFile($name);
	MSConfig::Autoload($name, $class_name);
}

require_once(MSSE_LIB_DIR.'/msconfig.php');
require_once(MSSE_LIB_DIR.'/msexceptionizer.php');
new MSExceptionizer();
set_exception_handler(['MSConfig', 'HandleException']);
register_shutdown_function(['MSConfig', 'OnShutDown']);
require_once(dirname(__FILE__).'/global_config.php');
?>
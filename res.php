<?
$method=$_GET['action'];

if($_POST['charset']==''){$_POST['charset']='utf-8';}

switch ($method){
	case 'getConfig' :
	
	foreach($_POST as $name => $val)
	{
		
	if($name !== 'base_url')
	{
		if($name !== 'ip'){
		echo $name.' = "'.$val.'"
';
		}
	}
	else
	{	
		echo $name.' = "'.$val.'/"
';
	}	
   }
	
	break;	
	case 'baseUrl' :
	echo $_POST['url'].'/';
	break;
	case 'getVersionDS' :
	$data = '1.7.5';
	
	header('Content-Type: application/json');
   
    echo ($data);
	
	
	break;
	default:
	$null=0;
}




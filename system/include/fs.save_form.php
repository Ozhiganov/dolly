<?php
$fs = new MSFieldSet('save_form', ['action' => function($fs, ...$args){
	MSConfig::RequireFile('traits', 'datacontainer', 'filesystemstorage');
	$types = ['Email' => ['IsEmail'], 'Tel' => ['IsPhoneNum'], 'File' => true, 'Textarea' => true];
	$conf = new FileSystemStorage('/fs_config.php', ['readonly' => false, 'root' => MSSE_INC_DIR]);
	$i = 0;
	$data = [];
	foreach($fs->AsIFieldsData() as $n => $f)
	 {
		if('fields' === $n)
		 {
			$data[$n] = [];
			foreach($args[$i] as $k => $f)
			 {
				if(!isset($f['title'])) continue;
				$r = [urldecode($k), $f['title'], []];
				if(isset($f['type']) && isset($types[$f['type']])) $r[2]['type'] = $f['type'];
				if(!empty($f['required'])) $r[2]['required'] = true;
				$data[$n][] = $r;
			 }
		 }
		// elseif('selector' === $n && '' === $args[$i]);
		else
		 {
			if($n === 'id') $$n = $args[$i];
			else $data[$n] = $args[$i];
		 }
		++$i;
	 }
	if(is_numeric($id) && isset($conf->$id)) $conf->$id = $data;
	else $id = $conf($data);
}, 'show_e_msg' => true]);
class FieldsTypes extends \MSFieldSet\POSTField
{
	public function MakeInput()
	 {
		return "<div class='fields_types' data-name='{$this->GetName()}' data-input_name='{$this->GetInputName()}'></div>";
	 }
}
$fs->AddField('title', 'Название', ['default' => 'Обработчик №1', 'required' => true]);
$fs->AddField('email', 'Email, кому', ['type' => 'Email', 'required' => true])->SetCheck('IsEmail');
$fs->AddField('subject', 'Тема письма', ['default' => 'Сообщение с сайта', 'required' => true]);
$fs->AddField('template', 'Шаблон письма', ['type' => 'Textarea', 'required' => true]);
$fs->AddField('fields', 'Валидация полей формы', ['type' => '\FieldsTypes']);
$fs->AddField('msg_success', 'Сообщение после успешной отправки формы', ['default' => l10n()->message_sent, 'required' => true]);
$fs->AddField('from', 'Email, от кого', ['type' => 'Email', 'default' => "admin@$_SERVER[HTTP_HOST]"]);
$fs->AddField('form_fields', '', ['type' => 'Hidden']);
$fs->AddField('selector', '', ['type' => 'Hidden']);
$fs->AddField('id', '', ['type' => 'Hidden']);
$fs->SetMsg('Изменения сохранены.');
$fs->SetRedirect($_SERVER['REQUEST_URI']);// проверять url!!!
?>
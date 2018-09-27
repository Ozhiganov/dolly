<div class="topbar">
    <h1 class="main-title"><?=l10n()->forms_handler?></h1>
</div>
<div class="forms inner">
    <div class="tabs">
        <div class="tabs_content">
            <div class="tab tab_0"><?php
MSConfig::RequireFile('traits', 'datacontainer', 'filesystemstorage');
$fs_conf = new FileSystemStorage('/fs_config.php', ['readonly' => true, 'root' => MSSE_INC_DIR]);
if(count($fs_conf))
 {
?>				<form method='post' action='/admin.php?action=delete_form' class='form _form_handlers'><?php
	foreach($fs_conf as $id => $row) : ?><div class='form__row'>
	<div class=''><?=$row->title?></div>
	<div class=''><?=$row->subject?></div>
	<div class=''><?=$row->email?></div>
	<input type='checkbox' name='ids[]' value='<?=$id?>' />
</div><?php endforeach;
?>          		<input type='submit' value='<?=l10n()->delete?>' disabled='disabled' />
				</form>
<script type='text/javascript'>$(function(){
$('.form._form_handlers').submit(function(){return confirm('Удалить отмеченные элементы?');});
var $_IDS = '.form._form_handlers input[name="ids[]"]', i_submit = $('.form._form_handlers [type="submit"]');
$($_IDS).change(function(){i_submit.prop('disabled', !$($_IDS + ':checked').length);});
});</script>
<style type='text/css'>
.form._form_handlers{}
.form._form_handlers .form__row{border:1px solid #ccd8e4;padding:8px 10px 5px 32px;margin:0;position:relative;}
.form._form_handlers .form__row + .form__row{border-top:none;}
.form._form_handlers input[name="ids[]"]{margin:0;padding:0;position:absolute;top:10px;left:10px;}
.form._form_handlers input[type='submit']{margin:10px 0;}
</style><?php
 }
else echo 'Не создано ни одного обработчика';
?>			</div>
            
        </div>
    </div>
</div>
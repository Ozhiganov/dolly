<?php
MSConfig::RequireFile('filesystemstorage');
$fs_conf = new FileSystemStorage('/fs_config.php', ['readonly' => true, 'root' => MSSE_INC_DIR]);
$fs = MSFieldSet::Get('save_form');
MSFieldSet::Handle();
$fs_data = $fs->GetData();
$o = ['document_title' => l10n()->forms_constructor, 'js' => ['forms'], 'css' => ['forms']];
if($fs_data->status_type) Filter::CopyValues($o, $fs_data, 'status_type', 'status_msg');
$html = '';
$has_err = false;
foreach($fs as $name => $field)
 if('Hidden' === $field->type) $html .= $field->input;
 else
  {
	if($field->state)
	 {
		$msg = "<span class='form__err_msg' data-state='$field->state'>$field->msg</span>";
		$c = " _$field->state";
		$has_err = true;
	 }
	else $msg = $c = '';
	$html .= "<div class='form__row$c' data-name='$name'><label class='form__label'>$field->title</label>$field->input$msg</div>";
  }
$l = l10n();
$c = $has_err ? '' : ' _hidden';
$o['body_content'] = "<form method='post' action='$_SERVER[REQUEST_URI]' class='form$c'><input type='button' value='' class='form__toggle_state' /><div class='form__top'></div><fieldset>$html<div class='form__row'><input type='button' class='msui_small_button btn_select_submit' value='$l->select_submit_button' data-value-start='$l->select_submit_button' data-value-finish='$l->finish' /> <input type='button' class='msui_small_button _icon _delete _hidden btn_deselect_submit' value='$l->delete' /></div></fieldset>
<div class='form__bottom'>$fs_data->hidden_field<input class='msui_button' type='submit' value='$l->save' /> <input class='form__close msui_small_button' type='button' value='$l->close' /></div>
</form>".html::Hidden('name', 'fs_config', 'value', json_encode($fs_conf));
$ui = new IframeUI();
$o['toolbar_content'] = "<div class='forms_list btn_loader'></div>";
$ui->Show($o);
?>
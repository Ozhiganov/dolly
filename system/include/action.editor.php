<?php
// $fs = MSFieldSet::Get('save_form');
// MSFieldSet::Handle();
// $fs_data = $fs->GetData();
$code = '
var OK = "' . l10n()->ok . '";
var SAVE = "' . l10n()->save . '";
var ADD_TO_REPLACES = "' . l10n()->add_to_replaces . '";
var CANCEL = "' . l10n()->cancel . '";
var OR = "' . l10n()->or . '";
var ON = "' . l10n()->on . '";
var SUCCESS = "' . l10n()->success . '";
var APPEND = "' . l10n()->add_to_replaces . '";
var CUT = "' . l10n()->cut_all . '";
var BACK = "' . l10n()->back . '";
';
	// <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js" dollyeditor></script>
	 // <link rel="stylesheet" href="/dolly_templates/css/dollysites_editpanel.css" type="text/css" dollyeditor>
	 // <script type="text/javascript" src="/dolly_templates/js/dollysites_editpanel.js" dollyeditor></script>
	 // <script type="text/javascript" src="/dolly_templates/js/nicEdit.js" dollyeditor></script>
$o = ['document_title' => 'Визуальный редактор', 'js_inline' => $code, 'js' => ['dollysites_editpanel', 'nicEdit'], 'css' => ['dollysites_editpanel']];
// if($fs_data->status_type) Filter::CopyValues($o, $fs_data, 'status_type', 'status_msg');
// $html = "<form method='post' action='$_SERVER[REQUEST_URI]' class='form _hidden'><div class='form__top'></div><fieldset>";
// foreach($fs as $field) $html .= 'Hidden' === $field->type ? $field->input : "<div class='form__row'><label class='form__label'>$field->title</label>$field->input</div>";
// $html .= "$fs_data->hidden_field
// <input class='msui_button' type='submit' value='Сохранить' /> <input class='form__close msui_small_button' type='button' value='Закрыть' />
// </fieldset></form>".html::Hidden('name', 'fs_config', 'value', FSConfig::Instance()->GetJSON());
$ui = new IframeUI();
// $o['toolbar_content'] = "<div class='forms_list btn_loader'></div>";
// $o['body_content'] = $html;
$ui->Show($o);
?>
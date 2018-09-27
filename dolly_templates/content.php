<?php
function upFirstLetter($str, $encoding = 'UTF-8')
{
    return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding)
        . mb_substr($str, 1, null, $encoding);
}

$target = array(
    array('en', 'Английский'),// locale!!!
    array('ru', 'Русский'),// locale!!!
);

$source = array(
    array('ru', 'Русский'),// locale!!!
    array('en', 'English'),// locale!!!
);

$options = array();
foreach(l10n('lang_list') as $k => $v) $options[$k] = [$k, upFirstLetter($v)];

$translate_adapter = Settings::staticGet('translateAdapter');
?>
<script type="text/javascript">
    function setAction(action) {
        $('#form').attr("action", '../admin.php?action=' + action)
    }

</script>
<form method="post" enctype="multipart/form-data" id="form" action="../admin.php?action=save_page">

    <div class="topbar">

        <h1 class="main-title"><?=l10n()->content?></h1>

        <div class="right button save">
            <?=l10n()->save?>
        </div>
    </div>

    <div class="content_editor inner">
        <div class="tabs">
            <div class="tabs_selectors">
                <ul class="list">
                    <li id="tab_0" onclick="setAction('translate_save')"><?=l10n()->translator?></li>
                    <li id="tab_1" onclick="setAction('synonims_save')"><?=l10n()->synonymizer?></li>
                    <li id="tab_2"
                        onclick="setAction('save_content_settings')"><?=l10n()->settings?></li>
                </ul>
            </div>
            <div class="tabs_content">
                <div class="tab tab_0">
                    <div class="tanslator_form">
                        <div class="col-xs-12 pd0 item">
                            <div class="sinonymizer">
                                <?=html::Checkbox('id', 'translate', 'class', 'super_checkbox', 'name', 'translate', 'checked', $translate_adapter && $translate_adapter !== 'NotTranslate', 'value', 1)?>
                                <label for="translate" class="label syn"><?=l10n()->turn_on?> <?=l10n()->translator?></label>
                            </div>
                        </div>


                        <div class="col-xs-12 pd0 item translaters">
							<div class="disabled"></div>
                            <table class="table borderless" border="0">
								<tr>
									<td><span class="label"><?=l10n()->select_translator?></span></td>
								</tr>
								<tr>
									<td>
										<select name="translate_backend" class="magic_select">
<?php
										$translaters = array(
											array('YandexTranslate', 'Yandex ' . l10n()->translator),
											array('GoogleTranslate', 'Google ' . l10n()->translator),
											// array('BaiduTranslate', 'Baidu ' . l10n()->translator),
										);
										_select($translaters, $translate_adapter);
?>										</select>
									</td>
								</tr>
                                <tr>
                                        <td><span class="label"><?=l10n()->site_language?></span></td>
                                        <td><span class="label"><?=l10n()->translate_into?></span></td>
                                </tr>
                                <tr>
                                    <div class="first">
                                        <td>
                                            <select name="translate_source" class="magic_select">
                                                <?php
                                                $optionsSource = array_merge($source, $options);

                                                _select($optionsSource, @Settings::staticGet('translateSource'));
                                                ?>
                                            </select></td>
                                    </div>
                                    <div class="change_position">
                                        <!-- <div class="btn">
                                             <i class="fa fa-angle-left"></i>
                                             <i class="fa fa-angle-right"></i>
                                         </div>-->

                                    </div>
                                    <div class="second">
                                        <td>
                                            <select name="translate_target" class="magic_select">
                                                <?php
                                                $optionsTarget = array_merge($target, $options);
                                                _select($optionsTarget, @Settings::staticGet('translateTarget'));
                                                ?>
                                            </select>
                                        </td>
                                    </div>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab tab_1">
                    <div class="sinonymizer">

                        <div class="">
                            <input type="checkbox"
                                   id="status"
                                   class="super_checkbox"
                                   name="synonims_status"
                                   value="on"
                                <?php echo (Settings::staticGet('synonimize')) ? 'checked' : ''; ?>
                            >
                            <label for="status" class="label syn">
                                <?=l10n()->turn_on?> <?=l10n()->synonymizer?>
                            </label>
                        </div>
                        <div class="options">
                            <div class="disabled"></div>
                            <div class="left pd0">
                                <span class="label"><?=l10n()->select_base?></span>
                            </div>
                            <div class="right pd0">
                                <select name="synonims_file" class="magic_select">
                                    <option value="none"><?=l10n()->select_synonyms_file?></option>
                                    <?php
                                    $options = array();
                                    foreach (glob('*.syns') as $dict) {
                                        $options[] = array($dict, $dict);
                                    }
                                    _select($options, @Settings::staticGet('synsDictonary'));
                                    ?>
                                </select>
                            </div>
</form>

<div class="col-xs-12 pd0 devider">
    <span class="label grey"><?=l10n()->or?></span>
</div>
<div class="left pd0"><span class="label"><?=l10n()->upload_file?></span></div>
<div class="right pd0">
    <input type="file" id="upload_files" name="upload_files">
    <label for="upload_files"><?=l10n()->select_file?></label>
    <!--<div class="button upload_button">Загрузить</div>-->
</div>
<div class="left pd0">
    <span class="label"><?=l10n()->format?></span>
</div>
<div class="right pd0">
    <div class="words">
        <label for="first_word" class="label"><?=l10n()->word?> 1</label>
        <input type="text" class="word" name="first_word" value="=>">
        <label for="second_word" class="label"><?=l10n()->word?> 2</label>
        <input type="text" class="word" name="second_word" value="|">
        <label for="third_word" class="label"><?=l10n()->word?> 3</label>
    </div>
</div>
</div>
</div>
</div>
<div class="tab tab_2">
    <div class="settings">
        <div class="left pd0">
            <span class="label"><?=l10n()->trigger_first?></span>
        </div>
        <div class="right pd0">
            <select name="syn_pos" class="magic_select">
                <?php
                $options = array(
                    array('_1', l10n()->synonymizer),
                    array('_2', l10n()->translator),
                );
                _select($options, @Settings::staticGet('synonymsOrder'));
                ?>
            </select>
        </div>
    </div>
</div>
</div>
</div>
</div>
</form>
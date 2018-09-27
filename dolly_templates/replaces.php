<script type="text/javascript">
    var FIND_WORD = '<?=l10n()->find_what?>';
    var REPLACE_WORD = '<?=l10n()->replace_with?>';
    var SET_TEXT = '<?=l10n()->enter_text?>';
    var PREG = '<?=l10n()->regex?>';
</script>
<?php function printReplace($key=1, $value=array(), $self = null, $first = false) { ?>
    <div class="fields">
        <div class="column" id="column_<?php echo $key;?>">
            <div class="input_wrap">
                <div class="remove"></div>
                <div class="left pd0">
                    <input type="text" name="out[<?php echo $key;?>][l_input]" class="textbox" placeholder="<?=l10n()->enter_text?>" value="<?php echo @strip_tags($value['l_input']);?>">
                </div>
                <div class="right pd0">
                    <input type="text" name="out[<?php echo $key;?>][r_input]" class="textbox" placeholder="<?=l10n()->enter_text?>" value="<?php echo @strip_tags($value['r_input']);?>">
                </div>
            </div>
            <div class="textarea_wrap hidden">
                <div class="remove"></div>
                <div class="left pd0">
                    <textarea name="out[<?php echo $key;?>][l_textarea]" class="magic_textarea" placeholder="<?=l10n()->enter_text?>"><?php echo htmlspecialchars_decode($value['l_textarea']);?></textarea>
                </div>
                <div class="right pd0">
                    <textarea name="out[<?=$key?>][r_textarea]" placeholder="<?=l10n()->enter_text?>" class="magic_textarea"><?=isset($value['r_textarea']) ? htmlspecialchars_decode($value['r_textarea']) : ''?></textarea>
                </div>
                <div class="change_wrap">
                    <div class="change_type">
                        <input type="checkbox" class="super_checkbox" name="out[<?=$key?>][change_type]" id="regular[<?=$key?>]" <?php echo (@$value['change_type'] == 'preg') ? 'checked' : null;?>>
                        <label for="regular[<?php echo $key;?>]" class="label"><?=l10n()->regex?></label>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>
<form action="../admin.php?action=add_replacement&sub=edit" method="post" id="form">
    <div class="topbar">
        <h1 class="main-title"><?=l10n()->text_replacements?></h1>
        <div class="right button save">
            <?=l10n()->save?>
        </div>
    </div>
    <div class="text_changer">
        <div id="replaces">
            <div class="fieldtitle" style="padding: 30px 26px 0px 46px;">
                <div class="left">
                    <label for="" class="label" style="font-size: 17px;"><?=l10n()->find_what?></label> <br><br>
                </div>
                <div class="right">
                    <label for="" class="label" style="font-size: 17px;"><?=l10n()->replace_with?></label> <br><br>
                </div>
            </div>
            <?php if (!sizeof($new)) {
                printReplace(1, null, $this);
            } else {
                if (isset($new) AND is_array($new)) {
                    $i = 0;
                    foreach ($new as $key => $value) {
                        if (@$value['change_type'] == 'script') {
                            continue;
                        }
                        ++$i;

                        printReplace($key, $value, $this);
                    }
                    if (!$i) {
                        printReplace(1, null, $this);
                    }
                }
            } ?>
        </div>

        <div class="buttons">
            <div class="add_column"><?=l10n()->add_replacement?></div>
        </div>
    </div>

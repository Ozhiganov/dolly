<head>
    <link href="../dolly_css/dolly_style.css" rel="stylesheet">
    <script type="text/javascript">
        var string_replace = '<?php echo $this->t('#STRING_REPLACE_TYPE#');?>';
        var preg_replace   = '<?php echo $this->t('#PREG_REPLACE_TYPE#');?>';
    </script>

    <script src="../dolly_js/jquery-1.11.3.min.js"></script>
    <script src="../dolly_js/jquery-ui.min.js"></script>
    <script src="../dolly_js/select.js"></script>

    <script>
        $(function() {
            $( "#navbar" ).tabs({
                collapsible: true
            });
        });
    </script>

    <script src="../dolly_js/tinymce/tinymce.min.js"></script>
    <script src="../dolly_js/jquery.livequery.js"></script>
    <script src="../dolly_js/main.js"></script>

</head>
<div class = "page_2">
    <div class="page_thanks">


        <div class="page_visible page_visible_3" data-page="3">
            <div class="form_step_3_wrap_form">
<form class="form_step_3" action="../admin.php?action=add_replacement&sub=edit&mode=api&key=<?php echo $_GET['key'];?>" method="post" id="edit_form" >
    <div class="form_wrap_2 forms-border" >
        <div class="wrap_label"><?php echo $this->t('#REPLACES#');?></div>
        <div class="wrap_items">
            <?php if (!sizeof($new)) { ?>
                <div class="item item_1">
                    <div class="input_wrap">
                        <input type="text" class="l_input" name="out[1][l_input]">
                        <input type="text" class="r_input" name="out[1][r_input]">
                    </div>
                    <div class="delete"></div>
                    <div class="textarea_wrap">
                        <textarea class="l_textarea" name="out[1][l_textarea]"></textarea>
                        <textarea class="r_textarea" name="out[1][r_textarea]"></textarea>
                        <div class="change_wrap">
                            <div class="change_type">
                                <input type="checkbox" id="change_type_1" name="out[1][change_type]">
                                <label for="change_type_1"><span class="text text_1"><?php echo $this->t('#STRING_REPLACE_TYPE#');?></span><span class="text text_2"><?php echo $this->t('#PREG_REPLACE_TYPE#');?></span></label>
                            </div>
                        </div>
                        <div class="delete"></div>
                    </div>
                </div>
            <?php } else {
                if (isset($new) AND is_array($new)) { ?>


                    <?php foreach ($new as $key => $value) { ?>
                        <div class="item item_<?php echo $key;?>">
                            <div class="delete"></div>
                            <div class="input_wrap">
                                <input type  = "text"
                                       class = "l_input"
                                       name  = "out[<?php echo $key;?>][l_input]"
                                       value = "<?php echo htmlspecialchars_decode($value['l_input']);?>"/>
                                <input type  = "text"
                                       class = "r_input"
                                       name  = "out[<?php echo $key;?>][r_input]"
                                       value = "<?php echo htmlspecialchars_decode($value['r_input']);?>"/>
                            </div>
                            <div class="textarea_wrap" style="display: none;">
                                <textarea class="l_textarea" name="out[<?php echo $key;?>][l_textarea]"><?php echo $value['l_textarea'];?></textarea>
                                <textarea class="r_textarea" name="out[<?php echo $key;?>][r_textarea]"><?php echo $value['r_textarea'];?></textarea>
                                <div class="change_wrap">
                                    <div class="change_type">
                                        <input type="checkbox"
                                               id="change_type_1"
                                               name="out[<?php echo $key;?>][change_type]" <?php echo ($value['change_type'] == 'preg') ? 'checked' : null;?>/>
                                        <label for="change_type_<?php echo $key-1;?>"><span class="text text_1"><?php echo $this->t('#STRING_REPLACE_TYPE#');?></span><span class="text text_2"><?php echo $this->t('#PREG_REPLACE_TYPE#');?></span></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php }}} ?>
        </div>

        <div class="add_new_item" style="height: 36px;"><?php echo $this->t('#ADD_REPLACE#');?></div>
        <input class="form_submit" name="save" type="submit" value="<?php echo $this->t('#SAVE#');?>">
    </div>
</form>
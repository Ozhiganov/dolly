<script type="text/javascript">
    function removeScript(id) {
        var script = $('#' + id + '_old').val()
        $.ajax({
            url: '/admin.php?action=remove_scripts',
            type: 'POST',
            data: {script: script},
            success: function (data) {
                location.reload()
            }
        })
    }

    function editScript(id) {
        var script = $('#' + id + '_old').val()
        var newScript = $('#' + id + '_new').val()
        $.ajax({
            url: '/admin.php?action=remove_scripts',
            type: 'POST',
            data: {script: script, new: newScript},
            success: function (data) {
                location.reload()
            }
        })
    }
</script>
<div class="topbar">
    <h1 class="main-title"><?=l10n()->scripts?></h1>
</div>
<div class="scripts inner">
    <div class="tabs">
        <div class="tabs_selectors">
            <ul class="list">
                <li id="tab_0"><?=l10n()->all_scripts?></li>
                <!--<li id="tab_1">Реклама</li>
                <li id="tab_2">Счетчики</li>-->
            </ul>
        </div>
        <div class="tabs_content">
            <div class="tab tab_0">
                <div class="scripts_table">
                    <div class="title_bar">
                        <div class="col-xs-8 pd0"><span class="name label"><?=l10n()->script?></span>
                        </div>
                        <!--<div class="col-xs-4 pd0">
                            <span class="label"><?=l10n()->type?></span>
                        </div>-->
                    </div>
                    <div class="table_content">
                        <?php foreach ($scripts as $key => $script) {
                            if (!$script) {
                                continue;
                            } ?>
                            <div class="item">
                                <div class="col-xs-8 pd0 shortcode">
                                    <div class="code">
                                        <?php echo htmlspecialchars($script); ?>
                                    </div>
                                </div>
                                <!--<div class="col-xs-1 pd0 type"></div>-->
                                <div class="col-xs-3 pd0 edit_button">
                                    <div class="edit"><?=l10n()->edit?></div>
                                    <div class="hidden_buttons">
                                        <div class="remove"
                                             onclick="removeScript(<?php echo $key; ?>);">
                                            <?=l10n()->delete?>
                                        </div>
                                        <div class="save"
                                             onclick="editScript(<?php echo $key; ?>);">
                                            <?=l10n()->save?>
                                        </div>
                                    </div>
                                </div>
                                <div class="textarea_hidden">
                                    <textarea name="code[1]"
                                              id="<?php echo $key; ?>_new"><?php echo trim($script); ?></textarea>
                                    <textarea name="code[1]" id="<?php echo $key; ?>_old"
                                              style="display: none"><?php echo $script; ?></textarea>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script type='text/javascript'>
    // scripts part of jquery code
    $(function () {
        $('.table_content .item').dblclick(function () {
            $(this).find('.textarea_hidden').toggle();
            $(this).find('.hidden_buttons').toggle();
            $(this).find('.edit').toggle();
            $(this).toggleClass('active');
        });
        $('.table_content .item .edit').click(function () {
            $(this).parent().parent().trigger('dblclick');

        })
        $('.table_content .item .remove').click(function () {
            $(this).parent().parent().parent().remove();
        });
        $('.table_content .item .save').click(function () {
            var code = $(this).parent().parent().parent().find('textarea').val();
            $(this).parent().parent().parent().find('.shortcode .code').text(code);
            $(this).parent().parent().parent().trigger('dblclick');
        });
        $('.table_content .item').each(function () {
            var code = $(this).find('textarea').val();
            $(this).find('.shortcode .code').text(code);
        });
    });
</script>
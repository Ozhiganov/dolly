<form method="post" action="admin.php?action=admin_auth_info" id="form">

    <div class="topbar">
        <h1 class="main-title"><?=l10n()->auth_prefs?></h1>

        <div class="right button save">
            <?=l10n()->save?>
        </div>
    </div>

    <div class="text_changer" style="margin:50px;">
        <label for="login" style="font-size:15px;"><?=l10n()->login?></label><br>
        <input type="text" class="input" id="login" name="login" style="width: 20%" value="<?php echo $login; ?>"><br><br>

        <label for="password" style="font-size:15px;"><?=l10n()->password?></label><br>
        <input type="text" class="input" id="password" name="password" style="width: 20%"
               value="<?php echo $password; ?>"><br>
</form>
</div>


<script type="text/javascript">
    $(function () {
        $('.table_content .item').dblclick(function () {
            $(this).find('.textarea_hidden').toggle();
            $(this).find('.hidden_buttons').toggle();
            $(this).find('.edit').toggle();
            $(this).toggleClass('active');
        });
        $('.table_content .item .edit').click(function () {
            $(this).parent().parent().trigger('dblclick');
        });
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
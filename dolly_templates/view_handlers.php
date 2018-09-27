<?php
$apiKeyString = "&mode=api&key={$_GET['key']}";
?>
<head>
    <link href="../dolly_css/dolly_style.css" rel="stylesheet">

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
<div id="success-page">
    <div class = "page_4">
        <div class="page_thanks">
            <div class="title"><?php echo $this->t('#MENU_HANDLERS_SETTINGS#');?></div>
            <form method="post" action="../admin.php?action=handlers_settings<?php echo $apiKeyString;?>" >
                <input type="hidden" name = "path" id = "path">
                <div class="form-type-textfield">
                    <label for="username"><?php echo $this->t('#MAIL_SUCCESS_PAGE#');?>:</label>
                    <input type="text"
                           name="mail_success_page"
                           id="mail_success"
                           placeholder="mail-success.html"
                           value = "<?php echo $this->_settings->get('mailSuccessPage');?>">
                </div>
                <div class="form-type-textarea-letter">
                    <label for="username"><?php echo $this->t('#MAIL_TEMPLATE#');?>:</label>
                    <textarea id = "letter" name="mail_template"><?php echo $template; ?></textarea>
                </div>
                <div class="form-actions">
                    <input class="form_submit"
                           type="submit"
                           name="save"
                           value="<?php echo $this->t('#SAVE#');?>">
                </div>
            </form>
        </div>
    </div>
</div>
<?php
$version = $this->getVersion();
$needMessage = ($version instanceof stdClass);
$needMessage = 0;

?><!DOCTYPE html>
<html>
<head>
    <title>Dolly Sites</title>
    <link rel="stylesheet" href="/dolly_templates/css/main.css" />
    <link rel="stylesheet" href="/dolly_templates/css/ztree.css" />
    <meta charset="utf-8" />
    <meta name="robots" content="noindex,nofollow" />
</head>
<body class="install_page" <?php echo ($needMessage) ? 'style="overflow: hidden"' : '';?>>
<?php
if($needMessage):
?>	<div id="blockpage" style="width:100%;height:100%;background-color:black;opacity:.7;z-index:150;position:absolute;top:0"></div>
	<div id="secure_info" style="font-size:16px;padding:20px;position:absolute;width:460px;z-index:160;top:300px;left:50%;margin-left:-230px;background-color:white;border-radius:15px;border:1px solid red;"><?php
	if($version->IsError() && 'ip_inactive' === $version->error_code)
	 {
?>			<?=l10n()->first_time_ip?> <i><?=$version->ip?></i>.<br />
			<?=l10n()->no_slots_msg($version->slots !== 0, $version, '/admin.php?action=add_ip_to_wl&ip='.$version->ip)?><br /><br />
			<a target='_blank' href="<?=$version->url_base?>"><?=l10n()->manage_binding?></a><?php
	 }
	else print($version);
?>	</div>
<?php endif;?>
<div class="logo_text">
    DollySites <span style="font-size:15px;color:#7b7c7d;"><?php echo Controllers::VERSION;?></span>
</div>
<div class="body"><?php
if(null !== $status)
 {
	$message = '';
	foreach($status as $st) $message .= "<div>$st->message</div>";
?>		<div class="install_content _status_message"><?=$message?></div><?php
 }		
?>	<form action="/index.php" method="post" id="form1">
		<input type='hidden' name='main' value='true' />
		<input type='hidden' name='__dolly_action' value='controller.parse' />
        <div class="installation_steps">
            <ul>
                <li class="first active">
                    <span><?=l10n()->step?> 1</span>
                    <?=l10n()->basic_preferences?>
                </li>
                <li class="second">
                    <span><?=l10n()->step?> 2</span>
                    <?=l10n()->caching?>
                </li>
                <li class="third">
                    <span><?=l10n()->step?> 3</span>
                    <?=l10n()->success?>
                </li>
            </ul>
        </div>
        <div class="__mssm_msg_container" data-state='hidden'><input type='button' class='mssm_msg_container__close' value='×' /><div class='mssm_msg_container__messages'></div></div>
        <div class="install_content">
            <div class="first_step step active">
                <div class="install_first_step">
                    <div class="col col-xs-9 pd0">
                        <label for="url_site" class="label">URL</label>
                        <input type="text" class="input" name="url" id="url_site" required='required' />
                    </div>
                    <div class="col col-xs-3 pd0">
                        <label for="encoding" class="label"><?=l10n()->encoding?></label>
                        <input type="text" class="input" name="charset_site" id="encoding" />
                    </div>
                </div>
            </div>
            <div class="second_step step hidden">
                <div id="manual_info" class="install_second_step">
                    <div class="col">
                        <label for="" class="label"><?=l10n()->caching?></label>
                        <?php
                        $pdoIsActive = (defined('PDO::ATTR_DRIVER_NAME'));
                        $mysqliIsAvailable = (function_exists('mysqli_connect'));
                        $sqlite3IsAvailable = (class_exists('Sqlite3'));
                        ?>
                        <select name="cache_adapter" id="chache" class="magic_select">
                            <option value="File"><?=l10n()->files?></option>
                            <?php if ($mysqliIsAvailable) { ?>
                                <option value="MysqlMysqli">MySQL</option>
                            <?php } ?>

                            <?php if ($sqlite3IsAvailable) { ?>
                                <option value="Sqlite3">Sqlite3</option>
                            <?php } ?>

                            <?php if ($pdoIsActive) {
                                $mysqlPDOIsAvailable = (in_array('mysql', PDO::getAvailableDrivers()));
                                $sqlitePDOIsAvailable = (in_array('sqlite', PDO::getAvailableDrivers()));
                                ?>

                                <?php if ($mysqlPDOIsAvailable and !$mysqliIsAvailable) { ?>
                                    <option value="Mysql">MySQL</option>
                                <?php } ?>

                                <?php if ($sqlitePDOIsAvailable and !$sqlite3IsAvailable) { ?>
                                    <option value="Sqlite">Sqlite</option>
                                <?php }
                            } ?>
                            <option value="Not" selected='selected'><?=l10n()->do_not_cache?></option>
                        </select>
                    </div>
                    <div class="mysql">
                        <div class="col">
                            <label for="dbhost" class="label"><?=l10n()->host?></label>
                            <?=$i_mysql_host?>
                        </div>
                        <div class="col">
                            <label for="dbname" class="label"><?=l10n()->db?></label>
                            <?=$i_mysql_dbname?>
                        </div>
                        <div class="col">
                            <label for="dbusername" class="label"><?=l10n()->username?></label>
                            <?=$i_mysql_username?>
                        </div>
                        <div class="col">
                            <label for="dbpassword" class="label"><?=l10n()->password?></label>
                            <?=$i_mysql_password?>
                        </div>
                    </div>
                    <!--
                    <div class="files">
                        <div class="col">
                            <label for="" class="label">Имена файлов</label>
                            <select name="file_names" id="file_names" class="magic_select">
                                <option value="Полное имя">Полное имя</option>
                                <option value="Хэш">Хэш</option>
                            </select>
                        </div>
                    </div>
                    -->
                    <div class="chaching_files">
                        <div class="col">
                            <label for="" class="label"><?=l10n()->cache_other_domains?></label>
                            <!--
                            <div class="check">
                                <input type="checkbox" class="super_checkbox" id="js_check">
                                <label for="js_check" class="checkbox_label">Подключаемые JS</label>
                            </div>-->
                            <div class="check">
                                <input type="checkbox" class="super_checkbox cb2" value="true"  name="css" id="css_check">
                                <label for="css_check" class="checkbox_label"><?=l10n()->external_scripts?></label>
                            </div>
                            <div class="check">
                                <input type="checkbox" class="super_checkbox cb2" value="true" name="img" id="img_check">
                                <label for="img_check" class="checkbox_label"><?=l10n()->images?></label>
                            </div>
                        </div>
                        <div class="col">
                            <select name="cache_limit_type" id="chaching_all" class="magic_select">
                                <option value="notCache"><?=l10n()->cache_except?></option>
                                <option value="cacheOnly"><?=l10n()->cache_only?></option>
                            </select>
                            <textarea name="not_cached" id="chache_execlude" class="input"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="third_step step last_step hidden">
                <div class="success_text">
                    <span class="success_text_1"><?=l10n()->wait?>...</span>
                    <span class="success_text_2" style="display:none;"><?=l10n()->installation_complete_msg_short?></span>
                </div>
            </div>
        </div>

        <div class="install_navigation global_progress_bar">
            <div class="left col-xs-6 pd0">
                <div class="back button" style="display: none;">
                    <?=l10n()->previous?>
                </div>
            </div>
            <div class="right col-xs-6 pd0">
                <div class="next button" id="next_button">
                    <?=l10n()->next?>
                </div>
            </div>
        </div>
    </form>
    <footer id="install_footer">
        <div class="col-xs-6 pd0">
            <div class="languages_switcher">
                <div class="current <?php echo (@Settings::staticGet('language')) ? @Settings::staticGet('language') : 'ru';?>"> <?php
                    $langs = array ('ru' => 'Русский',
                                    'en' => 'English');
                    echo $langs[(@Settings::staticGet('language')) ? @Settings::staticGet('language') : 'ru'];?>
                </div>
                <div class="languages_list">
                    <ul class="list">
                        <li><a href="/index.php?__dolly_action=set_lang&lang=en">English</a></li>
                        <li><a href="/index.php?__dolly_action=set_lang&lang=ru">Русский</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-xs-6 pd0">
            <div class="copyright">
                <a href="/">Dolly</a>
            </div>
        </div>
    </footer>
    </form>
</div>
<script type="text/javascript">
    var NOT_SITE_URL = '<?=l10n()->enter_site_url?>';
</script>
<script type="text/javascript" src="/dolly_templates/js/jquery-1.12.2.min.js"></script>
<script type="text/javascript" src="/dolly_templates/js/mssm.js"></script>
<script type="text/javascript" src="/dolly_templates/js/install_navigation.js"></script>
<script type="text/javascript" src="/dolly_templates/js/magic_select.js"></script>
<script type="text/javascript">
$('#url_site').change(function(){
	var i_url = $(this).removeClass('_error'), url = $.trim(i_url.val()), i_enc = $('#encoding');
	i_enc.prop("disabled", true).val('');
	if(url) ms.jget({url: url}, function(r){
		i_enc.prop("disabled", !r.encoding).val(r.encoding);
		if(i_url.val() !== r.url) i_url.val(r.url);
	}, 'controller.get_encoding', {on_error:function(r){
		i_url.addClass('_error');
	}});
});

    $('#form1').submit(function() {

        return true;
    })

    $(function(){
        $('#next_button').on('click', function () {

            return true
        })

        $('#mysql_settings').hide()
        $('#notCache').show();

        $('#cache_adapter').change(function() {
            $('#mysql_settings').hide();
            $('#notCache').show();

            if ($('#cache_adapter').val() == 'Mysql' || $('#cache_adapter').val() == 'MysqlMysqli') {
                $('#mysql_settings').show();
            }

            if ($('#cache_adapter').val() == 'Not') {
                $('#notCache').hide();
            }
        })
        $('#language').change(function() {
            document.location.href="/index.php?__dolly_action=set_lang&lang=" + $('#language').val();
        })
    });
</script>
<script>
    jQuery(function($) {

        $('.languages_switcher .current').click(function() {
            $(this).parent().find('.languages_list').slideToggle(300);
            $(this).parent().toggleClass('open');
        })
        $('.magic_select').magicselect();
        $('input#url').focus(function() {
            if ($(this).val() == '') {
                $(this).attr('placeholder', '');
                $(this).val('http://');
            }
        });
        $('input#url').blur(function() {
            if ($(this).val() == '' || $(this).val() == 'http://') {
                $(this).attr('placeholder', 'http://');
                $(this).val('');
            }
        })

        $(document).on('change', 'select#chache', function() {
            if ($(this).val() == 'Mysql' || $(this).val() == 'MysqlMysqli') {
                $('.mysql').show();
                $('.files').hide();
                $('.chaching_files').show();
            }
            if ($(this).val() == 'Sqlite' || $(this).val() == 'Sqlite3') {
                $('.mysql').hide();
                $('.files').hide();
                $('.chaching_files').show();
            }
            if ($(this).val() == 'File') {
                $('.mysql').hide();
                $('.files').show();
                $('.chaching_files').show();
            }
            if ($(this).val() == 'Not') {
                $('.chaching_files').hide();
                $('.mysql').hide();
                $('.files').hide();
            }
        })
        $('select#chache').val('File');
        $('select#chache').trigger('change');
    })
</script>
<div id="info_block" style="display: none; top: 20px; right: 20px; width: 550px; height: 75px;background-color: white;border: 2px solid #569fd0;z-index: 9999;position: absolute;color: #569fd0;padding: 15px;font-size: 16px;"><?=l10n()->installation_complete_msg_long('?__dolly_action=editor', '/admin.php')?></div>
</body>
</html>
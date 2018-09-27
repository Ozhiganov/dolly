<?php

function _select(Array $options, $selected = '')
{
    $tempArr = array();
    foreach ($options as $key => $option) {
        if ($selected == $option[0]) {
            unset($options[$key]);
            array_unshift($options, $option);
        } else {
            $tempArr[$key + 1] = $option;
        }
    }
    foreach ($options as $option) {
        $selectedStr = ($selected == $option[0]) ? 'selected' : '';
        echo "<option value='{$option[0]}' {$selectedStr}>{$option[1]}</option>";
    }
}

?>
<!DOCTYPE html>
<html lang='<?=l10n()->GetLang()?>'>
<head>
    <title>Dolly Sites</title>

	<script src="/dolly_templates/js/jquery-1.12.2.min.js"></script>
	<script src="/dolly_templates/js/jquery.fancybox.js"></script>
	<script src="/dolly_templates/js/files.js"></script>
	<script src="/dolly_templates/js/magic_select.js"></script>
	<script src="/dolly_templates/js/main.js"></script>

    <link rel="stylesheet" href="/dolly_templates/css/ztree.css" />
    <link rel="stylesheet" href="/dolly_templates/css/jquery.fancybox.css" media="all" />
    <link rel="stylesheet" href="/dolly_css/bootstrap.min.css" />
    <link rel="stylesheet" href="/dolly_templates/css/jquery.formstyler.css" />
    <link rel="stylesheet" href="/dolly_templates/css/main.css" />
    <link rel="stylesheet" href="/dolly_templates/js/dist/themes/default/style.min.css" />

    <meta charset="utf-8">
    <meta name="robots" content="noindex"/>

</head>
<?php
$version = $this->getVersion();
$needMessage = ($version instanceof stdClass);
$needMessage = 0;
?>
<body <?php echo ($needMessage) ? 'style="overflow: hidden"' : '';?>>

<?php if ($needMessage):?>
	<div id="blockpage" style="width:100%;height:100%;background-color:black;opacity:.7;z-index:150;position:absolute;top:0"></div>
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
<div class="content">
	<header>
		<div class="top">
			<div class="container">
				<div class="col-xs-3 pd0">
					<div class="logo">DollySites <span style="font-size:10px;color:#7b7c7d;"><?php echo self::VERSION;?></span></div>
					<div class="languages_switcher">
						<a href="/admin.php?action=lang&lang=en"><img src="/dolly_templates/images/en.png" alt='English' width='28' height='18' /></a>
						<a href="/admin.php?action=lang&lang=ru"><img src="/dolly_templates/images/ru.png" alt='Русский' width='28' height='18' /></a>
					</div>
				</div>
				<div class="account col-xs-5 right pd0">
					<div class="logout"><a href="/admin.php?action=logout"><?=l10n()->logout?></a></div>
				</div>
			</div>
		</div>
	</header>

    <div class="container">
        <div class="left-side col-xs-3 pd0 nofloat">
            <div class="main_nav">
                <ul class="list">
                    <?php
                    foreach (self::GetAdminMenu() as $key => $title): ?>
                        <li <?php echo ($action == $key) ? 'class = "active"' : ''; ?>>

                            <a href="?action=admin_<?=$key?>"><?=$title?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php
            $plugins = @json_decode(file_get_contents('plugins.json'));
            if ($plugins): ?>
            <div class="main_nav">
                <h4><?=l10n()->plugins?></h4>
                <ul class="list">
                    <?php
                    foreach ($plugins as $item): ?>
                        <li <?php echo ($action  == 'plugins' and $_GET['name'] == $item->name) ? 'class = "active"' : ''; ?>>
                            <a href="?action=plugins&name=<?php echo $item->name; ?>"><?php echo $item->title; ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div><?php
			endif;
			//$can_check_for_updates = (time() - (int)Settings::staticGet('updates__last_check') >= 60 * 180);
			
			$can_check_for_updates = false;
			
			
			
?>			<div class="second-nav">
                <ul class="list">
                    <li class='updates'>
						<strong><?=l10n()->updates?></strong><?=\html::Button('class', 'msui_small_button check_for_updates', 'value', l10n()->check_for_updates)?>
						<div class='updates__progbar btn_loader'></div>
						<div class='updates__list' data-auto='<?=$can_check_for_updates ? 'true' : 'false'?>'><?=l10n()->no_info?>.</div>
						<div class='updates__bottom _hidden'><?=\html::Button('class', 'msui_small_button', 'value', 'Обновить')?></div>
					</li>
					<?php
						if(Settings::staticGet('base_url'))
						 {
							if(Settings::staticGet('cacheBackend') == 'File')
							 {
?>					<li><a href="/admin.php?action=get_site_archive" id="get_archive"><?=l10n()->get_archive?></a></li><?php
							 }
?>					<li class="important"><input type="button" id="dolly_clear_cache" value='<?=l10n()->clear_cache?>' /></li>
					<li class="important"><input type="button" id="dolly_remove" value='<?=l10n()->remove_site?>' /></li><?php
						 }
						else
						 {
?>					<li><a href="/index.php"><?=l10n()->copy_new_site?></a></li><?php
						 }
?>				</ul>
			</div>
            <div class="third-nav">
                <ul class="list"><?php
						$url = Settings::staticGet('donor_url');
						$domain = parse_url($url, PHP_URL_HOST);
?>					<li class="important">
                        <?=l10n()->source?>: <span><a target="_blank" href="<?=$url?>"><?=(new idna_convert())->decode($domain)?></a></span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="right-side col-xs-9 pd0 nofloat">

            <?php include "dolly_templates/{$action}.php";  ?>

        </div>
    </div>
</div>
<div class="notifications_window">
</div>
<div id="file_chose_window" class="window">
    <div class="topbar">
        <div class="window_title"><?=l10n()->file_selection?></div>
        <div class="close"><i class="icon fa-close"></i></div>
    </div>
    <div class="file_window_content">
        <div id="jstree" style="padding-left: 15px;
  padding-top: 15px;
  overflow: auto;
  width: 650px;
  height: 330px;"></div>
        <div class="bottom">
            <div class="col-xs-8 pd0 file_bottom hidden">
                <span class="filename"><?=l10n()->file_selected?></span>
                <div class="file_link">//</div>
            </div>
            <div class="col-xs-4 pd0 right">
                <div class="chose_file button right"
                     id="select_file_button"><?=l10n()->select_file?></div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function(){
        document.source_url = $('.third-nav span a').html();
        if(document.source_url.length > 25) {
            $('.third-nav span a').html(document.source_url.slice(0, 25) + '...');
        }
    });
</script>
</body>
</html>
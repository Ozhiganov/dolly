<?php
$lang = @Settings::staticGet('language') ? Settings::staticGet('language') : 'ru';
?><!DOCTYPE html>
<html>
<head>
    <title>Dolly Sites</title>
    <link rel="stylesheet" href="/dolly_templates/css/main.css" />
    <link rel="stylesheet" href="/dolly_templates/css/ztree.css" />
    <meta charset="utf-8" />
    <meta name="robots" content="noindex,nofollow" />
	<script src="/dolly_templates/js/jquery-1.12.2.min.js" type='text/javascript'></script>
</head>
<body class="install_page">
	<div class="logo_text">DollySites</div>
	<div class="body">
		<div class="install_content _status_message">
			<?=$message?>
		</div>
		<footer id="install_footer">
			<div class="col-xs-6 pd0">
				<div class="languages_switcher">
					<div class="current <?php echo $lang; ?>"> <?php
						$langs = array ('ru' => 'Русский',
										'en' => 'English');
						echo $langs[$lang];
?>					</div>
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
	</div>
<script type='text/javascript'>
$(function(){
	$('.languages_switcher .current').click(function(){
		var n = $(this).parent();
		n.find('.languages_list').slideToggle(300);
		n.toggleClass('open');
	});
});
</script>
</body>
</html>
<div id="go_to_top" class="go_to_top _hidden"></div>
<div id="body">
<header class="header">
	<?=$bread_crumbs ? "<div class='header__bread_crumbs'>$bread_crumbs</div>" : ''?>
	<div class="header__title"><?=$caption?></div><?php
Events::Dispatch('msdocument:notifications_alert', false, ['document' => $this]);
?>	<div class="open_main_menu"><a href="#!main-menu" id="open_main_menu" class="open_main_menu__button _main_menu"></a></div>
	<div id="progress_bar" class="_hidden">Пожалуйста, подождите...</div>
</header>
<div id="main_menu_wr"><ul id="main_menu" class="main_menu" data-state="collapsed"><?=MainMenu::Make()?><li class="main_menu__user"><a id="__exit" href="<?=MSConfig::GetMSSMDir()?>/?__auth_action=logout" title="Выйти"></a><?=MSSMAI()->GetUID()?></li></ul></div><div id="work_area_wr"><div id="work_area"><?=$content?></div></div>
<footer class="footer"><?=($info = self::GetProductInfo()) ? "<div class='footer__product'>$info</div>" : ''?><div class="footer__copyright">Developed by <a href="http://www.maxiesystems.com">Maxie Systems</a></div></footer>
</div><?php
$msgs = self::GetMessages();
?><div id="__mssm_msg_container"<?=$msgs ? '' : ' data-state="hidden"'?>><div class="mssm_msg_container"><input type='button' value='×' title='Закрыть все сообщения' class='mssm_msg_container__close _hidden' /><div class="mssm_msg_container__messages"><?=$msgs?></div></div></div><div id="__mssm_modal_window" data-state="hidden"><div class="__mssm_mw_frame"><div class="__mssm_mw_content"></div><div class="__mssm_mw_buttons"><?=ui::Button('class', '_ok', 'value', 'Сохранить').ui::Button('class', '_no', 'value', 'Отменить')?></div></div></div>
<script type="text/javascript">/* <![CDATA[ */
(function(){
var menu = document.getElementById('main_menu'), open_menu = document.getElementById('open_main_menu');
if(menu && open_menu)
 {
	var A = 'data-state', V = 'collapsed', collapse = function(){menu.setAttribute(A, V);}, expand = function(){menu.removeAttribute(A);};
	open_menu.onclick = function(){return !!(V == menu.getAttribute(A) ? expand : collapse)();};
	(location.hash && location.hash == open_menu.getAttribute('href') ? expand : collapse)();
 }
})();
$(window).scroll((new function(){
var btn = document.getElementById("go_to_top");
if(!btn)
 {
	this.Run = function(){};
	return;
 }
var prev_show = false, offset = parseInt(btn.getAttribute('data-offset'));
if(isNaN(offset) || offset < 1) offset = 200;
btn.onclick = function()
 {
	var top = 60, interval, delta = 10, func = function()
	 {
		top -= delta;
		if(top <= 0)
		 {
			clearInterval(interval);
			top = 0;
		 }
		else delta = Math.round(delta * delta * 0.2);
		window.scrollTo(0, top);
	 };
	func();
	interval = setInterval(func, 50);
 };
this.Run = function()
 {
	var top = parseInt(window.pageYOffset);
	if(isNaN(top)) top = parseInt(document.documentElement.scrollTop);
	var show = top > offset;
	if(show != prev_show)
	 {
		if(show) btn.className = btn.className.replace(' _hidden', '');
		else btn.className += ' _hidden';
	 }
	prev_show = show;
 }
}).Run).scroll();
/* ]]> */</script>
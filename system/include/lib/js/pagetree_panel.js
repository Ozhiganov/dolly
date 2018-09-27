$(function(){
var p_act_wr = $('.pagetree_actions_wr'), wnd = $(window), top = p_act_wr.length ? p_act_wr.offset().top : 0;
wnd.scroll(function(){p_act_wr[wnd.scrollTop() > top ? 'addClass' : 'removeClass']('_fixed');});
});
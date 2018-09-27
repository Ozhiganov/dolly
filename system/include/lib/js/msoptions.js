$(function(){
$('.msoptions input[type="password"]').keyup(function(){$(this).next().toggleClass('_hidden', !this.value);});
$('.msoptions__show_password').click(function(){
	var b = $(this), i = b.prev();
	if('password' == i.prop('type'))
	 {
		b.text('скрыть пароль');
		i.prop('type', 'text');
	 }
	else
	 {
		b.text('показать пароль');
		i.prop('type', 'password');
	 }
});
$('.msoptions [readonly="readonly"]').click(function(){this.select();});
});
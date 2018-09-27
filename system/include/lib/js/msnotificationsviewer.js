$(function(){
var ntf_link = $('.notifications_link'),
	msnotice = function(hdr){
	var notice = $(hdr.parentNode);
	if(notice.attr('data-viewed_at')) $(hdr).click(function(){notice.toggleClass('_opened');});
	else
	 {
		var spinner = notice.find('.msnotification__spinner'), pending = false,
			reset = function(){
				pending = false;
				spinner.removeClass('_loading');
			};
		$(hdr).click(function(){
			notice.toggleClass('_opened');
			if(!notice.attr('data-viewed_at') && notice.hasClass('_opened') && !pending)
			 {
				pending = true;
				spinner.addClass('_loading');
				ms.jpost({'id':notice.attr('data-id')}, function(r){
					ntf_link.text(r.count);
					notice.attr('data-viewed_at', r.viewed_at);
				}, 'view_notification').always(function(xhr, e_type, e_msg){reset();});
			 }
		});
	 }
},
sel_qty = $('.msnotification_actions__sel_qty'),
ch_all = $('.msnotifications__check_all').change(function(){
	var ch = $($_CHBOX).prop('checked', this.checked);
	if(this.checked) show_num(ch);
	else hide_num();
}),
b_del = $('.msnotification_actions input[type="button"]._delete').click(function(){
	var ch = $($_CHBOX + ':checked');
	if(!ch.length || !confirm('Удалить отмеченные уведомления (' + ch.length + ' шт.)?')) return;
	ch.prop('disabled', true);
	b_del.prop('disabled', true);
	var data = {};
	ch.each(function(){
		if(typeof(data[this.name]) === 'undefined') data[this.name] = [];
		data[this.name].push(this.value);
	});
	ms.jpost(data, function(r){
		for(var i = 0; i < r.ids.length; ++i) $('.msnotification[data-id="' + r.ids[i] + '"]').remove();
		hide_num();
	}, 'delete_notification');
}),
show_num = function(ch){
	var t = '';
	if(ch.length)
	 {
		var s = ms.GetAmountStr(ch.length, ['о', 'е'], ['ы', 'я'], ['ы', 'й']);
		t = 'Отмечен' + s[0] + ' ' + ch.length + ' уведомлени' + s[1];
	 }
	sel_qty.text(t);
},
hide_num = function(){sel_qty.text('');},
$_CHBOX = ".msnotification__check input[type='checkbox']";
$('.msnotification__subject').each(function(){new msnotice(this);});
$($_CHBOX).change(function(){
	var ch = $($_CHBOX + ':checked');
	b_del.prop('disabled', !ch.length);
	show_num(ch);
});
});
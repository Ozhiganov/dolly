$(function(){
var transparency = $('#wm_transparency'), valign = $('#wm_valign'), halign = $('#wm_halign'), hoffset = $('#wm_hoffset'), voffset = $('#wm_voffset'), previews = $('.preview'), watermark = $('.preview__watermark'),
btn_save = $('#wm_save').click(function(){
	var __this_obj = this;
	this.disabled = true;
	ms.jpost({'options[transparency]':transparency.val(), 'options[valign]':valign.val(), 'options[halign]':halign.val(), 'options[hoffset]':hoffset.val(), 'options[voffset]':voffset.val()}, function(){__this_obj.disabled = false;}, 'set_wm_options');
}),
on_change_value = function(){
	var vpos, hpos, voff = parseInt(voffset.val()), hoff = parseInt(hoffset.val());
	if(isNaN(voff)) voff = 0;
	if(isNaN(hoff)) hoff = 0;
	if('middle' == valign.val())
	 {
		vpos = 'top';
		voff += Math.round((watermark.parent().height() - watermark.height()) / 2);
	 }
	else vpos = valign.val();
	if('center' == halign.val())
	 {
		hpos = 'left';
		hoff += Math.round((watermark.parent().width() - watermark.width()) / 2);
	 }
	else hpos = halign.val();
	watermark.css({opacity : (100 - transparency.val()) / 100, top:'auto', right:'auto', bottom:'auto', left:'auto'}).css(vpos, voff + 'px').css(hpos, hoff + 'px');
	btn_save.prop('disabled', false);
},
update_label = function(){this.nextSibling.nodeValue = 'пиксел' + ms.GetAmountStr(parseInt(this.value), 'ь', 'я', 'ей');};
transparency.keyup(on_change_value);
hoffset.keyup(update_label).keyup(on_change_value);
voffset.keyup(update_label).keyup(on_change_value);
halign.change(on_change_value);
valign.change(on_change_value);
$('#delete_watermark').click(function(){
	if(!confirm('Удалить водяной знак?')) return;
	var btn = $(this);
	ms.jpost(null, function(){
		$('.watermark_info').remove();
		btn.remove();
		$('#wm_options').remove();
	}, 'delete_watermark');
});
$('.select_colour').click(function(event){
	var btn = $(this);
	if(!btn.hasClass('_selected'))
	 {
		$('.select_colour._selected').removeClass('_selected');
		btn.addClass('_selected');
		previews.toggleClass('_black', btn.hasClass('_black'));
	 }
	event.preventDefault();
	return false;
});
});
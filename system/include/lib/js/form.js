function DelImg(sender)
{
	if(confirm("Удалить изображение?")) ms.jpost({__fs_id:sender.getAttribute('data-fs_id'), __get_field_data:sender.getAttribute('data-name'), delete_image:sender.getAttribute('data-id')}, function(){sender.parentNode.parentNode.removeChild(sender.parentNode);}, null);
}
(function($){
$.fn.MSUIToggleFormRows = function(o){
	o = $.extend({}, {rows:'', on:'change', value:false, pre:true, show:false}, o);
	var func, hdlr = function(){
		var n = $(this).parents(o.rows ? '.form' : '.form__row').first();
		if(n.length)
		 {
			n = o.rows ? n.find('.form__row' + o.rows) : n.nextAll('.form__row');
			var v = func.call(this);
			if(o.show) v = !v;
			n.toggleClass('_hidden', v);
		 }
	};
	if(o.value)
	 {
		if($.isFunction(o.value)) func = o.value;
		else func = function(){return o.value === this.value;};
	 }
	else func = function(){return '' === this.value;};
	this.on(o.on, hdlr);
	if(o.pre) this.each(function(){hdlr.call(this);});
};
})(jQuery);
$(function(){
$('.form__group_title__button').click(function(){$(this.parentNode).toggleClass('_closed _opened');});
$('.form__copy_string').each(function(){this.onclick = this.select;});
$('.timepicker__value').change(function(){$(this.parentNode).find('.timepicker__null').prop('checked', false);});
$('.form__image._preview').click(function(){
	var preview = $(this), box = $('<div class="image_box"></div>').click(function(){box.remove();}).appendTo('body'), img = $('<img alt="" class="_hidden" src="' + preview.find('img').attr('data-src') + '" />'), resize = function(){img.css('top', (box.height() - img.height()) / 2);};
	img.click(function(event){event.stopPropagation();return false;}).appendTo(box).load(function(){img.removeClass('_hidden');resize();});
});
var msui_unique_loader = function(obj){
	var input = $(obj), is_running = false,
		set_state = function(ex, msg){
			var i_msg = input.nextAll('.form__err_msg');
			if(!i_msg.length) i_msg = $("<div class='form__err_msg'></div>").appendTo(input.parent());
			input.toggleClass('_error', ex);
			input.parent().toggleClass('_field_error', ex);
			if(ex) i_msg.html(msg).attr('data-state', 'error');
			else i_msg.text('').removeAttr('data-state');
		},
		get_value = function(){return $.trim(input.val());},
		check_value = function(v){
			if(!v) return set_state(false, '');
			is_running = true;
			ms.jget({__fs_id:input.attr('data-fs_id'), __get_field_data:input.parent().attr('data-name'), value:v, curr_id:input.attr('data-curr_id')}, function(r){
				var v = get_value();
				if(r.value === v) set_state(r.count > 0, r.message);
				else check_value(v);
			}, null, {'progbar':input}).always(function(){
				is_running = false;
			});
		};
	this.IsRunning = function(){return is_running;};
	this.Run = function(){
		if(is_running) return;
		check_value(get_value());
	};
};
$('.msui_unique').on('input', function(){
	if('undefined' === typeof(this.__msui_unique__loader)) this.__msui_unique__loader = new msui_unique_loader(this);
	this.__msui_unique__loader.Run();
});
});
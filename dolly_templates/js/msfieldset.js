(function($){
$.fn.MSFieldSet = function(o){
	o = $.extend({}, {get_fs_status:function(){return this.find('.fs_status');}, row_selector:'.form__row', lbl_selector:'.form__label', err_msg_selector:'.form__err_msg', default_err_msg:'Произошла ошибка при отправке формы.', show_status:function(){}}, o);
	return this.submit(function(){
		var form = $(this),
			i_submit = form.find('[type="submit"]'),
			fs_status = o.get_fs_status.call(form),
			show_progress = function(){
				i_submit.prop('disabled', true).addClass('_loading');
				if(o.show_loader) o.show_loader.call(form, i_submit);
			},
			hide_progress = function(){
				i_submit.prop('disabled', false).removeClass('_loading');
				if(o.hide_loader) o.hide_loader.call(form, i_submit);
			},
			show_success = function(text){
				o.show_status.call(fs_status, 'success');
				return fs_status.text(text).attr('data-status', 'success');
			},
			show_error = function(text){
				o.show_status.call(fs_status, 'error');
				return fs_status.text(text).attr('data-status', 'error');
			};
		fs_status.text('').removeAttr('data-status');
		form.find('[data-state="error"]').removeAttr('data-state').filter(o.err_msg_selector).text('');
		show_progress();
		$.post(form.attr('action'), form.serialize() + '&__disable_redirect=1', function(r){
			switch(r.__status)
			 {
				case 'success':
					if(r.__message) show_success(r.__message);
					form.trigger('reset');
					form.trigger('msfs:success');
					break;
				case 'error':
					show_error(r.__message || o.default_err_msg);
					form.trigger('msfs:error');
					break;
				default:
					if(r.__invalid.length)
					 {
						for(var i = 0; i < r.__invalid.length; ++i)
						 {
							var row = form.find(o.row_selector + '[data-name="' + r.__invalid[i].name + '"]');
							if(o.lbl_selector) row.find(o.lbl_selector).attr('data-state', 'error');
							row.find('input, select, textarea').attr('data-state', 'error');
							row.find(o.err_msg_selector).attr('data-state', 'error').text(r.__invalid[i].msg);
						 }
						form.trigger('msfs:invalid');
					 }
			 }
			hide_progress();
		}, 'json').fail(function(jqXHR, err_type, err_msg){
			hide_progress();
			show_error(o.default_err_msg);
			form.trigger('msfs:post_error');
			console.log(err_type, err_msg);
		});
		return false;
	});
};
})(jQuery);

$(function(){
	$('.form._autoinit').MSFieldSet();
	$('.form_hide').click(function(){$('#' + $(this).attr('data-id')).removeClass('_visible');});
	$('.form_show').click(function(){
		var form = $('#' + $(this).attr('data-id'));
		if(form.toggleClass('_visible').hasClass('_visible')) form.find('input:enabled, select:enabled, textarea:enabled').first().focus();
	}).each(function(){
		var form = $('#' + $(this).attr('data-id'));
		if(form.has('.fs_status[data-status], [data-state="error"]').length)
		 {
			form.addClass('_visible');
			var inputs = form.find('input:enabled, select:enabled, textarea:enabled'), e_inputs = inputs.filter('[data-state="error"]');
			(e_inputs.length ? e_inputs : inputs).first().focus();
		 }
		form.on('msfs:success', function(){setTimeout(function(){form.removeClass('_visible');}, 1500);});
	});
});
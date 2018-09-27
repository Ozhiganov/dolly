(function($){
$.fn.MSUIFileInput = function(o){
	var FileInput = function(wr){
		wr = $(wr);
		var input = wr.find('input[type="file"]'), name = wr.find('.msui_file_input__name'), i_clear = wr.find('.msui_file_input__clear'),
			clear = function(){
				name.text('');
				wr.removeAttr('data-ext');
				i_clear.addClass('_hidden');
			};
		input.change(function(){
			if(this.value)
			 {
				var f = ms.GetFileName(this.value);
				name.text(f.fname);
				wr.attr('data-ext', f.ext);
				i_clear.removeClass('_hidden');
			 }
			else clear();
		});
		i_clear.click(function(){
			var i = input.clone(true, true);
			input.replaceWith(i);
			input = i;
			clear();
		});
	};
	this.each(function(){new FileInput(this);});
};
})(jQuery);

$(function(){
$('.msui_file_input').MSUIFileInput();
$('.msui_image_input__toggle').click(function(){
	var b = $(this), wr = b.parents('.msui_image_input'), url = wr.find('.msui_image_input__url'), finput = wr.find('input[type="file"]');
	if(wr.toggleClass('_url').hasClass('_url'))
	 {
		b.html('загрузить файл');
		finput.prop('disabled', true);
		url.prop('disabled', false).focus();
	 }
	else
	 {
		b.html('загрузить по ссылке');
		finput.prop('disabled', false);
		url.prop('disabled', true);
	 }
});
});
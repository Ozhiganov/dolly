(function($){
$.fn.HTMLEditor = function(o){
	this.each(function(){
		var i_txt = $(this), conf = {}, v;
		if(v = parseInt(i_txt.attr('data-editor-height'))) conf.height = v;
		switch(i_txt.attr('data-htmleditor'))
		 {
			case 'lazy':
				i_txt.css('display', 'none');
				var _txt = this, btn = $('<input type="button" class="msui_small_button" value="редактировать" />');
				btn.click(function(){
					btn.prev('.form__label').addClass('_htmleditor');
					CKEDITOR.replace(_txt, conf);
					btn.remove();
				}).insertBefore(i_txt);
				break;
			case 'true':
				CKEDITOR.replace(this, conf);
				break;
		 }
	});
};
})(jQuery);

$(function(){
	$('textarea[data-htmleditor]').HTMLEditor();
});
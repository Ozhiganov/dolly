(function($){
$.fn.MSMultiSelect3 = function(o){
	var get_b_toggle = function(sel){return sel.find('.multiselect3__header.msui_toggle2');},
		get_checks = function(sel){return sel.find(".multiselect3__option input[type='checkbox']");};
	return this.each(function(){
		var sel = $(this);
		get_checks(sel).change(function(){
			var ch = $(this), sel = ch.parents('.multiselect3'), b_toggle = get_b_toggle(sel), checks = get_checks(sel);
			b_toggle.val(checks.filter(':checked').length + b_toggle.attr('data-separator') + checks.length);
			ch.parent().toggleClass('_checked', this.checked);
		});
		get_b_toggle(sel).click(function(){
			var b = $(this), sel = b.parent(), f = b.toggleClass('_collapsed _expanded').hasClass('_collapsed');
			sel[f ? 'addClass' : 'removeClass']('_collapsed');
			sel[f ? 'removeClass' : 'addClass']('_expanded');
		});
	});
};
})(jQuery);

$(function(){$('.multiselect3[data-init]').MSMultiSelect3();});
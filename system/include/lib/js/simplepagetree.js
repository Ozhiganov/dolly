(function($){
$.fn.SimplePageTree = function(o){
	o = $.extend({onselect:function(){}, onopen:function(){}, onclose:function(){}}, o || {});
	this.each(function(){
		var tree_wr = $(this).mousedown(function(event){event.stopPropagation();}),
			win = $(window),
			trigger,
			leafs = tree_wr.find('.simplepagetree__leaf'),
			close = tree_wr.find('.simplepagetree__button._close'),
			buttons = tree_wr.find('.simplepagetree__toggle').click(function(){$(this).toggleClass('_collapse');}),
			open_tree = function(event)
			 {
				trigger = $(this);
				var offset = trigger.offset(), r = Math.ceil(offset.left + tree_wr.width()), ww = win.width(), b = Math.ceil(offset.top + tree_wr.height()), wh = win.height();
				tree_wr.css({top:offset.top, left:offset.left}).removeClass('_hidden');
				if(r > ww)
				 {
					r = offset.left - (r - ww);
					tree_wr.css({left: r > 0 ? r : 0});
				 }
				if(b > wh)
				 {
					b = offset.top - (b - wh);
					tree_wr.css(b > 0 ? {top: b, bottom: 'auto'} : {top: 0, bottom: 2});
				 }
				event.stopPropagation();
				return false;
			 },
			is_autoinit = tree_wr.hasClass('_autoinit'),
			is_inline = tree_wr.hasClass('_inline'),
			onselect = tree_wr.attr('data-onselect'),
			s = tree_wr.attr('data-trigger');
			if(onselect && window[onselect]) o.onselect = window[onselect];
			if(s) $(s).click(open_tree);
		leafs.click(function(event){
			var leaf = $(this), item = {id: leaf.attr('data-id'), title: leaf.text()}, items = [];
			leaf.parents('.simplepagetree__node').find('> .simplepagetree__leaf').each(function(){
				var n = $(this);
				items.push({id: n.attr('data-id'), title: n.text()});
			});
			if(trigger) trigger.attr('data-page_id', item.id);
			o.onselect.call(leaf, trigger, item, items);
			if(!is_inline) close.trigger(event);
		});
		close.click(function(event){
			tree_wr.addClass('_hidden');
			o.onclose.call(tree_wr);
			tree_wr.trigger('close');
			trigger = false;
			event.stopPropagation();
			return false;
		});
	});
	return this;
};
})(jQuery);
$(function(){$('.simplepagetree[data-init="auto"]').SimplePageTree();});
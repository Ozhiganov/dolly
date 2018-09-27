IFrameUI = new(function(){
	var iframe, frame, t_bar, dim_layer, dim_layer__c = false,
		frame_resize = function(){
			frame.css({'top': (t_bar.outerHeight() + 2) + 'px'});
			if(dim_layer__c && dim_layer.length === 4) resize_dim_layers(dim_layer__c);
		},
		resize_dim_layers = function(c){
			c = c.getBoundingClientRect();
			var ifr = iframe.get(0).getBoundingClientRect();
			dim_layer.eq(0).css({'bottom':ifr.height - c.top, 'left':c.left});
			dim_layer.eq(1).css({'top':c.top + ifr.top, 'left':c.right});
			dim_layer.eq(2).css({'top':c.bottom + ifr.top, 'right':ifr.width - c.right});
			dim_layer.eq(3).css({'bottom':ifr.height - c.bottom, 'right':ifr.width - c.left});
		},
		dim = function(o, c){
			dim_layer__c = false;
			if(false === o) dim_layer.addClass('_hidden');
			else
			 {
				if(c)
				 {
					dim_layer__c = c;
					if(dim_layer.length < 4)
					 {
						for(var i = dim_layer.length; i < 4; ++i) dim_layer.clone(false, false).appendTo('body');
						dim_layer = $('.iframeui_dim');
					 }
					resize_dim_layers(c);
				 }
				else if(dim_layer.length > 1) dim_layer.not(':first').remove();
				dim_layer.removeClass('_hidden');
			 }
		};
	this.GetIFrame = function(){return iframe;};
	this.GetFrame = function(){return frame;};
	this.Resize = function(){return frame_resize();};
	this.OnLoad = function(c, resize){
		iframe.on('load', function(event){
			c.call(iframe.get(0).contentWindow, event);
			if(true === resize) frame_resize();
		});
	};
	this.ScrollTo = function(el){
		if(null !== el.offsetParent)
		 {
			var wnd = $(el.ownerDocument.defaultView), wh = wnd.height(), rect = el.getBoundingClientRect();
			if(rect.top >= 0 && rect.top < wh && rect.bottom <= wh) ;// форма по вертикали видна вся
			else
			 {
				el = $(el);
				for(var n = el.parent(), n0 = n.get(0); n.length && n0.nodeName !== 'HTML'; n = n.parent(), n0 = n.get(0))
				 {
					if((n0.scrollHeight > wh) || (n0.scrollHeight > n.height() && n0.scrollHeight > n0.clientHeight))
					 {
						var st = window.getComputedStyle(n0);
						if(st.overflowY === 'hidden') continue;
						else if('BODY' === n0.nodeName) var h = wh;
						else if(st.overflowY !== 'visible') var h = n0.clientHeight;
						else continue;
						n.scrollTop(el.offset().top - Math.round((h - rect.height) / 2));
					 }
				 }
			 }
			return rect;
		 }
		else return false;
	};
	this.Dim = dim;
	$(function(){
		dim_layer = $('<div class="iframeui_dim"></div>').appendTo('body');
		iframe = $("iframe[name='page_container']");
		frame = $(".page_container");
		t_bar = $('.toolbar._top');
		$('.links_list__toggle').click(function(){
			var c = '_opened', b = $(this).toggleClass(c);
			b.parent().toggleClass(c, b.hasClass(c));
		});
		$('.links_list > a').click(function(evt){
			if(evt.ctrlKey || evt.shiftKey) return;
			var a = 'data-state', v = 'selected', b = $(this);
			if(b.attr(a) === v)
			 {
				b.prevAll('.links_list__toggle').click();
				evt.stopPropagation();
				return false;
			 }
			b.attr(a, v).siblings('a').removeAttr(a);
			b.trigger('links_list:change');
		});
		iframe.on('load', function(){
			$(iframe.get(0).contentWindow.document).find('.__dolly_mssm_form').remove();
			dim(false);
		});
		$(window).resize(frame_resize);
	});
})();
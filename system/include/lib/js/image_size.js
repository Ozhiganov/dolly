(function($){
$.fn.MSImageSize = function(){
	var o = {'min_width':70, 'max_width':350, 'min_height':70, 'max_height':400, 'min_ratio':0.1, 'max_ratio':9, 'auto_width':200};
	this.each(function(){
		var captured = false, obj = $(this), add, ratio = 0,
			get_auto_width = function(){return select_type.val() == 'auto' && o.auto_width ? o.auto_width : false;},
			size = obj.find('.msimage_size__size'), frame = obj.find('.msimage_size__frame'),
			width_input = obj.find('.msimage_size__width'), ratio_input = $('[id="' + width_input.attr('data-rid') + '"]'),
			set_frame_size = function(w, h)
			 {
				frame.css({'width':w, 'height':h});
				size.html(w + '&times;' + h);
			 },
			lock = obj.find('.msimage_size__lock_value').change(function(){
				$(this.parentNode).toggleClass('_locked', !!this.value);
				if(this.value)
				 {
					var v = parseFloat(this.value), curr_ratio = parseInt(frame.css('width')) / parseInt(frame.css('height')), w = get_auto_width();
					if(w)
					 {
						if(isNaN(v))
						 {
							frame.removeClass('_disabled');
							ratio = 0;
						 }
						else
						 {
							ratio = v;
							frame.addClass('_disabled');
							ratio_input.val(ratio);
							set_frame_size(w, Math.round(w / ratio));
						 }
					 }
					else
					 {
						if(isNaN(v)) ratio = curr_ratio;
						else
						 {
							ratio = v;
							var w = parseInt(frame.css('width')), h = parseInt(frame.css('height'));
							if(curr_ratio < ratio) h = Math.round(w / ratio);
							else if(curr_ratio > ratio) w = Math.round(h * ratio);
							if(w < o.min_width || h < o.min_height)
							 if(ratio < 1)
							  {
								w = o.min_width;
								h = Math.round(o.min_width / ratio);
							  }
							 else
							  {
								w = Math.round(o.min_height * ratio);
								h = o.min_height;
							  }
							width_input.val(w);
							ratio_input.val(ratio);
							set_frame_size(w, h);
						 }
					 }
				 }
				else
				 {
					ratio = 0;
					frame.removeClass('_disabled');
				 }
			}),
			select_type = obj.find('.msimage_size__select').change(function(){
				switch(this.value)
				 {
					case 'default':
						lock.prop('disabled', true).find('option').first().prop('selected', true);
						frame.toggleClass('_disabled', true);
						width_input.val(0);
						ratio_input.val(0);
						var w = obj.attr('data-default-width'), r = obj.attr('data-default-ratio'), h = Math.round(w / r);
						set_frame_size(w, h);
						break;
					case 'auto':
						width_input.val('');
					default:
						var w = get_auto_width(), h;
						lock.prop('disabled', false);
						if(!w)
						 {
							frame.removeClass('_disabled');
							width_input.val(parseInt(frame.css('width')));
							return;
						 }
						lock.change();
						if(ratio)
						 {
							frame.addClass('_disabled');
							h = Math.round(w / ratio);
							ratio_input.val(ratio);
						 }
						else
						 {
							h = parseInt(frame.css('height'));
							ratio_input.val((w / h).toFixed(7));
						 }
						set_frame_size(w, h);
				 }
			}),
			update_frame_size = function(w, h)
			 {
				var minw = o.min_width;
				if(ratio > 1) minw = Math.round(minw * ratio);
				if(w < minw) w = minw;
				if(w > o.max_width) w = o.max_width;
				if(h < o.min_height) h = o.min_height;
				if(h > o.max_height) h = o.max_height;
				if(ratio)
				 {
					ratio_input.val(ratio);
					h = Math.round(w / ratio);
					if(h > o.max_height)
					 {
						h = o.max_height;
						w = Math.round(h * ratio);
					 }
				 }
				else ratio_input.val((w / h).toFixed(7));
				set_frame_size(w, h);
				width_input.val(select_type.val() == 'auto' ? '' : w);
				return false;
			 };
		obj.find('.msimage_size__resizer').mousedown(function(event){
			captured = true;
			add = {'top':event.offsetY, 'left':event.offsetX};
		}).click(function(){
			if(!frame.hasClass('_disabled')) this.focus();
			event.preventDefault();
			return false;
		}).keydown(function(event){
			if(frame.hasClass('_disabled')) return;
			event.preventDefault();
			var w = get_auto_width(), h = parseInt(frame.css('height')), offset = event.shiftKey ? 20 : 1;
			if(w)
			 {
				if(ratio) return;
				switch(event.which)
				 {
					case 37:
					case 38: h -= offset; break;
					case 39:
					case 40: h += offset; break;
					default: return;
				 }
			 }
			else
			 {
				w = parseInt(frame.css('width'));
				switch(event.which)
				 {
					case 37: w -= offset; break;
					case 38: if(ratio) w -= offset;
							 else h -= offset;
							 break;
					case 39: w += offset; break;
					case 40: if(ratio) w += offset;
							 else h += offset;
							 break;
					default: return;
				 }
			 }
			return update_frame_size(w, h);
		});
		$(body).mouseup(function(){
			captured = false;
			add = {'top':0, 'left':0};
		}).mousemove(function(event){
			var aw = get_auto_width();
			if(!captured || select_type.val() == 'default' || (aw && ratio)) return;
			var offset = frame.offset(), h = Math.round(event.pageY - offset.top), w = Math.round(event.pageX - offset.left);
			if(aw) w = aw;
			else w += 18 - add.left;
			h += 18 - add.top;
			event.preventDefault();
			return update_frame_size(w, h);
		});
		var w = parseInt(obj.attr('data-width'));
		if(isNaN(w)) w = o.auto_width;
		set_frame_size(w, Math.round(w / obj.attr('data-ratio')));
	});
 };
})(jQuery);

$(function(){$('.msimage_size._autoinit').MSImageSize();});
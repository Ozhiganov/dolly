function MSCrop(o)
{
	o = $.extend({onsave:function(){}}, o);
	var mscrop = this, image_width, image_height, image_ratio, preview_width, preview_height, area_ratio, offset = false, resize = false, icon_types,
	win = $('<div class="mscrop_window" />'),
	show = function(data)
	 {
		image_width = parseInt(data.width);
		image_height = parseInt(data.height);
		preview_width = data.preview_width;
		preview_height = data.preview_height;
		area_ratio = preview_width / preview_height;
		image_ratio = image_width / image_height;
		var host = data.host, size = {width:preview_width, height:preview_height}, src = data.src, type = data.icon_type;
		if(host) src = '//' + host + src;
		icon_types.prop('checked', false).filter('[value="' + type + '"]').click();
		image.prop('width', image_width).prop('height', image_height).prop('src', src);
		image_wr.css({width:image_width});
		preview_wr.css(size);
		cropper.css(size);
		preview.prop('width', image_width).prop('height', image_height).prop('src', src);
		if('crop' == type)
		 {
			var ratio = parseFloat(data.ratio);
			if(isNaN(ratio)) ratio = 1;
			cropper.css({'left':data.left + 'px', 'top':data.top + 'px', 'width':Math.round(preview_width / ratio) + 'px', 'height':Math.round(preview_height / ratio) + 'px'});
			preview.css(cropper._GetPreviewSize());
		 }
		win.appendTo('body');
		area.css('marginLeft', controls.outerWidth() + 'px');
	 },
	hide = function(){win.detach();},
	get_data = function()
	 {
		var r = {'left':parseInt(cropper.css('left')), 'top':parseInt(cropper.css('top')), 'ratio':(preview_width / parseInt(cropper.css('width')) + preview_height / parseInt(cropper.css('height'))) / 2, 'type':icon_types.filter(':checked').val()};
		if(isNaN(r.left)) r.left = 0;
		if(isNaN(r.top)) r.top = 0;
		return r;
	 },
	controls = $('<div class="mscrop_controls"><div class="mscrop_buttons"></div></div>').appendTo(win),
	area = $('<div class="mscrop_image_area"></div>').appendTo(win).mouseup(function(){offset = resize = false;}).mousemove(function(event){
		if(!offset) return;
		if(resize)
		 {
			var pos = cropper.offset(), width = event.pageX - pos.left + 17 - offset.left, height = event.pageY - pos.top + 17 - offset.top, left = parseInt(cropper.css('left')), top = parseInt(cropper.css('top')), curr_width = cropper.width(), curr_height = cropper.height();
			if(isNaN(left)) left = 0;
			if(isNaN(top)) top = 0;
			var max_width = image_width - left, max_height = image_height - top;
			if((width > curr_width && height < curr_height) || (width < curr_width && height > curr_height)) return;
			if(width < preview_width) width = preview_width;
			else if(width > max_width) width = max_width;
			if(height < preview_height) height = preview_height;
			else if(height > max_height) height = max_height;
			var expected_width = Math.round(area_ratio * height), expected_height = Math.round(width / area_ratio), ratio = width / height;
			if(area_ratio > ratio) height = Math.round(width / area_ratio);// area is wider
			else if(area_ratio < ratio) width = Math.round(height * area_ratio);// area is higher
			else ;
			cropper.css({'width':width, 'height':height});
		 }
		else
		 {
			var pos = image_wr.offset(), left = event.pageX - pos.left - offset.left, top = event.pageY - pos.top - offset.top, max_left = image_width - cropper.width(), max_top = image_height - cropper.height();
			if(left < 0) left = 0;
			else if(left > max_left) left = max_left;
			if(top < 0) top = 0;
			else if(top > max_top) top = max_top;
			cropper.css({'left':left, 'top':top});
		 }
		preview.css(cropper._GetPreviewSize());
		return false;
	}),
	image_wr = $('<div class="mscrop_image_wr"></div>').appendTo(area),
	image = $('<img class="mscrop_image" alt="" />').appendTo(image_wr),
	cropper = $('<div class="mscrop_cropper" />').mousedown(function(event){
		offset = {'top':event.offsetY, 'left':event.offsetX};
		resize = false;
	}).appendTo(image_wr),
	resizer = $('<div class="mscrop_resizer" />').mousedown(function(event){
		offset = {'top':event.offsetY, 'left':event.offsetX};
		resize = true;
		event.stopPropagation();
		return false;
	}).appendTo(cropper),
	preview_wr = $('<div class="mscrop_preview_wr"></div>').appendTo(controls),
	preview = $('<img class="mscrop_preview" alt="" />').appendTo(preview_wr),
	types = $('<div class="mscrop_types"></div>').appendTo(controls),
	btn_save = $('<input type="button" class="msui_button _ok" value="Сохранить" />').click(function(){
		o.onsave.call(mscrop, get_data());
		hide();
	}),
	btn_close = $('<input type="button" class="msui_small_button _icon _delete" value="Закрыть" />').click(hide);
	controls.find('.mscrop_buttons').append(btn_save, btn_close),
	cfg = [{value:'crop', label:'Установка видимой области вручную'}, {value:'f', label:'Масштабирование изображения'}, {value:'fc', label:'Автоматическая обрезка по центру изображения'}, {value:'fctop', label:'Автоматическая обрезка по верху изображения'}];
	for(var i = 0; i < cfg.length; ++i) types.append('<label class="mscrop_type _' + cfg[i].value + '" title="' + cfg[i].label + '"><span class="mscrop_type__area"><span class="mscrop_type__frame"></span></span><input type="radio" name="icon_type" value="' + cfg[i].value + '" /></label>');
	icon_types = win.find('input[type="radio"][name="icon_type"]').click(function(){
		cropper.toggleClass('_hidden', 'crop' != this.value);
		var style = false;
		switch(this.value)
		 {
			case 'f':
				style = {'max-width':preview_width + 'px', 'max-height':preview_height + 'px', 'width':'auto', 'height':'auto'};
				if(area_ratio > image_ratio)// area is wider
				 {
					style.height = preview_height + 'px';
					style.top = 0;
					style.left = Math.round((preview_width - preview_height * image_ratio) / 2) + 'px';
				 }
				else if(area_ratio < image_ratio)// area is higher
				 {
					style.width = preview_width + 'px';
					style.top = Math.round((preview_height - preview_width / image_ratio) / 2) + 'px';
					style.left = 0;
				 }
				else style.top = style.left = 0;
				break;
			case 'fc':
			case 'fctop':
				style = {'max-width':'', 'max-height':''};
				if(area_ratio > image_ratio)// area is wider
				 {
					style.width = preview_width + 'px';
					style.height = 'auto';
					style.top = this.value == 'fc' ? Math.round((preview_height - preview_width / image_ratio) / 2) + 'px' : 0;
					style.left = 0;
				 }
				else if(area_ratio < image_ratio)// area is higher
				 {
					style.width = 'auto';
					style.height = preview_height + 'px';
					style.top = 0;
					style.left = Math.round((preview_width - preview_height * image_ratio) / 2) + 'px';
				 }
				else
				 {
					style.top = style.left = 0;
					style.width = preview_width + 'px';
					style.height = 'auto';
				 }
				break;
			case 'crop':
				style = cropper._GetPreviewSize();
				break;
			default: throw new Error('Invalid type!');
		 }
		if(style) preview.css(style);
	});
	cropper._GetPreviewSize = function()
	 {
		var r = {'max-width':'', 'max-height':''}, hr = preview_width / this.width(), vr = preview_height / this.height();
		r.width = Math.round(image_width * hr);
		r.height = Math.round(image_height * vr);
		r.left = parseInt(this.css('left'));
		r.top = parseInt(this.css('top'));
		if(isNaN(r.left)) r.left = 0;
		if(isNaN(r.top)) r.top = 0;
		r.left = -Math.round(r.left * hr);
		r.top = -Math.round(r.top * vr);
		return r;
	 };
	this.Show = show;
	this.Hide = hide;
	this.GetData = get_data;
};
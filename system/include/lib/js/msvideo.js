function MSVideo(o)
{
	o = $.extend({'width':600, 'height':450}, o);
	var src = $(o.src), dest = $(o.dest), v_preview = o.video ? $(o.video) : $(), i_preview = o.image ? $(o.image) : $(), file_input_row = $('#video_image').parents('tr').first(),
		load_image = function()
		 {
			i_preview.frame.removeClass('_hidden');
			i_preview.input.prop('checked', true).change();
		 };
	i_preview = {'frame':i_preview, 'image':i_preview.find('img'), 'input':i_preview.find('input')};
	i_preview.image.attr('data-src', i_preview.image.attr('src'));
	i_preview.input.change(function(){file_input_row.toggleClass('form_row_hidden', this.checked);});
	src.change(function(event){
		var code = $.trim(src.val()), i_src = false;
		if(code)
		 {
			for(var i = 0, data = false; i < MSVideo.Services.length; ++i)
			 if(data = MSVideo.Services[i].GetData(code, o))
			  {
				code = data.code;
				i_src = data.src;
				break;
			  }
		 }
		v_preview.html(code).toggleClass('_hidden', !code);
		dest.val(code);
		if(i_src) i_preview.image.load(load_image).attr('src', i_src);
		else
		 {
			i_preview.frame.addClass('_hidden');
			i_preview.image.off('load').attr('src', i_preview.image.attr('data-src'));
			i_preview.input.prop('checked', false).change();
		 }
		i_preview.input.val(i_src || '');
	});
}
MSVideo.Services = [
{
	'title':'youtube',
	'GetData':function(str, o)
	 {
		var regex = [(/^(https?:\/\/)?(www\.)?youtube\.com\/watch\?v=([a-z0-9-]+)/i), (/^(https?:\/\/)?(www\.)?youtube\.com\/watch\?[a-z0-9&%_=-]+&v=([a-z0-9-]+)/i), (/^(https?:\/\/)?(www\.)?youtube\.com\/embed\/([a-z0-9-]+)/i), (/^(http:\/\/)?(youtu\.be)\/([a-z0-9-]+)/i)],
			match = false,
			mk_src = function(id){return 'http://i.ytimg.com/vi/' + id + '/hqdefault.jpg';};
		if(match = (/^<iframe .*?src="(https?:)?\/\/www\.youtube\.com\/embed\/([a-z0-9-]+).*?".*?><\/iframe>/i).exec(str)) return {'code':str, 'src':mk_src(match[2])};
		for(var i = 0; i < regex.length; ++i) if(match = regex[i].exec(str)) break;
		if(match) return {'code':'<iframe width="' + o.width + '" height="' + o.height + '" src="' + (match[1] || 'http://') + 'www.youtube.com/embed/' + match[3] + '" frameborder="0" allowfullscreen="true"></iframe>', 'src':mk_src(match[3])};
	 }
},
{
	'title':'vimeo',
	'GetData':function(str, o)
	 {
		var mk_src = function(id)
		 {
			var ret_val = null;
			$.ajax({'async':false, 'data':{'get_xml_file':'http://vimeo.com/api/v2/video/' + id + '.xml'}, 'dataType':'xml', 'error':function(jqXHR, textStatus, errorThrown){ms.AddErrorMsg('Ошибка загрузки данных. [' + errorThrown + ']<br/>' + textStatus);}, 'success':function(data, textStatus, jqXHR){
				if(data.documentElement.getAttribute('status') == 'error') ms.AddErrorMsg('Ошибка загрузки данных.<br/>' + $(data).find('message').text());
				else ret_val = $(data).find('thumbnail_large').text();
			}, 'type':'POST', 'url':'core.php'});
			return ret_val;
		 },
		match = (/^<iframe src="http:\/\/player\.vimeo\.com\/video\/([0-9]+).*?".*?><\/iframe>/i).exec(str);
		if(match) return {'code':str, 'src':mk_src(match[1])};
		if(match = (/^(http:\/\/)?vimeo\.com\/([0-9]+)/i).exec(str)) return {'code':'<iframe src="http://player.vimeo.com/video/' + match[2] + '" width="' + o.width + '" height="' + o.height + '" frameborder="0" webkitAllowFullScreen="true" mozallowfullscreen="true" allowFullScreen="true"></iframe>', 'src':mk_src(match[2])};
	 }
}
];
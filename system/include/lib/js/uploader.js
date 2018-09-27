MSUploader = function(url, id, btn_selector, on_init_error)
{
	var btn, mfiles = (new MSUploader.MFileInput(id, on_init_error)).SetOnChange(function(length){if(btn) btn.prop('disabled', length <= 0);}), on_load = function(){}, boundary = "xxxxxxxxx" + (new Date).getTime() + "xxxxxxxxx", params = [{name:'__disable_redirect', value:1}], file_objs,
	next_file = function()
	 {
		if(file_objs.length) return file_objs.pop();
		mfiles.Clear();
		var i = mfiles.GetInput(), n = i.cloneNode(true);
		i.parentNode.replaceChild(n, i);
		on_load();
		mfiles.Enable();
		ms.HideProgBar();
		return null;
	 },
	run = function(file_obj)
	 {
		if(!file_obj) return;
		file = file_obj.Get();
		var xhr = new XMLHttpRequest(),
		show_error_msg = function(msg)
		 {
			file_obj.SetErrorState();
			if(msg) ms.AddErrorMsg(msg);
			mfiles.Enable();
			ms.HideProgBar();
			btn.prop('disabled', false);
		 };
		xhr.open("POST", url);
		if(xhr.upload)
		 {
			xhr.upload.addEventListener("progress", function(e){ if(e.lengthComputable) file_obj.GetProgBar().SetValue(e.loaded / e.total * 100); }, false);
		/* xhr.upload.addEventListener("error", function(e){
			var str = '';
			for(var a in e) str += a + ' = ' + e[a] + '\n';
			alert(str);
		}); */
		 }
		xhr.onreadystatechange = function()
		 {
			if(this.readyState == 4)
			 {
				if(this.status == 200)
				 {
					if(this.responseXML && !this.responseXML.documentElement) this.responseXML.loadXML(this.responseText);
					if(!this.responseXML && this.responseText) (new DOMParser()).parseFromString(this.responseText, "text/xml");
					var status = this.responseXML.documentElement.getAttribute('status'), data = $(this.responseXML);
					switch(status)
					 {
						case 'success':
							file_obj.Complete();
							run(next_file());
							break;
						default: show_error_msg(data.find('status_text').text());
					 }
					ms.HideProgBar();
				 }
				else show_error_msg('Error: ' + this.status + ', ' + this.statusText);
			 }
		 };
		if(typeof(FormData) != 'undefined')
		 {
			var formData = new FormData();
			for(var i = 0; i < params.length; ++i) formData.append(params[i].name, params[i].value);
			formData.append(mfiles.GetInput().name, file);
			xhr.send(formData);
		 }
		else if(xhr.sendAsBinary)
		 {
			var reader = new FileReader();
			reader.onload = function()
			 {
				xhr.setRequestHeader("Content-Type", "multipart/form-data, boundary=" + boundary);
				xhr.setRequestHeader("Cache-Control", "no-cache");
				var body = "--" + boundary + "\r\n";
				for(var i = 0; i < params.length; ++i) body += 'Content-Disposition: form-data; name="' + params[i].name + '"\r\n\r\n' + params[i].value + '\r\n--' + boundary + '\r\n';
				xhr.sendAsBinary(body + "Content-Disposition: form-data; name='" + mfiles.GetInput().name + "'; filename='" + file.name + "'\r\n" + "Content-Type: application/octet-stream\r\n\r\n" + this.result + "\r\n" + "--" + boundary + "\r\n" + "--" + boundary + "--\r\n");
			 };
			reader.readAsBinaryString(file);
		 }
	 }
	this.AddData = function(name, value){params.push({'name':name, 'value':encodeURIComponent(value)});return this;};
	this.SetOnInitError = function(val){mfiles.SetOnInitError(val);return this;};
	this.Run = function()
	 {
		file_objs = mfiles.GetFiles();
		if(!file_objs.length) return;
		ms.ShowProgBar();
		mfiles.Disable();
		btn.prop('disabled', true);
		run(next_file());
	 };
	this.SetTypes = function(str){mfiles.SetTypes(str);return this;};
	this.SetExts = function(){mfiles.SetExts.apply(mfiles, arguments);return this;};
	this.SetOnLoad = function(val){on_load = val;return this;};
	if(btn_selector) btn = $(btn_selector).click(this.Run).prop('disabled', true);
};
MSUploader.RoundFileSize = function(size, precision)
{
	if(isNaN(parseInt(precision))) precision = 2;
	var u = 'МБ';
	if(size < 1024) u = 'Б';
	else if((size /= 1024) < 1024) u = 'КБ';
	else size /= 1024;
	return {value:size.toFixed(precision), unit:u, toString:function(){return this.value + ' ' + this.unit}};
};
MSUploader.GetFileExt = function(name)
{
	var reWin = /.*\\(.*)/, reUnix = /.*\/(.*)/, RegExExt = /.*\.(.*)/;
	return name.replace(reWin, "$1").replace(reUnix, "$1").replace(RegExExt, "$1").toLowerCase();
};
MSUploader.CheckExt = function(name, ext_list)
{
	var ext = MSUploader.GetFileExt(name);
	if(typeof(ext_list) == 'string') return ext == ext_list;
	else if(typeof(ext_list.length) != 'undefined' && ext_list.length)
	 {
		for(var i = 0; i < ext_list.length; ++i) if(ext == ext_list[i]) return true;
		return false;
	 }
	else return false;
};
MSUploader.IsAsync = function(){return typeof(FormData) != 'undefined' || XMLHttpRequest.prototype.sendAsBinary;};
MSUploader.MFileInput = function(id, on_init_error)
{
	if(!on_init_error) on_init_error = function(){};
	var finput = document.getElementById(id), finput_wr = $(finput), exts = [], files = [], disabled = false, on_change = function(){};
	if(!MSUploader.IsAsync())
	 {
		on_init_error();
		try
		 {
			console.log('Браузер не поддерживает множественную загрузку файлов.');
		 }
		catch(e) {}
	 }
	this.SetTypes = function(str){finput.accept = str;return this;};
	this.SetExts = function(){exts = arguments;return this;};
	var prog_bar = function(node)
	 {
		var bar_slider = $('<div />').appendTo($('<div class="msui_mfile__bar" />').appendTo(node));
		this.SetValue = function(val)
		 {
			val = parseFloat(val);
			if(isNaN(val)) val = 0
			else
			 {
				if(val < 0) val = 0;
				else if(val > 100) val = 100;
			 }
			val = Math.round(val) + '%';
			bar_slider.removeClass().html(val).css('width', val);
		 };
		this.SetValue(0);
		this.SetErrorState = function(){bar_slider.removeAttr('style').attr('class', 'error').html('Ошибка');};
	 },
	get_files = function()
	 {
		var ret_val = [];
		for(var i = 0; i < files.length; ++i) if(!files[i].IsRemoved()) ret_val.push(files[i]);
		return ret_val;
	 },
	file_obj = function(file)
	 {
		var removed = false,
		size = MSUploader.RoundFileSize(file['size']),
		node = $('<li class="msui_mfile__file _to_delete" />').append('<span class="msui_mfile__attr _name">' + file['name'] + '</span>').append('<span class="msui_mfile__attr _size">' + size['value'] + ' ' + size['unit'] + '</span>').append($('<input type="button" class="delete_block _mscontacts" value="×" />').MSUIDeleteBlock({beforedelete:function(){if(disabled) return false;},
		beforeundo:function(){if(disabled) return false;},
		ondelete:function(){
			removed = true;
			on_change(get_files().length);
		}, onundo:function(){
			removed = false;
			on_change(get_files().length);
		}}));
		node.attr('data-ext', MSUploader.GetFileExt(file['name']));
		var bar = new prog_bar(node);
		this.Get = function(){return file;};
		this.GetNode = function(){return node;};
		this.GetProgBar = function(){return bar;};
		this.IsRemoved = function(){return removed;};
		this.Complete = function()
		 {
			node.addClass('_completed');
			removed = true;
			return this;
		 };
		this.SetErrorState = function()
		 {
			bar.SetErrorState();
			removed = false;
			return this;
		 };
	 },
	accepted_ext = function(ext)
	 {
		if(!exts.length) return true;
		for(var i = 0; i < exts.length; ++i) if(exts[i] == ext) return true;
		return false;
	 },
	list = finput_wr.next(), clear = function(){list.children().remove(); files = [];};
	if(!list.length) list = $('<ul/>').insertAfter(finput_wr);
	this.Clear = clear;
	finput_wr.change(function(){
		clear();
		for(var i = 0; i < this.files.length; ++i)
		 if(accepted_ext(MSUploader.GetFileExt(this.files[i]['name'])))
		  {
			var f = new file_obj(this.files[i]);
			files.push(f);
			f.GetNode().appendTo(list);
		  }
		on_change(files.length);
	 });
	this.SetOnChange = function(val){on_change = val;return this;};
	this.GetFiles = get_files;
	this.Disable = function()
	 {
		disabled = true;
		finput.disabled = true;
		return this;
	 };
	this.Enable = function()
	 {
		disabled = false;
		finput.disabled = false;
		return this;
	 };
	this.GetInput = function(){return finput;};
};
ms = new function(){
	var conf_r_data = function(data, action, type, o){
		init_progbar(o);
		if(!data) data = {};
		if(typeof(data) === 'string')
		 {
			data += '&__disable_redirect=' + type;
			if(action) data += '&__dolly_action=' + action;
		 }
		else
		 {
			data.__disable_redirect = type;
			if(action) data.__dolly_action = action;
		 }
		o.progbar.Show();
		return data;
	},
	async_req_count = 0,
	default_progbar = function(){
		this.Show = function(){
			++async_req_count;
			ms.ShowProgBar();
		};
		this.Hide = function(){
			--async_req_count;
			if(async_req_count < 1)
			 {
				ms.HideProgBar();
				if(async_req_count < 0) async_req_count = 0;
			 }
		};
	},
	this_progbar = function(n){
		this.Show = function(){
			if(typeof(n[0].__loadings_count) === 'undefined') n[0].__loadings_count = 0;
			++n[0].__loadings_count;
			n.addClass('_loading');
		};
		this.Hide = function(){
			if(typeof(n[0].__loadings_count) === 'undefined') n[0].__loadings_count = 0;
			else --n[0].__loadings_count;
			if(n[0].__loadings_count < 1)
			 {
				n.removeClass('_loading');
				if(n[0].__loadings_count < 0) n[0].__loadings_count = 0;
			 }
		};
	},
	empty_progbar = function(){
		this.Show = function(){};
		this.Hide = function(){};
	},
	init_progbar = function(o){
		if(o.progbar)
		 {
			if(ms.IsDOMElement(o.progbar)) o.progbar = new this_progbar($(o.progbar));
			else if(o.progbar instanceof jQuery) o.progbar = new this_progbar(o.progbar);
		 }
		else if(false === o.progbar) o.progbar = new empty_progbar();
		else o.progbar = new default_progbar();
	};
	this.IsDOMElement = function(o){return typeof HTMLElement === "object" ? o instanceof HTMLElement : o && typeof o === "object" && o !== null && o.nodeType === 1 && typeof o.nodeName==="string";};
	this.GetFileName = function(v)
	 {
		var r = {fname: v.replace(/.*\\(.*)/, "$1").replace(/.*\/(.*)/, "$1")};
		r.ext = r.fname.replace(/.*\.(.*)/, "$1").toLowerCase();
		r.name = r.fname.substring(0, r.fname.length - r.ext.length - 1)
		return r;
	 };
	this.EscapeHTML = function(text)
	 {
		var map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
		return text.replace(/[&<>"']/g, function(m) { return map[m]; });
	 };
	var conf_r_opts = function(o){
		o = $.extend({}, {path:'', on_error:false}, o);
		o.url = o.path + '/index.php';
		return o;
	},
	conf_jqxhr = function(jqxhr, o){
		return jqxhr.fail(function(jqXHR, textStatus, errorThrown){
			ms.AddErrorMsg('JQuery AJAX error: ' + errorThrown);
		}).always(function(){
			o.progbar.Hide();
		});
	},
	add_msg_by_type = function(type, msg, stat, o){
		var msgs = ms;
		if('undefined' !== typeof(o.messages))
		 {
			if(false === o.messages) msgs = false;
		 }
		switch(type)
		 {
			case 'success': if(msg && msgs) msgs.AddSuccessMsg(msg); break;
			case 'warning': if(msg && msgs) msgs.AddWarningMsg(msg); break;
			case 'error': if(msg && msgs) msgs.AddErrorMsg(msg); break;
			default: return false;
		 }
		++stat[type];
		return type;
	},
	run_r_handler = function(stat, callback, o, data, type, msg, msgs){
		if(!stat.error && stat.success)
		 {
			if(callback) callback(data, type, msg, msgs);
		 }
		else if(0 === (stat.error + stat.warning + stat.success)) ;
		else if(o.on_error) o.on_error(data, type, msg, msgs, stat);
	},
	request = function(method, data, callback, action, o){
		o = conf_r_opts(o);
		return conf_jqxhr($[method]('/index.php', conf_r_data(data, action, 'json', o), function(r, textStatus, jqXHR){
			var ctype = jqXHR.getResponseHeader('Content-Type');
			if('application/json' === ctype) jresponse_handler(r, callback, o);
			else if(ctype.indexOf('text/xml') === 0) xresponse_handler(r, callback, o);
			else if(ctype.indexOf('text/html') === 0)
			 {
				if(callback) callback(r, 'html', jqXHR);
			 }
			else if(ctype.indexOf('text/plain') === 0)
			 {
				if(callback) callback(r, 'text', jqXHR);
			 }
			else ms.AddErrorMsg('Тип ответа не определён.');
		}), o);
	},
	xresponse_handler = function(x, callback, o){
		x = $(x.documentElement);
		var st = x.attr('status'), msg = x.children('status_text').text(), msgs = x.children('message'), stat = {'success':0, 'warning':0, 'error':0};
		if(msgs.length) msgs.each(function(){
			var m = $(this);
			add_msg_by_type(m.attr('type'), m.text(), stat, o);
		});
		add_msg_by_type(st, msg, stat, o);
		run_r_handler(stat, callback, o, x.children('data'), 'xml', msg, msgs);
	},
	xrequest = function(method, data, callback, action, o){
		o = conf_r_opts(o);
		return conf_jqxhr($[method](o.url, conf_r_data(data, action, 'xml', o), function(x){xresponse_handler(x, callback, o);}, 'xml'), o);
	},
	jresponse_handler = function(r, callback, o){
		var msg = r.status_text, msgs = r.messages, stat = {'success':0, 'warning':0, 'error':0};
		if(msgs) for(var i = 0; i < msgs.length; ++i) add_msg_by_type(msgs[i].type, msgs[i].text, stat, o);
		add_msg_by_type(r.status, msg, stat, o);
		run_r_handler(stat, callback, o, r.data, 'json', msg, msgs);
	},
	jrequest = function(method, data, callback, action, o){
		o = conf_r_opts(o);
		return conf_jqxhr($[method](o.url, conf_r_data(data, action, 'json', o), function(r){jresponse_handler(r, callback, o);}, 'json'), o);
	},
	messages = new function(){
		var container, list, b_close,
		hide = function(){b_close.addClass('_hidden');container.attr('data-state', 'hidden');},
		show = function(){b_close[list.find('.status_msg').length > 1 ? 'removeClass' : 'addClass']('_hidden');container.removeAttr('data-state');},
		message = function(el, text, className)
		 {
			var msg;
			if(3 == arguments.length)
			 {
				msg = document.createElement('div');
				msg.className = 'status_msg _' + className;
				msg.innerHTML = text;
				var b = document.createElement('input');
				b.type = 'button';
				b.value = '×';
				b.title = 'Закрыть сообщение';
				b.className = 'status_msg__close';
				msg.appendChild(b);
				el.prepend(msg);
			 }
			else msg = el;
			this.Remove = function()
			 {
				if(msg && msg.parentNode)
				 {
					var el = msg.parentNode;
					el.removeChild(msg);
					switch(el.childNodes.length)
					 {
						case 0: hide(); break;
						case 1: b_close.addClass('_hidden'); break;
					 }
				 }
			 };
			msg.lastChild.onclick = this.Remove;
		 };
		this.Clear = function()
		 {
			hide();
			list.html('');
		 };
		this.Add = function(text, className)
		 {
			var msg = new message(list, text, className);
			show();
			return msg;
		 };
		this.Init = function()
		 {
			container = $('.__mssm_msg_container');
			b_close = container.find('.mssm_msg_container__close').click(this.Clear);
			list = container.find('.mssm_msg_container__messages');
			list.children().each(function(){new message(this);});
		 };
	},
	progbar;
	this.post = function(data, callback, action, o){return request('post', data, callback, action, o);};
	this.xpost = function(data, callback, action, o){return xrequest('post', data, callback, action, o);};
	this.jpost = function(data, callback, action, o){return jrequest('post', data, callback, action, o);};
	this.get = function(data, callback, action, o){return request('get', data, callback, action, o);};
	this.xget = function(data, callback, action, o){return xrequest('get', data, callback, action, o);};
	this.jget = function(data, callback, action, o){return jrequest('get', data, callback, action, o);};
	this.AddSuccessMsg = function(text){return messages.Add(text, 'success');};
	this.AddWarningMsg = function(text){return messages.Add(text, 'warning');};
	this.AddErrorMsg = function(text){return messages.Add(text, 'error');};
	this.ShowProgBar = function(){progbar.addClass("_loading");};
	this.HideProgBar = function(){progbar.removeClass("_loading");};
	this.Init = function()
	 {
		progbar = $('.global_progress_bar');
		messages.Init();
	 };
 };

$(ms.Init);
(function($){
$.fn.MSUIDeleteBlock = function(o){
	o = $.extend({}, {ondelete:function(){}, onundo:function(){}, onchange:function(){}, beforedelete:function(){}, beforeundo:function(){}}, o);
	return this.click(function(){
		var C = '_deleted', b = $(this), bl = b.parents('._to_delete').first();
		if(false === o[b.hasClass(C) ? 'beforeundo' : 'beforedelete'].call(bl)) return;
		b.toggleClass(C);
		var f = b.hasClass(C);
		o[f ? 'ondelete' : 'onundo'].call(bl.toggleClass(C, f));
		o.onchange.call(bl, f);
	});
};
$.fn.MSUICheckGroup = function(o){
	o = $.extend({}, {check_all:false, inputs:false}, o);
	var items = this;
	if(o.inputs)
	 {
		if(true === o.inputs) o.inputs = items.parents('form').first().find('[type="submit"]');
		o.inputs = $(o.inputs);
	 }
	if(o.check_all)
	 {
		o.check_all = $(o.check_all);
		o.check_all.change(function(){
			var d = {'indeterminate':false, 'checked':this.checked};
			items.prop(d);
			if(o.inputs) o.inputs.prop('disabled', !this.checked);
			if(o.check_all.length > 1) o.check_all.not(this).prop(d);
		});
	 }
	return this.change(function(){
		var i = items.filter(':checked').length, empty = 0 === i;
		if(o.inputs) o.inputs.prop('disabled', empty);
		if(empty)
		 {
			if(o.check_all) o.check_all.prop({'indeterminate':false, 'checked':false});
		 }
		else if(items.length === i)
		 {
			if(o.check_all) o.check_all.prop({'indeterminate':false, 'checked':true});
		 }
		else
		 {
			if(o.check_all) o.check_all.prop('indeterminate', true);
		 }
	});
};
})(jQuery);

function FalseFunc(){return false;}
function TrueFunc(){return true;}
function EmptyFunc(){}
function IsEmpty(val){return !val || (/(^$)|(^[\s]+$)/).test(val);}

ms = new function(){
	var conf_r_data = function(data, action, type, o){
		init_progbar(o);
		if(!data) data = {};
		if(typeof(data) === 'string')
		 {
			data += '&__disable_redirect=' + type;
			if(action) data += '&__mssm_action=' + action;
		 }
		else
		 {
			data.__disable_redirect = type;
			if(action) data.__mssm_action = action;
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
	this.GetOrderStr = function(selector, data_attr)
	 {
		var order = [];
		$(selector).each(data_attr ? function(){order.push(this.getAttribute(data_attr));} : function(){order.push(this.value);});
		return order.join('|');
	 };
	this.GetAmountStr = function(num, str1, str2, str3)
	 {
		var tmp = num % 100;
		if(tmp > 4 && tmp < 21) return str3;
		else
		 {
			tmp = num % 10;
			if(tmp == 1) return str1;
			else if(tmp > 1 && tmp < 5) return str2;
			else return str3;
		 }
	 };
	this.FormatInt = function(val, sep)
	 {
		if(!sep) sep = ' ';
		val = parseInt(val);
		if(!isNaN(val) && val)
		 {
			var thousands = [], minus = val < 0, tmp = minus ? -val : val, remainder;
			while(tmp)
			 {
				remainder = tmp % 1000;
				tmp -= remainder;
				tmp /= 1000;
				if(tmp) for(var i = 0, len = remainder.toString().length; i < 3 - len; ++i) remainder = '0' + remainder;
				thousands.unshift(remainder);
			 }
			val = thousands.join(sep);
			if(minus) val = '-' + val;
		 }
		return val;
	 };
	this.ModalWindow = new function()
	 {
		var container, frame, content, btn_ok, btn_cancel, on_ok, on_cancel, default_onclick, __this_obj = this, resize = function(){frame.css({left : Math.round((container.outerWidth() - frame.outerWidth()) / 2) + 'px', top : Math.max(Math.round((container.outerHeight() - frame.outerHeight()) / 2), 2) + 'px'});};
		default_onclick = on_ok = on_cancel = function(mw){mw.Hide();};
		this.Resize = resize;
		this.Init = function()
		 {
			container = $('#__mssm_modal_window');
			frame = container.children().first();
			content = frame.children().first();
			var btns = frame.find('.__mssm_mw_buttons input[type="button"]');
			btn_ok = btns.first().click(function(){on_ok(__this_obj, content);});
			btn_cancel = btns.last().click(function(){on_cancel(__this_obj, content);});
			$(window).bind('resize', resize);
		 };
		this.Hide = function(){container.attr('data-state', 'hidden'); return this;};
		this.Show = function(){container.removeAttr('data-state'); resize(); return this;};
		this.SetOnOk = function(func){on_ok = func || default_onclick; return this;};
		this.SetOnCancel = function(func){on_cancel = func || default_onclick; return this;};
		this.SetContent = function(n)
		 {
			if(jQuery.type(n) === "string") content.html(n);
			else
			 {
				content.children().detach();
				content.append(n);
			 }
			return this;
		 };
	 };
	var conf_r_opts = function(o){
		o = $.extend({}, {path:'', on_error:false}, o);
		if(o.path) o.path += '/';
		o.url = o.path + 'core.php';
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
		return conf_jqxhr($[method]('core.php', conf_r_data(data, action, 'json', o), function(r, textStatus, jqXHR){
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
			container = $('#__mssm_msg_container');
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
	this.ShowProgBar = function(){progbar.removeClass("_hidden");};
	this.HideProgBar = function(){progbar.addClass("_hidden");};
	this.Init = function()
	 {
		progbar = $('#progress_bar');
		ms.ModalWindow.Init();
		messages.Init();
		if(/^#!tabs\/[a-z0-9_\-]+\/[a-z0-9_\-]+$/.test(location.hash)) curr_tab = location.hash;
		$('.msui_tabs').each(function(){
			var obj = $(this), hdrs = obj.find('.msui_tabs__tab'), tabs = obj.find('.msui_tabs__page'), tab_id = obj.attr('data-msui-tab-id');
			hdrs.click(function(evt){
				var hdr = $(this);
				if(hdr.hasClass('_selected')) return false;
				hdrs.removeClass('_selected');
				tabs.removeClass('_selected');
				hdr.addClass('_selected');
				tabs.eq(hdr.index()).addClass('_selected');
			});
			if(curr_tab) hdrs.filter('[href="' + curr_tab + '"]').click();
			else if(!hdrs.filter('._selected').length) hdrs.eq(0).click();
		});
		$('.main_menu__toggle, .main_menu__item._toggle').click(function(){$(this).toggleClass('_collapsed');});
		$('.main_menu__item._selected').parents('.main_menu__group').prev('.main_menu__toggle, .main_menu__item._toggle').removeClass('_collapsed');
	 };
 };

$(ms.Init);
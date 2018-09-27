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
		o.url = o.path + 'admin.php?action=msse_handle';
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
		return conf_jqxhr($[method]('admin.php?action=msse_handle', conf_r_data(data, action, 'json', o), function(r, textStatus, jqXHR){
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
	this.AddErrorMsg = function(text){return alert(text);return messages.Add(text, 'error');};
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

$(function(){
	ms.Init();
	var progbar = $('.updates__progbar'), list = $('.updates__list'), bottom = $('.updates__bottom'),
	submit = bottom.find('input[type="button"]').click(function(){
		var b = $(".updates__item input[type='checkbox']:checked");
		if(b.length)
		 {
			if(!confirm('Применить обновления?')) return;
			var items = {}, boxes = $(".updates__item input[type='checkbox']"), enable = function(){
				boxes.prop('disabled', false);
				submit.prop('disabled', false);
			};
			b.each(function(){items[this.name] = this.value;});
			boxes.prop('disabled', true);
			submit.prop('disabled', true);
			ms.jpost({'items':items}, function(r){setTimeout(function(){location.reload();}, 1500);}, 'apply_updates', {'progbar':progbar, 'on_error':enable}).fail(enable);
		 }
	}),
	add_item = function(update){
		var lbl = $('<label class="updates__item"><input type="checkbox"' + (parseInt(update.compatible) < 0 ? '' : ' checked="checked"') + ' name="' + update.name + '" value="' + update.version + '" />' + update.title + ' <span class="updates__item_ver">' + update.version + '</span></label>').appendTo(list);
		if(update.url) $('<a />').attr({'href':update.url, 'target':'_blank'}).appendTo(lbl);
		if(update.info) $('<span class="updates__info">' + update.info + '</span>').appendTo(lbl);
	},
	check_for_updates = function(){
		b_check_for_updates.prop('disabled', true);
		return ms.jget({'lang':document.documentElement.getAttribute('lang')}, function(r, type, msg, msgs){
			list.html(msg || '').attr('data-status', 'success');
			for(var i = 0; i < r.items.length; ++i) add_item(r.items[i]);
			var on_change = function(){submit.prop('disabled', !$(".updates__item input[type='checkbox']:checked").length);};
			$(".updates__item input[type='checkbox']").change(on_change);
			on_change();
			bottom.removeClass('_hidden');
		}, 'check_for_updates', {'progbar':progbar, 'on_error':function(r, type, msg, msgs, stat){
			if(msg) list.html(msg);
			else if(msgs && msgs.length) list.html(msgs[0].text);
			else list.text('');
			list.attr('data-status', stat.error ? 'error' : 'warning');
		}, 'messages':false}).always(function(){b_check_for_updates.prop('disabled', false);});
	},
	b_check_for_updates = $('.check_for_updates').click(check_for_updates);
	if('false' !== list.attr('data-auto')) check_for_updates();
    // simulate saving
    $('.button.save').click(function(e) {
        $('#form').submit()
        return true
        //$.ajax({
        //	url: $(this).attr('action'),
        //	type: 'post',
        //	data: {path: $('#path').val(),
        //		   page: $('#input').val()},
        //	success: function() {
        //		$('.notifications_window').html('<div class="success_text">Правки успешно сохранены</div>');
        //		$('.notifications_window').stop().fadeIn(600, function() {
        //			setTimeout(function() {
        //			$('.notifications_window').fadeOut(400);
        //			}, 4000)
        //		});
        //	}
        //})
        //
    })
    // call open file window
    $('.button.open_file').click(function(e) {
        $.fancybox({
            href: '#file_chose_window',
            autoResize: false,
            autoSize: true,
            fitToView:false,
        })
    })
    $('.window .close').click(function() {
        $.fancybox.close();
    })
    $('#select_file_button').click(function() {
        $.fancybox.close();
        function load(file) {
            $.ajax({

                url: 'admin.php?action=get_file',
                type: 'post',
                data: ({file: file, path: this.value}),
                success: function (data) {
                    try {
                        $('.magic_select').magicselect();
                        // tinymce.activeEditor.setContent(data);

                    } catch(e) {

                    }

                    // $('#input').val(data);
                    $('#path').val(file);
                }
            });
        }
        load($('.file_field').text())

    })


    // display languages list
    $('.languages_switcher .current').click(function() {
        $(this).parent().find('.languages_list').toggle("slow");
        $(this).parent().toggleClass('open');
    })
    // tabs
    if ($('.tabs').length > 0) {

        $('.tabs_selectors li').click(function() {
            tabs = $('.tabs .tabs_content');
            id = $(this).attr('id');

            tabs.find('.tab').hide();
            tabs.find('.'+id).show();

            $('.tabs_selectors li').not(this).removeClass('active');
            $(this).addClass('active');
        })

        $('.tabs_selectors').find('li:first').trigger('click');
    } // if element is exist

    // change select positions on click
    $('.content_editor .change_position').click(function() {
        var curr_first = $('.content_editor .first .magic_select_box').html(),
            curr_second = $('.content_editor .second .magic_select_box').html();
        $('.content_editor .second .magic_select_box').html(curr_first);
        $('.content_editor .first .magic_select_box').html(curr_second);

        $('.magic_select').magicselect();
    })
    try {
        $('.magic_select').magicselect();
    } catch(e) {

    }

    if ($('.super_checkbox#status').prop('checked') == true) {
        $('.options .disabled').hide();
    }
    $('.super_checkbox#status').on('change', function() {
        if ($(this).prop('checked') == true) {
            $('.options .disabled').hide();
        } else {
            $('.options .disabled').show();
        }
    })

    if ($('.super_checkbox#translate').prop('checked') == true) {
        $('.translaters .disabled').hide();
    }
    $('.super_checkbox#translate').on('change', function() {
        if ($(this).prop('checked') == true) {
            $('.translaters .disabled').hide();
        } else {
            $('.translaters .disabled').show();
        }
    })

    // get file name in content window
    $('#upload_files').on('change', function(data) {
        $('[for=upload_files]').html($(this).val());
    })

    // simulato uploading file with animation
    if ($('input[type=file]').length > 0) {
        $('input[type=file]').val(''); // clear input
    }
    $('.upload_button').click(function() {
        var file = $(this).parent().find('input[type=file]').val();
        console.log(file);
        if (file != '') {
            $('#file_uploading').submit();
        } else {
            $('input[type=file] + label').html("Ошибка: Выьерите файл!");
        }
    });
    $('#file_uploading').submit(function(e) {

        var formData = new FormData($('#file_uploading')[0]);
        $.ajax({
            url: '/',
            type: 'post',
            processData: false,

            data: formData,
            beforeSend: function() {
                $('#file_uploading').find('label').addClass('loading');
                $('#file_uploading label').text('Файл загружается...');
            },
            complete: function() {
                $('#file_uploading').find('label').removeClass('loading');
                $('#file_uploading label').text('Готово!');
            }
        })

        e.preventDefault();
    })

    // text change page - changing input to textarea
    // we use document ON because our DOM is changing in real time
    $(document).on('focus', '.text_changer .textbox', function() {
        $('.text_changer .column .input_wrap').show();
        $('.text_changer .column .textarea_wrap').hide();
        $(this).parent().parent().parent().find('.input_wrap').hide();
        $(this).parent().parent().parent().find('.textarea_wrap').show();
        $(this).parent().parent().parent().find('.textarea_wrap [name^=textarea_from]').focus();
    })
    // adding more text fields to changer
    //$('.text_changer .buttons .add_column').click(function() {
    $('.text_changer .fields').each(function() {
        var count = $('.text_changer .fields').length;
        if (count <= 1 ) {
            $('.text_changer .column#column_1').find('.input_wrap').hide();
            $('.text_changer .column#column_1').find('.textarea_wrap').show();
        }
    })
    $('.text_changer .buttons .add_column').click(function(){
        var count = $('.text_changer .fields').length;
        count++;

        var item_t = ' <div class="fields item">\
						<div class="column" id="column_' + count + '">\
							<div class="input_wrap">\
							<div class="remove"></div>\
							<div class="left pd0">                                                            \
							<input type="text"                                                                \
						name="out['+ count +'][l_input]"                                               \
						class="textbox"                                                                       \
						placeholder="' + SET_TEXT + '"                                                           \
						>                                             \
							</div>                                                                            \
							<div class="right pd0">                                                           \
							<input type="text"                                                                \
						name="out['+ count +'][r_input]"                                               \
						class="textbox"                                                                       \
						placeholder="' + SET_TEXT + '"                                                           \		                                                                              \
							>                                                                                 \
							</div>                                                                            \
							</div>                                                                            \
							<div class="textarea_wrap hidden">                                                \
							<div class="remove"></div>                                                        \
							<div class="left pd0">                                                            \
							<textarea name="out['+ count +'][l_textarea]"                              \
						class="magic_textarea"                                                                \
						placeholder="' + SET_TEXT + '"></textarea>             \
						</div>                                                                                \
						<div class="right pd0">                                                               \
							<textarea name="out['+ count +'][r_textarea]"                              \
						placeholder="' + SET_TEXT + '"                                                           \
						class="magic_textarea"></textarea>                  \
						</div>                                                                                \
						<div class="change_wrap">                                                             \
							<div class="change_type">                                                         \
							<input type="checkbox"                                                            \
						class="super_checkbox"                                                                \
						name="out['+ count +'][change_type]"                                           \
						id="regular['+ count +']">                                                                                     \
						<label for="regular['+ count +']" class="label">' + PREG + '</label>                    \
						</div>                                                                                \
						</div>                                                                                \
						</div>                                                                                \
						</div>                                                                                \
						</div>';
        //$('.form_step_3 .form_wrap_2 .item .input_wrap').show();
        //$('.form_step_3 .form_wrap_2 .item .textarea_wrap').hide();
        $('#replaces').append(item_t);

        $('.input_wrap').show();
        $('.textarea_wrap').hide();

        $('.text_changer .column#column_' + count).find('.input_wrap').hide();
        $('.text_changer .column#column_' + count).find('.textarea_wrap').show();
    });
    //$('.form_step_3 .form_wrap_2 .item .delete').live('click', function(){
    //	$(this).parents('.item:first').remove();
    //});
    //});
    //var id =  parseInt($('.text_changer .column:last').attr('id').match(/\d+/)) + 1,
    //	content = $('.text_changer .column:first').html(),
    //	textarea_from = parseInt($('.text_changer .column:last').find('[name^=textarea_from]').attr('name').match(/\d+/)) + 1,
    //	textarea_to = parseInt($('.text_changer .column:last').find('[name^=textarea_to]').attr('name').match(/\d+/)) + 1,
    //	checkbox_id = parseInt($('.text_changer .column:last').find('[name^=regular]').attr('name').match(/\d+/)) + 1;
    //	label_regular = parseInt($('.text_changer .column:last').find('[for^=regular]').attr('for').match(/\d+/)) + 1;
//
    //	$('.text_changer .fields').append('<div class="column" id="column_'+id+'">'+content+'</div>');
    //	// set different textarea names
    //	$(document).find('#column_'+id).find('[name^=textarea_from]').attr('name', 'textarea_from['+textarea_from+']');
    //	$(document).find('#column_'+id).find('[name^=textarea_to]').attr('name', 'textarea_to['+textarea_to+']');
    //	$(document).find('#column_'+id).find('[name^=regular]').attr('name', 'regular['+checkbox_id+']').attr('id', 'regular['+checkbox_id+']');
    //	$(document).find('#column_'+id).find('[for^=regular]').attr('for', 'regular['+label_regular+']');
    //
//
    //	// close all textareas
    //	$(document).find('#column_'+id).children('.input_wrap').show();
    //	$(document).find('#column_'+id).children('.textarea_wrap').hide();
    //	// close all inputes and open current textareas
    //	$('.text_changer .column').find('.input_wrap').show();
    //	$('.text_changer .column').find('.textarea_wrap').hide();
    //	$(document).find('#column_'+id).children('.input_wrap .textbox').trigger('click');
    //	$('#column_'+id).find('.input_wrap').hide();
    //	$('#column_'+id).find('.textarea_wrap').show();
    //	$('#column_'+id).find('.textarea_wrap textarea:first').focus();
//
    //	})
    // make first field opened when page load
    //if ($('.text_changer').length > 0) {
    //	$('.text_changer .column#column_1').find('.input_wrap').hide();
    //	$('.text_changer .column#column_1').find('.textarea_wrap').show();
    //}
    // remove columns in text changer

    $(document).on('click', '.text_changer .remove', function() {
        if ($('.text_changer .column').length > 1) {
            $(this).parent().parent().parent().remove();
        } else {

            $(this).parent().parent().parent().remove();
        }
    })
    // copy all text to input
    $(document).on('keydown', '.text_changer .left .magic_textarea', function() {
        var val = $(this).val();
        $(this).parent().parent().parent().find('.input_wrap .left .textbox').val(val);
    });
    $(document).on('keydown', '.text_changer .right .magic_textarea', function() {
        var val = $(this).val();
        $(this).parent().parent().parent().find('.input_wrap .right .textbox').val(val);
    })

    //// end text changer scripts

    /// main nav ajax requests

    function getPage(name) {
        $.ajax({
            url: '/admin.php?action=get_template&name='+name,
            type: 'post',
            success: function(data) {
                if (data != '') {
                    $('.right-side').html(data);

                } else {
                    $('.notifications_window').html('<div class="error">Ошибка загрузки страницы!</div>');

                }
            },
            error: function(data) {
                $('.notifications_window').html('<div class="error">Ошибка:'+data+'</div>');
            }
        })
    }


    //$('.main_nav a').click(function(e) {
    //			e.preventDefault();
    //			var name = $(this).attr('page-name');
    //
    //            //getPage(name)
    //	        return true;
    //
    //		})

    $('#dolly_remove').click(function(){
		if(confirm('You sure?')) document.location.href = '/admin.php?action=remove_site';
    });
    $('#dolly_clear_cache').click(function(){
		if(confirm('You sure?')) document.location.href = '/admin.php?action=clear_site_cache';
    });
});
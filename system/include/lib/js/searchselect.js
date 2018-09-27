$(function(){
	$('.msui_search_select').mousedown(function(event){event.stopPropagation();});
	$('.msui_search_select input[type="text"]').keydown(function(event){
		if(event.which === KEY_CODE.ENTER)
		 {
			event.stopPropagation();
			return false;
		 }
	});
	$('.msui_search_select__clear').click(function(){
		var b = $(this), i_id = b.prevAll("input[type='hidden']"), i_tx = b.prevAll("input[type='text']"), prev_v = i_id.val(), prev_t = i_tx.val();
		close_inputs();
		i_id.val('');
		i_tx.removeAttr('name').val('');
		if(prev_v !== '' || prev_t !== '') i_id.parent().trigger('msui:change', {value:'', text:''});
	});
	var $_INPUT = '.msui_search_select input[type="search"]', KEY_CODE = {UP:38, DOWN:40, ENTER:13},
		close_inputs = function(i_not){
			$('.msui_search_select__list').text('');
			var i = $($_INPUT);
			if(i_not) i = i.not(i_not);
			i.val('').removeClass('_opened');
		},
		compare_str = function(prev, next){
			if(!prev || !next) return;
			if(prev === next) return 0;
			if(next.indexOf(prev) === 0 || prev.indexOf(next) === 0) return next.length - prev.length;
			return false;
		},
		compare_prm = function(p, p0){
			for(var i in p) if(p[i] !== p0[i]) return false;
			for(var i in p0) if(p[i] !== p0[i]) return false;
			return true;
		},
		search_query = function(qstr, input){
			var fields = {'search_select_name': input.attr('data-name'), 'text': qstr, 'r': {}}, params = input.attr('data-params');
			if(params)
			 {
				params = params.split(',');
				var form = input.parents('form').first();
				for(var i = 0; i < params.length; ++i) fields.r[params[i]] = form.find('[name="' + params[i] + '"]').val();
			 }
			if('' !== qstr)
			 {
				if(typeof(input[0].__search_requests) === 'undefined')
				 {
					input[0].__search_requests = {};
					input[0].__search_requests[qstr] = {v:this, p:fields.r};
				 }
				else
				 {
					for(var s in input[0].__search_requests)
					 if(!compare_prm(fields.r, input[0].__search_requests[s].p))
					  {
						input[0].__search_requests = {};
						break;
					  }
					if(typeof(input[0].__search_requests[qstr]) === 'undefined')
					 {
						for(var s in input[0].__search_requests)
						 if(compare_str(s, qstr))
						  {
							if(false === input[0].__search_requests[s].v) return;
						  }
						input[0].__search_requests[qstr] = {v:this, p:fields.r};
					 }
					else if(true !== input[0].__search_requests[qstr].v) return;
				 }
			 }
			var x = ms.xget(fields, function(x){
				var list = input.nextAll('.msui_search_select__list'), items = x.find('item');
				list.text('');
				if(items.length && input.hasClass('_opened')) items.each(function(){
					var n = $(this);
					$("<input type='button' data-id='" + n.attr('id') + "' class='msui_search_select__item' value='" + n.text() + "' />").appendTo(list);
				});
				if('' !== qstr) input[0].__search_requests[qstr].v = items.length > 0;
			}, 'searchselect:handle', {progbar:input});
			if('' !== qstr) x.fail(function(){delete input[0].__search_requests[qstr];});
		},
		show_list = function(input){
			close_inputs(input);
			var v = $.trim(input.val());
			if(!v && !input.hasClass('_show_latest')) return;
			new search_query(v, input);
		},
		get_items = function(){return $(this).nextAll('.msui_search_select__list').find('.msui_search_select__item');};
	$(document).on('input search', $_INPUT + '._opened', function(event){show_list($(this));})
	.on('keydown', $_INPUT + '._opened', function(event){
		switch(event.which)
		 {
			case KEY_CODE.UP:
				get_items.call(this).last().focus();
				return false;
			case KEY_CODE.DOWN:
				get_items.call(this).first().focus();
				return false;
			case KEY_CODE.ENTER:
				var n = $(this);
				if(n.hasClass('_new'))
				 {
					n.nextAll(".msui_search_select__list").text('');
					var i_id = n.nextAll("input[type='hidden']"), i_tx = n.prevAll("input[type='text']").attr('name', n.attr('data-name') + '_text'), prev_v = i_id.val(), prev_t = i_tx.val(), next_t = n.val();
					i_tx.val(next_t);
					i_id.val('');
					n.val('').removeClass('_opened');
					i_tx.focus();
					if(prev_v !== '' || prev_t !== next_t) i_id.parent().trigger('msui:change', {value:'', text:next_t});
					return false;
				 }
		 }
	})
	.mousedown(function(){close_inputs();})
	.on('keydown', '.msui_search_select__item', function(event){
		switch(event.which)
		 {
			case KEY_CODE.UP:
				var n = $(this), prev = n.prev();
				(prev.length ? prev : n.parent().prevAll('input[type="search"]')).focus();
				return false;
			case KEY_CODE.DOWN:
				var n = $(this), next = n.next();
				(next.length ? next : n.parent().prevAll('input[type="search"]')).focus();
				return false;
		 }
	})
	.on('click', '.msui_search_select__item', function(){
		var n = $(this), list = n.parent(), i_id = list.prevAll('input[type="hidden"]'), prev_v = i_id.val(), next_v = n.attr('data-id'), next_t = n.val();
		i_id.val(next_v);
		list.prevAll('input[type="text"]').removeAttr('name').val(next_t);
		list.text('');
		list.prevAll('input[type="search"]').val('').removeClass('_opened');
		if(prev_v !== next_v) i_id.parent().trigger('msui:change', {value:next_v, text:next_t});
	});	
	$($_INPUT).focus(function(){
		var n = $(this);
		if(!n.hasClass('_opened')) show_list(n.addClass('_opened'));
	});
});
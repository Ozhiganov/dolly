function MSMultiSelect2(src)
{
	if(src.hasClass('_ex'))
	 {
		var rows = src.find('.multiselect2__block'),
		options = src.find('>select option'),
		get_empty_row = function(input){
			var ret_val = false;
			src.find('.multiselect2__block').find('input[type="text"]').each(function(){
				if(input != this && IsEmpty(this.value))
				 {
					ret_val = $(this).parents('.multiselect2__block').first();
					return false;
				 }
			});
			return ret_val;
		},
		s_row = function(row){
			var onchange = function(evt){
				if(typeof(evt.which) == 'undefined');
				else switch(evt.which)
				 {
					case 37:
					case 38:
					case 39:
					case 40: return;
				 }
				var obj = $(this), erow = get_empty_row(this), lis = obj.next().find('>li');
				if(IsEmpty(this.value))
				 {
					if(erow) erow.remove();
					lis.removeClass('_hidden');
				 }
				else
				 {
					if(!erow)
					 {
						var new_row = $(this.parentNode.cloneNode(true)).appendTo(src);
						new s_row(new_row);
						new_row.find('input[type="text"]').val('');
					 }
					lis.each(function(){
						var n = $(this);
						n[n.text().toLowerCase().indexOf(input.val().toLowerCase()) === 0 ? 'removeClass' : 'addClass']('_hidden');
					});
				 }
			},
			input = row.find('input[type="text"]').attr('autocomplete', 'off').bind('input paste change', onchange).focus(function(){$(this).next().find('>li').removeClass('_hidden selected');});
			row.find('.delete_block').MSUIDeleteBlock({ondelete:function(){input.prop('disabled', true);}, onundo:function(){input.prop('disabled', false);}});
			if(options.length)
			 {
				var list = input.next('ul'), li_click = function()
				 {
					input.val(this.innerHTML);
					list.find('li').removeClass('selected');
					this.className = 'selected';
					input.change();
				 };
				if(list.length) list.find('li').mousedown(li_click);
				else
				 {
					list = $('<ul />');
					input.after(list);
					options.each(function(){list.append($('<li />').html(this.innerHTML).mousedown(li_click));});
				 }
			 }
		 };
		rows.each(function(){new s_row($(this));});
	 }
	else
	 {
		var opts = src.find('.multiselect2__block select').change(function(){
			var b = $(this).next('.delete_block');
			var s = src.find('.multiselect2__block select:not(:disabled)'), se = s.filter(function(){return !this.value;});
			if(this.value)
			 {
				if(!se.length && s.length < opts.length - 1) $(this.parentNode).clone(true).appendTo(src);
				b.removeClass('_hidden');
			 }
			else
			 {
				b.addClass('_hidden');
				if(se.length > 1) se.filter(':gt(0)').remove();
			 }
		}).first().find('option');
		src.find('.delete_block').MSUIDeleteBlock({ondelete:function(){this.find('select').prop('disabled', true);}, onundo:function(){this.find('select').prop('disabled', false);}});
	 }
}

$(function(){$('.multiselect2').each(function(){new MSMultiSelect2($(this));});});
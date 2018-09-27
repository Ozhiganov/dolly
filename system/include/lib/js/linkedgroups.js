function LinkedGroups(block)
{
	block = $(block);
	var select = block.find('select'),
	select_change = function()
	 {
		var target = $(this), wr = target.parents('.msui_linked_groups_wr').first(), groups = wr.find('.msui_linked_groups').filter(function(){return !$(this).find('.dropdown_list').first().find('[type="hidden"]').val();});
		if(!groups.length)
		 {
			var n = target.parents('.msui_linked_groups').first().clone();
			new LinkedGroups(n);
			n.find('.dropdown_list').removeAttr('data-disabled').find('.dropdown_list__item').first().click();
			wr.append(n);
		 }
	 },
	group_change = function()
	 {
		var crumbs = block.find('.dropdown_list'), src = this, index = crumbs.index(this), id = this.find('[type="hidden"]').val(), prev = null, prev_id = null;
		crumbs.attr('data-disabled', 'true');
		select.prop('disabled', true);
		if(index < 0) throw new Error('Invalid crumb index!');
		if(index > 0) prev = crumbs.eq(index - 1);
		for(var i = index + 1; i < crumbs.length; ++i) crumbs.eq(i).remove();
		select.find('option').remove();
		if(prev) prev_id = prev.find('[type="hidden"]').val();
		$.post('core.php', {action: 'get_linked_groups', id: id ? id : prev_id, input_name: select.attr('name'), input_id: select.attr('id')}, function(x){
			if('success' == x.documentElement.getAttribute('status'))
			 {
				x = $(x);
				var items = x.find('item'), groups = x.find('group');
				if(items.length)
				 {
					items.each(function(){
						var n = $(this);
						$('<option/>').attr('value', n.attr('value')).text(n.text()).appendTo(select);
					});
					select.prop('disabled', false);
				 }
				if(!id && prev_id);
				else if(groups.length)
				 {
					var new_list = new DropDownList($(DropDownList.CreateMarkup()).insertAfter(src), {onchange:group_change});
					new_list.AddOption('', '—', true);
					groups.each(function(){
						var n = $(this);
						new_list.AddOption(n.attr('value'), n.text());
					});
				 }
				block.find('.dropdown_list').removeAttr('data-disabled');
			 }
			else console.log($(x).find('message').text());
		});
	 };
	block.find('.msui_linked_groups__delete').click(function(){
		block.addClass('_st_deleted');
		block.find('.dropdown_list').attr('data-disabled', 'true');
		select.prop('disabled', true);
	});
	block.find('.msui_linked_groups__undo_deleting').click(function(){
		block.removeClass('_st_deleted');
		block.find('.dropdown_list').removeAttr('data-disabled');
		if(select.find('option').length) select.prop('disabled', false);
	});
	block.find('.dropdown_list').each(function(){new DropDownList(this, {onchange:[group_change, select_change]});});
	select.change(select_change);
}

$(function(){$('.msui_linked_groups').each(function(){new LinkedGroups(this);});});
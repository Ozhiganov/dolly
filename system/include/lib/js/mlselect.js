function MLSelect(id)
{
	var wr = $('[id="' + id + '"]'),
	get = function(){return wr.find('select');},
	fs_id = wr.attr('data-fs_id'),
	curr_id = wr.attr('data-curr_id'),
	f_name = wr.attr('data-name'),
	name = get().filter("[name]").prop('name'),
	disable = function(){return get().prop('disabled', true);},
	enable = function(){return get().prop('disabled', false);},
	onchange = function(){
		var sender = $(this), target = this;
		for(var n = sender.next('select'); n.length; n = sender.next('select')) n.remove();
		get().filter("[name]").removeAttr('name');
		if(isNaN(parseInt(this.value)))
		 {
			if(this.previousSibling)
			 {
				this.previousSibling.name = name;
				target = this.previousSibling;
			 }
			else this.name = name;
			wr.trigger('mlselect:change', {select:target});
		 }
		else
		 {
			disable();
			this.name = name;
			ms.xget({__fs_id:fs_id, __get_field_data:f_name, parent_id:this.value, curr_id:curr_id, name:name}, function(x){
				var nodes = x.find('item');
				if(nodes.length)
				 {
					var s = $('<select class="msui_select" />').change(onchange).append($('<option />').html('&#151;').val(''));
					nodes.each(function(){
						var opt = $('<option />').html(this.firstChild.nodeValue);
						for(var i = 0; i < this.attributes.length; ++i)
						 if('value' === this.attributes[i].name) opt.val(this.getAttribute('value'));
						 else opt.attr(this.attributes[i].name, this.attributes[i].value);
						opt.appendTo(s);
					});
					sender.after(s);
				 }
				enable();
				wr.trigger('mlselect:change', {select:target});
			});
		 }
	};
	var n = enable().change(onchange);
	if(n.length < 2 && !n.find('option').length) n.prop('disabled', true);
};
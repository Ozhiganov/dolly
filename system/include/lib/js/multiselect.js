$(function(){
	$('.simplepagetree[data-init="multiselect"]').SimplePageTree({'onselect':function(trigger, item, items){
		trigger.prev('input[type="hidden"]').val(item.id);
		for(var t = '', i = 0; i < items.length; ++i) t += '<span class="imultiselect__title" data-id="' + items[i].id + '">' + items[i].title + '</span> ';
		trigger.next().html(t);
		var msl = trigger.parents('.multiselect').first();
		if(msl.length)
		 {
			if(!msl.find('.imultiselect__hval').filter(function(j){return !this.value;}).length)
			 {
				var dest = msl.find('.imultiselect').last(), r = dest.clone(true);
				r.find('.imultiselect__delete._deleted').trigger('click');
				r.find('.imultiselect__title').remove();
				r.find('input[type="text"], input[type="hidden"]').val('');
				r.insertAfter(dest).find('input:not([type="hidden"])').first().focus();
			 }
		 }
	}});
	$('.delete_block.imultiselect__delete').MSUIDeleteBlock({onchange:function(disabled){this.find('input').prop('disabled', disabled).filter('.imultiselect__hdel').prop('disabled', !disabled);}});
});
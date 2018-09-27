$(function(){
var msg_confirm = $('.pagetree').attr('data-msg_confirm') || 'Удалить страницу {title} со всеми фотографиями, файлами и подразделами?',
delete_a_page = function(){
	var btn = $(this), title = [];
	btn.parents('.pagetree__leaf').add(btn.parents('.pagetree__node').prev('.pagetree__leaf')).find('.pagetree__title').each(function(){title.push($(this).text());});
	if(!confirm(msg_confirm.replace('{title}', title.length ? ' «' + title.join(' → ') + '»' : ''))) return;
	$('<form method="post" action="core.php"><input type="hidden" name="ids" value="' + btn.attr('data-id') + '" /><input type="hidden" name="__mssm_action" value="delete_page" /></form>').css('display', 'none').appendTo(document.body).submit();
},
DragableItem = new (function(){
var dummy = $('<div class="dummy _hidden" />').appendTo(document.body);
this.OnStartDrag = function(x, y)
{
	this.SetPosition(x, y);
	$(this.parentNode).addClass('_captured').next('.pagetree__node').addClass('_child_nodes');
	dummy.html(this.innerHTML).removeClass('_hidden');
};
this.OnEndDrag = function(dests)
{
	dummy.addClass('_hidden');
	$(this.parentNode).removeClass('_captured').next().removeClass('_child_nodes');
	if(dests[0]) $(dests[0]).removeClass('_dest_drag_over');
};
this.Create = function(item)
{
	item.SetPosition = function(x, y){dummy.css({left: (x - 10) + 'px', top: y + 'px'});};
 	item.OnStartDrag = DragableItem.OnStartDrag;
	item.OnEndDrag = DragableItem.OnEndDrag;
	item.OnDragOver = function(dest)
	 {
		var row = this.parentNode, r = $(row), d = $(dest[0]), r_next = r.next('.pagetree__node'), d_next = d.next('.pagetree__node');
		r.parent().children('.pagetree__leaf').each(function(i){if(i = (this == dest[0]) << 1 | this == row) return (d[i > 1 ? 'before' : 'after'](row).parent().attr('data-moved', 1)) && false;});
		if(r_next.length) r.after(r_next);
		if(d_next.length) d.after(d_next);
	 };
	return item;
};
this.OnDestDragOver = function(){$(this).addClass('_dest_drag_over');};
this.OnDestDragOut = function(){save_order.removeClass('_hidden');$(this).removeClass('_dest_drag_over');};
}),
toggle_node_state = function()
{
	var b = $(this), branch = b.next('.pagetree__branch');
	if(!branch.children('.pagetree__leaf').length)
	 {
		var p = b.parent();
		ms.get({'id':p.prev('.pagetree__leaf').attr('data-id'), 'level':p.parent().attr('data-level')}, function(r, type){
			if('html' === type)
			 {
				r = $(r);
				r.find('.pagetree__node_state').click(toggle_node_state);
				init_dnd(r.find('.pagetree__leaf'));
				r.replaceAll(branch);
				r.find('.pagetree__action._delete').click(delete_a_page);
			 }
			else ms.AddErrorMsg('Неправильный ответ сервера.');
		}, 'get_branch', {'progbar':branch});
	 }
	b.toggleClass('_expand');
},
btns = $('.pagetree__node_state').click(toggle_node_state),
collapse_all = $('.nodes_state._collapse_all').click(function(){btns.addClass('_expand');ms.post({'expand':0}, function(){}, 'set_expanded', {'success_msg':'', 'progbar':false});}),
expand_all = $('.nodes_state._expand_all').click(function(){btns.removeClass('_expand');ms.post({'expand':1}, function(){}, 'set_expanded', {'success_msg':'', 'progbar':false});}),
save_order = $('.global_action._save_order');
$('.pagetree__action._delete').click(delete_a_page);
if(expand_all.length && expand_all.attr('data-default')) btns.removeClass('_expand');
save_order.click(function(){
	var order = [];
	$('.pagetree__root[data-moved], .pagetree__branch[data-moved]').each(function(){
		var q = [];
		$(this).removeAttr('data-moved').children('.pagetree__leaf').each(function(){q.push(this.getAttribute('data-id'));});
		if(q.length > 1) order.push(q.join('|'));
	});
	if(order.length) ms.jpost({order:order}, function(){save_order.addClass('_hidden');}, 'set_page_order');
});
if(typeof InitGroupDeleting !== 'undefined') InitGroupDeleting();
if($('.pagetree').attr('data-dragndrop') === 'true')
 {
	var leafs = $('[data-order-by="position"] > .pagetree__leaf'),
	init_dnd = function(leafs)
	 {
		leafs.each(function(j){
			var drag = MSDragNDropManager.InitItem(DragableItem.Create(this.firstChild)), parent = this.parentNode;
			leafs.each(function(k){
				if(k != j && parent == this.parentNode)
				 {
					this.OnDragOver = DragableItem.OnDestDragOver;
					this.OnDragOut = DragableItem.OnDestDragOut;
					drag.AttachTarget(this);
				 }
			});
		});
	 };
	init_dnd(leafs);
	MSDragNDropManager.Init();
 }
else init_dnd = function(){};
});
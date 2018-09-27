function MSMap(block, new_id, new_lng, new_lat, new_zoom)
{
	var map, id = block.find('input[name="id"]'), lng = block.find('input[name="lng"]'), lat = block.find('input[name="lat"]'), zoom = block.find('input[name="zoom"]'), type = block.find('input[name="type"]'), map_area = block.find('.map__area'), title = block.find('input[name="title"]'), groups = block.find('input[name="group_id[]"]'), title_placeholder = title.attr('placeholder'),
 	colors = [{title:'blue', value:'blue'}, {title:'orange', value:'orange'}, {title:'darkblue', value:'darkblue'}, {title:'pink', value:'pink'}, {title:'darkgreen', value:'darkgreen'}, {title:'red', value:'red'}, {title:'darkorange', value:'darkorange'}, {title:'violet', value:'violet'}, {title:'green', value:'green'}, {title:'white', value:'white'}, {title:'grey', value:'grey'}, {title:'yellow', value:'yellow'}, {title:'lightblue', value:'lightblue'}, {title:'brown', value:'brown'}, {title:'night', value:'#004056'}, {title:'black', value:'black'}],
	menu = new function(){
		var m = $('#msmaps_placemark_menu'), placemark, balloonContent, close = function(){
			m.addClass('hidden');
			placemark = null;
		};
		if(!m.length)
		 {
			var html = '';
			for(var i = 0; i < colors.length; ++i) html += '<span style="background:' + colors[i].value + '" data-title="' + colors[i].title + '"></span>';
			m = $('<div id="msmaps_placemark_menu" class="hidden"><textarea placeholder="Текст будет виден при клике на точке" name="ballooncontent"></textarea><div class="colors">' + html + '</div><div class="bottom"><span class="pseudolink delete">удалить</span><span class="pseudolink close">сохранить</span></div></div>').appendTo(document.body);
			m.find('.colors span').click(function(){placemark.options.set('preset', 'twirl#' + this.getAttribute('data-title') + 'CircleIcon');});
			balloonContent = m.find('[name="ballooncontent"]').change(function(){placemark.properties.set('balloonContent', this.value.replace(/\n/g, '<br/>'))});
			m.find('.bottom .close').click(close);
			m.find('.bottom .delete').click(function(){
				placemark.getMap().geoObjects.remove(placemark);
				close();
			});
		 }
		this.Show = function(event)
		 {
			m.css({top:event.get('position')[1] + 16, left:event.get('position')[0] - 200}).removeClass('hidden');
			placemark = event.get('target');
			var s = placemark.properties.get('balloonContent');
			if(s) s = s.replace(/<br\s*\/?>/g, "\n");
			balloonContent.val(s).focus();
		 };
	};
	if(new_id)
	 {
		map_area.attr('id', 'map_area_' + new_id);
		id.val(new_id);
		lng.val(new_lng);
		lat.val(new_lat);
		zoom.val(new_zoom);
	 }
	block.find('.map__action._save').click(function(){
		var params = {id:id.val(), title:title.val(), group_id:[], type:type.val()}, center = map.getCenter();
		groups.each(function(){if(this.checked) params.group_id.push(this.value);});
		params.lat = center[0];
		params.lng = center[1];
		params.zoom = map.getZoom();
		params.points = [];
		map.geoObjects.each(function(o){
			var coord = o.geometry.getCoordinates();
			params.points.push({id:o.properties.get('data-id'), lat:coord[0], lng:coord[1], preset:o.options.get('preset'), ballooncontent:o.properties.get('balloonContent')});
		});
		ms.jpost(params, null, 'save');
	});
	block.find('.map__action._expanded').click(function(){
		var b = $(this).toggleClass('_expanded _collapsed'), c = b.hasClass('_collapsed');
		b.attr('title', c ? 'Показать карту' : 'Свернуть карту');
		block.toggleClass('_collapsed', c);
		title.prop('disabled', c);
		if(c) title.removeAttr('placeholder');
		else
		 {
			map.container.fitToViewport();
			title.attr('placeholder', title_placeholder);
		 }
	});
	block.find('.map__action._delete').click(function(){if(confirm('Удалить?')) ms.jpost({id:id.val()}, function(){block.remove();}, 'delete');});
	map = new ymaps.Map(map_area.attr('id'), {center: [lat.val(), lng.val()], zoom: zoom.val(), type: type.val()});
	var sc = new ymaps.control.SearchControl({useMapBounds: true, noPlacemark: true});
	map.controls.add('zoomControl').add('typeSelector').add('mapTools').add(sc);
	sc.events.add('resultselect', function(event){
		var geo_obj = event.get('target').getResultsArray()[event.get('resultIndex')];
		geo_obj.options.set('draggable', true);
		geo_obj.options.set('openBalloonOnClick', false);
		geo_obj.events.add('dblclick', menu.Show);
		map.geoObjects.add(geo_obj);
	});
	map.events.add('contextmenu', function(event){
		event.get('domEvent').callMethod('preventDefault');
		var p = new ymaps.Placemark(event.get("coordPosition"), null, {draggable: true, openBalloonOnClick: false});
		p.events.add('dblclick', menu.Show);
		event.getMap().geoObjects.add(p);
	});
	map.events.add('typechange', function(event){type.val(event.get('newType'));});
	block.find('input[name="points"]').each(function(){
		var options = {draggable: true, openBalloonOnClick: false}, preferences = {'data-id':this.getAttribute('data-id')}, s;
		if(s = this.getAttribute('data-preset')) options.preset = s;
		if(s = this.getAttribute('data-ballooncontent')) preferences.balloonContent = Base64.Decode(s);
		var p = new ymaps.Placemark([this.getAttribute("data-lat"), this.getAttribute("data-lng")], preferences, options);
		p.events.add('dblclick', menu.Show);
		map.geoObjects.add(p);
	});
}

if(typeof(ymaps) != 'undefined') ymaps.ready(function(){
	var maps = $('#maps'), pattern = $('.map._prototype');
	$('.add_msmap').click(function(){
		ms.jpost(null, function(r){
			var nodes = maps.find('.map'), n = pattern.clone(true, true).removeClass('_prototype').prependTo(maps), drag = MSDragNDropManager.InitItem((new DraggableItem(n)).Get());
			new MSMap(n, r.id, r.lng, r.lat, r.zoom);
			nodes.each(function(){
				drag.AttachTarget(this);
				$(this).find('.map__action._move').get(0).AttachTarget(n.get(0));
			});
		}, 'add');
	});
	$('.b_state._expand').click(function(){$('.map__action._collapsed').click();});
	$('.b_state._collapse').click(function(){$('.map__action._expanded').click();});
	var IgnoreX = function(){return true;},
	DraggableItem = function(map)
	 {
		var item = map.find('.map__action._move').get(0), moved = false;
		item.SetPosition = function(x, y){map.css('top', y - 6);};
		item.OnStartDrag = function(x, y)
		 {
			moved = false;
			map.css('width', map.outerWidth(false)).after('<div class="dnd_dummy" style="height:' + (map.outerHeight(false) - 2) + 'px;"></div>').css('top', y - 8).addClass('_captured');
		 };
		item.OnEndDrag = function()
		 {
			map.removeClass('_captured').css('width', '');
			maps.find('.dnd_dummy').remove();
			if(moved) ms.post({order:ms.GetOrderStr('#maps .map [name="id"]')});
		 };
		item.OnDragOver = function(dest)
		 {
			dest = dest[0];
			var src = this.parentNode.parentNode, before;
			$(maps.children('.map')).each(function(){
				if(this == dest) { before = false; return false; }
				else if(this == src) { before = true; return false; }
			});
			$(dest)[before ? 'after' : 'before'](src)[before ? 'after' : 'before'](maps.find('.dnd_dummy'));
			moved = true;
		 };
		this.Get = function(){return item;};
	 },
	blocks = maps.find('.map');
	MSDragNDropManager.Init();
	blocks.each(function(i){
		var map = $(this);
		new MSMap(map);
		var drag = MSDragNDropManager.InitItem((new DraggableItem(map)).Get());
		blocks.each(function(k){if(k != i) drag.AttachTarget(this);});
	});
});
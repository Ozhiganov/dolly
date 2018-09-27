MSYMaps = new(function(){
var maps = {};
this.Get = function(id){return maps[id];};
this.Create = function(id)
 {
	if(maps[id]) throw new Error('Карта с идентификатором `' + id + '` уже инициализирована.');
	return new(function(id){
		maps[id] = this;
		var container = $('#' + id), lng = container.attr('data-lng'), lat = container.attr('data-lat'), zoom = container.attr('data-zoom'), marks = [], map = new ymaps.Map(container.get(0), {center: [lat, lng], zoom: zoom}), sctrl = new ymaps.control.SearchControl({noPlacemark:true}), expand = new ymaps.control.Button('<img class="msmap__ibtn _fullscreen" alt="" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAwUlEQVR4Xq2TwQrCMBBEd9W73+AX1kshpRCptKRH+6NCr17iLKgsmwT24MCw7SOZw5BlIspUas05Dxow84QRyehApRZ7WQR2k2BPwItcagdMzBwtBEsYoRVwh7cipH15hZffYRRGYugBZ7HhQfGk+EzfHx2iQ01IspwF1OUv8b8BUh7cVXgPz1TRqEqJqqzOUWJ/ApiRTNARnlTwRX2fFQ+f8zucWruwodyr6yF5LovABu8uPKmt3bsLwUKwESNZ/gYb8obj7vn4jwAAAABJRU5ErkJggg==" />', {selectOnClick:false, position:{top:5, left:5}}), marks_list = new ymaps.control.ListBox({data:{title:'Выбрать метку...'}, items:[]}, {position:{bottom:25, left:5}}), curr_mark;
		expand.events.add('click', function(){
			container.toggleClass('_fullscreen');
			map.container.fitToViewport();
		});
		map.controls.add('zoomControl').add('typeSelector').add(sctrl).add(expand).add(marks_list);
		// sctrl.events.add('resultselect', function(evt){console.log(evt);});
		// sctrl.events.add('resultshow', function(evt){console.log(evt);});
		map.events.add('click', function(event){if(curr_mark) curr_mark[curr_mark.IsPlaced() ? 'Move' : 'Place'](event.get("coordPosition"));});
		this.AddMark = function(lat_id, lng_id)
		 {
			return new (function(lat_field, lng_field){
				marks.push(this);
				var li = new ymaps.control.ListBoxItem({data: {content: 'Метка', _mark:this}}, {selectOnClick:true}), mark;
				li.events.add('select', function(event){curr_mark = event.get('target').data.get('_mark'); curr_mark.Center();});
				li.events.add('deselect', function(){curr_mark = false;});
				marks_list.add(li);
				this.Place = function(p)
				 {
					mark = new ymaps.Placemark(p, null, {draggable:true});
					map.geoObjects.add(mark);
					mark.events.add('drag', function(event){
						var p = event.get('target').geometry.getCoordinates();
						lat_field.value = p[0];
						lng_field.value = p[1];
					});
					lat_field.value = p[0];
					lng_field.value = p[1];
					return this;
				 };
				this.IsPlaced = function() { return !isNaN(parseFloat(lng_field.value)) && !isNaN(parseFloat(lat_field.value)); };
				this.Center = function() { if(this.IsPlaced()) map.setCenter([lat_field.value, lng_field.value]); return this; };
				this.Move = function(c) { mark.geometry.setCoordinates(c); return this; }
				if(this.IsPlaced()) this.Place([lat_field.value, lng_field.value]);
			 })(lat_id, lng_id);
		 };
	})(id);
 };
})();

if(typeof(ymaps) != 'undefined') ymaps.ready(function(){
$('.msmap').each(function(){
	var map = MSYMaps.Create(this.id), points = $('[type="hidden"][data-map-id="' + this.id + '"]');
	for(var i = 0; i < points.length; i += 2) map.AddMark(points.get(i), points.get(i + 1));
}).keypress(function(evt){if(13 == evt.which){evt.stopPropagation();return false;}});
});
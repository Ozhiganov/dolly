$(function(){
	var crop = new MSImageCropper();
	$('.image.crop[name^="crop_"]').click(function(){
		var f = $(this.form),
			by_name = function(name){return f.find('input[name="' + name + '"]').val();},
			by_fname = function(name){return f.find('input[type="hidden"][name^="tables["][name$="][' + name + ']"]').val();};
		crop.SetOnSaveData(function(x){location.reload(true);})
			.SetImageId(by_fname('item_id'))
			.Show(by_name('_src'), by_name('_width'), by_name('_height'), by_name('_new_width'), by_name('_new_height'), by_name('_crop_width'), by_name('_crop_height'), by_fname('top_x'), by_fname('top_y'), by_fname('bottom_x'), by_fname('bottom_y'), by_fname('icon_type'));
	});
});
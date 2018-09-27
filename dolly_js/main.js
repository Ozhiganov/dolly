(function ($) {
	$(document).ready(function() {
		$('.url_wrap').on('change', function(){
			var text = $(this).val();
			if(text != ''){
				$('.bot_wrap1').css('visibility', 'visible');
				$('.form_install .form_encoding span').css('visibility', 'visible');
				//$('.page_visible_1 .on').show();
			} else {
				$('.bot_wrap1').css('visibility', 'hidden');
				$('.form_install .form_encoding span').css('visibility', 'hidden');
				$('.page_visible_1 .on').hide();
			}
		});
		//$("<div><div>").addClass('adress_span').insertBefore('#form .form_url_site input');
		$('.form_step_3 .form_wrap_2 .wrap_items').each(function(){
			//var string_replace = 'Обычный текст';
			//var preg_replace = 'Регулярное выражение';
			var count = $('.form_step_3 .form_wrap_2 .wrap_items .item').length;
			if(count == 1){
				$('.form_step_3 .form_wrap_2 .item_1 .input_wrap').hide();
				$('.form_step_3 .form_wrap_2 .item .textarea_wrap').show();
			} else {
				$('.form_step_3 .form_wrap_2 .item .input_wrap').show();
				$('.form_step_3 .form_wrap_2 .item .textarea_wrap').hide();
			}
			$('.form_step_3 .form_wrap_2 .add_new_item').click(function(){
				count++;

				var item = '<div class="item item_'+count+'"> \
								<div class="delete"></div> \
								<div class="input_wrap" style="display: none;"> \
									<input type="text" class="l_input" name="out['+count+'][l_input]"/> \
									<input type="text" class="r_input" name="out['+count+'][r_input]"/> \
								</div> \
								<div class="textarea_wrap" style="display: block;"> \
									<textarea class="l_textarea" name="out['+count+'][l_textarea]"></textarea> \
									<textarea class="r_textarea" name="out['+count+'][r_textarea]"></textarea> \
									<div class="change_wrap"> \
										<div class="change_type"> \
											<input type="checkbox" id="change_type_'+count+'" name="out['+count+'][change_type]'+count+'"/> \
											<label for="change_type_'+count+'"><span class="text text_1">' + string_replace + '</span><span class="text text_2">' + preg_replace + '</span></label> \
										</div> \
									</div> \
								</div> \
							</div>';
				$('.form_step_3 .form_wrap_2 .item .input_wrap').show();
				$('.form_step_3 .form_wrap_2 .item .textarea_wrap').hide();
				$('.form_step_3 .form_wrap_2 .wrap_items').append(item);
			});
			$('.form_step_3 .form_wrap_2 .item .delete').livequery('click', function(){
				$(this).parents('.item:first').remove();
			});
		});
		$('.form_step_3 .form_wrap_2 .item input').livequery('focus', function(){
			$('.form_step_3 .form_wrap_2 .item .textarea_wrap').hide();
			$('.form_step_3 .form_wrap_2 .item .input_wrap').show();
			$(this).parents('.item:first').find('.input_wrap').hide();
			$(this).parents('.item:first').find('.textarea_wrap').show();
			if($(this).hasClass('l_input')){
				$(this).parents('.item:first').find('.textarea_wrap .l_textarea').focus();
			} else {
				$(this).parents('.item:first').find('.textarea_wrap .r_textarea').focus();
			}
		});
		$('.form_step_3 .form_wrap_2 .item textarea').livequery('change', function(){
			var text = $(this).val();
			var search_array = text.split('\n');
			if($(this).hasClass('l_textarea')){
				$(this).parents('.item:first').find('input.l_input').val(search_array[0]);
			} else {
				$(this).parents('.item:first').find('input.r_input').val(search_array[0]);
			}
		});

		$('.lang select').livequery(function(){
			$(this).sSelect();
		});
	});
})(jQuery);



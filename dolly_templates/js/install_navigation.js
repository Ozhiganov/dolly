$(function(){
var first_step = $('.install_content .first_step'),
	second_step = $('.install_content .second_step'),
	third_step = $('.install_content .third_step'),
	first_label = $('.installation_steps .first'),
	second_label = $('.installation_steps .second'),
	third_label = $('.installation_steps .third'),
	i_url = $('#url_site');
	$('.install_navigation .next.button').click(function(){
		if($.trim(i_url.val()) == '')
		 {
			i_url.addClass('_error');
			ms.AddErrorMsg(NOT_SITE_URL);
			i_url.focus();
			return false;
		 }
		else i_url.removeClass('_error');
		if(first_step.hasClass('active'))
		 {
			first_step.removeClass('active').hide();
			second_step.addClass('active').show();
			second_label.addClass('active');
			$('.button.back').show();
		 }
		else if(second_step.hasClass('active'))
		 {
			second_step.removeClass('active').hide();
			third_step.addClass('active').show();
			third_label.addClass('active');
			$('.button.next, .button.back').hide();
			$('.success_text').animate({opacity: 1});
			ms.jpost($('#form1').serialize(), function(r){
				$('body').append('<iframe id="backpage" src="/" style="opacity:0;" onload="document.iframeAddBase()"></iframe');
				document.iframeAddBase = function(){
					var n = $('#backpage').contents();
					n.find('body').prepend('<base target="_parent">');
					n.find('a').attr({'target': '_parent'});
				};
				var bg = $('#backpage').css({position:'absolute', 'top':0, 'left':0, 'width':'100%', 'height':'100%', 'border':'none', '-webkit-filter':'blur(7px)', '-moz-filter':'blur(7px)', 'filter':'blur(7px)', 'z-index':'-1'}).animate({opacity: 1}, 3000, 'swing', function(){
					$('.success_text_1').hide();	
					$('.success_text_2').fadeIn();						
					setTimeout(function(){
						$({blurRadius: 7}).animate({blurRadius: 0}, {
							duration: 2000,
							easing: 'swing',
							step: function(){$('#backpage').css({"-webkit-filter": "blur(" + this.blurRadius + "px)", "-moz-filter": "blur(" + this.blurRadius + "px)", "filter": "blur(" + this.blurRadius + "px)"});}
						});
						$('body > *:not(iframe, #info_block)').fadeOut('slow', function(){$('#info_block').fadeIn('slow');});
					}, 2500);
				});
			}, '');
		 }
	});
	$('.install_navigation .back.button').click(function(){
		if(third_step.hasClass('active'))
		 {
			second_step.addClass('active').show();
			third_step.removeClass('active').hide();
		 }
		else if(second_step.hasClass('active'))
		 {
			first_step.addClass('active').show();
			second_step.removeClass('active').hide();
			$(this).hide();
		 }
	});
});
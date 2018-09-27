<div class="topbar">
    <h1 class="main-title"><?=l10n()->images?></h1>
    <div class="right button save">
        <?=l10n()->save?>
    </div>
</div>
<form id="form" enctype="multipart/form-data" action="../admin.php?action=images_save" role="form" method="post">

    <div class="images-page inner">
        <div class="tabs">
            <div class="tabs_selectors">
                <ul class="list">
                    <li id="tab_0"><?=l10n()->common_params?></li>
                    <li id="tab_1"><?=l10n()->copyright?></li>
                </ul>
            </div>
            <div class="tabs_content">
                <div class="tab tab_0">
                    <div class="manual_settings">
                        <div class="left col-xs-5 pd0">
                            <div class="mirror_checkbox">
                                <input type="checkbox"
                                       class="super_checkbox"
                                       id="mirror"
                                       name="reflection"
                                       value="_1"
                                    <?php echo (@Settings::staticGet('reflection')) ? 'checked' : ''; ?>
                                >
                                <label for="mirror"><?=l10n()->flip_horz?></label>
                            </div>
                            <div class="options">
                                <!--
                                <div class="scaling">
                                    <span class="label"><?=l10n()->scale?></span>
                                    <input type="text"
                                           id="scaling"
                                           class="input"
                                           name="scaling"
                                           value="<?php echo (@Settings::staticGet('scale')) ? Settings::staticGet('scale') : 100;?>"

                                    >
                                    %
                                </div>-->
                                <div class="minimal_scale">
                                    <span class="label"><?=l10n()->min_size?></span>
                                    <div class="inputer">
                                        <input type="text" 
                                               name="height" 
                                               class="size input"
                                               value="<?php echo (@Settings::staticGet('img_min_h')) ? Settings::staticGet('img_min_h') : 150;?>"
                                        >
                                    </div>
                                    <span class="devider">X</span>
                                    <div class="inputer">
                                        <input type="text" 
                                               name="width" 
                                               class="size_second input"
                                               value="<?php echo (@Settings::staticGet('img_min_w')) ? Settings::staticGet('img_min_w') : 150;?>"

                                        >
                                    </div>
                                </div>
                                <!--<div class="execution">
                                    <span class="label"><?php /* echo $this->t('Исключение'); */ ?></span>
											<textarea name="execlude" class="input" id="execlude">/img/1.png
/logo.jpg</textarea>
                                </div>-->
                            </div>
                        </div>
                        <div class="right col-xs-7 pd0">
                            <div class="trigger "></div>
                            <img src="admin.php?action=get_handled_image" class="image">

                        </div>
                    </div>
                </div>
                <div class="tab tab_1">
                    <div class="copyright-settings">
                        <div class="left col-xs-5 pd0">
                            <div class="enable_copyright">
                                <input type="checkbox"
                                       value="on"
                                       class="super_checkbox"
                                       id="enable_copyright"
                                       name="enable_copyright"
                                    <?php echo (@Settings::staticGet('enable_copyright')) ? 'checked' : ''; ?>
                                >
                                <label for="enable_copyright"><?=l10n()->enable_copyright?></label>
                            </div>
                            <div class="options">
                                <div class="trigger disabled"></div>
                                <div id = "select_processor">
                                <select name="copyright_type" id="copyright_type" class="copyright_type magic_select2">
                                    <?php
                                    $options = array(
                                        array('text', l10n()->text),
                                        array('image', l10n()->image),
                                    );
                                    _select($options, @Settings::staticGet('logo_type'));
                                    ?>
                                </select>
                                </div>
                                <div class="text-handler">
                                    <div class="col">
                                        <span class="label"><?=l10n()->text?></span>
                                        <input type="text"
                                               class="input"
                                               id="text_copyright"
                                               name="text_copyright"
                                               value="<?php echo (@Settings::staticGet('logo_type') == 'text') ? substr(@Settings::staticGet('logo'), 6) : 'DollySites'; ?>">
                                    </div>
                                    <div class="col">
                                        <span class="label"><?=l10n()->font_color?>:</span>
                                        <input type="text"
                                               value="<?php
                                               $color = @Settings::staticGet('logo_collor');
                                               $color = ($color) ? $color : '#ffffff';
                                               echo $color ; ?>"

                                        class="input"
                                               id="text_color"
                                               name="logo_collor">
                                        <div class="colorpick">
                                            <div class="chosed_color"></div>
                                        </div>
                                        <div
                                            class="button_chosecolor button"><?=l10n()->select_color?></div>
                                    </div>

                                    <div class="col">
                                        <!--<span class="label"><?=l10n()->background_color?>:</span>
                                    <input type="text" value="#ffffff" class="input" id="text_background">
                                    <div class="colorpick_background">
                                        <div class="chosed_color"></div>
                                    </div>
                                    <div class="button_chosecolor button"><?=l10n()->select_color?></div>
                                    -->
                                    </div>
                                </div>


                                <div class="image-handler">
                                    <div class="col">
                                        <span class="label"><?=l10n()->select_image?></span>

                                            <input type="file"
                                                   name="upload_files"
                                                   id="upload_files"
                                                   value="<?php echo @Settings::staticGet('logo');?>">
                                            <!--<div class="button upload_button"><?php /* echo $this->t('Загрузить'); */ ?></div>-->
                                    </div>
                                </div>
                                <div class="col">
                                    <!--<span class="label"><?=l10n()->transparency?></span>-->
                                    <div id="opacity-range" style="display: none"></div>
                                </div>
                                <div class="col position">
                                    <span class="label"><?=l10n()->position?></span>
                                    <div class="position_square">
                                        <div pos='0' class="square active left-top"></div>
                                        <div pos='2' class="right-top square"></div>
                                        <div pos='1' class="bottom-left square"></div>
                                        <div pos='3' class="bottom-right square"></div>
                                        <input type="hidden" id="logo_pos" name="logo_pos" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="right col-xs-7 pd0">
                            <div class="trigger disabled"></div>
                            <img src="admin.php?action=get_handled_image" class="image">
                            <!--<div class="green-area">
                                <div class="left-top">
                                    <div class="copyright">Custom text</div>
                                </div>
                                <div class="right-top"></div>
                                <div class="left-bottom"></div>
                                <div class="right-bottom"></div>
                            </div>-->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</form>
<script src="dolly_templates/js/jquery-1.12.2.min.js"></script>
<script src="dolly_templates/js/jquery.fancybox.js"></script>
<script src="dolly_templates/js/nouislider.min.js"></script>
<script src="dolly_templates/js/colpick.js"></script>
<script src="dolly_templates/js/inputmask.min.js"></script>
<script src="dolly_templates/js/jquery.inputmask.min.js"></script>

<script>
    jQuery(function ($) {
        $.get(
            'admin.php?action=get_handled_image',
            function (data) {
                $('.image').attr('src', 'data:image/jpg;base64,' + data)
            });

        if ($('#enable_copyright').prop('checked') == true) {
            $('.copyright-settings .trigger.disabled').removeClass('disabled');
        }

        $('#enable_copyright').on('change', function () {
            refreshImage()

            if ($(this).prop('checked') == true) {
                $('.copyright-settings .trigger.disabled').removeClass('disabled');
            } else {
                $('.copyright-settings .trigger').addClass('disabled')
            }
        });

        if ($('#mirror').prop('checked') == true) {
            $('.manual_settings .trigger.disabled').removeClass('disabled');
        }

        $('#mirror').on('change', function () {
            refreshImage()

            if ($(this).prop('checked') == true) {
                $('.manual_settings .trigger.disabled').removeClass('disabled');
            } else {
               // $('.manual_settings .trigger').addClass('disabled')
            }
        });
        // formating and styling copyright text type
        var copyright_text = $('[name=text_copyright]').val();
        // set copyright to preview window
        $('.copyright-settings .green-area .copyright').text(copyright_text);
        $('#scaling').inputmask('9{1,3}', {clearIncomplete: true, greedy: false})
        // color picker
        //colorpick
        $('#scaling').on('change', function () {
            refreshImage();

        })
        $('#text_color').val('<?php echo $color;?>');
        $('#text_background').val('#ff2200');
        $('.colorpick').colpick({
            layout: 'hex',
            submit: 0,
            onChange: function (hsb, hex, rgb, el, bySetColor) {
                $(el).find('.chosed_color').css('background-color', '#' + hex);
                $(el).parent().find('input').val('#' + hex);
                $('.green-area .copyright').css('color', '#' + hex);
                refreshImage();
            }
        });
        $('.colorpick_background').colpick({
            layout: 'hex',
            submit: 0,
            onChange: function (hsb, hex, rgb, el, bySetColor) {
                $(el).find('.chosed_color').css('background-color', '#' + hex);
                $(el).parent().find('input').val('#' + hex);
                $('.green-area .copyright').css('background-color', '#' + hex);
            }
        })
        $('.button_chosecolor').click(function () {
            $(this).parent().find('.colorpick').trigger('click');
        })
        // opacity slider range
        var range = document.getElementById('opacity-range');

        noUiSlider.create(range, {
            start: [0],
            step: 1,
            direction: 'ltr',
            orientation: 'horizontal',
            range: { // Slider can select '0' to '100'
                'min': 1,
                'max': 50,
            },
        });
        range.noUiSlider.on('change', function (val) {

            var value = 100 - Math.round(val, 3);


            $('.green-area .copyright').css('opacity', '.' + value);
        })
        // position of copyright
        $('.position_square .square').click(function () {
            $('#logo_pos').val($(this).attr('pos'))
            refreshImage()
            if ($(this).hasClass('left-top')) {
                // place to left top corner
                var elem = $('.green-area .copyright');
                $('.green-area .left-top').html(elem);
                $(elem).not('.green-area .left-top .copyright').remove();
                $('.position_square .square.active').removeClass('active');
                $(this).addClass('active');
            }
            if ($(this).hasClass('right-top')) {
                // place to right top corner
                var elem = $('.green-area .copyright');
                $('.green-area .right-top').html(elem);
                $(elem).not('.green-area .right-top .copyright').remove();
                $('.position_square .square.active').removeClass('active');
                $(this).addClass('active');
            }
            if ($(this).hasClass('bottom-left')) {
                // place to right top corner
                var elem = $('.green-area .copyright');
                $('.green-area .left-bottom').html(elem);
                $(elem).not('.green-area .left-bottom .copyright').remove();
                $('.position_square .square.active').removeClass('active');
                $(this).addClass('active');
            }
            if ($(this).hasClass('bottom-right')) {
                // place to right top corner
                var elem = $('.green-area .copyright');
                $('.green-area .right-bottom').html(elem);
                $(elem).not('.green-area .right-bottom .copyright').remove();
                $('.position_square .square.active').removeClass('active');
                $(this).addClass('active');
            }
        })
        // change copyright type
        $('.copyright_type').on('change', function () {

                if ($(this).val() == 'text') {
                    refreshImage()

                    $('.image-handler').slideUp(300);
                    $('.text-handler').slideDown(300);
                }
                if ($(this).val() == 'image') {
                    $('.image-handler').slideDown(300);
                    $('.text-handler').slideUp(300);
                }
        })
        $('#copyright_type').change();

        $('#text_color').on('change', function () {
            refreshImage();
        })
        $('#text_copyright').on('change', function () {
            refreshImage();
        })
        $('#upload_files').on('change', function () {
            refreshImage();
        })

        function refreshImage() {

            var data = new FormData();
            data.append('type', $('#copyright_type').val());
            data.append('text', $('#text_copyright').val());
            data.append('enable_copyright', ($('#enable_copyright').prop('checked') == true) ? $('#enable_copyright').val() : '');
            data.append('reflection',  ($('#mirror').prop('checked') == true) ? 'on' : '');
            data.append('collor', $('#text_color').val());
            data.append('logo_pos', $('#logo_pos').val());
            data.append('scaling', $('#scaling').val());
            data.append('file', $('#upload_files').prop('files')[0]);
            $.ajax({
                url: 'admin.php?action=get_handled_image',
                contentType: false,
                processData: false,
                dataType: 'text',
                cache: false,
                data: data,
                type: 'POST',
                success: function (data) {
                    $('.image').attr('src', 'data:image/jpg;base64,' + data)
                }
            });

        }

    })
</script>

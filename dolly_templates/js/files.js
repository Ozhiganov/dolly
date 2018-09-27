// function if we select node

var setting = {

    callback: {
        beforeClick: BeforeClick,
        onClick: onClick
    }


};

function onClick(event, treeId, treeNode) {
    if (!treeNode.getParentNode()) {
        var parentdir = treeNode.getPath()[0].name;
    } else {

        // define tree parent folders
        var path = '';
        for (i = 0; i < treeNode.getPath().length; i++) {
            if (i == treeNode.getPath().length - 1) {
                var spacer = '';
            } else {
                var spacer = '/';
            }
            path += treeNode.getPath()[i].name + spacer;
            var parentdir = path;
        }

    }

    $('.bottom .file_link').html(parentdir.replace('//', '/'));
    $('.file_field').html(parentdir.replace('//', '/'));
    $('.bottom .file_bottom').show();

};

function BeforeClick() {
    // $.fancybox.reposition();
};
var zNodes =[

];

$(document).ready(function(){

    try {
        $.fn.zTree.init($("#file_chose_window .files"), setting, zNodes);
    } catch(e) {
    }


});

$('.forms_settings .item').dblclick(function() {
    if ($(this).hasClass('active')) {
        $(this).toggleClass('active');
        $('.forms_settings .item').removeClass('innactive');
        $(this).find('.hidden_settings').toggle();
        $(this).find('.thead').toggleClass('active');
        $(this).find('.typical_nav').show();
        $(this).find('.hidden_nav').hide();
    } else {
        $('.forms_settings .item.active').removeClass('active');
        $('.forms_settings .item').not(this).find('.hidden_settings').hide();
        $('.forms_settings .item').not(this).find('.thead').removeClass('active');
        $(this).removeClass('innactive');
        $('.forms_settings .item').not(this).addClass('innactive');
        $(this).find('.thead').toggleClass('active');
        $(this).toggleClass('active');
        $(this).find('.hidden_nav').toggle();
        $(this).find('.typical_nav').toggle();
        $(this).find('.hidden_settings').toggle();

    }
});

$('.forms_settings .item .cancel, .forms_settings .item .edit').click(function() {
    $(this).closest('.item').trigger('dblclick');
});

$('select.magic_select2').on('change', function() {
    if ($(this).val() == 'this') {
        $(this).parent().parent().find('.handler').hide();
    } else if ($(this).val() == 'mail') {
        $(this).parent().parent().find('.handler').hide();
        $(this).parent().parent().find('.handler_email').show();
    } else if ($(this).val() == 'script') {
        $(this).parent().parent().find('.handler').hide();
        $(this).parent().parent().find('.handler_scripts').show();
    } else {
        $(this).parent().parent().find('.handler').hide();
        $(this).parent().parent().find('.handler_other').show();
    }
})



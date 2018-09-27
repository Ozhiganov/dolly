<?php
return;
?>
<div class="topbar">
    <h1 class="main-title"><?=l10n()->file_editor?></h1>
</div>

<div class="visual_editor_box inner">
    <div class="chose_file">
        <div class="col-xs-4 pd0">
            <label for="file"><?=l10n()->select_file?></label>
        </div>
        <div class="col-xs-8 right pd0">
            <input type="file" class="hidden">
            <div class="col-xs-9">
                <div class="file_field">
                    <?=l10n()->select_file?>
                </div>
            </div>
            <div class="col-xs-3 right pd0">
                <div class="button open_file">
                    <?=l10n()->select?>
                </div>
            </div>
        </div>
    </div>
    
</div>

<script src="/dolly_templates/js/dist/jstree.js"></script>

<script type="text/javascript">
    $(function () {
        $('#jstree').jstree({
            'core': {'data' : { 'url' : function (node) {
                return node.id === '#' ?
                    '/admin.php?action=get_pages' :
                    '/admin.php?action=get_pages&parent=' + node.id;
            }}
            }
        }).on("changed.jstree", function (e, data) {
            if (data.selected.toString().indexOf('.htm') > 0) {
                $('.bottom .file_link').html(data.selected);
                $('.file_field').html(data.selected);
                $('.bottom .file_bottom').show();
            } else {
                $('#jstree').jstree('open_node', data.selected.toString());
            }
        });
    });

</script>
<!-- script src="../dolly_js/main.js"></script -->

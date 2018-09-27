<script type="text/javascript">
    function startLoadingAnimation()
    {
        var imgObj = $("#loadImg");
        imgObj.show();

        var centerY = $(window).scrollTop() + ($(window).height() + imgObj.height())/3;
        var centerX = $(window).scrollLeft() + ($(window).width() + imgObj.width())/3;

        imgObj.offset({top:centerY, left:centerX});
    }

    function stopLoadingAnimation()
    {
        $("#loadImg").hide();
    }
</script>
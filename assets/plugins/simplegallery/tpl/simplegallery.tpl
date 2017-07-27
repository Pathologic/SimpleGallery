<style type="text/css">
	.sg_image {
        width:[+w+]px;
    }
    .sg_image .img {
		width:[+w+]px;
		height:[+h+]px;
	}
    .sg_image .name {
        width:[+w+]px;
    }
</style>
<script type="text/javascript">
var sgConfig = {
	rid:[+id+],
	_modxSiteUrl:'[+site_url+]',
	_xtRefreshBtn:[+refreshBtn+],
	_xtThumbPrefix:'[+thumb_prefix+]',
	_xtAjaxUrl:'[+url+]',
	_xtTpls:'[+tpls+]',
	sgLoaded:false,
	sgSort:null,
	sgLastChecked:null,
	sgBeforeDragState:null,
    sgDisableSelectAll:null,
    clientResize:[+clientResize+]
};
(function($){
    $('#documentPane').on('click','#sg-tab',function(){
        if (!sgConfig.sgLoaded) {
            sgHelper.init();
            sgConfig.sgLoaded = true;
        }
        $('#sg_pages').pagination('select');
    });
    $(window).on('load', function(){
        if ($('#sg-tab')) {
            $('#sg-tab.selected').trigger('click');
        }
    });
})(jQuery)
</script>
<div id="SimpleGallery" class="tab-page">
<h2 class="tab" id="sg-tab">[+tabName+]</h2>
</div>

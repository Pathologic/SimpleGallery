<link rel="stylesheet" type="text/css" href="[+site_url+]assets/lib/SimpleTab/js/easy-ui/themes/bootstrap/easyui.css">
<link rel="stylesheet" type="text/css" href="[+site_url+]assets/lib/SimpleTab/js/easy-ui/themes/icon.css">
<link rel="stylesheet" type="text/css" href="[+site_url+]assets/plugins/simplegallery/css/style.css">
<style type="text/css">
.sg_image {
	width:[+w+]px;
}
.sg_image .img {
	width:[+w+]px;
	height:[+h+]px;
    background-color:#fff;
    background-position: center center;
}
.sg_image .del {
	background: url([+theme+]/images/icons/delete.png) 0 0 no-repeat;
}
.sg_image .edit {
	background: url([+theme+]/images/icons/error.png) 0 0 no-repeat;
	left:10px;
	top:10px;
}
.btn-deleteAll {
	background: url([+theme+]/images/icons/trash.png) -2px center no-repeat;
}
.btn-move {
    background: url([+theme+]/images/icons/layout_go.png) center center no-repeat;
}
.btn-placeTop {
    background: url([+theme+]/images/icons/arrow_up.png) center center no-repeat;
}
.btn-placeBottom {
    background: url([+theme+]/images/icons/arrow_down.png) center center no-repeat;
}
</style>
<script type="text/javascript">
var sgConfig = {
	rid:[+id+],
	_modxTheme:'[+theme+]',
	_modxSiteUrl:'[+site_url+]',
	_xtRefreshBtn:'[+refreshBtn+]',
	_xtThumbPrefix:'[+thumb_prefix+]',
	_xtAjaxUrl:'[+url+]',
	_xtTpls:'[+tpls+]',
	sgLoaded:false,
	sgSort:null,
	sgFileId:0,
	sgLastChecked:null,
	sgBeforeDragState:null
};
(function($) {
	$(window).load(function(){
    	if ($('#sg-tab')) {
    		$('#sg-tab.selected').trigger('click');    
		}
	});
	$(document).ready(function() {
		$('#sg-tab').click(function(){
    		if (sgConfig.sgLoaded) {
        		$('#sg_pages').pagination('select');
    		} else {
        		sgHelper.init();
        		$('#sg_pages').pagination('select');
				sgConfig.sgLoaded = true;
    		}
		})
	})
})(jQuery);
</script>
<div id="SimpleGallery" class="tab-page" style="display:none;width:100%;-moz-box-sizing: border-box; box-sizing: border-box;">
<h2 class="tab" id="sg-tab">[+tabName+]</h2>
</div>
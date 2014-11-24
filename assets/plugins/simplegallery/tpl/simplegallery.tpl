<link rel="stylesheet" type="text/css" href="[+site_url+]assets/plugins/simplegallery/js/easy-ui/themes/bootstrap/easyui.css">
<link rel="stylesheet" type="text/css" href="[+site_url+]assets/plugins/simplegallery/js/easy-ui/themes/icon.css">
<link rel="stylesheet" type="text/css" href="[+site_url+]assets/plugins/simplegallery/css/style.css">
<style type="text/css">
.sg_image {
	width:[+w+]px;
}
.sg_image img {
	width:[+w+]px;
	height:[+h+]px;
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
</style>
<script type="text/javascript">
var rid = [+id+],
	_modxManagerUrl = '[+manager_url+]',
	_modxTheme = '[+theme+]',
	_modxSiteUrl = '[+site_url+]',
	_xtRefreshBtn = '[+refreshBtn+]',
	_xtThumbPrefix = '[+thumb_prefix+]',
	_xtAjaxUrl = '[+url+]',
	_xtTpls = '[+tpls+]',
    sgLoaded = false,
    sgSort = null,
    sgFileId = 0,
    sgLastChecked = null,
    sgBeforeDragState = null;
(function($) {
	$(window).load(function(){
    	if ($('#sg-tab')) {
    		$('#sg-tab.selected').trigger('click');    
		}
	})
	$(document).ready(function() {
		$('#sg-tab').click(function(){
    		if (sgLoaded) {
        		$('#sg_pages').pagination('select');
    		} else {
        		sgHelper.init();
        		$('#sg_pages').pagination('select');
        		sgLoaded = true;
    		}
		})
	})
})(jQuery);
</script>
<div id="SimpleGallery" class="tab-page" style="display:none;width:100%;-moz-box-sizing: border-box; box-sizing: border-box;">
<h2 class="tab" id="sg-tab">[+tabName+]</h2>
</div>
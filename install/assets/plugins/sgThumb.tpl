//<?php
/**
 * sgThumb
 * 
 * Plugin to create thumbnails for SimpleGallery images
 *
 * @category 	plugin
 * @version 	0.9
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Pathologic (m@xim.name)
 * @internal	@properties &tconfig=Thumbnails Configuration;textarea;[
{"rid":1,"options":"w%3D1140%26h%3D500%26q%3D96%26f%3Djpg","folder":"slider"},
{"template":11,"options":"w%3D120%26h%3D120%26zc%3D1","folder":"120x120"},
{"template":12,"options":"w%3D355","folder":"355x"}
]
 * @internal	@events OnFileBrowseUpload,OnSimpleGalleryRefresh,OnSimpleGalleryDelete
 * @internal    @installset base
 */

require MODX_BASE_PATH.'assets/plugins/simplegallery/plugin.sgthumb.php';
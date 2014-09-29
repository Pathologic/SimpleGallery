//<?php
/**
 * SimpleGallery
 * 
 * Plugin to create image galleries
 *
 * @category 	plugin
 * @version 	0.1
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Pathologic (m@xim.name)
 * @internal	@properties &tabName=Tab name;text;SimpleGallery &templates=Templates;text; &role=Roles;text; &folder=Galleries folder;text;assets/galleries/ &thumbsCache=Thumbs cache folder;text;assets/.sgThumbs/   &w=Thumbs width;text;140 &h=Thumbs height;text;105 &createTable=Create table;list;Yes,No;No
 * @internal	@events OnDocFormRender,OnEmptyTrash
 * @internal    @installset base
 */

require MODX_BASE_PATH.'assets/plugins/simplegallery/plugin.simplegallery.php';
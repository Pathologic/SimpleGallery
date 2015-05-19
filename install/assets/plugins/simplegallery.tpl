//<?php
/**
 * SimpleGallery
 * 
 * Plugin to create image galleries
 *
 * @category 	plugin
 * @version 	0.9
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Pathologic (m@xim.name)
 * @internal	@properties &tabName=Tab name;text;SimpleGallery &controller=Controller class;text; &templates=Templates;text; &documents=Documents;text; &ignoreDoc=Ignore Documents;text; &role=Roles;text; &folder=Galleries folder;text;assets/galleries/ &thumbsCache=Thumbs cache folder;text;assets/.sgThumbs/   &w=Thumbs width;text;140 &h=Thumbs height;text;105  &customThumbOptions=Custom thumb options;text; &clientResize=Client Resize;list;No,Yes;No
 * @internal	@events OnDocFormRender,OnEmptyTrash
 * @internal    @installset base
 */

require MODX_BASE_PATH.'assets/plugins/simplegallery/plugin.simplegallery.php';
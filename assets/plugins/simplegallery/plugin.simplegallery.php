<?php
if (IN_MANAGER_MODE != 'true') die();
$e = &$modx->event;
if ($e->name == 'OnDocFormRender' && !!$id) {
	include_once (MODX_BASE_PATH.'assets/plugins/simplegallery/lib/plugin.class.php');
	global $modx_lang_attribute;
	$simpleGallery = new \SimpleGallery\sgPlugin($modx,$modx_lang_attribute);
	$output = $simpleGallery->render();
	if ($output) $e->output($output);
}
if ($e->name == 'OnEmptyTrash') {
	$where = implode(',',$ids);
	$modx->db->delete($modx->getFullTableName("sg_images"), "`sg_rid` IN ($where)");
	include_once (MODX_BASE_PATH.'assets/plugins/simplegallery/lib/plugin.class.php');
	$simpleGallery = new \SimpleGallery\sgPlugin($modx);
	$simpleGallery->clearFolders($ids,MODX_BASE_PATH.$e->params['thumbsCache'].$e->params['folder']);
	$simpleGallery->clearFolders($ids,MODX_BASE_PATH.$e->params['folder']);
	$sql = "ALTER TABLE {$modx->getFullTableName('sg_images')} AUTO_INCREMENT = 1";
	$rows = $modx->db->query($sql);

}

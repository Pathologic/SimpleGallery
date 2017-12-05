<?php
if (IN_MANAGER_MODE != 'true') die();
$e = &$modx->event;
if ($e->name == 'OnDocFormRender') {
    include_once(MODX_BASE_PATH . 'assets/plugins/simplegallery/lib/plugin.class.php');
    global $modx_lang_attribute, $richtexteditorIds;
    //Hack to check if TinyMCE scripts are loaded
    if (isset($richtexteditorIds['TinyMCE4'])) {
        $modx->loadedjscripts['TinyMCE4'] = array('version' => '4.3.6');
    }
    $plugin = new \SimpleGallery\sgPlugin($modx, $modx_lang_attribute);
    if ($id) {
        $output = $plugin->render();
    } else {
        $output = $plugin->renderEmpty();
    }
    if ($output) $e->output($output);
}
if ($e->name == 'OnEmptyTrash') {
    if (empty($ids)) return;
    include_once(MODX_BASE_PATH . 'assets/plugins/simplegallery/lib/plugin.class.php');
    $plugin = new \SimpleGallery\sgPlugin($modx);
    $where = implode(',', $ids);
    $modx->db->delete($plugin->_table, "`sg_rid` IN ({$where})");
    $plugin->clearFolders($ids, MODX_BASE_PATH . $e->params['thumbsCache'] . $e->params['folder']);
    $plugin->clearFolders($ids, MODX_BASE_PATH . $e->params['folder']);
    $sql = "ALTER TABLE {$plugin->_table} AUTO_INCREMENT = 1";
    $rows = $modx->db->query($sql);
}

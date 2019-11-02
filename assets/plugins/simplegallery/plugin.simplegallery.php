<?php
if (IN_MANAGER_MODE != 'true') {
    die();
}
$e = $modx->event;
if ($e->name == 'OnDocFormRender') {
    include_once(MODX_BASE_PATH . 'assets/plugins/simplegallery/lib/plugin.class.php');
    global $richtexteditorIds;
    //Hack to check if TinyMCE scripts are loaded
    if (isset($richtexteditorIds['TinyMCE4'])) {
        $modx->loadedjscripts['TinyMCE4'] = array('version' => '4.3.6');
    }
    $plugin = new \SimpleGallery\sgPlugin($modx, $modx->getConfig('lang_code'));
    if ($id) {
        $output = $plugin->render();
    } else {
        $output = $plugin->renderEmpty();
    }
    if ($output) {
        $modx->event->output($output);
    }
}
if ($e->name == 'OnEmptyTrash') {
    if (empty($ids)) {
        return;
    }
    include_once(MODX_BASE_PATH . 'assets/plugins/simplegallery/lib/plugin.class.php');
    $plugin = new \SimpleGallery\sgPlugin($modx);
    $where = implode(',', $ids);
    $modx->db->delete($plugin->_table, "`sg_rid` IN ({$where})");
    $plugin->clearFolders($ids, MODX_BASE_PATH . $e->params['thumbsCache'] . $e->params['folder']);
    $plugin->clearFolders($ids, MODX_BASE_PATH . $e->params['folder']);
    $sql = "ALTER TABLE {$plugin->_table} AUTO_INCREMENT = 1";
    $rows = $modx->db->query($sql);
}
if ($e->name == 'OnDocDuplicate' && isset($allowDuplicate) && $allowDuplicate == 'Yes') {
    include_once(MODX_BASE_PATH . 'assets/plugins/simplegallery/lib/plugin.class.php');
    $plugin = new \SimpleGallery\sgPlugin($modx);
    $sql = "SHOW COLUMNS FROM {$plugin->_table}";
    $rows = $modx->db->query($sql);
    $columns = array();
    while ($row = $modx->db->getRow($rows)) {
        if ($row['Key'] == 'PRI') {
            continue;
        }
        $columns[] = '`' . $row['Field'] . '`';
    }
    $q = $modx->db->query("SELECT `sg_id` FROM {$plugin->_table} WHERE `sg_rid`={$id} LIMIT 1");
    if (!$modx->db->getValue($q)) {
        return;
    }
    $fields = implode(',', $columns);
    $oldFolder = $e->params['folder'] . $id . '/';
    $newFolder = $e->params['folder'] . $new_id . '/';
    $values = str_replace(['`sg_rid`', '`sg_image`'], [$new_id, "REPLACE(`sg_image`,'{$oldFolder}', '{$newFolder}')"],
        $fields);
    $sql = "INSERT INTO {$plugin->_table} ({$fields}) SELECT {$values} FROM {$plugin->_table} WHERE `sg_rid`={$id}";
    $modx->db->query($sql);
    $plugin->copyFolders(MODX_BASE_PATH . $oldFolder, MODX_BASE_PATH . $newFolder);
    $oldFolder = $e->params['thumbsCache'] . $e->params['folder'] . $id . '/';
    $newFolder = $e->params['thumbsCache'] . $e->params['folder'] . $new_id . '/';
    $plugin->copyFolders(MODX_BASE_PATH . $oldFolder, MODX_BASE_PATH . $newFolder);
}

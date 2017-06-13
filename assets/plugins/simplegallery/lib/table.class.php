<?php namespace SimpleGallery;

require_once(MODX_BASE_PATH . 'assets/lib/SimpleTab/table.abstract.php');
use SimpleTab\dataTable;

/**
 * Class sgData
 * @package SimpleGallery
 */
class sgData extends dataTable
{
    protected $table = 'sg_images';
    protected $parentTable = 'site_content';
    protected $pkName = 'sg_id';
    protected $indexName = 'sg_index';
    protected $rfName = 'sg_rid';

    protected $jsonFields = array(
        'sg_properties'
    );

    protected $file = null;

    protected $default_field = array(
        'sg_image'       => '',
        'sg_title'       => '',
        'sg_description' => '',
        'sg_properties'  => '',
        'sg_add'         => '',
        'sg_isactive'    => 1,
        'sg_rid'         => 0,
        'sg_index'       => 0,
        'sg_createdon'   => '',
    );
    public $thumbsCache = 'assets/.sgThumbs/';
    protected $params = array();
    /**
     * @var \Helpers\FS
     */
    protected $fs = null;

    /**
     * @param $ids
     * @param int $rid
     * @param bool $fire_events
     * @return mixed
     */
    public function deleteAll($ids, $rid, $fire_events = false)
    {
        $ids = $this->cleanIDs($ids, ',', array(0));
        if (empty($ids) || is_scalar($ids)) {
            return false;
        }
        $_ids = $this->sanitarIn($ids);
        $images = $this->query("SELECT `sg`.*,`c`.`template` FROM {$this->makeTable($this->table)} `sg` LEFT JOIN {$this->makeTable($this->parentTable)} `c` ON `c`.`id` = `sg`.`sg_rid` WHERE `sg`.`sg_id` IN ({$_ids}) AND `sg`.`sg_rid`={$rid}");
        $tmp = array();
        while ($row = $this->modx->db->getRow($images)) {
            $row['filepath'] = $this->fs->takeFileDir($row['sg_image']);
            $row['name'] = $this->fs->takeFileName($row['sg_image']);
            $row['filename'] = $this->fs->takeFileBasename($row['sg_image']);
            $row['ext'] = $this->fs->takeFileExt($row['sg_image']);
            $row['mime'] = $this->fs->takeFileMIME($row['sg_image']);
            $results = $this->getInvokeEventResult('OnBeforeSimpleGalleryDelete',$row,$fire_events);
            $flag = is_array($results) && !empty($results);
            if (!$flag) $tmp[$row['sg_id']] = $row;
        }

        $ids = array_keys($tmp);
        $out = !empty($ids);
        if ($out) {
            $out = parent::deleteAll($ids, $rid, $fire_events);
            foreach ($tmp as $id => $image) {
                $this->deleteThumb($image['sg_image']);
                $this->invokeEvent('OnSimpleGalleryDelete', $image, $fire_events);
            }
        }

        return $out;
    }

    /**
     * @param $ids
     * @param $rid
     * @param int $to
     * @param bool $fire_events
     * @return bool
     */
    public function move($ids, $rid, $to, $fire_events = false)
    {
        $ids = $this->cleanIDs($ids, ',', array(0));
        $templates = isset($this->params['templates']) ? $this->params['templates'] : array();
        $templates = $this->cleanIDs($templates, ',', array(0));
        $documents = isset($this->params['documents']) ? $this->params['documents'] : array();
        $documents = $this->cleanIDs($documents, ',', array(0));
        $ignoreDocuments = isset($this->params['ignoreDoc']) ? $this->params['ignoreDoc'] : array();
        $ignoreDocuments = $this->cleanIDs($ignoreDocuments, ',', array(0));

        if (empty($ids) || !$to) {
            return false;
        }

        $template = $this->getTemplate($to);
        $flag = false;
        if (!empty($templates) && in_array($template, $templates)) {
            $flag = true;
        }
        if (!empty($documents) && in_array($to,$documents) &&!in_array($to,$ignoreDocuments)) {
            $flag = $flag || true;
        }
        if (!$flag) {
            return false;
        }

        $ids = implode(',', $ids);
        $rows = $this->query("SELECT `sg_id`,`sg_image` FROM {$this->makeTable($this->table)} WHERE `sg_id` IN ({$ids})");
        $images = $this->modx->db->makeArray($rows);
        $_old = $this->params['folder'] . $rid . '/';
        $_new = $this->params['folder'] . $to . '/';
        $flag = $this->fs->makeDir(MODX_BASE_PATH . $_new, $this->modx->config['new_folder_permissions']);
        if ($flag) {
            foreach ($images as $image) {
                $oldFile = MODX_BASE_PATH . $image['sg_image'];
                $newFile = str_replace($_old, $_new, $oldFile);
                if (!@rename($oldFile, $newFile)) {
                    $this->modx->logEvent(0, 3, "Cannot move {$oldFile} to {$_new}", "SimpleGallery");
                } else {
                    $this->deleteThumb($image['sg_image']);
                    $this->invokeEvent('OnSimpleGalleryMove', array(
                        'sg_id'    => $image['sg_id'],
                        'sg_image' => $image['sg_image'],
                        'sg_rid'   => $rid,
                        'to'       => $to,
                        'oldFile'  => $oldFile,
                        'newFile'  => $newFile,
                        'template' => $template
                    ), $fire_events);
                }
            }
            $rows = $this->query("SELECT count(`sg_id`) FROM {$this->makeTable($this->table)} WHERE `sg_rid`={$to}");
            $index = $this->modx->db->getValue($rows);
            $this->query("UPDATE {$this->makeTable($this->table)} SET `sg_rid` = {$to}, `sg_image` = REPLACE(`sg_image`,'{$_old}','{$_new}') WHERE (`sg_id` IN ({$ids})) ORDER BY `sg_index` ASC");
            $out = $this->modx->db->getAffectedRows();
            $this->clearIndexes($ids, $rid);
            $this->query("SET @index := " . ($index - 1));
            $this->query("UPDATE {$this->makeTable($this->table)} SET `sg_index` = (@index := @index + 1) WHERE (`sg_id` IN ({$ids})) ORDER BY `sg_index` ASC");
        } else {
            $this->modx->logEvent(0, 3, "Cannot create {$_new} folder", "SimpleGallery");
            $out = false;
        }

        return $out;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        switch ($key) {
            case 'filepath': {
                $image = $this->get('sg_image');
                $out = $this->fs->takeFileDir($image);
                break;
            }
            case 'filename': {
                $image = $this->get('sg_image');
                $out = $this->fs->takeFileBasename($image);
                break;
            }
            case 'ext': {
                $image = $this->get('sg_image');
                $out = $this->fs->takeFileExt($image);
                break;
            }
            case 'mime': {
                $image = $this->get('sg_image');
                $out = $this->fs->takeFileMIME($image);
                break;
            }
            default: {
                $out = parent::get($key);
            }
        }

        return $out;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key, $value)
    {
        switch ($key) {
            case 'sg_image': {
                if (empty($value) || !is_scalar($value) || !$this->fs->checkFile($value)) {
                    $value = '';
                }
                break;
            }
            case 'sg_isactive':
            case 'sg_rid':
            case 'sg_index': {
                $value = (int)$value;
                if ($value < 0) {
                    $value = 0;
                }
                break;
            }
            case 'sg_createdon':
            case 'sg_add':
            case 'sg_description':
            case 'sg_title': {
                if (!is_scalar($value)) {
                    $value = '';
                }
                break;
            }
        }
        parent::set($key, $value);

        return $this;
    }

    /**
     * @param bool $fire_events
     * @param bool $clearCache
     * @return bool|int|null
     */
    public function save($fire_events = false, $clearCache = false)
    {
        $out = $this->_save($fire_events, $clearCache);
        if ($out === false) {
            $this->modx->logEvent(0, 3, implode("<br />", $this->getLog()), 'SimpleGallery');
            $this->clearLog();
        }

        return $out;
    }

    /**
     * @param bool $fire_events
     * @param bool $clearCache
     * @return int|null
     */
    protected function _save($fire_events = false, $clearCache = false)
    {
        if (empty($this->field['sg_image'])) {
            $this->log['emptyImage'] = 'Image is empty in <pre>' . print_r($this->field, true) . '</pre>';

            return false;
        }
        $rid = $this->get('sg_rid');
        if (empty($rid)) {
            $rid = $this->default_field['sg_rid'];
        }
        $rid = (int)$rid;

        $template = $this->getTemplate($rid);
        $this->set('template', $template);
        if ($this->newDoc) {
            $q = $this->query('SELECT count(`sg_id`) FROM ' . $this->makeTable($this->table) . ' WHERE `sg_rid`=' . $rid);
            $this->field['sg_index'] = $this->modx->db->getValue($q);
            $this->touch('sg_createdon');
        }

        $fields = $this->toArray();
        $fields['sgObj'] = $this;
        $fields['newDoc'] = $this->newDoc;
        $results = $this->getInvokeEventResult('OnBeforeSimpleGallerySave', $fields, $fire_events);
        $flag = is_array($results) && !empty($results);
        $out = null;
        if (!$flag && ($out = parent::save($fire_events, $clearCache))) {
            $fields = $this->toArray();
            $this->invokeEvent('OnSimpleGallerySave', $fields, $fire_events);
        }

        return $out;
    }

    /**
     * @param int|array $item
     * @param bool $fire_events
     */
    public function refresh($item, $fire_events = false)
    {
        $fields = array();
        if (is_int($item)) {
            $this->edit($item);
            if ($this->getID()) {
                $fields = $this->toArray();
                $fields['template'] = $this->getTemplate($fields['sg_rid']);
                $fields['filepath'] = $this->get('filepath');
                $fields['filename'] = $this->get('filename');
            }
        } elseif (is_array($item)) {
            $fields = $item;
            $fields['sg_properties'] = \jsonHelper::jsonDecode($fields['sg_properties'], array('assoc' => true), true);
            $fields['filepath'] = $this->fs->takeFileDir($fields['sg_image']);
            $fields['filename'] = $this->fs->takeFileBasename($fields['sg_image']);
        }
        if ($fields) {
            $this->invokeEvent('OnSimpleGalleryRefresh', $fields, $fire_events);
        }
    }

    /**
     * @param $rid
     * @return mixed
     */
    protected function getTemplate($rid)
    {
        $q = $this->query("SELECT `template` FROM {$this->makeTable($this->parentTable)} WHERE `id`={$rid} LIMIT 1");

        return $this->modx->db->getValue($q);
    }

    /**
     * @param $file
     * @param $rid
     * @param string $title
     * @param bool $fire_events
     * @return bool|int
     */
    public function upload($file, $rid, $title = '', $fire_events = false)
    {
        $out = false;
        $file = $this->fs->relativePath($file);
        if (!$this->fs->checkFile($file)) {
            $this->log['fileFailed'] = 'File check failed: ' . $file;

            return $out;
        }
        $info = getimagesize(MODX_BASE_PATH . $file);
        $options = array();
        if ($info[0] > $this->modx->config['maxImageWidth'] || $info[1] > $this->modx->config['maxImageHeight']) {
            $options[] = "w={$this->modx->config['maxImageWidth']}&h={$this->modx->config['maxImageHeight']}";
        }

        $ext = $this->fs->takeFileExt($file, true);
        if (in_array($ext, array('jpg', 'jpeg')) && isset($this->params['jpegQuality'])) {
            $quality = 100 * $this->params['jpegQuality'];
            $options[] = "q={$quality}&ar=x";
        }

        $options = implode('&', $options);
        if (empty($options) || (isset($this->params['clientResize']) && $this->params['clientResize'] == 'Yes') || (isset($this->params['skipPHPThumb']) && $this->params['skipPHPThumb'] == 'Yes') ? true : @$this->makeThumb('',
            $this->fs->relativePath($file), $options)
        ) {
            $info = getimagesize(MODX_BASE_PATH . $file);
            $properties = array(
                'width'  => $info[0],
                'height' => $info[1],
                'size'   => $this->fs->fileSize($file)
            );
            $this->create(array(
                'sg_image'      => $this->fs->relativePath($file),
                'sg_rid'        => $rid,
                'sg_title'      => $title,
                'sg_properties' => $properties
            ));

            $results = $this->getInvokeEventResult('OnBeforeFileBrowserUpload', array(
                'sgObj'    => $this,
                'filepath' => $this->get('filepath'),
                'filename' => $this->get('filename'),
                'template' => $this->get('template'),
                'sg_rid'   => $rid
            ), $fire_events);
            $flag = is_array($results) && !empty($results);
            if (!$flag) {
                $out = $this->save($fire_events);
            }
            if ($out) {
                $this->invokeEvent('OnFileBrowserUpload', array(
                    'sgObj'    => $this,
                    'filepath' => $this->get('filepath'),
                    'filename' => $this->get('filename'),
                    'template' => $this->get('template'),
                    'sg_rid'   => $rid
                ), $fire_events);
                $this->makeBackEndThumb($this->get('sg_image'));
            }
        }

        return $out;
    }

    /**
     * @param $url
     * @return bool
     */
    public function makeBackEndThumb($url)
    {
        $w = 140;
        $h = 105;
        $thumbsCache = $this->thumbsCache;
        if (isset($this->params)) {
            if (isset($this->params['thumbsCache'])) {
                $thumbsCache = $this->params['thumbsCache'];
            }
            if (isset($this->params['w'])) {
                $w = $this->params['w'];
            }
            if (isset($this->params['h'])) {
                $h = $this->params['h'];
            }
        }
        $thumbOptions = isset($this->params['customThumbOptions']) ? $this->params['customThumbOptions'] : 'w=[+w+]&h=[+h+]&far=C&bg=FFFFFF&f=jpg';
        $thumbOptions = urldecode(str_replace(array('[+w+]', '[+h+]'), array($w, $h), $thumbOptions));
        return $this->makeThumb($thumbsCache, $url, $thumbOptions);
    }
}

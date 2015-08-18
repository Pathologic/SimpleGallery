<?php
namespace SimpleGallery;
require_once (MODX_BASE_PATH . 'assets/lib/SimpleTab/table.abstract.php');

class sgData extends \SimpleTab\dataTable {
	/* @var autoTable $table */
	protected $table = 'sg_images';
	protected $pkName = 'sg_id';
    protected $indexName = 'sg_index';
    protected $rfName = 'sg_rid';
	
	protected $jsonFields = array(
		'sg_properties'
	);
	
	protected $file = null;

	protected $default_field = array(
		'sg_image' => '',
		'sg_title' => '',
		'sg_description' => '',
		'sg_properties' => '',
		'sg_add' => '',
		'sg_isactive' => 1,
		'sg_rid' => 0,
		'sg_index' => 0,
		'sg_createdon' => '',
	);
	public $thumbsCache = 'assets/.sgThumbs/';
	protected $params = array();
	protected $fs = null;
	
    /**
     * @param $ids
     * @param null $fire_events
     * @return mixed
     */
    public function deleteAll($ids, $rid, $fire_events = NULL) {
		$_ids = $this->cleanIDs($ids, ',', array(0));
		if(empty($_ids) || is_scalar($_ids)) return false;
		$images = $this->query('SELECT `sg_id`,`sg_image` FROM '.$this->makeTable($this->table).' WHERE `sg_id` IN ('.$this->sanitarIn($_ids).')');
        $out = parent::deleteAll($ids, $rid, $fire_events);
    	$rows = $this->query("SELECT `template` FROM {$this->makeTable('site_content')} WHERE id={$rid}");
        $template = $this->modx->db->getValue($rows);
        while ($row = $this->modx->db->getRow($images)) {
			$this->deleteThumb($row['sg_image']);
			$this->invokeEvent('OnSimpleGalleryDelete',array(
				'id'		=>	$row['sg_id'],
				'filepath' => $this->fs->takeFileDir($row['sg_image']),
				'name' => $this->fs->takeFileName($row['sg_image']),
				'filename' => $this->fs->takeFileBasename($row['sg_image']),
				'ext' => $this->fs->takeFileExt($row['sg_image']),
				'mime' => $this->fs->takeFileMIME($row['sg_image']),
				'template'	=>	$template
				),$fire_events);
		}
		return $out;
	}

    /**
     * @param $ids
     * @param int $to
     * @return bool
     */
    public function move($ids, $rid, $to, $fire_events = NULL) {
        $ids = $this->cleanIDs($ids, ',', array(0));
        $templates = isset($this->params['templates']) ? $this->params['templates'] : array();
        $templates = $this->cleanIDs($templates,',',array(0));
        if(empty($ids) || empty($templates) || is_scalar($ids) || is_scalar($templates) || !$to) return false;
        $rows = $this->query("SELECT `template` FROM {$this->makeTable('site_content')} WHERE id={$to}");
        $template = $this->modx->db->getValue($rows);
        if (!in_array($template,$templates)) return;
        $ids = implode(',',$ids);
        $rows = $this->query("SELECT `sg_id`,`sg_image` FROM {$this->makeTable($this->table)} WHERE `sg_id` IN ({$ids})");
        $images = $this->modx->db->makeArray($rows);
        $_old = $this->params['folder'].$rid.'/';
        $_new = $this->params['folder'].$to.'/';
        $flag = $this->fs->makeDir(MODX_BASE_PATH.$_new, $this->modx->config['new_folder_permissions']);
        if ($flag) {
            foreach ($images as $image) {
                $oldFile = MODX_BASE_PATH . $image['sg_image'];
                $newFile = str_replace($_old, $_new, $oldFile);
                if (!@rename($oldFile, $newFile)) {
                    $this->modx->logEvent(0, 3, "Cannot move {$oldFile} to {$_new}", "SimpleGallery");
                } else {
                    $this->deleteThumb($image['sg_image']);
                    $this->invokeEvent('OnSimpleGalleryMove',array(
                        'id'        => $image['sg_id'],
                        'image'     => $image['sg_image'],
                        'rid'       => $rid,
                        'to'        => $to,
                        'oldFile'   => $oldFile,
                        'newFile'   => $newFile,
                        'template'  => $template
                    ),$fire_events);
                }
            }
            $rows = $this->query("SELECT count(`sg_id`) FROM {$this->makeTable($this->table)} WHERE `sg_rid`={$to}");
            $index = $this->modx->db->getValue($rows);
            $this->query("UPDATE {$this->makeTable($this->table)} SET `sg_rid` = {$to}, `sg_image` = REPLACE(`sg_image`,'{$_old}','{$_new}') WHERE (`sg_id` IN ({$ids})) ORDER BY `sg_index` ASC");
            $out = $this->modx->db->getAffectedRows();
            $this->clearIndexes($ids,$rid);
            $this->query("SET @index := ".($index - 1));
            $this->query("UPDATE {$this->makeTable($this->table)} SET `sg_index` = (@index := @index + 1) WHERE (`sg_id` IN ({$ids})) ORDER BY `sg_index` ASC");
        } else {
            $this->modx->logEvent(0, 3, "Cannot create {$_new} folder", "SimpleGallery");
            $out = false;
        }
        return $out;
    }

	public function get($key){
		switch($key){
			case 'filepath':{
				$image = $this->get('sg_image');
				$out = $this->fs->takeFileDir($image);
				break;
			}
			case 'filename':{
				$image = $this->get('sg_image');
				$out = $this->fs->takeFileBasename($image);
				break;
			}
			case 'ext':{
				$image = $this->get('sg_image');
				$out = $this->fs->takeFileExt($image);
				break;
			}
			case 'mime':{
				$image = $this->get('sg_image');
				$out = $this->fs->takeFileMIME($image);
				break;
			}
			default:{
				$out = parent::get($key);
			}
		}
		return $out;
	}

	public function set($key, $value)
    {
    	switch($key) {
			case 'sg_image':{
				if (empty($value) || !is_scalar($value) || !$this->fs->checkFile($value)) {
					$value = '';
				}
				break;
			}
			case 'sg_isactive':
			case 'sg_rid':
			case 'sg_index':{
				$value = (int)$value;
				if($value < 0){
					$value = 0;
				}
				break;
			}
			case 'sg_createdon':
			case 'sg_add':
			case 'sg_description':
			case 'sg_title':{
				if(!is_scalar($value)){
					$value = '';
				}
				break;
			}
    	}
        parent::set($key, $value);
        return $this;
    }

    /**
     * @param null $fire_events
     * @param bool $clearCache
     */
    public function save($fire_events = null, $clearCache = false) {
		$out = $this->_save($fire_events, $clearCache);
		if($out === false){
			$this->modx->logEvent(0, 3, implode("<br />", $this->getLog()), 'SimpleGallery');
			$this->clearLog();
		}
		return $out;
	}
	protected function _save($fire_events = null, $clearCache = false) {
		if (empty($this->field['sg_image'])){
			$this->log['emptyImage'] = 'Image is empty in <pre>' . print_r($this->field, true) . '</pre>';
			return false;
		}
		$rid = $this->get('sg_rid');
		if(empty($rid)){
			$rid = $this->default_field['sg_rid'];
		}
		$rid = (int)$rid;
		if ($this->newDoc) {	
			$q = $this->query('SELECT count(`sg_id`) FROM '.$this->makeTable($this->table).' WHERE `sg_rid`='.$rid);
			$this->field['sg_index'] = $this->modx->db->getValue($q);
			$this->touch('sg_createdon');
		}
		$q = $this->query('SELECT `template` FROM '.$this->makeTable('site_content').' WHERE id='.$rid);
		$template = $this->modx->db->getValue($q);
		if ($out = parent::save($fire_events, $clearCache)) {
			if ($this->newDoc) {
				$this->invokeEvent('OnFileBrowserUpload',array(
					'filepath' => $this->get('filepath'),
					'filename' => $this->get('filename'),
					'template' => $template,
                    'rid'      => $rid
					),$fire_events);
			}
			$fields = $this->field;
			$fields['template'] = $template;
            $fields['sg_id'] = $out;
            $fields['newDoc'] = $this->newDoc;
			$this->invokeEvent('OnSimpleGallerySave',$fields,$fire_events);
		}
		return $out;
	}

    /**
     * @param $id
     */
    public function refresh($id, $fire_events = null) {
		$fields = $this->edit($id)->toArray();
		$q = $this->query('SELECT template FROM '.$this->makeTable('site_content').' WHERE id='.$fields['sg_rid']);
		$fields['template'] = $this->modx->db->getValue($q);
		$this->invokeEvent('OnSimpleGalleryRefresh',$fields,$fire_events);
	}

}
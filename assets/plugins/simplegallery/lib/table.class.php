<?php
namespace SimpleGallery;
require_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/autoTable.abstract.php');
require_once (MODX_BASE_PATH . 'assets/lib/Helpers/FS.php');
require_once (MODX_BASE_PATH . 'assets/lib/Helpers/PHPThumb.php');

class sgData extends \autoTable {
	/* @var autoTable $table */
	protected $table = 'sg_images';
	protected $pkName = 'sg_id';
	
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
     * @param $modx
     * @param bool $debug
     */
    public function __construct($modx, $debug = false) {
		parent::__construct($modx, $debug);
        $this->modx = $modx;
        $this->params = (isset($modx->event->params) && is_array($modx->event->params)) ? $modx->event->params : array();
        $this->fs = \Helpers\FS::getInstance();
	}

	public function fieldNames(){
		$fields = array_keys($this->getDefaultFields());
		$fields[] = $this->fieldPKName();
		return $fields;
	}

    /**
     * @param $ids
     * @param null $fire_events
     * @return mixed
     */
    public function deleteAll($ids, $rid, $fire_events = NULL) {
		$ids = $this->cleanIDs($ids, ',', array(0));
		if(empty($ids) || is_scalar($ids)) return false;
		$ids = implode(',',$ids);
        $rows = $this->query("SELECT `template` FROM {$this->makeTable('site_content')} WHERE id={$rid}");
		$template = $this->modx->db->getValue($rows);
		$images = $this->query('SELECT `sg_id`,`sg_image` FROM '.$this->makeTable($this->table).' WHERE `sg_id` IN ('.$this->sanitarIn($ids).')');
		$this->clearIndexes($ids,$rid);
        $out = $this->delete($ids, $fire_events);
        $this->query("ALTER TABLE {$this->makeTable($this->table)} AUTO_INCREMENT = 1");
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

    private function clearIndexes($ids, $rid) {
        $rows = $this->query("SELECT MIN(`sg_index`) FROM {$this->makeTable($this->table)} WHERE `sg_id` IN ({$ids})");
        $index = $this->modx->db->getValue($rows);
        $index = $index - 1;
        $this->query("SET @index := ".$index);
        $this->query("UPDATE {$this->makeTable($this->table)} SET `sg_index` = (@index := @index + 1) WHERE (`sg_index`>{$index} AND `sg_rid`={$rid} AND `sg_id` NOT IN ({$ids})) ORDER BY `sg_index` ASC");
        $out = $this->modx->db->getAffectedRows();
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

    /**
     * @param $ids
     * @param $dir
     * @param $rid
     */
    public function place($ids, $dir, $rid) {
        $ids = $this->cleanIDs($ids, ',', array(0));
        if(empty($ids) || is_scalar($ids)) return false;
        $rows = $this->query("SELECT count(`sg_id`) FROM {$this->makeTable($this->table)} WHERE `sg_rid`={$rid}");
        $index = $this->modx->db->getValue($rows);
        $cnt = count($ids);
        $ids = implode(',',$ids);
        if ($dir == 'top') {
            $this->query("SET @index := " . ($index - $cnt - 1));
            $this->query("UPDATE {$this->makeTable($this->table)} SET `sg_index` = (@index := @index + 1) WHERE (`sg_id` IN ({$ids})) ORDER BY `sg_index` ASC");
            $this->query("SET @index := -1");
        } else {
            $this->query("SET @index := -1");
            $this->query("UPDATE {$this->makeTable($this->table)} SET `sg_index` = (@index := @index + 1) WHERE (`sg_id` IN ({$ids})) ORDER BY `sg_index` ASC");
            $this->query("SET @index := " . ($cnt - 1));
        }
        $this->query("UPDATE {$this->makeTable($this->table)} SET `sg_index` = (@index := @index + 1) WHERE (`sg_id` NOT IN ({$ids})) AND `sg_rid` = {$rid} ORDER BY `sg_index` ASC");
        $out = $this->modx->db->getAffectedRows();
        return $out;
    }
    /**
     * @param $url
     * @param bool $cache
     */
    public function deleteThumb($url, $cache = false) {
    	$url = $this->fs->relativePath($url);
		if (empty($url)) return;
		if ($this->fs->checkFile($url)) unlink(MODX_BASE_PATH . $url);
        $dir = $this->fs->takeFileDir($url);
        $iterator = new \FilesystemIterator($dir);
        if (!$iterator->valid()) rmdir($dir);
		if ($cache) return;
		$thumbsCache = isset($this->params['thumbsCache']) ? $this->params['thumbsCache'] : $this->thumbsCache;
		$thumb = $thumbsCache.$url;
		if ($this->fs->checkFile($thumb)) $this->deleteThumb($thumb, true);
	}

    /**
     * @param $sourceIndex
     * @param $targetIndex
     * @param $sourceId
     * @param $rid
     * @return mixed
     */
    public function reorder($sourceIndex, $targetIndex, $sourceId, $rid) {
		/* more refactoring  needed */
		if ($sourceIndex < $targetIndex) {
			$rows = $this->query('UPDATE '.$this->makeTable($this->table).' SET `sg_index`=`sg_index`-1 WHERE `sg_index`<='.(int)$targetIndex.' AND `sg_index`>='.(int)$sourceIndex.' AND `sg_rid`='.(int)$rid);
		} else {
			$rows = $this->query('UPDATE '.$this->makeTable($this->table).' SET `sg_index`=`sg_index`+1 WHERE `sg_index`<'.(int)$sourceIndex.' AND `sg_index`>='.$targetIndex.' AND `sg_rid`='.(int)$rid);
		}
		return $this->query('UPDATE '.$this->makeTable($this->table).' SET `sg_index`='.(int)$targetIndex.' WHERE `sg_id`='.(int)$sourceId);
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
	public function touch(){
		$this->set('sg_createdon', date('Y-m-d H:i:s', time() + $this->modx->config['server_offset_time']));
		return $this;
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
			$this->field['sg_createdon'] = date('Y-m-d H:i:s', time() + $this->modx->config['server_offset_time']);
		}
		$q = $this->query('SELECT `template` FROM '.$this->makeTable('site_content').' WHERE id='.$rid);
		$template = $this->modx->db->getValue($q);
		if ($out = parent::save($fire_events, $clearCache)) {
			if ($this->newDoc) {
				$this->invokeEvent('OnFileBrowserUpload',array(
					'filepath' => $this->get('filepath'),
					'filename' => $this->get('filename'),
					'template' => $template
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

    /**
     * @param $folder
     * @param $url
     * @param $options
     * @return bool
     */
    public function makeThumb($folder,$url,$options) {
		if (empty($url)) return false;
		$thumb = new \Helpers\PHPThumb();
		$inputFile = MODX_BASE_PATH . $this->fs->relativePath($url);
		$outputFile = MODX_BASE_PATH. $this->fs->relativePath($folder). '/' . $this->fs->relativePath($url);
		$dir = $this->fs->takeFileDir($outputFile);
		$this->fs->makeDir($dir, $this->modx->config['new_folder_permissions']);
		if ($thumb->create($inputFile,$outputFile,$options)) {
			return true;
		} else {
			$this->modx->logEvent(0, 3, $thumb->debugMessages, 'SimpleGallery');
			return false;
		}
	}

    /**
     * @param  string $name
     * @return string
     */
    public function stripName($name) {
        return $this->modx->stripAlias($name);
    }
}
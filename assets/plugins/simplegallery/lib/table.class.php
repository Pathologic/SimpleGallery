<?php
namespace SimpleGallery;
require_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/autoTable.abstract.php');

class sgData extends \autoTable {
	/* @var autoTable $table */
	protected $table = 'sg_images';
	protected $pkName = 'sg_id';
	/* @var autoTable $_table */

	public $default_field = array(
		'sg_id' => 0,
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
	
    /**
     * @param $modx
     * @param bool $debug
     */
    public function __construct($modx, $debug = false) {
		parent::__construct($modx, $debug);
        $this->modx = $modx;
        $this->params = $modx->event->params;
	}

    /**
     * @param $ids
     * @param null $fire_events
     * @return mixed
     */
    public function delete($ids, $fire_events = NULL) {
		$ids = $this->cleanIDs($ids, ',', array(0));
		if(empty($ids) || is_scalar($ids) || !min($ids)) return false;
		$fields = $this->edit(min($ids))->toArray();
		$ids = implode(',',$ids);
		$rows = $this->query('SELECT `template` FROM '.$this->makeTable('site_content').' WHERE id='.$fields['sg_rid']);
		$template = $this->modx->db->getValue($rows);
		$images = $this->query('SELECT `sg_id`,`sg_image` FROM '.$this->makeTable($this->table).' WHERE `sg_id` IN ('.$this->sanitarIn($ids).')');
		$out = parent::delete($ids, $fire_events);
		while ($row = $this->modx->db->getRow($images)) {
			$this->deleteThumb($row['sg_image']);
			$this->invokeEvent('OnSimpleGalleryDelete',array(
				'id'		=>	$row['sg_id'],
				'filepath' => $this->get('filepath'),
				'filename' => $this->get('filename'),
				'template'	=>	$template
				),true);
		}
		$index = $fields['sg_index'] - 1;
		$this->query("SET @index := ".$index);
		$this->query("UPDATE {$this->makeTable($this->table)} SET `sg_index` = (@index := @index + 1) WHERE (`sg_index`>{$index} AND `sg_rid`={$fields['sg_rid']}) ORDER BY `sg_index` ASC");
		$this->query("ALTER TABLE {$this->makeTable($this->table)} AUTO_INCREMENT = 1");
		return $out;
	}

    /**
     * @param $url
     * @param bool $cache
     */
    public function deleteThumb($url, $cache = false) {
		if (empty($url)) return;
		$thumb = MODX_BASE_PATH.$url;
		if (file_exists($thumb) && is_readable($thumb)) {
			$dir = pathinfo($thumb);
			$dir = $dir['dirname'];
			unlink($thumb);
			$iterator = new \FilesystemIterator($dir);
			if (!$iterator->valid()) rmdir($dir);
		}
		if ($cache) return;
		$thumbsCache = $this->thumbsCache;
		if (isset($this->modx->pluginCache['SimpleGalleryProps'])) {
			$pluginParams = $this->modx->parseProperties($this->modx->pluginCache['SimpleGalleryProps']);
			if (isset($pluginParams['thumbsCache'])) $thumbsCache = $pluginParams['thumbsCache'];
		}
		$thumb = $thumbsCache.$url;
		if (file_exists(MODX_BASE_PATH.$thumb)) $this->deleteThumb($thumb, true);
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
				$out = MODX_BASE_PATH.str_replace('/'.$this->get('filename'), '', $image);
				break;
			}
			case 'filename':{
				$image = $this->get('sg_image');
				$out = end(explode('/', $image));
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
    	if ($key == 'sg_image') {
    		if (!file_exists(MODX_BASE_PATH.$value) || !is_readable(MODX_BASE_PATH.$value)) {
				$this->modx->logEvent(0, 3, 'File '.$value.' does not exist or is not readable', 'SimpleGallery');
    			$value = '';
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
		if ($this->newDoc) {
			$q = $this->query('SELECT count(`sg_id`) FROM '.$this->makeTable($this->table).' WHERE `sg_rid`='.$this->field['sg_rid']);
			$this->field['sg_index'] = $this->modx->db->getValue($q);
			$this->field['sg_createdon'] = date('Y-m-d H:i:s', time() + $this->modx->config['server_offset_time']);
		}
		$q = $this->query('SELECT `template` FROM '.$this->makeTable('site_content').' WHERE id='.$this->field['sg_rid']);
		$template = $this->modx->db->getValue($q);
		if (parent::save($fire_events, $clearCache)) {
			if ($this->newDoc) {
				$this->invokeEvent('OnFileBrowserUpload',array(
					'filepath' => $this->get('filepath'),
					'filename' => $this->get('filename'),
					'template' => $template
					),true);
			} 
			$fields = $this->field;
			$fields['template'] = $template;
			$this->invokeEvent('OnSimpleGallerySave',$fields,true);
		}
	}

    /**
     * @param $id
     */
    public function refresh($id) {
		$fields = $this->edit($id)->toArray();
		$q = $this->query('SELECT template FROM '.$this->makeTable('site_content').' WHERE id='.$fields['sg_rid']);
		$fields['template'] = $this->modx->db->getValue($q);
		$this->invokeEvent('OnSimpleGalleryRefresh',$fields,true);
	}

    /**
     * @param $folder
     * @param $url
     * @param $options
     * @return bool
     */
    public function makeThumb($folder,$url,$options) {
		if (empty($url)) return false;
		if(file_exists(MODX_BASE_PATH.'assets/snippets/phpthumb/phpthumb.class.php')){
			include_once(MODX_BASE_PATH.'assets/snippets/phpthumb/phpthumb.class.php');
		}
		$thumb = new \phpthumb();
		$thumb->sourceFilename = MODX_BASE_PATH.$url;
		$options = strtr($options, Array("," => "&", "_" => "=", '{' => '[', '}' => ']'));
		parse_str($options, $params);
		foreach ($params as $key => $value) {
        	$thumb->setParameter($key, $value);
    	}
  		$outputFilename = MODX_BASE_PATH.$folder.$url;
  		$dir = pathinfo($outputFilename, PATHINFO_DIRNAME);
  		if (!is_dir($dir)) mkdir($dir,intval($this->modx->config['new_folder_permissions'],8),true);
		if ($thumb->GenerateThumbnail() && $thumb->RenderToFile($outputFilename)) {
        	return true;
		} else {
			return false;
		}
	}
}
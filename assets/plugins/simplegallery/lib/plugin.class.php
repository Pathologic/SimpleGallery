<?php
namespace SimpleGallery;
include_once (MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php');
include_once (MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');
require_once (MODX_BASE_PATH . 'assets/lib/Helpers/FS.php');

class sgPlugin {
	public $modx = null;
	public $pluginName = 'SimpleGallery';
	public $params = array();
    public $table = 'sg_images';
	public $tpl = 'assets/plugins/simplegallery/tpl/simplegallery.tpl';
	public $jsListDefault = 'assets/plugins/simplegallery/js/scripts.json';
	public $jsListCustom = 'assets/plugins/simplegallery/js/custom.json';
    public $_table = '';
	protected $fs = null;
	
	public $DLTemplate = null;
	public $lang_attribute = '';

    /**
     * @param $modx
     * @param string $lang_attribute
     * @param bool $debug
     */
    public function __construct($modx, $lang_attribute = 'en', $debug = false) {
        $this->modx = $modx;
        $this->_table = $modx->getFullTableName($this->table);
        $this->lang_attribute = $lang_attribute;
        $this->params = $modx->event->params;
        if (!isset($this->params['template']) && $modx->event->name != 'OnEmptyTrash') {
            $this->params['template'] = array_pop($modx->getDocument($this->params['id'],'template','all','all'));
        }
        $this->DLTemplate = \DLTemplate::getInstance($this->modx);
        $this->fs = \Helpers\FS::getInstance();
    }

	public function clearFolders($ids = array(), $folder) {
        foreach ($ids as $id) $this->fs->rmDir($folder.$id.'/');
    }

    /**
     * @return string
     */
    public function prerender() {
        if (!$this->checkTable()) {
            $result = $this->createTable();
            if (!$result) {
                $this->modx->logEvent(0, 3, "Cannot create {$this->table} table.", $this->pluginName);
                return;
            }
			$this->registerEvents(array('OnSimpleGallerySave','OnSimpleGalleryDelete','OnSimpleGalleryRefresh'));
        }
        $output = '';
    	$templates = isset($this->params['templates']) ? explode(',',$this->params['templates']) : false;
		$roles = isset($this->params['roles']) ? explode(',',$this->params['roles']) : false;
		if (!$templates || ($templates && !in_array($this->params['template'],$templates)) || ($roles && !in_array($_SESSION['mgrRole'],$roles))) return false;
		$plugins = $this->modx->pluginEvent;
		if(array_search('ManagerManager',$plugins['OnDocFormRender']) === false && !isset($this->modx->loadedjscripts['jQuery'])) {
			//TODO: replace simplegallery to SimpleTab
			$output .= '<script type="text/javascript" src="'.$this->modx->config['site_url'].'assets/lib/simplegallery/js/jquery/jquery-1.9.1.min.js"></script>';
            $this->modx->loadedjscripts['jQuery'] = array('version'=>'1.9.1');
            $output .='<script type="text/javascript">var jQuery = jQuery.noConflict(true);</script>';
		}
		$tpl = MODX_BASE_PATH.$this->tpl;
		if($this->fs->checkFile($tpl)) {
			$output .= '[+js+]'.file_get_contents($tpl);
		} else {
			$this->modx->logEvent(0, 3, "Cannot load {$this->tpl} .", $this->pluginName);
		}
		return $output;
    }

    /**
     * @param $list
     * @param array $ph
     * @return string
     */
    public function renderJS($list,$ph = array()) {
    	$js = '';
    	$scripts = MODX_BASE_PATH.$list;
		if($this->fs->checkFile($scripts)) {
			$scripts = @file_get_contents($scripts);
			$scripts = $this->DLTemplate->parseChunk('@CODE:'.$scripts,$ph);
			$scripts = json_decode($scripts,true);
			foreach ($scripts['scripts'] as $name => $params) {
				if (!isset($this->modx->loadedjscripts[$name]) && $this->fs->checkFile($params['src'])) {
					$this->modx->loadedjscripts[$name] = array('version'=>$params['version']);
					$js .= '<script type="text/javascript" src="'.$this->modx->config['site_url'].$params['src'].'"></script>';
				} else {
                    $this->modx->logEvent(0, 3, 'Cannot load '.$params['src'], 'SimpleGallery');
                }
			}
		} else {
			if ($list == $this->jsListDefault) $this->modx->logEvent(0, 3, "Cannot load {$this->jsListDefault} .", $this->pluginName);
		}
		return $js;
    }

	/**
	 * @return array
	 */
	public function getTplPlaceholders() {
		$templates = trim(preg_replace('/,,+/',',',preg_replace('/[^0-9,]+/', '', $this->params['templates'])),',');
		$tpls = '[]';
		if (!empty($templates)) {
			$table = $this->modx->getFullTableName('site_templates');
			$sql = "SELECT id,templatename FROM $table WHERE id IN ($templates) ORDER BY templatename ASC";
			$tpls = json_encode($this->modx->db->makeArray($this->modx->db->query($sql)));
		}
		$ph = array(
			'lang'			=> 	$this->lang_attribute,
			'id'			=>	$this->params['id'],
			'url'			=> 	$this->modx->config['site_url'].'assets/plugins/simplegallery/ajax.php',
			'theme'			=>  MODX_MANAGER_URL.'media/style/'.$this->modx->config['manager_theme'],
			'tabName'		=>	$this->params['tabName'],
			'site_url'		=>	$this->modx->config['site_url'],
			'manager_url'	=>	MODX_MANAGER_URL,
			'thumb_prefix' 	=> 	$this->modx->config['site_url'].'assets/plugins/simplegallery/ajax.php?mode=thumb&url=',
			'kcfinder_url'	=> 	MODX_MANAGER_URL."media/browser/mcpuk/browse.php?type=images",
			'w' 			=> 	isset($this->params['w']) ? $this->params['w'] : '200',
			'h' 			=> 	isset($this->params['h']) ? $this->params['h'] : '150',
			'refreshBtn'	=>	($_SESSION['mgrRole'] == 1) ? '<div id="sg_refresh" class="btn-right btn"><div class="btn-text"><img src="'.MODX_MANAGER_URL.'media/style/'.$this->modx->config['manager_theme'].'/images/icons/refresh.png">\'+_sgLang[\'refresh_previews\']+\'</div></div>' : '',
			'tpls'			=>	$tpls
		);
		return $ph;
	}

    /**
     * @return string
     */
    public function render() {
		$output = $this->prerender();
		if ($output !== false) {
			$ph = $this->getTplPlaceholders();
			$ph['js'] = $this->renderJS($this->jsListDefault,$ph) . $this->renderJS($this->jsListCustom,$ph);
			$output = $this->DLTemplate->parseChunk('@CODE:'.$output,$ph);
		}
		return $output;
    }

    /**
     * @return bool
     */
    public function checkTable() {
        $sql = "SHOW TABLES LIKE '{$this->_table}'";
        return $this->modx->db->getRecordCount( $this->modx->db->query($sql));
    }

    public function createTable() {
    	$sql = <<< OUT
CREATE TABLE IF NOT EXISTS {$this->_table} (
`sg_id` int(10) NOT NULL auto_increment,
`sg_image` TEXT NOT NULL default '',
`sg_title` varchar(255) NOT NULL default '',
`sg_description` TEXT NOT NULL default '',
`sg_properties` TEXT NOT NULL default '',
`sg_add` TEXT NOT NULL default '',
`sg_isactive` int(1) NOT NULL default '1',
`sg_rid` int(10) default NULL,
`sg_index` int(10) NOT NULL default '0',
`sg_createdon` datetime NOT NULL,
PRIMARY KEY  (`sg_id`)
) ENGINE=MyISAM COMMENT='Datatable for SimpleGallery plugin.';
OUT;
    	return $this->modx->db->query($sql);
    }

	public function registerEvents($events = array(), $eventsType = '6') {
		$eventsTable = $this->modx->getFullTableName('system_eventnames');
		foreach ($events as $event) {
			$result = $this->modx->db->select('`id`',$eventsTable,"`name` = '{$event}'");
			if (!$this->modx->db->getRecordCount($result)) {
				$sql = "INSERT INTO {$eventsTable} VALUES (NULL, '{$event}', '{$eventsType}', '{$this->pluginName} Events')";
				if (!$this->modx->db->query($sql)) $this->modx->logEvent(0, 3, "Cannot register {$event} event.", $this->pluginName);
			}
		}
	}
}

<?php
namespace SimpleGallery;
include_once (MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php');
include_once (MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');

class sgPlugin {
	public $modx = null;
	public $params = array();
	public $DLTemplate = null;
	public $lang_attribute = '';
	
	public function __construct($modx, $lang_attribute = 'en', $debug = false) {
        $this->modx = $modx;
        $this->lang_attribute = $lang_attribute;
        $this->params = $modx->event->params;
        $this->DLTemplate = \DLTemplate::getInstance($this->modx);
        
    }

    public function clearFolders($ids = array(), $folder) {
		foreach ($ids as $id) $this->rmDir($folder.$id.'/');
    }

    public function rmDir($dirPath) {
    	if (is_dir($dirPath)) {
    		foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dirPath, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $path) {
    			$path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
			}
			rmdir($dirPath);
		}
    }

    public function prerender() {
    	$output = '';
    	$templates = isset($this->params['templates']) ? explode(',',$this->params['templates']) : false;
		$roles = isset($this->params['roles']) ? explode(',',$this->params['roles']) : false;
		if (($templates && !in_array($this->params['template'],$templates)) || ($roles && !in_array($_SESSION['mgrRole'],$roles))) return false;
		
		$createTable = isset($this->params['createTable']) ? $this->params['createTable'] : 'No';
		if ($createTable == 'Yes') {
			$output = '<script type="text/javascript">alert("';
			if ($this->createTable()) {
				$output .= 'Таблица создана. Измените настройки плагина SimpleGallery';
			} else {
				$output .= 'Не удалось создать таблицу.';
			}
			$output .= '");</script>';
			return $output;
		}
		$plugins = $this->modx->pluginEvent;
		if(array_search('ManagerManager',$plugins['OnDocFormRender']) === false) {
			$output .= '<script type="text/javascript" src="'.$this->modx->config['site_url'].'assets/js/jquery.min.js"></script>';
		}
		$tpl = MODX_BASE_PATH.'assets/plugins/simplegallery/tpl/simplegallery.tpl';
		if(file_exists($tpl)) {
			$output .= '[+js+]'.file_get_contents($tpl);
		}
		return $output;
    }

    public function renderJS($list,$ph = array()) {
    	$js = '';
    	$scripts = MODX_BASE_PATH.'assets/plugins/simplegallery/js/'.$list;
		if(file_exists($scripts)) {
			$scripts = @file_get_contents($scripts);
			$scripts = $this->DLTemplate->parseChunk('@CODE:'.$scripts,$ph);
			$scripts = json_decode($scripts,true);
			foreach ($scripts['scripts'] as $name => $params) {
				if (!isset($this->modx->loadedjscripts[$name]) && file_exists(MODX_BASE_PATH.$params['src'])) {
					$this->modx->loadedjscripts[$name] = array('version'=>$params['version']);
					$js .= '<script type="text/javascript" src="'.$this->modx->config['site_url'].$params['src'].'"></script>';
				};
			}
		}
		return $js;
    }
	    
    public function render() {
		$output = $this->prerender();

		$ph = array(
			'lang'			=> 	$this->lang_attribute,
			'id'			=>	$this->params['id'],
			'url'			=> 	$this->modx->config['site_url'].'assets/plugins/simplegallery/ajax.php',
			'theme'			=>  $this->modx->config['manager_theme'],
			'tabName'		=>	$this->params['tabName'],
			'site_url'		=>	$this->modx->config['site_url'],
			'manager_url'	=>	MODX_MANAGER_URL,
			'thumb_prefix' 	=> 	$this->modx->config['site_url'].'assets/plugins/simplegallery/ajax.php?mode=thumb&url=',
			'kcfinder_url'	=> 	MODX_MANAGER_URL."media/browser/mcpuk/browse.php?type=images",
			'w' 			=> 	isset($this->params['w']) ? $this->params['w'] : '200',
			'h' 			=> 	isset($this->params['h']) ? $this->params['h'] : '150',
			'refreshBtn'	=>	($_SESSION['mgrRole'] == 1) ? '<div id="sg_refresh" class="btn-right btn"><div class="btn-text"><img src="'.MODX_MANAGER_URL.'media/style/'.$this->modx->config['manager_theme'].'/images/icons/refresh.png">\'+_sgLang[\'refresh_previews\']+\'</div></div>' : ''
			);
		$ph['js'] = $this->renderJS('scripts.json',$ph) . $this->renderJS('custom.json',$ph);
		$output = $this->DLTemplate->parseChunk('@CODE:'.$output,$ph);
		return $output; 
    }
    public function createTable() {
    	$table = $this->modx->db->config['table_prefix'];
    	$sql = <<< OUT
CREATE TABLE IF NOT EXISTS `{$table}sg_images` (
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
    	if ($this->modx->db->query($sql)) {
    		$result = $this->modx->db->select('`id`',$table.'system_eventnames',"`name` IN ('OnSimpleGallerySave','OnSimpleGalleryDelete','OnSimpleGalleryRefresh')");
			if (!$this->modx->db->getRecordCount($result)) {
				$sql = "INSERT INTO `{$table}system_eventnames` VALUES (NULL, 'OnSimpleGallerySave', '6', 'SimpleGallery Events')";
				$this->modx->db->query($sql);
				$sql = "INSERT INTO `{$table}system_eventnames` VALUES (NULL, 'OnSimpleGalleryDelete', '6', 'SimpleGallery Events')";
				$this->modx->db->query($sql);
				$sql = "INSERT INTO `{$table}system_eventnames` VALUES (NULL, 'OnSimpleGalleryRefresh', '6', 'SimpleGallery Events')";
				$this->modx->db->query($sql);
			}
    		return true;
    	} else {
    		return false;
    	}
    }
}
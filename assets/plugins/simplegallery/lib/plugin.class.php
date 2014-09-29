<?php
namespace SimpleGallery;
include_once (MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php');
include_once (MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');

class sgPlugin {
	public $modx = null;
	public $params = array();
	public $DLTemplate = null;
	
	public function __construct($modx, $debug = false) {
        $this->modx = $modx;
        $this->params = $modx->event->params;
        $this->DLTemplate = \DLTemplate::getInstance($this->modx);
        
    }

    public function clearFolders($ids, $folder) {
		foreach ($ids as $id) $this->rmDir($folder.$id.'/');
    }

    public function rmDir($dirPath) {
    	foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dirPath, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $path) {
    		$path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
		}
		rmdir($dirPath);
    }
	    
    public function render() {
    	$templates = isset($this->params['templates']) ? explode(',',$this->params['templates']) : false;
		$roles = isset($this->params['roles']) ? explode(',',$this->params['roles']) : false;
		if (($templates && !in_array($this->params['template'],$templates)) || ($roles && !in_array($_SESSION['mgrRole'],$roles))) return false;
		
		$createTable = isset($this->params['createTable']) ? $this->params['createTable'] : 'No';
		$w = isset($this->params['w']) ? $this->params['w'] : '200';
		$h = isset($this->params['h']) ? $this->params['h'] : '150';
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
			$jquery = '<script type="text/javascript" src="'.$this->modx->config['site_url'].'assets/js/jquery.min.js"></script>';
		}

		$sql = "SELECT * FROM {$this->modx->getFullTableName('sg_images')} WHERE `sg_rid` = {$this->params['id']}";
		$result = $this->modx->db->query($sql);
		$total  = $this->modx->db->getRecordCount($result);
		$tpl = MODX_BASE_PATH.'assets/plugins/simplegallery/tpl/simplegallery.tpl';
		if(file_exists($tpl)) {
			$tpl = file_get_contents($tpl);
		} else {
			return false;
		}
		$ph = array(
			'jquery'		=>	$jquery,
			'id'			=>	$this->params['id'],
			'url'			=> 	$this->modx->config['site_url'].'assets/plugins/simplegallery/ajax.php',
			'theme'			=>  $this->modx->config['manager_theme'],
			'tabName'		=>	$this->params['tabName'],
			'site_url'		=>	$this->modx->config['site_url'],
			'manager_url'	=>	MODX_MANAGER_URL,
			'thumb_prefix' 	=> 	$this->modx->config['site_url'].'assets/plugins/simplegallery/ajax.php?mode=thumb&url=',
			'kcfinder_url'	=> 	MODX_MANAGER_URL."media/browser/mcpuk/browse.php?type=images",
			'w' 			=> 	$w,
			'h' 			=> 	$h,
			'total'			=> 	$total
			);
		$output = $this->DLTemplate->parseChunk('@CODE:'.$tpl,$ph);
		return $output; 
    }
    public function createTable() {
    	$table = $this->modx->db->config['table_prefix'];
    	$sql = <<< OUT
CREATE TABLE IF NOT EXISTS `{$table}sg_images` (
`sg_id` int(10) NOT NULL auto_increment,
`sg_image` varchar(255) NOT NULL default '',
`sg_title` varchar(255) NOT NULL default '',
`sg_description` varchar(255) NOT NULL default '',
`sg_properties` varchar(255) NOT NULL default '',
`sg_isactive` int(1) NOT NULL default '1',
`sg_rid` int(10) default NULL,
`sg_index` int(10) NOT NULL default '0',
`sg_createdon` datetime NOT NULL, 
PRIMARY KEY  (`sg_id`)
) ENGINE=MyISAM COMMENT='Datatable for SimpleGallery plugin.';
OUT;
    	if ($this->modx->db->query($sql)) {
    		return true;
    	} else {
    		return false;
    	}
    }
}
<?php
namespace SimpleGallery;
include_once (MODX_BASE_PATH . 'assets/lib/SimpleTab/plugin.class.php');

class sgPlugin extends \SimpleTab\Plugin {
	public $pluginName = 'SimpleGallery';
    public $table = 'sg_images';
	public $tpl = 'assets/plugins/simplegallery/tpl/simplegallery.tpl';
	public $emptyTpl = 'assets/plugins/simplegallery/tpl/empty.tpl';
	public $jsListDefault = 'assets/plugins/simplegallery/js/scripts.json';
	public $jsListCustom = 'assets/plugins/simplegallery/js/custom.json';
	public $jsListEmpty = 'assets/plugins/simplegallery/js/empty.json';
	public $cssListDefault = 'assets/plugins/simplegallery/css/styles.json';
	public $cssListCustom = 'assets/plugins/simplegallery/css/custom.json';
	public $pluginEvents = array('OnSimpleGallerySave', 'OnSimpleGalleryDelete', 'OnSimpleGalleryMove', 'OnSimpleGalleryRefresh');

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
			'url'			=> 	$this->modx->config['site_url'].'assets/plugins/simplegallery/ajax.php',
			'theme'			=>  MODX_MANAGER_URL.'media/style/'.$this->modx->config['manager_theme'],
			'site_url'		=>	$this->modx->config['site_url'],
			'manager_url'	=>	MODX_MANAGER_URL,
			'thumb_prefix' 	=> 	$this->modx->config['site_url'].'assets/plugins/simplegallery/ajax.php?mode=thumb&url=',
			'kcfinder_url'	=> 	MODX_MANAGER_URL."media/browser/mcpuk/browse.php?type=images",
			'w' 			=> 	isset($this->params['w']) ? $this->params['w'] : '200',
			'h' 			=> 	isset($this->params['h']) ? $this->params['h'] : '150',
			'refreshBtn'	=>	($_SESSION['mgrRole'] == 1) ? '<div id="sg_refresh" class="btn-right btn"><div class="btn-text"><img src="'.MODX_MANAGER_URL.'media/style/'.$this->modx->config['manager_theme'].'/images/icons/refresh.png">\'+_sgLang[\'refresh_previews\']+\'</div></div>' : '',
			'tpls'			=>	$tpls,
            'clientResize'  =>  $this->params['clientResize'] == 'Yes' ? '{maxWidth: '.$this->modx->config['maxImageWidth'].', maxHeight: '.$this->modx->config['maxImageWidth'].'}' : '{}'
		);
		return array_merge($this->params,$ph);
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
}

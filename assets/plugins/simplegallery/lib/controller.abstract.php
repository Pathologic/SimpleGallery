<?php namespace SimpleGallery;

require_once (MODX_BASE_PATH . 'assets/lib/Helpers/FS.php');

abstract class sgAbstractController {
	public $rid = 0;
	public $data = null;
	public $FS = null;
	public $isExit = false;
	public $output = null;
    public $params = null;
	
	protected $modx = null;
	
	public function __construct(\DocumentParser $modx){
		$this->rid = isset($_REQUEST['sg_rid']) ? (int)$_REQUEST['sg_rid'] : 0;
		$this->FS = \Helpers\FS::getInstance();
		$this->modx = $modx;
        $this->params = $modx->event->params;
	}
	
	public function callExit(){
		if($this->isExit){
			echo $this->output;
			exit;
		}
	}
	
	abstract public function listing();
}
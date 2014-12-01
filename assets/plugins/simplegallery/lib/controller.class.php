<?php namespace SimpleGallery;

require_once (MODX_BASE_PATH.'assets/plugins/simplegallery/lib/controller.abstract.php');
require_once (MODX_BASE_PATH.'assets/plugins/simplegallery/lib/table.class.php');

class sgController extends sgAbstractController{
	public function __construct(\DocumentParser $modx){
		parent::__construct($modx);
		$this->data = new \SimpleGallery\sgData($this->modx);
	}
	
	public function upload(){
		$out = array();
		include_once MODX_BASE_PATH.'assets/plugins/simplegallery/lib/FileAPI.class.php';

		if( !empty($_SERVER['HTTP_ORIGIN']) ){
			// Enable CORS
			header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
			header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
			header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type');
		}

		if( $_SERVER['REQUEST_METHOD'] == 'OPTIONS' ){
			$this->isExit = true;
			return;
		}

		if( strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' ){
			$files	= \FileAPI::getFiles(); // Retrieve File List
			$dir = $this->params['folder'].$this->rid."/";
			$flag = $this->FS->makeDir($dir, $this->modx->config['new_folder_permissions']);
			if ($files['sg_files']['error'] == UPLOAD_ERR_OK) {
        		$tmp_name = $files["sg_files"]["tmp_name"];
        		$name = $this->modx->stripAlias($_FILES["sg_files"]["name"]);
        		$name = $this->FS->getInexistantFilename($dir.$name, true);
        		$ext = $this->FS->takeFileExt($name);
        		if (in_array($ext, array('png', 'jpg', 'gif', 'jpeg' ))) {
        			if (@move_uploaded_file($tmp_name, $name)) {
        				$options = "w={$this->modx->config['maxImageWidth']}&h={$this->modx->config['maxImageHeight']}&q=96&f={$ext}";
        				if (@$this->data->makeThumb('',$this->FS->relativePath($name),$options)) {
	        				$info = getimagesize($name);
        					$properties = array (
	        					'width'=>$info[0],
	        					'height'=>$info[1],
	        					'size'=>filesize($name)
        					);
	        				$this->data->create(array(
		        				'sg_image' => $this->FS->relativePath($name),
		        				'sg_rid' => $this->rid,
		        				'sg_title' => preg_replace('/\\.[^.\\s]{2,4}$/', '', $_FILES["sg_files"]["name"]),
		        				'sg_properties' => $properties
	        				))->save();
        				} else {
        					@unlink($name);
        					$files['sg_files']['error'] = 100;
        				}
    				}
    			} else {
    				$files['sg_files']['error'] = 101;
    			}
    		}

			//fetchImages($files, $images);
    		$json	= array(
				'data'	=> array('_REQUEST' => $_REQUEST, '_FILES' => $files)
			);

			// JSONP callback name
			$jsonp	= isset($_REQUEST['callback']) ? trim($_REQUEST['callback']) : null;

			// Server response: "HTTP/1.1 200 OK"
			$this->isExit = true;
			$this->output = \FileAPI::makeResponse(array(
				  'status' => \FileAPI::OK
				, 'statusText' => 'OK'
				, 'body' => $json
			), $jsonp);
			return $out;
		}
	}
	public function remove(){
		$out = array();
		$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
		$out['success'] = false;
		if ($id) {
			if ($this->data->delete($id, $this->rid)) {
				$out['success'] = true;
			}
		}
		return $out;
	}
	public function removeAll(){
		$out = array();
		$ids = isset($_REQUEST['ids']) ? (string)$_REQUEST['ids'] : '';
		$out['success'] = false;
		if (!empty($ids)) {
			if ($this->data->deleteAll($ids, $this->rid)) {
				$out['success'] = true;
			}
		}
		return $out;
	}
    public function move() {
        $out = array();
        $ids = isset($_REQUEST['ids']) ? (string)$_REQUEST['ids'] : '';
        $to =  isset($_REQUEST['to']) ? (int)$_REQUEST['to'] : 0;
        $out['success'] = false;
        if (!empty($ids) && $to !== $this->rid && $to > 0) {
            if ($this->data->move($ids, $this->rid, $to)) {
                $out['success'] = true;
            }
        }
        return $out;
    }
    public function place() {
        $out = array();
        $ids = isset($_REQUEST['ids']) ? (string)$_REQUEST['ids'] : '';
        $dir = isset($_REQUEST['dir']) ? $_REQUEST['dir'] : 'top';
        $out['success'] = false;
        if (!empty($ids)) {
            if ($this->data->place($ids, $dir, $this->rid)) {
                $out['success'] = true;
            }
        }
        return $out;
    }
	public function edit(){
		$out = array();
		$id = isset($_REQUEST['sg_id']) ? (int)$_REQUEST['sg_id'] : 0;
		if ($id) {
			$fields = array(
				'sg_title'		 => $_REQUEST['sg_title'],
				'sg_description' => $_REQUEST['sg_description'],
				'sg_add'		 => $_REQUEST['sg_add']
			);
			$fields['sg_isactive'] = isset($_REQUEST['sg_isactive']) ? 1 : 0;
			$out['success'] = $this->data->edit($id)->fromArray($fields)->save();
		} else {
			$out['success'] = false;
		}
		return $out;
	}
	public function reorder(){
		$out = array();
		if (!$this->rid){
			$this->isExit = true;
			return;
		}
		$sourceIndex = (int)$_REQUEST['sourceIndex'];
		$targetIndex = (int)$_REQUEST['targetIndex'];
		$sourceId = (int)$_REQUEST['sourceId'];
		$rows = $this->data->reorder($sourceIndex,$targetIndex,$sourceId,$this->rid);

		$out['success'] = $rows;
		return $out;
	}
	public function thumb(){
		$out = array();
		$w = 200;
		$h = 150;
		$url = $_REQUEST['url'];
		$thumbsCache = $this->data->thumbsCache;
		if (isset($this->params)) {
			if (isset($this->params['thumbsCache'])) $thumbsCache = $this->params['thumbsCache'];
			if (isset($this->params['w'])) $w = $this->params['w'];
			if (isset($this->params['h'])) $h = $this->params['h'];
		}
		$file = MODX_BASE_PATH.$thumbsCache.$url;
		if ($this->FS->checkFile($file)) {
			$info = getimagesize($file);
			if ($w != $info[0] || $h != $info[1]) {
				@$this->data->makeThumb($thumbsCache,$url,"w=$w&h=$h&far=C&f=jpg");
			}
		} else {
			@$this->data->makeThumb($thumbsCache,$url,"w=$w&h=$h&far=C&f=jpg");
		}
		session_start();
		header("Cache-Control: private, max-age=10800, pre-check=10800");
		header("Pragma: private");
		header("Expires: " . date(DATE_RFC822,strtotime(" 360 day")));
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == filemtime($file))) {
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($file)).' GMT', true, 304);
  			$this->isExit = true;
			return;
		}
		header("Content-type: image/jpeg");
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT');
		readfile($file);
		return $out;
	}
	public function initRefresh(){
		$out = array();
		unset($_SESSION['refresh']);
		$out['success'] = false;
		if (isset($_REQUEST['template'])) {
			$templates = array();
			foreach ($_REQUEST['template'] as $template) {
				$templates[] = (int)$template['value'];
			}
			$templates = implode(',',$templates);
			$templates = $_SESSION['request']['templates'] = trim(preg_replace('/,,+/',',',preg_replace('/[^0-9,]+/', '', $templates)),',');
			if (!empty($templates)) {
				$table = $this->modx->getFullTableName('site_content');
				$sql = "SELECT id FROM $table WHERE template IN ($templates)";
				$rows = $this->modx->db->makeArray($this->modx->db->query($sql));
				$ids = array();
				foreach ($rows as $row) {
					$ids[] = $row['id'];
				}
				$ids = $_SESSION['refresh']['ids'] = implode(',',$ids);
				$table = $this->modx->getFullTableName('sg_images');
				$sql = "SELECT sg_id FROM $table WHERE sg_rid IN ($ids) ORDER BY sg_id ASC";
				$rows = $this->modx->db->query($sql);
				$total = $this->modx->db->getRecordCount($rows);
				$row = $this->modx->db->getRow($rows);
				$_SESSION['refresh']['minId'] = $row['sg_id'];
				$out['success'] = true;
				$out['total'] = (int)$_SESSION['refresh']['total'] = $total;
			}
		}
		return $out;
	}
	public function getRefreshStatus(){
		$out = array();
	
		$out['success'] = true;
		if (!isset($_SESSION['refresh']['processed'])) {
			$out['processed'] = 0;
		} else {
		 	$out['processed'] = $_SESSION['refresh']['processed'] < $_SESSION['refresh']['total'] ? $_SESSION['refresh']['processed'] : $_SESSION['refresh']['total'];
		}
		return $out;
	}
	public function processRefresh(){
		$out = array();
		$ids = trim(preg_replace('/,,+/',',',preg_replace('/[^0-9,]+/', '', $_SESSION['refresh']['ids'])),',');
		$table = $this->modx->getFullTableName('sg_images');
		$minId = (int)$_SESSION['refresh']['minId'];
		$sql = "SELECT sg_id FROM $table WHERE sg_rid IN ($ids) AND sg_id >= $minId ORDER BY sg_id ASC";
		$rows = $this->modx->db->query($sql);
		while ($image = $this->modx->db->getRow($rows)) {
			$result = $this->data->refresh($image['sg_id']);
			$_SESSION['refresh']['minId'] = $image['sg_id'];
			$_SESSION['refresh']['processed'] ++;
		}
		$out['success'] = true;
		return $out;
	}
	public function listing(){
		$out = array();
		if (!$this->rid){
			$this->isExit = true;
			return;
		}
		$param = array(
            "controller" 	=> 	"onetable",
            "table" 		=> 	"sg_images",
            'idField' 		=> 	"sg_id",
            "api" 			=> 	$this->data->fieldNames(),
            "idType"		=>	"documents",
            'ignoreEmpty' 	=> 	"1",
            'JSONformat' 	=> 	"new"
		);
		$display = 10;
		$display = isset($_REQUEST['rows']) ? (int)$_REQUEST['rows'] : $display;
		$offset = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
		$offset = $offset ? $offset : 1;
		$offset = $display*abs($offset-1);

		$param['display'] = $display;
		$param['offset'] = $offset;
		$param['sortBy'] = 'sg_index';
		$param['sortDir'] = 'DESC';

		$param['addWhereList'] = "`sg_rid`=$this->rid";
		$out = $this->modx->runSnippet("DocLister", $param);
		return $out;
	}
}
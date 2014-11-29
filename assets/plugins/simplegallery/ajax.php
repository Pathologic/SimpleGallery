<?php
define('MODX_API_MODE', true);
include_once(dirname(__FILE__)."/../../../index.php");
$modx->db->connect();
if (empty ($modx->config)) {
    $modx->getSettings();
}

if(!isset($_SESSION['mgrValidated'])){
    die();
}
if (isset($modx->pluginCache['SimpleGalleryProps'])) {
	$params = $modx->parseProperties($modx->pluginCache['SimpleGalleryProps']);
} else {
	die();
}

$roles = isset($params['role']) ? explode(',',$params['role']) : false;
if ($roles && !in_array($_SESSION['mgrRole'], $roles)) die();

$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : null;
$rid = isset($_REQUEST['sg_rid']) ? (int)$_REQUEST['sg_rid'] : 0;

include_once(MODX_BASE_PATH.'assets/plugins/simplegallery/lib/table.class.php');
require_once (MODX_BASE_PATH . 'assets/lib/Helpers/FS.php');

$FS = \Helpers\FS::getInstance();
$data = new \SimpleGallery\sgData($modx);

switch ($mode) {
	case 'upload' :
		include_once MODX_BASE_PATH.'assets/plugins/simplegallery/lib/FileAPI.class.php';

		if( !empty($_SERVER['HTTP_ORIGIN']) ){
			// Enable CORS
			header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
			header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
			header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type');
		}

		if( $_SERVER['REQUEST_METHOD'] == 'OPTIONS' ){
			exit;
		}

		if( strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' ){
			$files	= \FileAPI::getFiles(); // Retrieve File List
			$dir = $params['folder'].$rid."/";
			$flag = $FS->makeDir($dir, $modx->config['new_folder_permissions']);
			if ($files['sg_files']['error'] == UPLOAD_ERR_OK) {
        		$tmp_name = $files["sg_files"]["tmp_name"];
        		$name = $modx->stripAlias($_FILES["sg_files"]["name"]);
        		$name = $FS->getInexistantFilename($dir.$name, true);
        		$ext = $FS->takeFileExt($name);
        		if (in_array($ext, array('png', 'jpg', 'gif', 'jpeg' ))) {
        			if (@move_uploaded_file($tmp_name, $name)) {
        				$options = "w={$modx->config['maxImageWidth']}&h={$modx->config['maxImageHeight']}&q=96&f={$ext}";
        				if (@$data->makeThumb('',$FS->relativePath($name),$options)) {
	        				$info = getimagesize($name);
        					$properties = array (
	        					'width'=>$info[0],
	        					'height'=>$info[1],
	        					'size'=>filesize($name)
        					);
	        				$data->create(array(
		        				'sg_image' => $FS->relativePath($name),
		        				'sg_rid' => $rid,
		        				'sg_title' => preg_replace('/\\.[^.\\s]{2,4}$/', '', $_FILES["sg_files"]["name"]),
		        				'sg_properties' => json_encode($properties)
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
			\FileAPI::makeResponse(array(
				  'status' => FileAPI::OK
				, 'statusText' => 'OK'
				, 'body' => $json
			), $jsonp);
				exit;
		}
		break;
	case 'remove':
		$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
		$out['success'] = false;
		if ($id) {
			if ($data->delete($id)) {
				$out['success'] = true;
			}
		}
		break;
	case 'removeAll':
		$ids = isset($_REQUEST['ids']) ? (string)$_REQUEST['ids'] : '';
		$out['success'] = false;
		if (!empty($ids)) {
			if ($data->delete($ids)) {
				$out['success'] = true;
			}
		}
		break;
	case 'edit':
		$id = isset($_REQUEST['sg_id']) ? (int)$_REQUEST['sg_id'] : 0;
		if ($id) {
			$fields = array(
				'sg_title'		 => $_REQUEST['sg_title'],
				'sg_description' => $_REQUEST['sg_description'],
				'sg_add'		 => $_REQUEST['sg_add']
			);
			$fields['sg_isactive'] = isset($_REQUEST['sg_isactive']) ? 1 : 0;
			$data->edit($id)->fromArray($fields)->save();
			$out['success'] = true;
		} else {
			$out['success'] = false;
		}
		break;
	case 'reorder' :
		if (!$rid) die();
		$sourceIndex = (int)$_REQUEST['sourceIndex'];
		$targetIndex = (int)$_REQUEST['targetIndex'];
		$sourceId = (int)$_REQUEST['sourceId'];
		$rows = $data->reorder($sourceIndex,$targetIndex,$sourceId,$rid);

		$out['success'] = $rows;

		break;
	case 'thumb':
		$w = 200;
		$h = 150;
		$url = $_REQUEST['url'];
		$thumbsCache = $data->thumbsCache;
		if (isset($modx->pluginCache['SimpleGalleryProps'])) {
			$pluginParams = $modx->parseProperties($modx->pluginCache['SimpleGalleryProps']);
			if (isset($pluginParams['thumbsCache'])) $thumbsCache = $pluginParams['thumbsCache'];
			if (isset($pluginParams['w'])) $w = $pluginParams['w'];
			if (isset($pluginParams['h'])) $h = $pluginParams['h'];
		}
		$file = MODX_BASE_PATH.$thumbsCache.$url;
		if ($FS->checkFile($file)) {
			$info = getimagesize($file);
			if ($w != $info[0] || $h != $info[1]) {
				@$data->makeThumb($thumbsCache,$url,"w=$w&h=$h&far=C&f=jpg");
			}
		} else {
			@$data->makeThumb($thumbsCache,$url,"w=$w&h=$h&far=C&f=jpg");
		}
		session_start();
		header("Cache-Control: private, max-age=10800, pre-check=10800");
		header("Pragma: private");
		header("Expires: " . date(DATE_RFC822,strtotime(" 360 day")));
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == filemtime($file))) {
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($file)).' GMT', true, 304);
  			exit;
		}
		header("Content-type: image/jpeg");
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT');
		readfile($file);
		break;
	case 'initRefresh':
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
				$table = $modx->getFullTableName('site_content');
				$sql = "SELECT id FROM $table WHERE template IN ($templates)";
				$rows = $modx->db->makeArray($modx->db->query($sql));
				$ids = array();
				foreach ($rows as $row) {
					$ids[] = $row['id'];
				}
				$ids = $_SESSION['refresh']['ids'] = implode(',',$ids);
				$table = $modx->getFullTableName('sg_images');
				$sql = "SELECT sg_id FROM $table WHERE sg_rid IN ($ids) ORDER BY sg_id ASC";
				$rows = $modx->db->query($sql);
				$total = $modx->db->getRecordCount($rows);
				$row = $modx->db->getRow($rows);
				$_SESSION['refresh']['minId'] = $row['sg_id'];
				$out['success'] = true;
				$out['total'] = (int)$_SESSION['refresh']['total'] = $total;
			}
		}
		break;
	case 'getRefreshStatus':
		$out['success'] = true;
		if (!isset($_SESSION['refresh']['processed'])) {
			$out['processed'] = 0;
		} else {
		 	$out['processed'] = $_SESSION['refresh']['processed'] < $_SESSION['refresh']['total'] ? $_SESSION['refresh']['processed'] : $_SESSION['refresh']['total'];
		}
		break;
	case 'processRefresh':
		$ids = trim(preg_replace('/,,+/',',',preg_replace('/[^0-9,]+/', '', $_SESSION['refresh']['ids'])),',');
		$table = $modx->getFullTableName('sg_images');
		$minId = (int)$_SESSION['refresh']['minId'];
		$sql = "SELECT sg_id FROM $table WHERE sg_rid IN ($ids) AND sg_id >= $minId ORDER BY sg_id ASC";
		$rows = $modx->db->query($sql);
		while ($image = $modx->db->getRow($rows)) {
			$result = $data->refresh($image['sg_id']);
			$_SESSION['refresh']['minId'] = $image['sg_id'];
			$_SESSION['refresh']['processed'] ++;
		}
		$out['success'] = true;
		break;
	default:
		if (!$rid) die();
		$param = array(
            "controller" 	=> 	"onetable",
            "table" 		=> 	"sg_images",
            'idField' 		=> 	"sg_id",
            "api" 			=> 	$data->fieldNames(),
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

		$param['addWhereList'] = "`sg_rid`=$rid";
		$out = $modx->runSnippet("DocLister", $param);
		break;
}
echo ($out = is_array($out) ? json_encode($out) : $out);
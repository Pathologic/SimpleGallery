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
			$uploadDir = MODX_BASE_PATH.$dir;
			if (!is_dir($uploadDir)) mkdir($uploadDir,intval($modx->config['new_folder_permissions'],8),true);
			if ($files['sg_files']['error'] == UPLOAD_ERR_OK) {
        		$tmp_name = $files["sg_files"]["tmp_name"];
        		$name = $modx->stripAlias($_FILES["sg_files"]["name"]);
        		$name = $data->getInexistantFilename("$uploadDir/$name");
        		$ext = strtolower(end(explode('.',$name)));
        		if (in_array($ext,array('png', 'jpg', 'gif', 'jpeg' ))) {
        			if (@move_uploaded_file($tmp_name, "$uploadDir/$name")) {
        				if ($data->makeThumb('',$dir.$name,"w={$modx->config['maxImageWidth']}&h={$modx->config['maxImageHeight']}")) {
	        				$info = getimagesize("$uploadDir/$name");
        					$properties = array (
	        					'width'=>$info[0],
	        					'height'=>$info[1],
	        					'size'=>filesize("$uploadDir/$name")
        					);
	        				$data->create(array(
		        				'sg_image' => $dir.$name,
		        				'sg_rid' => $rid,
		        				'sg_title' => preg_replace('/\\.[^.\\s]{2,4}$/', '', $_FILES["sg_files"]["name"]),
		        				'sg_properties' => json_encode($properties)
	        				))->save();
        				} else {
        					@unlink($uploadDir.$name);
        				}
    				}
    			} else {
    				$files['sg_files']['error'] = UPLOAD_ERR_NO_FILE;
    			}
    		}
				// Fetch all image-info from files list
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
		$out['message'] = "Не удалось удалить.";
		if ($id) {
			if ($data->delete($id)) {
				$out['success'] = true;
				unset($out['message']);
			}
		}
		break;
	case 'removeAll':
		$ids = isset($_REQUEST['ids']) ? (string)$_REQUEST['ids'] : '';
		$out['success'] = false;
		$out['message'] = "Не удалось удалить.";
		if (!empty($ids)) {
			if ($data->delete($ids)) {
				$out['success'] = true;
				unset($out['message']);
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
			$out['message'] = "Не удалось сохранить.";
		}
		break;
	case 'reorder' :
		if (!$rid) die();
		$source = $_REQUEST['source'];
		$target = $_REQUEST['target'];
		$point = $_REQUEST['point'];
		$orderDir = $_REQUEST['orderDir'];
		$rows = $data->reorder($source,$target,$point,$rid,$orderDir);
		
		if ($rows) {
			$out['success'] = true;
		} else {
			$out['success'] = false;
			$out['message'] = "Не удалось сохранить данные.";
		}

		break;
	case 'thumb':
		$w = 200;
		$h = 150;
		$url = $_REQUEST['url'];
		$thumbsCache = 'assets/.sgThumbs/';
		if (isset($modx->pluginCache['SimpleGalleryProps'])) {
			$pluginParams = $modx->parseProperties($modx->pluginCache['SimpleGalleryProps']);
			if (isset($pluginParams['thumbsCache'])) $thumbsCache = $pluginParams['thumbsCache'];
			if (isset($pluginParams['w'])) $w = $pluginParams['w'];
			if (isset($pluginParams['h'])) $h = $pluginParams['h'];
		}
		$file = MODX_BASE_PATH.$thumbsCache.$url;
		if (file_exists($file)) {
			$info = getimagesize($file);
			if ($w != $info[0] || $h != $info[1]) {
				$data->makeThumb($thumbsCache,$url,"w=$w&h=$h&far=C&f=jpg");
			}
		} else {
			$data->makeThumb($thumbsCache,$url,"w=$w&h=$h&far=C&f=jpg");
		}
		header('Content-Type: image/jpeg');
		readfile($file);
		break;
	default:
		if (!$rid) die();
		$fields = "id,image,title,description,isactive,properties,createdon,index,add";
		$param = array(
            "controller" 	=> 	"onetable",
            "table" 		=> 	"sg_images",
            'idField' 		=> 	"sg_id",
            "api" 			=> 	"sg_".str_replace(',',',sg_',$fields),
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
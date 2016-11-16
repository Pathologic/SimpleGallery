<?php namespace SimpleGallery;

require_once(MODX_BASE_PATH . 'assets/lib/SimpleTab/controller.abstract.php');
require_once(MODX_BASE_PATH . 'assets/plugins/simplegallery/lib/table.class.php');

class sgController extends \SimpleTab\AbstractController
{
    public $rfName = 'sg_rid';

    public function __construct(\DocumentParser $modx)
    {
        parent::__construct($modx);
        $this->data = new \SimpleGallery\sgData($this->modx);
        $this->dlInit();
    }

    public function upload()
    {
        $out = array();
        include_once MODX_BASE_PATH . 'assets/plugins/simplegallery/lib/FileAPI.class.php';

        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            // Enable CORS
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
            header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            $this->isExit = true;
            return;
        }

        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $files = \FileAPI::getFiles(); // Retrieve File List
            $dir = $this->params['folder'] . $this->rid . "/";
            $flag = $this->FS->makeDir($dir, $this->modx->config['new_folder_permissions']);
            if ($files['sg_files']['error'] == UPLOAD_ERR_OK) {
                $tmp_name = $files["sg_files"]["tmp_name"];
                $name = $this->data->stripName($_FILES["sg_files"]["name"]);
                $name = $this->FS->getInexistantFilename($dir . $name, true);
                $ext = $this->FS->takeFileExt($name);
                if (in_array($ext, array('png', 'jpg', 'gif', 'jpeg'))) {
                    if (@move_uploaded_file($tmp_name, $name)) {
                        //Refactor needed
                        $info = getimagesize($name);
                        $options = array();
                        if ($info[0] > $this->modx->config['maxImageWidth'] || $info[1] > $this->modx->config['maxImageHeight']) {
                            $options[] = "w={$this->modx->config['maxImageWidth']}&h={$this->modx->config['maxImageHeight']}";
                        }
                        if (in_array($ext,array('jpg','jpeg'))) {
                            $quality = 100 * $this->params['jpegQuality'];
                            $options[] = "q={$quality}&ar=x";
                        }
                        $options = implode('&',$options);
                        if (empty($options) || $this->params['clientResize'] == 'Yes' || $this->params['skipPHPThumb'] == 'Yes' ? true : @$this->data->makeThumb('', $this->FS->relativePath($name), $options)) {
                            $info = getimagesize($name);
                            $properties = array(
                                'width' => $info[0],
                                'height' => $info[1],
                                'size' => filesize($name)
                            );
                            $this->data->create(array(
                                'sg_image' => $this->FS->relativePath($name),
                                'sg_rid' => $this->rid,
                                'sg_title' => preg_replace('/\\.[^.\\s]{2,4}$/', '', $_FILES["sg_files"]["name"]),
                                'sg_properties' => $properties
                            ))->save(true);
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
            $json = array(
                'data' => array('_REQUEST' => $_REQUEST, '_FILES' => $files)
            );

            // JSONP callback name
            $jsonp = isset($_REQUEST['callback']) ? trim($_REQUEST['callback']) : null;

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
    
    public function remove()
    {
        $out = array();
        $ids = isset($_POST['ids']) ? (string)$_POST['ids'] : '';
        $ids = isset($_POST['id']) ? (string)$_POST['id'] : $ids;
        $out['success'] = false;
        if (!empty($ids)) {
            if ($this->data->deleteAll($ids, $this->rid, true)) {
                $out['success'] = true;
            }
        }
        return $out;
    }

    public function move()
    {
        $out = array();
        $ids = isset($_POST['ids']) ? (string)$_POST['ids'] : '';
        $to = isset($_POST['to']) ? (int)$_POST['to'] : 0;
        $out['success'] = false;

        if (!empty($ids) && $to !== $this->rid && $to > 0) {
            if ($this->data->move($ids, $this->rid, $to, true)) {
                $out['success'] = true;
            }
        }

        return $out;
    }

    public function edit()
    {
        $out = array();
        $id = isset($_POST['sg_id']) ? (int)$_POST['sg_id'] : 0;
        if ($id) {
            $fields = array(
                'sg_title' => $_POST['sg_title'],
                'sg_description' => $_POST['sg_description'],
                'sg_add' => $_POST['sg_add']
            );
            $fields['sg_isactive'] = isset($_POST['sg_isactive']) ? 1 : 0;
            $out['success'] = $this->data->edit($id)->fromArray($fields)->save(true);
        } else {
            $out['success'] = false;
        }
        return $out;
    }

    public function reorder()
    {
        $out = array();
        if (!$this->rid) {
            $this->isExit = true;
            return;
        }
        $sourceIndex = (int)$_POST['sourceIndex'];
        $targetIndex = (int)$_POST['targetIndex'];
        $sourceId = (int)$_POST['sourceId'];
        $source = array('sg_index'=>$sourceIndex,'sg_id'=>$sourceId);
        $target = array('sg_index'=>$targetIndex);
        $point = $sourceIndex < $targetIndex ? 'top' : 'bottom';
        $orderDir = 'desc';
        $rows = $this->data->reorder($source, $target, $point, $this->rid, $orderDir);
        $out['success'] = $rows;
        return $out;
    }

    public function thumb()
    {
        $w = 200;
        $h = 150;
        $url = $_GET['url'];
        $thumbsCache = $this->data->thumbsCache;
        if (isset($this->params)) {
            if (isset($this->params['thumbsCache'])) $thumbsCache = $this->params['thumbsCache'];
            if (isset($this->params['w'])) $w = $this->params['w'];
            if (isset($this->params['h'])) $h = $this->params['h'];
        }
        $thumbOptions = isset($this->params['customThumbOptions']) ? $this->params['customThumbOptions'] : 'w=[+w+]&h=[+h+]&far=C&bg=FFFFFF&f=jpg';
        $thumbOptions = urldecode(str_replace(array('[+w+]', '[+h+]'), array($w, $h), $thumbOptions));
        $file = MODX_BASE_PATH . $thumbsCache . $url;
        if ($this->FS->checkFile($file)) {
            $info = getimagesize($file);
            if ($w != $info[0] || $h != $info[1]) {
                @$this->data->makeThumb($thumbsCache, $url, $thumbOptions);
            }
        } else {
            @$this->data->makeThumb($thumbsCache, $url, $thumbOptions);
        }
        session_start();
        header("Cache-Control: private, max-age=10800, pre-check=10800");
        header("Pragma: private");
        header("Expires: " . date(DATE_RFC822, strtotime(" 360 day")));
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == filemtime($file))) {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT', true, 304);
            $this->isExit = true;
            return;
        }
        header("Content-type: image/jpeg");
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT');
        ob_clean();
        readfile($file);
        return;
    }

    public function initRefresh()
    {
        $out = array();
        unset($_SESSION['refresh']);
        $out['success'] = false;
        if (isset($_POST['template'])) {
            $templates = array();
            foreach ($_POST['template'] as $template) {
                $templates[] = (int)$template['value'];
            }
            $templates = implode(',', $templates);
            $templates = $_SESSION['request']['templates'] = trim(preg_replace('/,,+/', ',', preg_replace('/[^0-9,]+/', '', $templates)), ',');
            if (!empty($templates)) {
                $table = $this->modx->getFullTableName('site_content');
                $sql = "SELECT id FROM $table WHERE template IN ($templates)";
                $rows = $this->modx->db->makeArray($this->modx->db->query($sql));
                $ids = array();
                foreach ($rows as $row) {
                    $ids[] = $row['id'];
                }
                $ids = $_SESSION['refresh']['ids'] = implode(',', $ids);
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

    public function getRefreshStatus()
    {
        $out = array();

        $out['success'] = true;
        if (!isset($_SESSION['refresh']['processed'])) {
            $out['processed'] = 0;
        } else {
            $out['processed'] = $_SESSION['refresh']['processed'] < $_SESSION['refresh']['total'] ? $_SESSION['refresh']['processed'] : $_SESSION['refresh']['total'];
        }
        return $out;
    }

    public function processRefresh()
    {
        $out = array();
        $ids = trim(preg_replace('/,,+/', ',', preg_replace('/[^0-9,]+/', '', $_SESSION['refresh']['ids'])), ',');
        $table = $this->modx->getFullTableName('sg_images');
        $minId = (int)$_SESSION['refresh']['minId'];
        $sql = "SELECT sg_id FROM $table WHERE sg_rid IN ($ids) AND sg_id >= $minId ORDER BY sg_id ASC";
        $rows = $this->modx->db->query($sql);
        while ($image = $this->modx->db->getRow($rows)) {
            $result = $this->data->refresh($image['sg_id'],true);
            $_SESSION['refresh']['minId'] = $image['sg_id'];
            $_SESSION['refresh']['processed']++;
        }
        $out['success'] = true;
        return $out;
    }

    public function dlInit() {
        parent::dlInit();
        $this->dlParams['sortBy'] = 'sg_index';
        $this->dlParams['sortDir'] = 'DESC';
    }

}

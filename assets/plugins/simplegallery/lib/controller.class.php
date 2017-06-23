<?php namespace SimpleGallery;

require_once(MODX_BASE_PATH . 'assets/lib/SimpleTab/controller.abstract.php');
require_once(MODX_BASE_PATH . 'assets/plugins/simplegallery/lib/table.class.php');
use \SimpleTab\AbstractController;

/**
 * Class sgController
 * @package SimpleGallery
 */
class sgController extends AbstractController
{
    public $rfName = 'sg_rid';

    /**
     * constructor.
     * @param \DocumentParser $modx
     */
    public function __construct(\DocumentParser $modx)
    {
        parent::__construct($modx);
        $this->data = new sgData($this->modx);
        $this->data->setParams($this->params);
        $this->dlInit();
    }

    /**
     *
     */
    public function upload()
    {
        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            // Enable CORS
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
            header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            $this->isExit = true;
            $this->output = json_encode(array(
                'success' => false,
                'message' => 'upload_failed_4'
            ));

            return;
        }

        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $file = $_FILES['file'];
            $dir = $this->params['folder'] . $this->rid . "/";
            $flag = $this->FS->makeDir($dir, $this->modx->config['new_folder_permissions']);
            $message = '';
            if ($flag && $file['error'] == UPLOAD_ERR_OK) {
                $tmp_name = $file["tmp_name"];
                $name = $this->data->stripName($file["name"]);
                $name = $this->FS->getInexistantFilename($dir . $name, true);
                $ext = $this->FS->takeFileExt($name, true);
                if (in_array($ext, array('png', 'jpg', 'gif', 'jpeg'))) {
                    if (@move_uploaded_file($tmp_name, $name)) {
                        $out = $this->data->upload($name, $this->rid,
                            preg_replace('/\\.[^.\\s]{2,4}$/', '', $file["name"]), true);
                        if (!$out) {
                            @unlink($name);
                            $message = 'unable_to_process_file';
                        }
                    } else {
                        $message = 'unable_to_move';
                    }
                } else {
                    $message = 'forbidden_file';
                }
            } else {
                $message = $flag ? 'upload_failed_' . $file['error'] : 'unable_to_create_folder';
            }
            $this->isExit = true;
            $this->output = json_encode(array(
                'success' => empty($message),
                'message' => $message
            ));
        } else {
            $this->isExit = true;
            $this->output = json_encode(array(
                'success' => false,
                'message' => 'upload_failed_4'
            ));
        }
    }

    /**
     * @return array
     */
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

    /**
     * @return array
     */
    public function move()
    {
        $out = array();
        $ids = isset($_POST['ids']) ? (string)$_POST['ids'] : '';
        $to = isset($_POST['to']) ? (int)$_POST['to'] : 0;
        $out['success'] = false;
        if ($_SESSION['mgrRole'] != 1) {
            return $out;
        }

        if (!empty($ids) && $to !== $this->rid && $to > 0) {
            if ($this->data->move($ids, $this->rid, $to, true)) {
                $out['success'] = true;
                unset($_SESSION['move']);
                $ids = explode(',',$ids);
                $_SESSION['move']['ids'] = $ids;
                $out['total'] = $_SESSION['move']['total'] = count($ids);
                $_SESSION['move']['processed'] = 0;
            }
        }

        return $out;
    }

    /**
     * @return array
     */
    public function edit()
    {
        $out = array();
        $id = isset($_POST['sg_id']) ? (int)$_POST['sg_id'] : 0;
        if ($id) {
            $fields = array(
                'sg_title'       => $_POST['sg_title'],
                'sg_description' => $_POST['sg_description'],
                'sg_add'         => $_POST['sg_add']
            );
            $fields['sg_isactive'] = isset($_POST['sg_isactive']) ? 1 : 0;
            $out['success'] = $this->data->edit($id)->fromArray($fields)->save(true);
        } else {
            $out['success'] = false;
        }

        return $out;
    }

    /**
     * @return array
     */
    public function reorder()
    {
        $out = array();
        $sourceIndex = (int)$_POST['sourceIndex'];
        $targetIndex = (int)$_POST['targetIndex'];
        $sourceId = (int)$_POST['sourceId'];
        $source = array('sg_index' => $sourceIndex, 'sg_id' => $sourceId);
        $target = array('sg_index' => $targetIndex);
        $point = $sourceIndex < $targetIndex ? 'top' : 'bottom';
        $orderDir = 'desc';
        $rows = $this->data->reorder($source, $target, $point, $this->rid, $orderDir);
        $out['success'] = $rows;

        return $out;
    }

    public function thumb()
    {
        $w = 140;
        $h = 105;
        $url = $_GET['url'];
        $thumbsCache = $this->data->thumbsCache;
        if (isset($this->params)) {
            if (isset($this->params['thumbsCache'])) {
                $thumbsCache = $this->params['thumbsCache'];
            }
            if (isset($this->params['w'])) {
                $w = $this->params['w'];
            }
            if (isset($this->params['h'])) {
                $h = $this->params['h'];
            }
        }
        $file = MODX_BASE_PATH . $thumbsCache . $url;
        if ($this->FS->checkFile($file)) {
            $info = getimagesize($file);
            if ($w != $info[0] || $h != $info[1]) {
                $this->data->makeBackEndThumb($url);
            }
        } else {
            $this->data->makeBackEndThumb($url);
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

    public function processMoveRefresh(){
        $out = array();
        $_time = microtime(true);
        $_ids = implode(',',$_SESSION['move']['ids']);
        $table = $this->modx->getFullTableName('sg_images');
        $sql = "SELECT `sg`.*,`c`.`template` FROM {$table} `sg` LEFT JOIN {$this->modx->getFullTableName('site_content')} `c` ON `c`.`id` = `sg`.`sg_rid` WHERE `sg`.`sg_id` IN ({$_ids})";
        $rows = $this->modx->db->query($sql); //TODO
        while ($image = $this->modx->db->getRow($rows)) {
            $this->data->refresh($image, true);
            $_SESSION['move']['processed']++;
            array_pop($_SESSION['move']['ids']);
            $time = microtime(true) - $_time;
            if ($time > 25) break;
        }
        $out['success'] = true;

        return $out;
    }

    /**
     * @return array
     */
    public function getMoveRefreshStatus()
    {
        $out = array();

        $out['success'] = true;
        if (!isset($_SESSION['move']['processed'])) {
            $out['processed'] = 0;
        } else {
            $out['processed'] = $_SESSION['move']['processed'] < $_SESSION['move']['total'] ? $_SESSION['move']['processed'] : $_SESSION['move']['total'];
        }

        return $out;
    }

    /**
     * @return array
     */
    public function initRefresh()
    {
        $out = array();
        $out['success'] = false;
        if ($_SESSION['mgrRole'] != 1) {
            return $out;
        }

        unset($_SESSION['refresh']);
        if (!empty($_POST['ids']) && !empty($_POST['method'])) {
            $ids = explode(',', $_POST['ids']);
            foreach ($ids as &$id) {
                $id = (int)$id;
            }
            $ids = array_unique(array_filter($ids));
            if (!empty($ids)) {
                $table = $this->modx->getFullTableName('site_content');
                if ($_POST['method'] == 'template') {
                    $_ids = implode(',', $ids);
                    $q = $this->modx->db->query("SELECT `id` FROM {$table} WHERE `template` IN ({$_ids})");
                    $ids = $this->modx->db->getColumn('id', $q);
                    if (empty($ids)) {
                        return $out;
                    }
                }

                $ids = $_SESSION['refresh']['ids'] = implode(',', $ids);
                $table = $this->modx->getFullTableName('sg_images');
                $q = $this->modx->db->query("SELECT COUNT(*) FROM {$table} WHERE `sg_rid` IN ({$ids})");
                $total = $this->modx->db->getValue($q);
                $q = $this->modx->db->query("SELECT MIN(`sg_id`) FROM {$table} WHERE `sg_rid` IN ({$ids})");
                $_SESSION['refresh']['minId'] = $this->modx->db->getValue($q);
                $out['success'] = true;
                $out['total'] = (int)$_SESSION['refresh']['total'] = $total;
            }
        }

        return $out;
    }

    /**
     * @return array
     */
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

    /**
     * @return array
     */
    public function processRefresh()
    {
        $out = array();
        $_time = microtime(true);
        $ids = $_SESSION['refresh']['ids'];
        $table = $this->modx->getFullTableName('sg_images');
        $minId = (int)$_SESSION['refresh']['minId'];
        $sql = "SELECT `sg`.*,`c`.`template` FROM {$table} `sg` LEFT JOIN {$this->modx->getFullTableName('site_content')} `c` ON `c`.`id` = `sg`.`sg_rid` WHERE `sg`.`sg_rid` IN ({$ids}) AND `sg`.`sg_id` >= {$minId} ORDER BY `sg`.`sg_id` ASC";
        $rows = $this->modx->db->query($sql); //TODO
        while ($image = $this->modx->db->getRow($rows)) {
            $this->data->refresh($image, true);
            $_SESSION['refresh']['minId'] = $image['sg_id'];
            $_SESSION['refresh']['processed']++;
            $time = microtime(true) - $_time;
            if ($time > 25) break;
        }
        $out['success'] = true;

        return $this->getRefreshStatus();
    }

    /**
     *
     */
    public function dlInit()
    {
        parent::dlInit();
        $this->dlParams['sortBy'] = 'sg_index';
        $this->dlParams['sortDir'] = 'DESC';
        $this->dlParams['dateSource'] = 'date';
        $this->dlParams['prepare'] = function ($data) {
            $data['sg_properties'] = \jsonHelper::jsonDecode($data['sg_properties'], array('assoc' => true));
            if (empty($data['sg_properties'])) {
                $data['sg_properties'] = array('width' => 0, 'height' => 0, 'size' => 0);
            }

            return $data;
        };
    }
}

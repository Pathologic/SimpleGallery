<?php
/**
 * sgLister
 *
 * DocLister wrapper for SimpleGallery table
 *
 * @category    snippet
 * @version    1.0.1
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal    @properties
 * @internal    @modx_category Content
 * @author      Pathologic (m@xim.name)
 */

include_once(MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');

if (isset($prepare)) {
    $_prepare = explode(',', $prepare);
} else {
    $_prepare = array();
}
$prepare = array();
$prepare[] = \APIhelpers::getkey($modx->event->params, 'BeforePrepare', '');
$prepare = array_merge($prepare, $_prepare);
$prepare[] = 'DLsgLister::prepare';
$prepare[] = \APIhelpers::getkey($modx->event->params, 'AfterPrepare', '');
$modx->event->params['prepare'] = trim(implode(',', $prepare), ',');

$params = array_merge(array(
    'depth'      => 0,
    'controller' => 'onetable',
    'config'     => 'sgLister:assets/snippets/simplegallery/config/'
), $modx->event->params, array(
    'showParent' => '-1'
));

if (!class_exists('DLsgLister', false)) {
    class DLsgLister
    {
        public static function prepare (
            array $data,
            DocumentParser $modx,
            $_DL
        ) {
            $imageField = $_DL->getCfgDef('imageField');
            $thumbOptions = $_DL->getCfgDef('thumbOptions');
            $thumbSnippet = $_DL->getCfgDef('thumbSnippet');
            if (!empty($thumbOptions) && !empty($thumbSnippet)) {
                $_thumbOptions = jsonHelper::jsonDecode($thumbOptions, ['assoc' => true], true);
                if (!empty($_thumbOptions) && is_array($_thumbOptions)) {
                    foreach ($_thumbOptions as $key => $value) {
                        $postfix = $key == 'default' ? '.' : '_' . $key . '.';
                        $data['thumb' . $postfix . $imageField] = $modx->runSnippet($thumbSnippet, array(
                            'input'   => $data[$imageField],
                            'options' => $value
                        ));
                        $fileFull = urldecode(MODX_BASE_PATH . $data['thumb' . $postfix . $imageField]);
                        if (file_exists($fileFull)) {
                            $info = getimagesize($fileFull);
                            if ($info) {
                                $data['thumb' . $postfix . 'width.' . $imageField] = $info[0];
                                $data['thumb' . $postfix . 'height.' . $imageField] = $info[1];
                            }
                        }
                    }
                } else {
                    $data['thumb.' . $imageField] = $modx->runSnippet($thumbSnippet, array(
                        'input'   => $data[$imageField],
                        'options' => $thumbOptions
                    ));
                    $fileFull = urldecode(MODX_BASE_PATH . $data['thumb.' . $imageField]);
                    if (file_exists($fileFull)) {
                        $info = getimagesize($fileFull);
                        if ($info) {
                            $data['thumb.width.' . $imageField] = $info[0];
                            $data['thumb.height.' . $imageField] = $info[1];
                        }
                    }
                }
            }
            $properties = jsonHelper::jsonDecode($data['sg_properties'], ['assoc' => true], true);
            if (is_array($properties)) {
                foreach ($properties as $key => $value) {
                    $data['properties.' . $key] = $value;
                }
            }

            return $data;
        }
    }
}

return $modx->runSnippet('DocLister', $params);

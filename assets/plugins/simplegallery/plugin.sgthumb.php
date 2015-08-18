<?php
$e = &$modx->event;
if (!function_exists('getThumbConfig')) {
    function getThumbConfig($tconfig,$rid,$template) {
        $out = array();
        include_once (MODX_BASE_PATH.'assets/snippets/DocLister/lib/jsonHelper.class.php');
        $thumbs = \jsonHelper::jsonDecode(urldecode($tconfig), array('assoc' => true), true);
        foreach ($thumbs as $thumb) {
            if ($thumb['rid'] == $rid || $thumb['template'] == $template) {
                $out = $thumb;
                break;
            }
        }
        return $out;
    }
}
if ($e->activePlugin == 'sgThumb') {
    $thumb = new \Helpers\PHPThumb();
    $fs = \Helpers\FS::getInstance();
    $thumbConfig = getThumbConfig($tconfig,$sg_rid,$template);
}
if (($e->name == "OnFileBrowserUpload" && isset($template)) || $e->name == "OnSimpleGalleryRefresh") {
    if (!empty($thumbConfig))  {
        extract($thumbConfig);
        $fs->makeDir($filepath.'/'.$folder);
        $thumb->create($filepath.'/'.$filename,$filepath.'/'.$folder.'/'.$filename,$options);
    }
}
if ($e->name == "OnSimpleGalleryDelete") {
    if (!empty($thumbConfig))  {
        extract($thumbConfig);
        $file = $filepath.'/'.$folder;
        $fs->rmDir($file);
    }
}
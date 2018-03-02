<?php
/*
Configuration example:

[
{"rid":1,"options":"w=1140&h=500&zc=1&q=96&f=jpg","folder":"slider"},
{"template":11,"options":"w=120&h=120&zc=1","folder":"120x120"},
{"template":12,"options":"w=355","folder":"355x"}
]

*/
$e = &$modx->event;
if (!function_exists('getThumbConfig')) {
    function getThumbConfig($tconfig,$rid,$template) {
        $out = array();
        include_once (MODX_BASE_PATH.'assets/snippets/DocLister/lib/jsonHelper.class.php');
        $thumbs = \jsonHelper::jsonDecode(urldecode($tconfig), array('assoc' => true), true);
        foreach ($thumbs as $thumb) {
            if (isset($thumb['rid'])) {
                $_rid = explode(',',$thumb['rid']);
                if (in_array($rid, $_rid)) $out[] = $thumb;
            } elseif (isset($thumb['template'])) {
                $_template = explode(',',$thumb['template']);
                if (in_array($template, $_template)) $out[] = $thumb;
            }
        }
        return $out;
    }
}
if (!isset($template)) {
    return;
}
$fs = \Helpers\FS::getInstance();
$thumbConfig = getThumbConfig($tconfig,$sg_rid,$template);
$keepOriginal = $keepOriginal == 'Yes' && !empty($originalFolder);
if ($keepOriginal) {
    $originalFolder = $filepath.'/'.$originalFolder;
    $fs->makeDir($originalFolder);
}
if ($e->name == 'OnFileBrowserUpload' && $keepOriginal) {
    $fs->copyFile($filepath.'/'.$filename,$originalFolder.'/'.$filename);
}
if ($e->name == 'OnSimpleGalleryRefresh' && $keepOriginal) {
    $file = $originalFolder.'/'.$filename;
    if ($fs->checkFile($file)) {
        $fs->copyFile($file,$filepath.'/'.$filename);
    } else {
        $fs->copyFile($filepath.'/'.$filename,$file);
    }
}
if ($e->name == "OnFileBrowserUpload" || $e->name == "OnSimpleGalleryRefresh") {
    $thumb = new \Helpers\PHPThumb();
    $thumb->optimize($filepath.'/'.$filename);
    if (!empty($thumbConfig))  {
        foreach ($thumbConfig as $_thumbConfig) {
            extract($_thumbConfig);
            $thumb = new \Helpers\PHPThumb();
            $fs->makeDir($filepath.'/'.$folder);
            $thumb->create($filepath.'/'.$filename,$filepath.'/'.$folder.'/'.$filename,$options);
            $thumb->optimize($filepath.'/'.$folder.'/'.$filename);
        }
    }
}
if ($e->name == "OnSimpleGalleryDelete") {
    if ($keepOriginal) {
        $file = $originalFolder.'/'.$filename;
        $fs->unlink($file);
    }
    if (!empty($thumbConfig))  {
        foreach ($thumbConfig as $_thumbConfig) {
            extract($_thumbConfig);
            $file = $filepath.'/'.$folder.'/'.$filename;
            $fs->unlink($file);
        }
    }
}

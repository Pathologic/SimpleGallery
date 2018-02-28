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
if (($e->name == "OnFileBrowserUpload" && isset($template)) || $e->name == "OnSimpleGalleryRefresh") {
    $thumb = new \Helpers\PHPThumb();
    $thumb->optimize($filepath.'/'.$filename);
    $fs = \Helpers\FS::getInstance();
    $thumbConfig = getThumbConfig($tconfig,$sg_rid,$template);
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
    $fs = \Helpers\FS::getInstance();
    $thumbConfig = getThumbConfig($tconfig,$sg_rid,$template);
    if (!empty($thumbConfig))  {
        foreach ($thumbConfig as $_thumbConfig) {
            extract($_thumbConfig);
            $file = $filepath.'/'.$folder.'/'.$filename;
            if (file_exists($file)) unlink($file);
        }
    }
}

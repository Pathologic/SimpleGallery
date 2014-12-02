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
$modx->invokeEvent('OnManagerPageInit');
if (isset($modx->pluginCache['SimpleGalleryProps'])) {
	$modx->event->params = $modx->parseProperties($modx->pluginCache['SimpleGalleryProps']);
} else {
	die();
}

$roles = isset($params['role']) ? explode(',',$params['role']) : false;
if ($roles && !in_array($_SESSION['mgrRole'], $roles)) die();

$mode = (isset($_REQUEST['mode']) && is_scalar($_REQUEST['mode'])) ? $_REQUEST['mode'] : null;
$out = null;
$controllerClass = isset($modx->event->params['controller']) ? $modx->event->params['controller'] : '';
if (empty($controllerClass) || !class_exists($controllerClass)) {
    require_once (MODX_BASE_PATH . 'assets/plugins/simplegallery/lib/controller.class.php');
    $controllerClass = '\SimpleGallery\sgController';
}
$controller = new $controllerClass($modx);
if($controller instanceof \SimpleGallery\sgAbstractController){
	if (!empty($mode) && method_exists($controller, $mode)) {
		$out = call_user_func_array(array($controller, $mode), array());
	}else{
		$out = call_user_func_array(array($controller, 'listing'), array()); 
	}
	$controller->callExit();
}
echo ($out = is_array($out) ? json_encode($out) : $out);
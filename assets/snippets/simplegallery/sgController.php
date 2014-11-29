<?php
$params = is_array($params) ? $params : array();

$params['dir'] = '/assets/snippets/simplegallery/controller/';
$params['controller'] = 'sg_site_content';

return $modx->runSnippet("DocLister", $params);
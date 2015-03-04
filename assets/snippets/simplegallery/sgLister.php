<?php
/**
 * sgLister
 *
 * DocLister wrapper for SimpleGallery table
 *
 * @category 	snippet
 * @version 	1.0.0
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal	@properties
 * @internal	@modx_category Content
 * @author      Pathologic (m@xim.name)
 */

include_once(MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');

$_prepare = explode(",", $prepare);
$prepare = array();
$prepare[] = \APIhelpers::getkey($modx->event->params, 'BeforePrepare', '');
$prepare = array_merge($prepare,$_prepare);
$prepare[] = 'DLsgLister::prepare';
$prepare[] = \APIhelpers::getkey($modx->event->params, 'AfterPrepare', '');
$modx->event->params['prepare'] = trim(implode(",", $prepare), ',');

$params = array_merge(array(
	"controller" 	=> 	"onetable",
	"config"		=>	"sgLister:assets/snippets/simplegallery/config/"
), $modx->event->params, array(
	'depth' => '0',
	'showParent' => '-1'
));

if(!class_exists("DLsgLister", false)){
	class DLsgLister{
		public static function prepare(array $data = array(), DocumentParser $modx, $_DL, prepare_DL_Extender $_extDocLister){
			$imageField = $_DL->getCfgDef('imageField');
			$thumbOptions = $_DL->getCfgDef('thumbOptions');
			$thumbSnippet = $_DL->getCfgDef('thumbSnippet');
			if(!empty($thumbOptions) && !empty($thumbSnippet)){
				$data['thumb.'.$imageField] = $modx->runSnippet($thumbSnippet, array(
					'input' => $data[$imageField],
					'options' => $thumbOptions
				));
				$info = getimagesize(MODX_BASE_PATH.$data['thumb.'.$imageField]);
				$data['thumb.width.'.$imageField] = $info[0];
				$data['thumb.height.'.$imageField] = $info[1];
			}
			$properties = json_decode($data['sg_properties'],true);
			foreach ($properties as $key => $value) {
				$data['properties.'.$key] = $value;
			}
            return $data;
		}
	}
}
return $modx->runSnippet('DocLister', $params);
?>
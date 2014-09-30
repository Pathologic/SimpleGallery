//<?php
/**
 * sgLister
 * 
 * DocLister wrapper for SimpleGallery table
 *
 * @category 	snippet
 * @version 	0.1
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal	@properties 
 * @internal	@modx_category Content
 * @author      Pathologic (m@xim.name)
 */

$params = array_merge(array(
	"controller" 	=> 	"onetable",
    "table" 		=> 	"sg_images",
    "idField" 		=> 	"sg_id",
	"idType"		=>	"documents",
	"ignoreEmpty" 	=> 	"1"
	), $modx->event->params);
if (!isset($documents)) {
	$parents = isset($parents) ? $modx->db->escape($parents) : $modx->documentIdentifier; 
	if (isset($params['addWhereList'])) $params['addWhereList'] .= " AND ";
	$params['addWhereList'] .= "`sg_rid` in ($parents)";
}
return $modx->runSnippet('DocLister', $params);
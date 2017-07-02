<?php
if (!defined('MODX_BASE_PATH')) {
    die('HACK???');
}

/**
 * site_content controller
 * @see http://modx.im/blog/addons/374.html
 *
 * @category controller
 * @license GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
 * @author Agel_Nash <Agel_Nash@xaker.ru>, kabachello <kabachnik@hotmail.com>
 *
 * @TODO add parameter showFolder - include document container in result data whithout children document if you set depth parameter.
 * @TODO st placeholder [+dl.title+] if menutitle not empty
 */
include_once(MODX_BASE_PATH . 'assets/snippets/DocLister/core/controller/site_content.php');
class sg_site_contentDocLister extends site_contentDocLister
{
    public function getDocs($tvlist = '')
    {
        $docs = parent::getDocs($tvlist);

        $table = $this->getTable('sg_images');
        $rid = $this->modx->db->escape(implode(',',array_keys($docs)));
        $sgOrderBy = $this->modx->db->escape($this->getCFGDef('sgOrderBy','sg_index ASC'));

        $sgDisplay = $this->getCFGDef('sgDisplay','all');
        $sgAddWhereList = $this->getCFGDef('sgAddWhereList','');

        if (!empty($sgAddWhereList)) $sgAddWhereList = ' AND ('.$sgAddWhereList.')';
        if (!empty($rid) && ($sgDisplay == 'all' || is_numeric($sgDisplay))) {
            switch ($sgDisplay) {
                case 'all':
                    $sql = "SELECT * FROM {$table} WHERE `sg_rid` IN ({$rid}) {$sgAddWhereList} ORDER BY {$sgOrderBy}";
                    break;
                case '1':
                    $sql = "SELECT * FROM (SELECT * FROM {$table} WHERE `sg_rid` IN ({$rid}) {$sgAddWhereList} ORDER BY {$sgOrderBy} LIMIT 4294967295) sg GROUP BY sg_rid";
                    break;
                default:
                    $sql = "SELECT * FROM (SELECT *, @rn := IF(@prev = `sg_rid`, @rn + 1, 1) AS rn, @prev := `sg_rid` FROM {$table} JOIN (SELECT @prev := NULL, @rn := 0) AS vars WHERE `sg_rid` IN ({$rid}) ORDER BY sg_rid, {$sgOrderBy}) AS sg WHERE rn <= {$sgDisplay}";
                    break;
            }
            $images = $this->dbQuery($sql);
            $count = $this->getCFGDef('count',0);
            if ($count) {
                $sql = "SELECT `sg_rid`, COUNT(`sg_rid`) AS cnt FROM {$table} WHERE `sg_rid` IN ({$rid}) {$sgAddWhereList} GROUP BY sg_rid";
                $_count = $this->dbQuery($sql);
                while ($count = $this->modx->db->getRow($_count)) {
                    $_rid = $count['sg_rid'];
                    $docs[$_rid]['count'] = $count['cnt'];
                }
            }
            while ($image = $this->modx->db->getRow($images)) {
                $_rid = $image['sg_rid'];
                $docs[$_rid]['images'][] = $image;
            }
        }
        $this->_docs = $docs;
        return $docs;
    }
}

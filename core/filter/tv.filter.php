<?php
if (!defined('MODX_BASE_PATH')) {
    die('HACK???');
}

require_once 'content.filter.php';
/**
 * Filters DocLister results by value of a given MODx Template Variables (TVs).
 * @author kabachello <kabachnik@hotmail.com>
 *
 */
class tv_DL_filter extends content_DL_filter{
	protected $tv_id;
    protected $tvName = null;
    /**
     * @var tv_DL_Extender
     */
    protected $extTV = null;

    public function init(DocLister $DocLister, $filter){
        $this->extTV = $DocLister->getExtender('tv', true, true);
        return parent::init($DocLister, $filter);
    }

	protected function parseFilter($filter) {
        $return = false;
        // use the parsing mechanism of the content filter for the start
        if (parent::parseFilter($filter)){
            $this->extTV->getAllTV_Name();
            $tmp = $this->extTV->getTVid($this->field);
            if(!is_array($tmp)){
                $tmp = array();
            }
            $tmp = array_keys($tmp);
            if(count($tmp)==1){
                $this->tv_id = $tmp[0];
            }
            if(!$this->tv_id){
                $tvid = $this->modx->db->query("SELECT id FROM ".$this->DocLister->getTable('site_tmplvars')." WHERE `name` = '".$this->modx->db->escape($this->field)."'");
                $this->tv_id = intval($this->modx->db->getValue($tvid));
            }

            if (!$this->tv_id){
                $this->DocLister->debug->warning('DocLister filtering by template variable "' . $this->DocLister->debug->dumpData($this->field) . '" failed. TV not found!');
            }else{
                // create the alias for the join
                $alias = 'dltv_' . $this->field;
                if($this->totalFilters>0){
                    $alias .= '_'.$this->totalFilters;
                }
                $this->setTableAlias($alias);
                $this->tvName = $this->field;
                $this->field = 'value';
                $return = true;
            }
        };
		
		return $return;
	}

	public function get_join(){
        $join = '';
        $exists = $this->extTV->checkTableAlias($this->tvName, "site_tmplvar_contentvalues");
        $alias = $this->extTV->TableAlias($this->tvName, "site_tmplvar_contentvalues", $this->getTableAlias());
        $this->field = $alias.".value";
        if(!$exists){
            $join = 'LEFT JOIN '.$this->DocLister->getTable('site_tmplvar_contentvalues',$alias).' ON `'.$alias.'`.`contentid`=`'.content_DL_filter::TableAlias.'`.`id` AND `'.$alias.'`.`tmplvarid`='.$this->tv_id;
        }
        return $join;
	}
}
<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2012 SugarCRM Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by SugarCRM".
 ********************************************************************************/


/**
 * Activity.php
 *
 * This is class to encapsulate Activity specific behavior.  Subclasses include Meetings and Calls.  This was used
 * to manage the unique many-to-many handling relationships present in the Meetings and Calls beans.
 *
 */

require_once('data/SugarBean.php');
abstract class Activity extends SugarBean
{

    //Member variable to store value of related records when secondary selects are made via create_new_list_query
    protected $secondary_select_count;

    //Member variable to indicate whether or not this create_new_list_query is called from list view display
    //If so, we set this to true so we may allow for the bean subclasses to appropriately format
    protected $alter_many_to_many_query;

    /**
     * createManyToManyDetailHoverLink
     *
     * This is a function to encapsulate creating a hover link for additional data.  It is called from subclasses that set alter_many_to_many_query
     * to true and wish to display a popup window listing the additional data from the many-to-many relationship.
     *
     * @param string $displayText String value to display in the link portion
     * @param string $exclude_id String id of the displayed related record so as to exclude it from the popup window
     * @return String HTML formatted contents to display a link with a + sign to generate a call to render a popup window
     *
     */
    public function createManyToManyDetailHoverLink($displayText, $exclude_id)
    {
        return "<span id='span_{$this->id}_{$this->table_name}'>{$displayText}<a href='#' style='text-decoration:none;'
        onMouseOver=\"javascript:toggleMore('span_{$this->id}_{$this->table_name}','','{$this->module_dir}','DisplayInline','bean_id={$this->id}&related_id={$exclude_id}');\"
        onFocus=\"javascript:toggleMore('span_{$this->id}_{$this->table_name}','','{$this->module_dir}','DisplayInline','bean_id={$this->id}&related_id={$exclude_id}');\"> +</a></span>";
    }


    /**
      * create_new_list_query
      *
      * Override from SugarBean.  The key here is that we are always setting $singleSelect to false for list views.
      *
      * @param string $order_by custom order by clause
      * @param string $where custom where clause
      * @param array $filter Optioanal
      * @param array $params Optional     *
      * @param int $show_deleted Optional, default 0, show deleted records is set to 1.
      * @param string $join_type
      * @param boolean $return_array Optional, default false, response as array
      * @param object $parentbean creating a subquery for this bean.
      * @param boolean $singleSelect Optional, default false.
      * @return String select query string, optionally an array value will be returned if $return_array= true.
      */
 	function create_new_list_query($order_by, $where,$filter=array(),$params=array(), $show_deleted = 0,$join_type='', $return_array = false,$parentbean=null, $singleSelect = true, $ifListForExport = false)
    {
        if(!isset($params['collection_list']))
        {
            $this->alter_many_to_many_query = true;
            $singleSelect = false;
        }

        return parent::create_new_list_query($order_by, $where, $filter, $params, $show_deleted, $join_type, $return_array, $parentbean, $singleSelect, $ifListForExport);
    }


    /**
      * loadFromRow
      *
      * Loads a row of data into instance of a bean. The data is passed as an array to this function
      * We override this instead of populateFromRow since this function is called from list view displays whereas
      * populateFromRow could be called in many other views.
      *
      * @param array $arr Array of data fetched from the database
      *
      */
     function loadFromRow($arr)
     {
         parent::loadFromRow($arr);
         if(isset($arr['secondary_select_count']))
         {
            $this->secondary_select_count = $arr['secondary_select_count'];
         }
     }

}
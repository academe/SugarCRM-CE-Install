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


require_once('include/SugarWireless/SugarWirelessView.php');
require_once('include/DetailView/DetailView2.php');

class ViewGS extends SugarWirelessView
{
    private $searchFields;
    private $searchString;
    private $searchRegex;
    private $matchHitStart = "<span class='searchHighlight'>";
    private $matchHitEnd = "</span>";

    public function ViewGS()
    {
        $this->searchString = empty($_REQUEST['q']) ? null : $_REQUEST['q'];
        $this->searchRegex = '/' . $this->searchString . '/i';
        $this->options['show_title'] = false;
		$this->options['show_header'] = false;
		$this->options['show_footer'] = false; 	  
		$this->options['show_javascript'] = false; 
		$this->options['show_subpanels'] = false; 
		$this->options['show_search'] = false; 
		parent::SugarView();
    }
    
    
     private function _getGlobalSearchFields()
     {
         $results = array();
         foreach ( $this->bean->field_name_map as $fieldName => $entry)
         {
             if( isset($entry['unified_search']) && $entry['unified_search'] )
             {
                 if($fieldName == 'email_addresses' || $fieldName == 'emails')
                    $results[] = 'email1';
                 else   
                    $results[] = $fieldName;
             }
         }
         return $results;     
     }
     
     function preDisplay()
     {
        $this->searchFields = $this->_getGlobalSearchFields();
        	
 	} 
 	
    /**
     * @see SugarView::display()
     */
    public function display()
    {
 		// no record, we should also provide a way out
 	    if (empty($this->bean->id)){
 	        sugar_die($GLOBALS['app_strings']['ERROR_NO_RECORD']);
 	    }	    

 	    // set up Smarty variables 	    
		$this->ss->assign('BEAN_ID', $this->bean->id);
		$this->ss->assign('BEAN_NAME', $this->bean->name);		
	   	$this->ss->assign('MODULE', $this->module);
	   	$this->ss->assign('MODULE_NAME', translate('LBL_MODULE_NAME',$this->module));

        //Get the fields to display
        $detailFields = $this->bean_details('WirelessDetailView');
	   	$this->ss->assign('DETAILS', $detailFields);

        //Of the fields to display, highlight text based on match
 	    $matchedFields = $this->setMatchedFields($detailFields);
		$this->ss->assign('fields', $matchedFields);

	   	$this->ss->assign('ENABLE_FORM', $this->checkEditPermissions());
	   	$this->ss->assign('LBL_GS_HELP', $GLOBALS['app_strings']['LBL_GS_HELP']);
	   	
	   	// display the detail view
        $file = 'include/MVC/View/tpls/gsdetail.tpl';

        if(file_exists('custom/'.$file))
        {
            $this->ss->display('custom/'.$file);
        } else {
		    $this->ss->display($file);
        }

    }
    
    protected function setMatchedFields($fields)
    {
        if($this->searchString == null)
        {
            return $fields;
        }


        foreach ($fields as &$field)
        {
            if($field['value'] == '')
            {
                continue;
            }

            //Check if we have a search match and set the highlight flag
            $matchReplace = $this->matchHitStart . '${0}' . $this->matchHitEnd;

            if(isset($field['name']) && ($field['name'] == 'email1' || $field['name'] == 'email2'))
            {
                if(preg_match_all("/\<a.*?\>(.*?)\<\/a\>/is", $field['value'], $matches))
                {
                    $aValue = $matches[1][0];
                    $aReplacedValue = preg_replace($this->searchRegex, $matchReplace, $aValue);
                    $newLink = preg_replace("/\<a(.*?)\>(.*?)\<\/a\>/i", "<a\${1}>{$aReplacedValue}<a>", $field['value']);
                    $field['value'] = $newLink;
                }
            } else if(isset($field['type']) && $field['type'] == 'phone') {
                //Do a string replacement for phone fields since it may contain special characters
                $matchReplace = $this->matchHitStart . $this->searchString . $this->matchHitEnd;
                $field['value'] = str_replace($this->searchString, $matchReplace, $field['value']);
            } else {
                if (isset($field['type']) && $field['type'] == 'enum') //TODO: Handle enums since we are destroying the key.
                {
                    continue;
                }
                $field['value'] = preg_replace($this->searchRegex, $matchReplace, $field['value']);
            }
        }

        return $fields;
    }
   /**
	 * Public function that attains the bean detail and sets up an array for
	 * Smarty consumption.
	 */
 	public function bean_details($view)
	{

 	    require_once 'modules/ModuleBuilder/parsers/views/GridLayoutMetaDataParser.php' ;
        global $current_user;

		// traverse through the wirelessviewdefs metadata to get the fields and values
		$bean_details = array();

        	foreach($this->searchFields as $field)
            {
	            // handle empty assigned_user_name
                if(empty($this->bean->assigned_user_name)) {
				   if(!empty($this->bean->assigned_user_id)){
				       $this->bean->assigned_user_name = get_assigned_user_name($this->bean->assigned_user_id);
				   }else{
                       $this->bean->assigned_user_name = $GLOBALS['current_user']->user_name;
				   }
				}

				
				$field_info = $this->setup_detail_field($field);

				if (is_array($field_info))
                {
                    $name = is_array($field) ? $field['name'] : $field;

					$bean_details[$name] = $field_info;
				}				
        	}
        
        return $bean_details;
 	}
}

<?php
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
 * CallsController.php
 *
 * This is the controller file to handle the Calls module specific actions
 */

require_once('include/MVC/Controller/SugarController.php');
class CallsController extends SugarController
{

    /**
     * action_DisplayInline
     *
     * This method handles the request to display an Ajax view of related many to many records.  It expects a bean_id
     * $_REQUEST parameter and an option related_id $_REQUEST parameter from the request.
     */
	public function action_DisplayInline()
    {
		$this->view = 'ajax';
		$body = '';
		$bean_id = isset($_REQUEST['bean_id']) ? $_REQUEST['bean_id'] : '';
		$caption = '';
		if(!empty($bean_id))
        {
            global $locale;
            $query = "SELECT c.first_name, c.last_name, c.salutation, c.title FROM contacts c LEFT JOIN calls_contacts mc ON c.id = mc.contact_id WHERE mc.call_id = '{$bean_id}'";
            if(!empty($_REQUEST['related_id']))
            {
                $query .= " AND c.id != '{$_REQUEST['related_id']}' AND c.deleted=0";
            }

            $result = $GLOBALS['db']->query($query);
            while(($row = $GLOBALS['db']->fetchByAssoc($result)) != null)
            {
				$body .=  $locale->getLocaleFormattedName($row['first_name'], $row['last_name'], $row['salutation'], $row['title']) . '<br/>';
			}
		}

		global $theme;
		$json = getJSONobj();
		$retArray = array();
		$retArray['body'] = $body;
		$retArray['caption'] = $caption;
	    $retArray['width'] = '100';
	    $retArray['theme'] = $theme;
	    echo 'result = ' . $json->encode($retArray);
	}
}
?>
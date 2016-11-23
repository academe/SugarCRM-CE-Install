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

require_once('service/v4/SugarWebServiceUtilv4.php');

class SugarWebServiceUtilv4_1 extends SugarWebServiceUtilv4
{
    /**
   	 * Validate the provided session information is correct and current.  Load the session.
   	 *
   	 * @param String $session_id -- The session ID that was returned by a call to login.
   	 * @return true -- If the session is valid and loaded.
   	 * @return false -- if the session is not valid.
   	 */
   	function validate_authenticated($session_id)
    {
   		$GLOBALS['log']->info('Begin: SoapHelperWebServices->validate_authenticated');
   		if(!empty($session_id)){

   			// only initialize session once in case this method is called multiple times
   			if(!session_id()) {
   			   session_id($session_id);
   			   session_start();
   			}

   			if(!empty($_SESSION['is_valid_session']) && $this->is_valid_ip_address('ip_address') && $_SESSION['type'] == 'user'){

   				global $current_user;
   				require_once('modules/Users/User.php');
   				$current_user = new User();
   				$current_user->retrieve($_SESSION['user_id']);
   				$this->login_success();
   				$GLOBALS['log']->info('Begin: SoapHelperWebServices->validate_authenticated - passed');
   				$GLOBALS['log']->info('End: SoapHelperWebServices->validate_authenticated');
   				return true;
   			}

   			$GLOBALS['log']->debug("calling destroy");
   			session_destroy();
   		}
   		LogicHook::initialize();
   		$GLOBALS['logic_hook']->call_custom_logic('Users', 'login_failed');
   		$GLOBALS['log']->info('End: SoapHelperWebServices->validate_authenticated - validation failed');
   		return false;
   	}


    function check_modules_access($user, $module_name, $action='write'){
        if(!isset($_SESSION['avail_modules'])){
            $_SESSION['avail_modules'] = get_user_module_list($user);
        }
        if(isset($_SESSION['avail_modules'][$module_name])){
            if($action == 'write' && $_SESSION['avail_modules'][$module_name] == 'read_only'){
                if(is_admin($user))return true;
                return false;
            }elseif($action == 'write' && strcmp(strtolower($module_name), 'users') == 0 && !$user->isAdminForModule($module_name)){
                //rrs bug: 46000 - If the client is trying to write to the Users module and is not an admin then we need to stop them
                return false;
            }
            return true;
        }
        return false;

    }


}
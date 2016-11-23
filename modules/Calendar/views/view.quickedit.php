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

require_once('include/MVC/View/views/view.ajax.php');
require_once('include/EditView/EditView2.php');


class CalendarViewQuickEdit extends SugarView {

	var $ev;
	protected $editable;	
	
	public function preDisplay(){
		global $beanFiles,$beanList;
		$module = $_REQUEST['current_module'];
		require_once($beanFiles[$beanList[$module]]);
		$this->bean = new $beanList[$module]();
		if(!empty($_REQUEST['record']))
			$this->bean->retrieve($_REQUEST['record']);
			
		if(!$this->bean->ACLAccess('DetailView')) {
			$json_arr = array(
				'success' => 'no',
			);
			echo json_encode($json_arr);
			die;	
		}

		if($this->bean->ACLAccess('Save')){
			$this->editable = 1;
		}else{
			$this->editable = 0;
		}		
    
	}
	
	public function display(){
		require_once("modules/Calendar/CalendarUtils.php");
		
		$module = $_REQUEST['current_module'];
		
		$_REQUEST['module'] = $module;
				
		$base = 'modules/' . $module . '/metadata/';
		$source = 'custom/'.$base.'quickcreatedefs.php';
		if (!file_exists($source)){
			$source = $base . 'quickcreatedefs.php';
			if (!file_exists($source)){
				$source = 'custom/' . $base . 'editviewdefs.php';
				if (!file_exists($source)){
					$source = $base . 'editviewdefs.php';
				}
			}
		}		

        $tpl = $this->getCustomFilePathIfExists('include/EditView/EditView.tpl');
		$this->ev = new EditView();
		$this->ev->view = "QuickCreate";
		$this->ev->ss = new Sugar_Smarty();
		$this->ev->formName = "CalendarEditView";
		$this->ev->setup($module,$this->bean,$source,$tpl);
		$this->ev->defs['templateMeta']['form']['headerTpl'] = "modules/Calendar/tpls/empty.tpl";
		$this->ev->defs['templateMeta']['form']['footerTpl'] = "modules/Calendar/tpls/empty.tpl";						
		$this->ev->process(false, "CalendarEditView");		
		
		if(!empty($this->bean->id)){
			require_once('include/json_config.php');
			global $json;
			$json = getJSONobj();
			$json_config = new json_config();
			$GRjavascript = $json_config->getFocusData($module, $this->bean->id);
        	}else{
        		$GRjavascript = "";
        	}	
	
		$json_arr = array(
				'success' => 'yes',
				'module_name' => $this->bean->module_dir,
				'record' => $this->bean->id,
				'edit' => $this->editable,
				'html'=> $this->ev->display(false, true),
				'gr' => $GRjavascript,
		);
			
		ob_clean();		
		echo json_encode($json_arr);
	}
}

?>

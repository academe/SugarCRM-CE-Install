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

require_once('include/MVC/View/SugarView.php');

class CalendarViewSaveActivity extends SugarView {

	function CalendarViewSave(){
 		parent::SugarView();
	}
	
	function process(){
		$this->display();
	}
	
	function display(){
		require_once("modules/Calendar/CalendarUtils.php");
		
		$field_list = CalendarUtils::get_fields();

		global $beanFiles,$beanList;
		$module = $_REQUEST['current_module'];
		require_once($beanFiles[$beanList[$module]]);
		$bean = new $beanList[$module]();
		$type = strtolower($beanList[$module]);
		$table_name = $bean->table_name;
		

		if(!empty($_REQUEST['record']))
			$bean->retrieve($_REQUEST['record']);
	
		if(!$bean->ACLAccess('Save')) {
			$json_arr = array(
				'success' => 'no',
			);
			echo json_encode($json_arr);
			die;	
		}

		require_once('include/formbase.php');
		
		$bean = populateFromPost("",$bean);

		
		if(!$_REQUEST['reminder_checked'])
			$bean->reminder_time = -1;


		if(empty($_REQUEST['record']) && strpos($_POST['user_invitees'],$bean->assigned_user_id) === false)
			$_POST['user_invitees'] .= ",".$bean->assigned_user_id;
		// fill invites and save the entry
		$this->save_activity($bean); 


		if($r_id = $bean->id){

			$u = new User();
			$u->retrieve($bean->assigned_user_id);

			$arr_rec = array();

			$bean->retrieve($r_id);
	
			if(isset($bean->parent_name))
				$bean->parent_name = $_REQUEST['parent_name'];
	
			$bean->fill_in_additional_parent_fields();
	

			global $timedate;
			
			$date_field = "date_start";
			if($_REQUEST['current_module'] == "Tasks")
				$date_field = "date_due";			
		
			$timestamp = SugarDateTime::createFromFormat($GLOBALS['timedate']->get_date_time_format(),$bean->$date_field,new DateTimeZone('UTC'))->format('U');
			
			if($_REQUEST['current_module'] == 'Calls')
				$users = $bean->get_call_users();
			if($_REQUEST['current_module'] == 'Meetings')
				$users = $bean->get_meeting_users();
			$user_ids = array();	
			foreach($users as $u)
				$user_ids[] = $u->id;


			$field_arr = array();
			foreach($field_list[$_REQUEST['current_module']] as $field){
				$field_arr[$field] = $bean->$field;
				if($bean->field_defs[$field]['type'] == 'text'){									
					$t = $field_arr[$field];	
					if(strlen($t) > 300){
						$t = substr($t, 0, 300);
						$t .= "...";
					}			
					$t = str_replace("\r\n","<br>",$t);
					$t = str_replace("\r","<br>",$t);
					$t = str_replace("\n","<br>",$t);
					$t = html_entity_decode($t,ENT_QUOTES);
					$field_arr[$field] = $t;
				}
			}	

			$json_arr = array(
				'success' => 'yes',
				'type' => $type,
				'module_name' => $bean->module_dir,
				'user_id' => $GLOBALS['current_user']->id,

				'detail' => 1,	
				'edit' => 1,		
				'record_name' => html_entity_decode($bean->name,ENT_QUOTES),
				'record' => $bean->id,
				
				'users' => $user_ids,

			);
	
			$json_arr = array_merge($json_arr,$field_arr);			
			$json_arr = array_merge($json_arr,CalendarUtils::get_time_data($bean));

		}else{
			$json_arr = array(
				'success' => 'no',
			);
		}

		ob_clean();
		echo json_encode($json_arr);		
	
	}

	
	private function save_activity(&$bean,$notify = true){	
	

		if($_REQUEST['current_module'] == 'Meetings'){		


				if(!empty($_POST['user_invitees'])) {
			    		$userInvitees = explode(',', trim($_POST['user_invitees'], ','));
			    	} else {
			    		$userInvitees = array();	
			    	}
			    
				// Calculate which users to flag as deleted and which to add
				$deleteUsers = array();
			    	$bean->load_relationship('users');
			    	// Get all users for the meeting
			    	$q = 'SELECT mu.user_id, mu.accept_status FROM meetings_users mu WHERE mu.meeting_id = \''.$bean->id.'\'';
			    	$r = $bean->db->query($q);
			    	$acceptStatusUsers = array();
			    	while($a = $bean->db->fetchByAssoc($r)) {
			    		  if(!in_array($a['user_id'], $userInvitees)) {
			    		  	$deleteUsers[$a['user_id']] = $a['user_id'];
			    		  } else {
			    		  	$acceptStatusUsers[$a['user_id']] = $a['accept_status'];  
			    		  }
			    	}
			    		
			    	if(count($deleteUsers) > 0) {
			    		$sql = '';
			    		foreach($deleteUsers as $u) {
			    		        $sql .= ",'" . $u . "'";
			    		}
			    		$sql = substr($sql, 1);
			    		// We could run a delete SQL statement here, but will just mark as deleted instead
			    		$sql = "UPDATE meetings_users set deleted = 1 where user_id in ($sql) AND meeting_id = '". $bean->id . "'";
			    		$bean->db->query($sql);
			    	}	  
			    
				// Get all contacts for the meeting
			    	if(!empty($_POST['contact_invitees'])) {
			    	   $contactInvitees = explode(',', trim($_POST['contact_invitees'], ','));
			    	} else {
			    	   $contactInvitees = array();	
			    	}
			    
				$deleteContacts = array();
			    	$bean->load_relationship('contacts');
			    	$q = 'SELECT mu.contact_id, mu.accept_status FROM meetings_contacts mu WHERE mu.meeting_id = \''.$bean->id.'\'';
			    	$r = $bean->db->query($q);
			    	$acceptStatusContacts = array();
			    	while($a = $bean->db->fetchByAssoc($r)) {
			    		  if(!in_array($a['contact_id'], $contactInvitees)) {
			    		  	 $deleteContacts[$a['contact_id']] = $a['contact_id'];
			    		  }	else {
			    		  	 $acceptStatusContacts[$a['contact_id']] = $a['accept_status'];
			    		  }
			    	}
			    	
			    	if(count($deleteContacts) > 0) {
			    		$sql = '';
			    		foreach($deleteContacts as $u) {
			    		        $sql .= ",'" . $u . "'";
			    		}
			    		$sql = substr($sql, 1);
			    		// We could run a delete SQL statement here, but will just mark as deleted instead
			    		$sql = "UPDATE meetings_contacts set deleted = 1 where contact_id in ($sql) AND meeting_id = '". $bean->id . "'";
			    		$bean->db->query($sql);
			    	}
		
				if(!empty($_POST['lead_invitees'])) {
			    	   $leadInvitees = explode(',', trim($_POST['lead_invitees'], ','));
			    	} else {
			    	   $leadInvitees = array();	
			    	}
			    
				$deleteLeads = array();
			    	$bean->load_relationship('leads');
			    	$q = 'SELECT mu.lead_id, mu.accept_status FROM meetings_leads mu WHERE mu.meeting_id = \''.$bean->id.'\'';
			    	$r = $bean->db->query($q);
			    	$acceptStatusLeads = array();
			    	while($a = $bean->db->fetchByAssoc($r)) {
			    		  if(!in_array($a['lead_id'], $leadInvitees)) {
			    		  	 $deleteLeads[$a['lead_id']] = $a['lead_id'];
			    		  }	else {
			    		  	 $acceptStatusLeads[$a['lead_id']] = $a['accept_status'];
			    		  }
			    	}
			    	
			    	if(count($deleteLeads) > 0) {
			    		$sql = '';
			    		foreach($deleteLeads as $u) {
			    		        $sql .= ",'" . $u . "'";
			    		}
			    		$sql = substr($sql, 1);
			    		// We could run a delete SQL statement here, but will just mark as deleted instead
			    		$sql = "UPDATE meetings_leads set deleted = 1 where lead_id in ($sql) AND meeting_id = '". $bean->id . "'";
			    		$bean->db->query($sql);
			    	}
			    	////	END REMOVE
			    	///////////////////////////////////////////////////////////////////////////
		
			    
			    	///////////////////////////////////////////////////////////////////////////
			    	////	REBUILD INVITEE RELATIONSHIPS
			    	$bean->users_arr = array();
			    	$bean->users_arr = $userInvitees;
			    	$bean->contacts_arr = array();
			    	$bean->contacts_arr = $contactInvitees;
				$bean->leads_arr = array();
			    	$bean->leads_arr = $leadInvitees;
		
			    	if(!empty($_POST['parent_id']) && $_POST['parent_type'] == 'Contacts') {
			    		$bean->contacts_arr[] = $_POST['parent_id'];
			    	}
			    
				if(!empty($_POST['parent_id']) && $_POST['parent_type'] == 'Leads') {
			    		$bean->leads_arr[] = $_POST['parent_id'];
			    	}
			    	
			    	
			    	$bean->save($notify);	    	

			    	
			    	// Process users
			    	$existing_users = array();
			    	if(!empty($_POST['existing_invitees'])) {
			    		$existing_users =  explode(",", trim($_POST['existing_invitees'], ','));
			    	}
			    
			    	foreach($bean->users_arr as $user_id) {
			    		if(empty($user_id) || isset($existing_users[$user_id]) || isset($deleteUsers[$user_id])) {
			    			continue;
			    		}
			    		
			    		if(!isset($acceptStatusUsers[$user_id])) {			    			
			    			$bean->users->add($user_id);
			    		} else {
			    			// update query to preserve accept_status
			    			$qU  = 'UPDATE meetings_users SET deleted = 0, accept_status = \''.$acceptStatusUsers[$user_id].'\' ';
			    			$qU .= 'WHERE meeting_id = \''.$bean->id.'\' ';
			    			$qU .= 'AND user_id = \''.$user_id.'\'';
			    			$bean->db->query($qU);
			    		} 
			    	}
			    	
				// Process contacts
			    	$existing_contacts =  array();
			    	if(!empty($_POST['existing_contact_invitees'])) {
			    	   $existing_contacts =  explode(",", trim($_POST['existing_contact_invitees'], ','));
			    	}
			    	    
			    	foreach($bean->contacts_arr as $contact_id) {
			    		if(empty($contact_id) || isset($exiting_contacts[$contact_id]) || isset($deleteContacts[$contact_id])) {
			    			continue;
			    		}
			    		
			    		if(!isset($acceptStatusContacts[$contact_id])) {
			    		    $bean->contacts->add($contact_id);
			    		} else {
			    			// update query to preserve accept_status
			    			$qU  = 'UPDATE meetings_contacts SET deleted = 0, accept_status = \''.$acceptStatusContacts[$contact_id].'\' ';
			    			$qU .= 'WHERE meeting_id = \''.$bean->id.'\' ';
			    			$qU .= 'AND contact_id = \''.$contact_id.'\'';
			    			$bean->db->query($qU);
			    		}
			    	}
		
				// Process leads
			    	$existing_leads =  array();
			    	if(!empty($_POST['existing_lead_invitees'])) {
			    	   $existing_leads =  explode(",", trim($_POST['existing_lead_invitees'], ','));
			    	}
			    	    
			    	foreach($bean->leads_arr as $lead_id) {
			    		if(empty($lead_id) || isset($exiting_leads[$lead_id]) || isset($deleteLeads[$lead_id])) {
			    			continue;
			    		}
			    		
			    		if(!isset($acceptStatusLeads[$lead_id])) {
			    		    $bean->leads->add($lead_id);
			    		} else {
			    			// update query to preserve accept_status
			    			$qU  = 'UPDATE meetings_leads SET deleted = 0, accept_status = \''.$acceptStatusLeads[$lead_id].'\' ';
			    			$qU .= 'WHERE meeting_id = \''.$bean->id.'\' ';
			    			$qU .= 'AND lead_id = \''.$lead_id.'\'';
			    			$bean->db->query($qU);
			    		}
			    	}
		
		}else if($_REQUEST['current_module'] == 'Calls'){
	
			    	if(!empty($_POST['user_invitees'])) {
			    		$userInvitees = explode(',', trim($_POST['user_invitees'], ','));
			    	}else {
			    		$userInvitees = array();	
			    	}
			    
				// Calculate which users to flag as deleted and which to add
				$deleteUsers = array();
			    	$bean->load_relationship('users');
			    	// Get all users for the call
			    	$q = 'SELECT mu.user_id, mu.accept_status FROM calls_users mu WHERE mu.call_id = \''.$bean->id.'\'';
			    	$r = $bean->db->query($q);
			    	$acceptStatusUsers = array();
			    	while($a = $bean->db->fetchByAssoc($r)) {
			    		  if(!in_array($a['user_id'], $userInvitees)) {
			    		  	 $deleteUsers[$a['user_id']] = $a['user_id'];
			    		  } else {
			    		     $acceptStatusUsers[$a['user_id']] = $a['accept_status'];  
			    		  }
			    	}
			    		
			    	if(count($deleteUsers) > 0) {
			    		$sql = '';
			    		foreach($deleteUsers as $u) {
					// make sure we don't delete the assigned user
					if ( $u != $bean->assigned_user_id )
			    		        $sql .= ",'" . $u . "'";
			    		}
			    		$sql = substr($sql, 1);
			    		// We could run a delete SQL statement here, but will just mark as deleted instead
			    		$sql = "UPDATE calls_users set deleted = 1 where user_id in ($sql) AND call_id = '". $bean->id . "'";
			    		$bean->db->query($sql);
			    	}	  
			    
				// Get all contacts for the call
			    	if(!empty($_POST['contact_invitees'])) {
			    	   $contactInvitees = explode(',', trim($_POST['contact_invitees'], ','));
			    	} else {
			    	   $contactInvitees = array();	
			    	}
			    
				$deleteContacts = array();
			    	$bean->load_relationship('contacts');
			    	$q = 'SELECT mu.contact_id, mu.accept_status FROM calls_contacts mu WHERE mu.call_id = \''.$bean->id.'\'';
			    	$r = $bean->db->query($q);
			    	$acceptStatusContacts = array();
			    	while($a = $bean->db->fetchByAssoc($r)) {
			    		  if(!in_array($a['contact_id'], $contactInvitees)) {
			    		  	 $deleteContacts[$a['contact_id']] = $a['contact_id'];
			    		  }else{
			    		  	 $acceptStatusContacts[$a['contact_id']] = $a['accept_status'];
			    		  }
			    	}
			    	
			    	if(count($deleteContacts) > 0) {
			    		$sql = '';
			    		foreach($deleteContacts as $u) {
			    		        $sql .= ",'" . $u . "'";
			    		}
			    		$sql = substr($sql, 1);
			    		// We could run a delete SQL statement here, but will just mark as deleted instead
			    		$sql = "UPDATE calls_contacts set deleted = 1 where contact_id in ($sql) AND call_id = '". $bean->id . "'";
			    		$bean->db->query($sql);
			    	}
		
				if(!empty($_POST['lead_invitees'])) {
			    	   $leadInvitees = explode(',', trim($_POST['lead_invitees'], ','));
			    	} else {
			    	   $leadInvitees = array();	
			    	}
			    
				// Calculate which leads to flag as deleted and which to add
				$deleteLeads = array();
			    	$bean->load_relationship('leads');
			    	// Get all leads for the call
			    	$q = 'SELECT mu.lead_id, mu.accept_status FROM calls_leads mu WHERE mu.call_id = \''.$bean->id.'\'';
			    	$r = $bean->db->query($q);
			    	$acceptStatusLeads = array();
			    	while($a = $bean->db->fetchByAssoc($r)) {
			    		  if(!in_array($a['lead_id'], $leadInvitees)) {
			    		  	 $deleteLeads[$a['lead_id']] = $a['lead_id'];
			    		  } else {
			    		     $acceptStatusLeads[$a['user_id']] = $a['accept_status'];  
			    		  }
			    	}
			    		
			    	if(count($deleteLeads) > 0) {
			    		$sql = '';
			    		foreach($deleteLeads as $u) {
					// make sure we don't delete the assigned user
					if ( $u != $bean->assigned_user_id )
			    		        $sql .= ",'" . $u . "'";
			    		}
			    		$sql = substr($sql, 1);
			    		// We could run a delete SQL statement here, but will just mark as deleted instead
			    		$sql = "UPDATE calls_leads set deleted = 1 where lead_id in ($sql) AND call_id = '". $bean->id . "'";
			    		$bean->db->query($sql);
			    	}
			    	////	END REMOVE
			    	///////////////////////////////////////////////////////////////////////////
		
			    
			    	///////////////////////////////////////////////////////////////////////////
			    	////	REBUILD INVITEE RELATIONSHIPS
			    	$bean->users_arr = array();
			    	$bean->users_arr = $userInvitees;
			    	$bean->contacts_arr = array();
			    	$bean->contacts_arr = $contactInvitees;
				$bean->leads_arr = array();
			    	$bean->leads_arr = $leadInvitees;
		
			    	if(!empty($_POST['parent_id']) && $_POST['parent_type'] == 'Contacts') {
			    		$bean->contacts_arr[] = $_POST['parent_id'];
			    	}
			    
				if(!empty($_POST['parent_id']) && $_POST['parent_type'] == 'Leads') {
			    		$bean->leads_arr[] = $_POST['parent_id'];
			    	}	
			    	
			    	
			    	$bean->save($notify);
			    	
			    
			    	// Process users
			    	$existing_users = array();
			    	if(!empty($_POST['existing_invitees'])) {
			    	   $existing_users =  explode(",", trim($_POST['existing_invitees'], ','));
			    	}
			    
			    	foreach($bean->users_arr as $user_id) {
			    	    if(empty($user_id) || isset($existing_users[$user_id]) || isset($deleteUsers[$user_id])) {
			    			continue;
			    		}
			    		
			    		if(!isset($acceptStatusUsers[$user_id])) {
			    			$bean->users->add($user_id);
			    		} else {
			    			// update query to preserve accept_status
			    			$qU  = 'UPDATE calls_users SET deleted = 0, accept_status = \''.$acceptStatusUsers[$user_id].'\' ';
			    			$qU .= 'WHERE call_id = \''.$bean->id.'\' ';
			    			$qU .= 'AND user_id = \''.$user_id.'\'';
			    			$bean->db->query($qU);
			    		} 
			    	}
			    	
				// Process contacts
			    	$existing_contacts =  array();
			    	if(!empty($_POST['existing_contact_invitees'])) {
			    	   $existing_contacts =  explode(",", trim($_POST['existing_contact_invitees'], ','));
			    	}
			    	    
			    	foreach($bean->contacts_arr as $contact_id) {
			    		if(empty($contact_id) || isset($exiting_contacts[$contact_id]) || isset($deleteContacts[$contact_id])) {
			    			continue;
			    		}
			    		
			    		if(!isset($acceptStatusContacts[$contact_id])) {
			    		    $bean->contacts->add($contact_id);
			    		} else {
			    			// update query to preserve accept_status
			    			$qU  = 'UPDATE calls_contacts SET deleted = 0, accept_status = \''.$acceptStatusContacts[$contact_id].'\' ';
			    			$qU .= 'WHERE call_id = \''.$bean->id.'\' ';
			    			$qU .= 'AND contact_id = \''.$contact_id.'\'';
			    			$bean->db->query($qU);
			    		}
			    	}
		
				// Process leads
			    	$existing_leads =  array();
			    	if(!empty($_POST['existing_lead_invitees'])) {
			    	   $existing_contacts =  explode(",", trim($_POST['existing_lead_invitees'], ','));
			    	}
			    	    
			    	foreach($bean->leads_arr as $lead_id) {
			    		if(empty($lead_id) || isset($existing_leads[$lead_id]) || isset($deleteLeads[$lead_id])) {
			    			continue;
			    		}
			    		
			    		if(!isset($acceptStatusLeads[$lead_id])) {
			    		    $bean->leads->add($lead_id);
			    		} else {
			    			// update query to preserve accept_status
			    			$qU  = 'UPDATE calls_leads SET deleted = 0, accept_status = \''.$acceptStatusLeads[$lead_id].'\' ';
			    			$qU .= 'WHERE call_id = \''.$bean->id.'\' ';
			    			$qU .= 'AND lead_id = \''.$lead_id.'\'';
			    			$bean->db->query($qU);
			    		}
			    	} 
			    	
		}else if($_REQUEST['current_module'] == 'Tasks'){
			if (!isset($GLOBALS['check_notify'])){
				$GLOBALS['check_notify'] = false;
			}
			$bean->save($GLOBALS['check_notify']);
		}	
	}
	

}

?>

<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */
require_once('include/Webservices/Utils.php');
require_once("include/Webservices/VtigerCRMObject.php");
require_once("include/Webservices/VtigerCRMObjectMeta.php");
require_once("include/Webservices/DataTransform.php");
require_once("include/Webservices/WebServiceError.php");
require_once 'include/Webservices/ModuleTypes.php';
require_once('include/Webservices/Create.php');
require_once 'include/Webservices/DescribeObject.php';
require_once 'include/Webservices/WebserviceField.php';
require_once 'include/Webservices/EntityMeta.php';
require_once 'include/Webservices/VtigerWebserviceObject.php';

require_once("modules/Users/Users.php");

class VTCreateTodoTask extends VTTask
{

	public $executeImmediately = true;

	public function getFieldNames()
	{
		return ['todo', 'description', 'time', 'days_start', 'days_end', 'status', 'priority', 'days', 'direction_start', 'datefield_start', 'direction_end', 'datefield_end', 'sendNotification', 'assigned_user_id', 'days', 'doNotDuplicate', 'duplicateStatus', 'updateDates'];
	}

	function getAdmin()
	{
		$user = Users::getActiveAdminUser();
		$current_user = vglobal('current_user');
		$this->originalUser = $current_user;
		$current_user = $user;
		return $user;
	}

	/**
	 * Execute task
	 * @param Vtiger_Record_Model $recordModel
	 */
	public function doTask($recordModel)
	{
		if (!\App\Module::isModuleActive('Calendar')) {
			return;
		}
		$adb = PearDatabase::getInstance();
		$current_user = vglobal('current_user');

		\App\Log::trace('Start ' . __CLASS__ . ':' . __FUNCTION__);
		$userId = $recordModel->get('assigned_user_id');
		if ($userId === null) {
			$userId = vtws_getWebserviceEntityId('Users', 1);
		}
		$moduleName = $recordModel->getModuleName();
		$adminUser = $this->getAdmin();
		if ($this->doNotDuplicate == 'true') {
			$entityId = $recordModel->getId();
			$sql = 'SELECT count(vtiger_activity.activityid) AS num FROM vtiger_activity 
					INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_activity.activityid
					WHERE vtiger_crmentity.deleted = 0 AND (vtiger_activity.link = ? OR vtiger_activity.process = ?) 
					AND vtiger_activity.activitytype = ? AND vtiger_activity.subject = ?';
			$status = vtlib\Functions::getArrayFromValue($this->duplicateStatus);
			if (count($status) > 0) {
				$sql .= " AND vtiger_activity.status NOT IN ('" . implode("','", $status) . "')";
			}
			$duplicates = $adb->pquery($sql, [$entityId, $entityId, 'Task', $this->todo]);
			$num = $adb->query_result_raw($duplicates, 0, 'num');
			if ($num > 0) {
				\App\Log::warning(__CLASS__ . '::' . __METHOD__ . ': To Do was ignored because a duplicate was found.' . $this->todo);
				return;
			}
		}

		if ($this->assigned_user_id == 'currentUser') {
			$userId = vtws_getWebserviceEntityId('Users', \App\User::getCurrentUserId());
		} else if ($this->assigned_user_id == 'triggerUser') {
			$userId = vtws_getWebserviceEntityId('Users', \App\User::getCurrentUserRealId());
		}

		if ($this->assigned_user_id == 'copyParentOwner') {
			$userId = $recordModel->get('assigned_user_id');
		} else if (!empty($this->assigned_user_id)) { // Added to check if the user/group is active
			$userExists = $adb->pquery('SELECT 1 FROM vtiger_users WHERE id = ? AND status = ?', array($this->assigned_user_id, 'Active'));
			if ($adb->num_rows($userExists)) {
				$assignedUserId = vtws_getWebserviceEntityId('Users', $this->assigned_user_id);
				$userId = $assignedUserId;
			} else {
				$groupExist = $adb->pquery('SELECT 1 FROM vtiger_groups WHERE groupid = ?', array($this->assigned_user_id));
				if ($adb->num_rows($groupExist)) {
					$assignedGroupId = vtws_getWebserviceEntityId('Groups', $this->assigned_user_id);
					$userId = $assignedGroupId;
				}
			}
		}

		if ($this->datefield_start == 'wfRunTime') {
			$baseDateStart = date('Y-m-d H:i:s');
		} else {
			$baseDateStart = $recordModel->get($this->datefield_start);
			if ($baseDateStart == '') {
				$baseDateStart = date('Y-m-d');
			}
		}

		$time = explode(' ', $baseDateStart);
		if (count($time) < 2) {
			$timeWithSec = Vtiger_Time_UIType::getTimeValueWithSeconds($this->time);
			$dbInsertDateTime = DateTimeField::convertToDBTimeZone($baseDateStart . ' ' . $timeWithSec);
			$time = $dbInsertDateTime->format('H:i:s');
		} else {
			$time = $time[1];
		}
		preg_match('/\d\d\d\d-\d\d-\d\d/', $baseDateStart, $match);
		$baseDateStart = strtotime($match[0]);

		if ($this->datefield_end == 'wfRunTime') {
			$baseDateEnd = date('Y-m-d H:i:s');
		} else {
			$baseDateEnd = $recordModel->get($this->datefield_end);
			if ($baseDateEnd == '') {
				$baseDateEnd = date('Y-m-d');
			}
		}

		$timeEnd = explode(' ', $baseDateEnd);
		if (count($timeEnd) < 2) {
			$parts = explode('x', $userId);
			$userId = $parts[1];
			$row = (new App\Db\Query())->select(['end_hour'])->from('vtiger_users')->where(['id' => $userId])->one();
			if ($row) {
				$timeEnd = $row['end_hour'];
				$timeWithSec = Vtiger_Time_UIType::getTimeValueWithSeconds($timeEnd);
				$dbInsertDateTime = DateTimeField::convertToDBTimeZone($baseDateEnd . ' ' . $timeWithSec);
				$timeEnd = $dbInsertDateTime->format('H:i:s');
			} else {
				$timeEnd = $adminUser->column_fields['end_hour'];
				$timeWithSec = Vtiger_Time_UIType::getTimeValueWithSeconds($timeEnd);
				$dbInsertDateTime = DateTimeField::convertToDBTimeZone($baseDateEnd . ' ' . $timeWithSec);
				$timeEnd = $dbInsertDateTime->format('H:i:s');
			}
		} else {
			$timeEnd = $timeEnd[1];
		}
		preg_match('/\d\d\d\d-\d\d-\d\d/', $baseDateEnd, $match);
		$baseDateEnd = strtotime($match[0]);

		$date_start = strftime('%Y-%m-%d', $baseDateStart + $this->days_start * 24 * 60 * 60 * (strtolower($this->direction_start) == 'before' ? -1 : 1));
		$due_date = strftime('%Y-%m-%d', $baseDateEnd + $this->days_end * 24 * 60 * 60 * (strtolower($this->direction_end) == 'before' ? -1 : 1));


		$fields = array(
			'activitytype' => 'Task',
			'description' => $this->description,
			'subject' => $this->todo,
			'taskpriority' => $this->priority,
			'activitystatus' => $this->status,
			'assigned_user_id' => $userId,
			'time_start' => $time,
			'time_end' => $timeEnd,
			'sendnotification' => ($this->sendNotification != '' && $this->sendNotification != 'N') ?
			true : false,
			'date_start' => $date_start,
			'due_date' => $due_date,
			'visibility' => 'Private',
			//'eventstatus' => ''
		);
		$field = Vtiger_ModulesHierarchy_Model::getMappingRelatedField($moduleName);
		if ($field) {
			$fields[$field] = $recordModel->getId();
		}
		$newRecordModel = Vtiger_Record_Model::getCleanInstance('Calendar');
		$newRecordModel->setData($fields);
		$newRecordModel->save();

		relateEntities(CRMEntity::getInstance($moduleName), $moduleName, $recordModel->getId(), 'Calendar', $newRecordModel->getId());

		if ($this->updateDates == 'true') {
			$adb->insert('vtiger_activity_update_dates', [
				'activityid' => $newRecordModel->getId(),
				'parent' => $recordModel->getId(),
				'task_id' => $this->id,
			]);
		}

		$current_user = vglobal('current_user');
		$current_user = $this->originalUser;
		\App\Log::trace('End ' . __CLASS__ . ':' . __FUNCTION__);
	}

	static function conv12to24hour($timeStr)
	{
		$arr = array();
		preg_match('/(\d{1,2}):(\d{1,2})(am|pm)/', $timeStr, $arr);
		if ($arr[3] == 'am') {
			$hours = ((int) $arr[1]) % 12;
		} else {
			$hours = ((int) $arr[1]) % 12 + 12;
		}
		return str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($arr[2], 2, '0', STR_PAD_LEFT);
	}

	public function getTimeFieldList()
	{
		return array('time');
	}
}

<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */
require_once('modules/com_vtiger_workflow/VTEntityMethodManager.php');

class VTEntityMethodTask extends VTTask
{

	public $executeImmediately = true;

	public function getFieldNames()
	{
		return array('methodName');
	}

	/**
	 * Execute task
	 * @param Vtiger_Record_Model $recordModel
	 */
	public function doTask($entityData)
	{
		$adb = PearDatabase::getInstance();
		$emm = new VTEntityMethodManager($adb);
		$emm->executeMethod($entityData, $this->methodName);
	}
}

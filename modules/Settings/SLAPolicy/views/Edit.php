<?php
/**
 * Settings SLAPolicy Edit View class.
 *
 * @package   View
 *
 * @copyright YetiForce Sp. z o.o.
 * @license   YetiForce Public License 3.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */
class Settings_SLAPolicy_Edit_View extends Settings_Vtiger_Index_View
{
	/**
	 * Process.
	 *
	 * @param \App\Request $request
	 */
	public function process(App\Request $request)
	{
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);
		if ($request->isEmpty('record')) {
			$recordModel = Settings_SLAPolicy_Record_Model::getCleanInstance();
		} else {
			$viewer->assign('RECORD_ID', $request->getInteger('record'));
			$recordModel = Settings_SLAPolicy_Record_Model::getInstanceById($request->getInteger('record'));
		}
		$viewer->assign('MODULES', $recordModel->getModule()->getModules());
		$viewer->assign('SOURCE_MODULE', $request->getByType('sourceModule', 'Alnum'));
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE', $request->getModule());
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->view('EditView.tpl', $qualifiedModuleName);
	}
}

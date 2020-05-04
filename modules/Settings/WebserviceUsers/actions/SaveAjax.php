<?php

/**
 * Save Application.
 *
 * @copyright YetiForce Sp. z o.o
 * @license YetiForce Public License 3.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Settings_WebserviceUsers_SaveAjax_Action extends Settings_Vtiger_Save_Action
{
	/**
	 * Save.
	 *
	 * @param \App\Request $request
	 */
	public function process(App\Request $request)
	{
		$data = [
			'server_id' => $request->getInteger('server_id'),
			'status' => $request->getInteger('status'),
			'user_name' => $request->getByType('user_name', 'Text'),
			'password_t' => $request->getRaw('password_t'),
			'type' => $request->getInteger('type'),
			'language' => $request->getByType('language', 'Text'),
			'popupReferenceModule' => $request->getByType('popupReferenceModule', 'Alnum'),
			'crmid' => $request->isEmpty('crmid') ? '' : $request->getInteger('crmid'),
			'crmid_display' => $request->getByType('crmid_display', 'Text'),
			'user_id' => $request->getInteger('user_id'),
			'istorage' => $request->isEmpty('istorage') ? '' : $request->getInteger('istorage'),
		];

		$typeApi = $request->getByType('typeApi', 'Alnum');
		if (!$request->isEmpty('record')) {
			$recordModel = Settings_WebserviceUsers_Record_Model::getInstanceById($request->getInteger('record'), $typeApi);
			foreach ($data as $key => $value) {
				$recordModel->set($key, $value);
			}
		} else {
			$recordModel = Settings_WebserviceUsers_Record_Model::getCleanInstance($typeApi);
			$recordModel->setData($data);
		}

		try {
			if ($recordModel->save() && $request->isEmpty('record') && \App\Config::api('enableEmailPortal', false)) {
				$recordModel->sendEmail();
			}
			$result = ['success' => true];
		} catch (\Exception $e) {
			$result = ['success' => false, 'message' => \App\Language::translate('LBL_DUPLICATE_LOGIN')];
		}

		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}

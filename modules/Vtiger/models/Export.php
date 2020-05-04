<?php

/**
 * Export Model Class.
 *
 * @copyright YetiForce Sp. z o.o
 * @license   YetiForce Public License 3.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 * @author    Arkadiusz Adach <a.adach@yetiforce.com>
 */
class Vtiger_Export_Model extends \App\Base
{
	/**
	 * Module model.
	 *
	 * @var Vtiger_Module_Model
	 */
	protected $moduleInstance;
	protected $focus;
	private $picklistValues;
	private $fieldArray;
	private $fieldDataTypeCache = [];
	protected $moduleName;
	protected $recordsListFromRequest = [];
	/**
	 * Query options.
	 *
	 * @var array
	 */
	protected $queryOptions;
	/**
	 * The type of exported file.
	 *
	 * @var string
	 */
	protected $exportType = 'csv';
	/**
	 * File extension.
	 *
	 * @var string
	 */
	protected $fileExtension = 'csv';

	/**
	 * Get supported file formats.
	 *
	 * @param string $moduleName
	 *
	 * @return array
	 */
	public static function getSupportedFileFormats(string $moduleName): array
	{
		return App\Config::module($moduleName, 'EXPORT_SUPPORTED_FILE_FORMATS') ?? ['LBL_CSV' => 'csv', 'LBL_XML' => 'xml'];
	}

	/**
	 * Get instance.
	 *
	 * @param string $moduleName
	 * @param string $exportType
	 *
	 * @return \self
	 */
	public static function getInstance(string $moduleName, string $exportType = 'csv')
	{
		if ('csv' === $exportType || empty($exportType)) {
			$componentName = 'Export';
		} else {
			$componentName = 'ExportTo' . ucfirst($exportType);
		}
		$modelClassName = Vtiger_Loader::getComponentClassName('Model', $componentName, $moduleName);
		return new $modelClassName();
	}

	/**
	 * Get instance from request.
	 *
	 * @param App\Request $request
	 *
	 * @return \self
	 */
	public static function getInstanceFromRequest(App\Request $request)
	{
		$module = $request->getByType('source_module', 'Alnum');
		if (empty($module)) {
			$module = $request->getModule();
		}
		$exportModel = static::getInstance($module, $request->getByType('export_type', 'Alnum'));
		$exportModel->initializeFromRequest($request);
		return $exportModel;
	}

	/**
	 * Initialize from request.
	 *
	 * @param \App\Request $request
	 *
	 * @throws \App\Exceptions\IllegalValue
	 */
	public function initializeFromRequest(App\Request $request)
	{
		$module = $request->getByType('source_module', 2);
		if (!empty($module)) {
			$this->moduleName = $module;
			$this->moduleInstance = Vtiger_Module_Model::getInstance($module);
			$this->moduleFieldInstances = $this->moduleInstance->getFields();
			$this->focus = CRMEntity::getInstance($module);
		}
		if (!$request->isEmpty('export_type')) {
			$this->exportType = $request->getByType('export_type');
		}
		if (!$request->isEmpty('viewname', true)) {
			$this->queryOptions['viewname'] = $request->getByType('viewname', 'Alnum');
		}
		$this->queryOptions['entityState'] = $request->getByType('entityState');
		$this->queryOptions['page'] = $request->getInteger('page');
		$this->queryOptions['mode'] = $request->getMode();
		$this->queryOptions['excluded_ids'] = $request->getArray('excluded_ids', 'Alnum');
	}

	/**
	 * Function exports the data based on the mode.
	 */
	public function exportData()
	{
		$module = $this->moduleName;
		$query = $this->getExportQuery();
		$headers = [];
		$exportBlockName = \App\Config::component('Export', 'BLOCK_NAME');
		//Query generator set this when generating the query
		if (!empty($this->accessibleFields)) {
			foreach ($this->accessibleFields as $fieldName) {
				if (!empty($this->moduleFieldInstances[$fieldName])) {
					$fieldModel = $this->moduleFieldInstances[$fieldName];
					// Check added as querygenerator is not checking this for admin users
					if ($fieldModel && $fieldModel->isExportTable()) { // export headers for mandatory fields
						$header = \App\Language::translate(html_entity_decode($fieldModel->get('label'), ENT_QUOTES), $module);
						if ($exportBlockName) {
							$header = App\Language::translate(html_entity_decode($fieldModel->getBlockName(), ENT_QUOTES), $module) . '::' . $header;
						}
						$headers[] = $header;
					}
				}
			}
		} else {
			foreach ($this->moduleFieldInstances as $fieldModel) {
				$header = \App\Language::translate(html_entity_decode($fieldModel->get('label'), ENT_QUOTES), $module);
				if ($exportBlockName) {
					$header = App\Language::translate(html_entity_decode($fieldModel->getBlockName(), ENT_QUOTES), $module) . '::' . $header;
				}
				$headers[] = $header;
			}
		}
		$isInventory = $this->moduleInstance->isInventory();
		if ($isInventory) {
			//Get inventory headers
			$inventoryModel = Vtiger_Inventory_Model::getInstance($module);
			$inventoryFields = $inventoryModel->getFields();
			$headers[] = 'Inventory::recordIteration';
			foreach ($inventoryFields as &$field) {
				$headers[] = 'Inventory::' . \App\Language::translate(html_entity_decode($field->get('label'), ENT_QUOTES), $module);
				foreach ($field->getCustomColumn() as $columnName => $dbType) {
					$headers[] = 'Inventory::' . $columnName;
				}
			}
			$table = $inventoryModel->getDataTableName();
		}
		$entries = [];
		$dataReader = $query->createCommand()->query();
		$i = 0;
		while ($row = $dataReader->read()) {
			$sanitizedRow = $this->sanitizeValues($row);
			if ($isInventory) {
				$sanitizedRow[] = $i++;
				$rows = (new \App\Db\Query())->from($table)->where(['crmid' => $row['id']])->orderBy('seq')->all();
				if ($rows) {
					foreach ($rows as &$row) {
						$sanitizedInventoryRow = $this->sanitizeInventoryValues($row, $inventoryFields);
						$entries[] = array_merge($sanitizedRow, $sanitizedInventoryRow);
					}
				} else {
					$entries[] = $sanitizedRow;
				}
			} else {
				$entries[] = $sanitizedRow;
			}
		}
		$dataReader->close();
		$this->output($headers, $entries);
	}

	/**
	 * Function that generates Export Query based on the mode.
	 *
	 * @throws \Exception
	 *
	 * @return \App\Db\Query
	 */
	public function getExportQuery()
	{
		$queryGenerator = new \App\QueryGenerator($this->moduleName);
		if (!empty($this->queryOptions['viewname'])) {
			$queryGenerator->initForCustomViewById($this->queryOptions['viewname']);
		}
		$fieldInstances = $this->moduleFieldInstances;
		$fields[] = 'id';
		foreach ($fieldInstances as &$fieldModel) {
			// Check added as querygenerator is not checking this for admin users
			if ($fieldModel->isViewEnabled() || $fieldModel->isMandatory()) {  // also export mandatory fields
				$fields[] = $fieldModel->getName();
			}
		}
		$queryGenerator->setFields($fields);
		$queryGenerator->setStateCondition($this->queryOptions['entityState']);
		$query = $queryGenerator->createQuery();
		$this->accessibleFields = $queryGenerator->getFields();
		switch ($this->queryOptions['mode']) {
			case 'ExportAllData':
				$query->limit(App\Config::performance('MAX_NUMBER_EXPORT_RECORDS'));
				break;
			case 'ExportCurrentPage':
				$pagingModel = new Vtiger_Paging_Model();
				$limit = $pagingModel->getPageLimit();
				$currentPage = $this->queryOptions['page'];
				if (empty($currentPage)) {
					$currentPage = 1;
				}
				$currentPageStart = ($currentPage - 1) * $limit;
				if ($currentPageStart < 0) {
					$currentPageStart = 0;
				}
				$query->limit($limit)->offset($currentPageStart);
				break;
			case 'ExportSelectedRecords':
				$idList = $this->recordsListFromRequest;
				$baseTable = $this->moduleInstance->get('basetable');
				$baseTableColumnId = $this->moduleInstance->get('basetableid');
				if (!empty($idList)) {
					if (!empty($baseTable) && !empty($baseTableColumnId)) {
						$query->andWhere(['in', "$baseTable.$baseTableColumnId", $idList]);
					}
				} else {
					$query->andWhere(['not in', "$baseTable.$baseTableColumnId", $this->queryOptions['excluded_ids']]);
				}
				$query->limit(App\Config::performance('MAX_NUMBER_EXPORT_RECORDS'));
				break;
			default:
				break;
		}
		return $query;
	}

	/**
	 * Function returns the export type - This can be extended to support different file exports.
	 *
	 * @return string
	 */
	public function getExportContentType(): string
	{
		return "text/{$this->exportType}";
	}

	/**
	 * @param string $moduleName
	 *
	 * @return string
	 */
	public function getFileName(): string
	{
		return str_replace(' ', '_', \App\Purifier::decodeHtml(\App\Language::translate($this->moduleName, $this->moduleName))) .
			".{$this->fileExtension}";
	}

	/**
	 * Function that create the exported file.
	 *
	 * @param array $headers - output file header
	 * @param array $entries - outfput file data
	 */
	public function output($headers, $entries)
	{
		$output = fopen('php://output', 'w');
		fputcsv($output, $headers);
		foreach ($entries as $row) {
			fputcsv($output, $row);
		}
		fclose($output);
	}

	/**
	 * Send HTTP Header.
	 */
	public function sendHttpHeader()
	{
		header("content-disposition: attachment; filename=\"{$this->getFileName()}\"");
		header("content-type: {$this->getExportContentType()}; charset=UTF-8");
		header('expires: Mon, 31 Dec 2000 00:00:00 GMT');
		header('last-modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('cache-control: post-check=0, pre-check=0', false);
	}

	/**
	 * this function takes in an array of values for an user and sanitizes it for export
	 * Requires modification after adding a new field type.
	 *
	 * @param array $arr - the array of values
	 */
	public function sanitizeValues($arr)
	{
		if (empty($this->fieldArray)) {
			$this->fieldArray = $this->moduleFieldInstances;
			foreach ($this->fieldArray as $fieldName => $fieldObj) {
				$columnName = $fieldObj->get('column');
				$this->fieldArray[$columnName] = $fieldObj;
			}
		}
		$recordId = (int) ($arr['id'] ?? 0);
		$module = $this->moduleInstance->getName();
		foreach ($arr as $fieldName => &$value) {
			if (isset($this->fieldArray[$fieldName])) {
				$fieldInfo = $this->fieldArray[$fieldName];
			} else {
				unset($arr[$fieldName]);
				continue;
			}
			$value = $fieldInfo->getUITypeModel()->getValueToExport($value, $recordId);
			$uitype = $fieldInfo->get('uitype');
			$fieldname = $fieldInfo->get('name');
			if (empty($this->fieldDataTypeCache[$fieldName])) {
				$this->fieldDataTypeCache[$fieldName] = $fieldInfo->getFieldDataType();
			}
			$type = $this->fieldDataTypeCache[$fieldName];
			if (15 === $uitype || 16 === $uitype || 33 === $uitype) {
				if (empty($this->picklistValues[$fieldname])) {
					$this->picklistValues[$fieldname] = $this->fieldArray[$fieldname]->getPicklistValues();
				}
				// If the value being exported is accessible to current user
				// or the picklist is multiselect type.
				if (33 === $uitype || 16 === $uitype || \array_key_exists($value, $this->picklistValues[$fieldname])) {
					// NOTE: multipicklist (uitype=33) values will be concatenated with |# delim
					$value = trim($value);
				} else {
					$value = '';
				}
			} elseif (99 === $uitype) {
				$value = '';
			} elseif (52 === $uitype || 'owner' === $type) {
				$value = \App\Fields\Owner::getLabel($value);
			} elseif ($fieldInfo->isReferenceField()) {
				$value = trim($value);
				if (!empty($value)) {
					$recordModule = \App\Record::getType($value);
					$displayValueArray = \App\Record::computeLabels($recordModule, $value);
					if (!empty($displayValueArray)) {
						foreach ($displayValueArray as $v) {
							$displayValue = $v;
						}
					}
					if (!empty($recordModule) && !empty($displayValue)) {
						$value = $recordModule . '::::' . $displayValue;
					} else {
						$value = '';
					}
				} else {
					$value = '';
				}
			} elseif (\in_array($uitype, [302, 309])) {
				$parts = explode(',', trim($value, ', '));
				$values = \App\Fields\Tree::getValuesById((int) $fieldInfo->getFieldParams());
				foreach ($parts as &$part) {
					foreach ($values as $id => $treeRow) {
						if ($part === $id) {
							$part = $treeRow['name'];
						}
					}
				}
				$value = implode(' |##| ', $parts);
			}
			if ('Documents' === $module && 'description' === $fieldname) {
				$value = strip_tags($value);
				$value = str_replace('&nbsp;', '', $value);
				array_push($new_arr, $value);
			}
		}
		return $arr;
	}

	public function sanitizeInventoryValues($inventoryRow, $inventoryFields)
	{
		$inventoryEntries = [];
		foreach ($inventoryFields as $columnName => $field) {
			$value = $inventoryRow[$columnName];
			if (\in_array($field->getType(), ['Name', 'Reference'])) {
				$value = trim($value);
				if (!empty($value)) {
					$recordModule = \App\Record::getType($value);
					$displayValue = \App\Record::getLabel($value);
					if (!empty($recordModule) && !empty($displayValue)) {
						$value = $recordModule . '::::' . $displayValue;
					} else {
						$value = '';
					}
				} else {
					$value = '';
				}
			} elseif ('Currency' === $field->getType()) {
				$value = $field->getDisplayValue($value);
			} else {
				$value;
			}
			$inventoryEntries['inv_' . $columnName] = $value;
			foreach ($field->getCustomColumn() as $customColumnName => $dbType) {
				$valueParam = $inventoryRow[$customColumnName];
				if ('currencyparam' === $customColumnName) {
					$field = $inventoryFields['currency'];
					$valueData = $field->getCurrencyParam([], $valueParam);
					if (\is_array($valueData)) {
						$valueNewData = [];
						foreach ($valueData as $currencyId => $data) {
							$currencyName = \App\Fields\Currency::getById($currencyId)['currency_name'];
							$valueNewData[$currencyName] = $data;
						}
						$valueParam = \App\Json::encode($valueNewData);
					}
				}
				$inventoryEntries['inv_' . $customColumnName] = $valueParam;
			}
		}
		return $inventoryEntries;
	}

	public function getModuleName()
	{
		return $this->moduleName;
	}

	public function setRecordList($listId)
	{
		return $this->recordsListFromRequest = $listId;
	}
}

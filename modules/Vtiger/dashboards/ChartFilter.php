<?php

/**
 * Widget as a chart with a filter.
 *
 * @copyright YetiForce Sp. z o.o
 * @license   YetiForce Public License 3.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Tomasz Kur <t.kur@yetiforce.com>
 */
class Vtiger_ChartFilter_Dashboard extends Vtiger_IndexAjax_View
{
	public function process(App\Request $request, $widget = null)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		// Initialize Widget to the right-state of information
		if ($widget && !$request->has('widgetid')) {
			$widgetId = $widget->get('id');
		} else {
			$widgetId = $request->getInteger('widgetid');
		}
		$widget = Vtiger_Widget_Model::getInstanceWithWidgetId($widgetId, \App\User::getCurrentUserId());
		$chartFilterWidgetModel = Vtiger_ChartFilter_Model::getInstance();
		$chartFilterWidgetModel->setWidgetModel($widget);
		$additionalFilterFields = $chartFilterWidgetModel->getAdditionalFiltersFields();
		$searchParams = App\Condition::validSearchParams($chartFilterWidgetModel->getTargetModule(), $request->getArray('search_params'), false);
		if (!empty($searchParams)) {
			foreach ($searchParams[0] as $fieldSearchInfo) {
				$fieldSearchInfo['searchValue'] = $fieldSearchInfo[2];
				$fieldSearchInfo['fieldName'] = $fieldName = $fieldSearchInfo[0];
				$fieldSearchInfo['specialOption'] = $fieldSearchInfo[3] ?? null;
				$searchParams[$fieldName] = $fieldSearchInfo;
			}
		}
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('CHART_MODEL', $chartFilterWidgetModel);
		$viewer->assign('ADDITIONAL_FILTERS_FIELDS', $additionalFilterFields);
		$viewer->assign('CHART_STACKED', $chartFilterWidgetModel->isStacked() ? 1 : 0);
		$viewer->assign('CHART_COLORS_FROM_DIVIDING_FIELD', $chartFilterWidgetModel->areColorsFromDividingField() ? 1 : 0);
		$viewer->assign('CHART_COLORS_FROM_FILTERS', $chartFilterWidgetModel->areColorsFromFilter() ? 1 : 0);
		$viewer->assign('SEARCH_DETAILS', $searchParams);
		$viewer->assign('CHART_DATA', $chartFilterWidgetModel->getChartData());
		if ($owners = $chartFilterWidgetModel->getRowsOwners()) {
			$viewer->assign('CHART_OWNERS', $owners);
		}
		if ($request->has('content')) {
			$viewer->view('dashboards/ChartFilterContents.tpl', $moduleName);
		} else {
			$widget->set('title', $chartFilterWidgetModel->getTitle());
			$viewer->view('dashboards/ChartFilterHeader.tpl', $moduleName);
		}
	}
}

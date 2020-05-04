{*<!-- {[The file is published on the basis of YetiForce Public License 3.0 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
<div id="tpl-Settings-ApiAddress-Configuration menuEditorContainer">
    <div class="widget_header row mb-2">
        <div class="col-md-12">
			{include file=\App\Layout::getTemplatePath('BreadCrumbs.tpl', $MODULE_NAME)}
		</div>
    </div>
	<div class="main_content">
		<form>
			<div class="col-12 form-row m-0">
				<div class="col-12 form-row">
					<h4>{\App\Language::translate('LBL_GLOBAL_CONFIG', $MODULENAME)} </h4>
				</div>
				<div class="col-12 form-row mb-2">
					<div class="col-sm-6 col-md-4">
						<div >
							{\App\Language::translate('LBL_MIN_LOOKUP_LENGTH', $MODULENAME)}:
						</div>
					</div>
					<div class="col-sm-6 col-md-4">
						<div class="text-center">
							<input name="min_length" type="text" class="api form-control m-0" value="{$CONFIG['global']['min_length']}">
						</div>
					</div>
				</div>
				<div class="clearfix"></div>
				<div class="col-12 form-row  mb-2">
					<div class='col-sm-6 col-md-4'>
						<div>
							{\App\Language::translate('LBL_NUMBER_SEARCH_RESULTS', $MODULENAME)}:
						</div>
					</div>
					<div class="col-sm-6 col-md-4">
						<div class="text-center">
							<input name="result_num" type="text" class="api form-control m-0" value="{$CONFIG['global']['result_num']}">
						</div>
					</div>
				</div>
				<div class="js-config-table table-responsive" data-js="container">
					<table class="table table-bordered">
						<thead>
							<tr>
								<th class="" scope="col">{\App\Language::translate('LBL_PROVIDER_NAME', $MODULENAME)}</th>
								<th class="text-center" scope="col">{\App\Language::translate('LBL_PROVIDER_ACTIVE', $MODULENAME)}</th>
								<th class="text-center" scope="col">{\App\Language::translate('LBL_PROVIDER_DEFAULT', $MODULENAME)}</th>
								<th class="text-center" scope="col">{\App\Language::translate('LBL_PROVIDER_ACTIONS', $MODULENAME)}</th>
							</tr>
						</thead>
						<tbody>
							{function UNSET_POPOVER}
								class="js-popover-tooltip text-center" data-content="{\App\Language::translate('LBL_PROVIDER_UNSET', $MODULENAME)}" data-placement="top"
							{/function}
							{foreach from=\App\Map\Address::getAllProviders() item=ITEM key=KEY}
								{assign var=IS_SET value=$ITEM->isSet()}
								<tr>
									<th class="" scope="row">{\App\Language::translate('LBL_PROVIDER_'|cat:$KEY|upper, $MODULENAME)}</th>
									<td {if !$IS_SET}{UNSET_POPOVER}{else}class="text-center"{/if}><input name="active" data-type="{$KEY}" type="checkbox"{if $ITEM->isActive() && $IS_SET} checked{/if}{if !$IS_SET} disabled{/if}></td>
									<td {if !$IS_SET}{UNSET_POPOVER}{else}class="text-center"{/if}><input name="default_provider" value="{$KEY}" type="radio"{if $DEFAULT_PROVIDER eq $KEY && $IS_SET} checked{/if}{if !$IS_SET} disabled{/if}></td>
									<td class="text-center">
										<button class="btn btn-outline-secondary btn-sm js-show-config-modal js-popover-tooltip mr-1" type="button" data-provider="{$KEY}"
										data-content="{\App\Language::translate('LBL_PROVIDER_CONFIG', $MODULENAME)}">
											<span class="fas fa-cog"></span>
										</button>
										<a href="{$ITEM->getLink()}" class="btn btn-outline-primary btn-sm js-popover-tooltip" role="button" target="_blank"
										data-content="{\App\Language::translate('LBL_PROVIDER_INFO_'|cat:$KEY|upper, $MODULENAME)}">
											<span class="fas fa-info"></span>
										</a>
									</td>
								</tr>
							{/foreach}
						</tbody>
					</table>
					<div class="w-100 d-flex justify-content-end mb-2">
						<button type="button" class="btn btn-success saveGlobal"><span class="fa fa-check mr-2"></span>{\App\Language::translate('LBL_SAVE_GLOBAL_SETTINGS', $MODULENAME)}</button>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>

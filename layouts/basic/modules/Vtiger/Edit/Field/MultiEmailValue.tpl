{*<!-- {[The file is published on the basis of YetiForce Public License 3.0 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
{strip}
	{assign var=TABINDEX value=$FIELD_MODEL->getTabIndex()}
	{if empty($ITEM['e']) }
		{assign var=ITEM_VAL value=''}
	{else}
		{assign var=ITEM_VAL value=$ITEM['e']}
	{/if}
	<div class="tpl-Base-Edit-Field-MultiEmailValue u-flex-default form-group mr-1 mb-2 js-multi-email js-multi-email-row-{counter}"
		 data-js="container">
		<label for="staticEmail2" class="sr-only" for="email-value">
			{\App\Language::translate('LBL_EMAIL_ADRESS', $MODULE)}
		</label>
		<div class="input-group">
			<div class="input-group-prepend">
				<button type="button" class="btn btn-outline-danger border js-remove-item" tabindex="{$TABINDEX}" data-js="click">
					<span class="fas fa-times" title="{\App\Language::translate('LBL_REMOVE', $MODULE)}"></span>
				</button>
			</div>
			<input name="{$FIELD_MODEL->getFieldName()}_tmp" value="{$ITEM_VAL}" type="text" class="form-control js-email" data-js="email" id="email-value" tabindex="{$TABINDEX}"
				   placeholder="{\App\Language::translate('LBL_EMAIL_ADRESS', $MODULE)}"
				   data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true} required,{/if}funcCall[Vtiger_MultiEmail_Validator_Js.invokeValidation]]"
				   aria-label="{\App\Language::translate('LBL_EMAIL_ADRESS', $MODULE)}"/>
			<div class="input-group-append btn-group-toggle" data-js="click" data-toggle="buttons">
				<label class="btn btn-outline-default border {if !empty($ITEM['o']) && $ITEM['o'] }active{/if} js-multi-email__checkbox"
					   data-js="checkbox" for="consent-to-send">
					<div class="c-float-label__container"
						 title="{\App\Language::translate('LBL_CONSENT_TO_SEND', $MODULE)}">
						<div class="c-float-label__hidden-ph">
							{\App\Language::translate('LBL_CONSENT_TO_SEND', $MODULE)}
						</div>
						<input type="checkbox" class="js-checkbox" data-js="js-checkbox" id="consent-to-send" tabindex="{$TABINDEX}" autocomplete="off" {if !empty($ITEM['o']) && $ITEM['o'] }checked="checked"{/if} />
					<span class="js-multi-email__checkbox__icon far {if !empty($ITEM['o']) && $ITEM['o'] }fa-check-square{else}fa-square{/if} position-absolute"
							  title="{\App\Language::translate('LBL_CONSENT_TO_SEND', $MODULE)}" data-js="class"></span>
						<label class="c-float-label__label" for="consent-to-send">
							{\App\Language::translate('LBL_CONSENT_TO_SEND', $MODULE)}
						</label>
					</div>
				</label>
			</div>
		</div>
	</div>
{/strip}

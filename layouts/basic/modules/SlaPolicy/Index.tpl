{*<!-- {[The file is published on the basis of YetiForce Public License 3.0 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
{strip}
	<!-- tpl-SlaPolicy-Index -->
	<div class="js-sla-policy relatedContainer" data-js="container">
		<input type="hidden" name="target" value="{$TARGET_MODULE}">
			<div class="form-group row text-center">
				<div class="col-12 flex">
					<label class="d-inline-block mr-2">{\App\Language::translate('LBL_POLICY_FROM',$MODULE_NAME)}: </label>
					<label class="d-inline-block mr-2"><input type="radio" name="policy_type" class="form-control d-inline-block mr-1 js-sla-policy-type-radio" value="default" />{\App\Language::translate('LBL_POLICY_FROM_DEFAULT', $MODULE_NAME)}</label>
					<label class="d-inline-block mr-2"><input type="radio" name="policy_type" class="form-control d-inline-block mr-1 js-sla-policy-type-radio" value="template" />{\App\Language::translate('LBL_POLICY_FROM_TEMPLATE', $MODULE_NAME)}</label>
					<label class="d-inline-block mr-2"><input type="radio" name="policy_type" class="form-control d-inline-block mr-1 js-sla-policy-type-radio" value="custom" />{\App\Language::translate('LBL_POLICY_FROM_CUSTOM', $MODULE_NAME)}</label>
					<button class="js-sla-policy-custom btn btn-success float-right d-none"><span class="fas fa-plus"></span> {\App\Language::translate('LBL_ADD_RECORD')}</button>
				</div>
			</div>
			<div class="js-sla-policy-template js-sla-policy-template--container form-group row d-none" data-js="container"></div>
			<div class="js-sla-policy-custom form-group row d-none" data-js="container"></div>
			<div class="row">
				<div class="col text-center">
					<button class="btn btn-success js-sla-policy-save-btn"><span class="fas fa-check mr-2"></span>{\App\Language::translate('LBL_SAVE')}</button>
				</div>
			</div>
	</div>
	<!-- /tpl-SlaPolicy-Index -->
{/strip}

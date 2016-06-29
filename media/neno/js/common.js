/**
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


/**
 * Highlight box
 * @param selector jQuery Selector
 *
 * @return void
 */
function highlightBox(selector) {

	jQuery(selector).addClass('highlighted-box');
	setTimeout(function () {
		jQuery(selector).removeClass('highlighted-box');
	}, 500);
}

/**
 * Load missing translations method
 *
 * @param listSelector List jQuery selector.
 * @param placement Where is the dropdown placed.
 *
 * @return void
 */
function loadMissingTranslationMethodSelectors(listSelector, placement) {
	apply = false;
	if (typeof listSelector != 'string') {
		var parent = jQuery('.translation-method-selector-container').parent();

		if (typeof parent.prop('id') == 'undefined' || parent.prop('id') == '') {
			listSelector = '.method-selectors';
		}
		else {
			listSelector = '#' + parent.prop('id');
		}
	}

	if (typeof placement != 'string') {
		placement = 'language';
	}

	if (typeof jQuery(this).prop("tagName") == 'undefined') {
		i = 1;
		jQuery(listSelector).each(function () {
			//Count how many we currently are showing
			var n = jQuery(this).find('.translation-method-selector-container').length;

			//If we are loading because of changing a selector, remove all children
			var selector_id = jQuery(this).find('.translation-method-selector').attr('data-selector-id');
			if (typeof selector_id !== 'undefined') {
				//Loop through each selector and remove the ones that are after this one
				for (var i = 0; i < n; i++) {
					if (i > selector_id) {
						jQuery(this).find("[data-selector-container-id='" + i + "']").remove();
					}
				}
			}
			//Create a string to pass the current selections
			var selected_methods_string = '';
			jQuery(this).find('.translation-method-selector').each(function () {
				selected_methods_string += '&selected_methods[]=' + jQuery(this).find(':selected').val();
			});
			var lang = jQuery(this).closest(listSelector).data('language');
			var otherParams = '';

			if (typeof lang != 'undefined') {
				otherParams = '&language=' + lang;
			}

			executeAjaxForTranslationMethodSelectors(listSelector, placement, n, selected_methods_string, jQuery(this).find('.translation-method-selector'), otherParams, false);
		});
	}
	else {

		//If we are loading because of changing a selector, remove all children
		var selector_id = jQuery(this).data('selector-id');
		var n = jQuery(this).closest(listSelector).find('.translation-method-selector-container').length;
		if (typeof selector_id !== 'undefined') {
			//Loop through each selector and remove the ones that are after this one
			for (var i = 0; i < n; i++) {
				if (i > selector_id) {
					jQuery(this).closest(listSelector).find("[data-selector-container-id='" + i + "']").remove();
					n--;
				}
			}
		}
		var selected_methods_string = '&selected_methods[]=' + jQuery(this).find(':selected').val();
		var lang = jQuery(this).closest(listSelector).data('language');
		var otherParams = '';
		var element = jQuery(this);

		if (typeof lang != 'undefined') {
			otherParams = '&language=' + lang;
		}

		var modal = jQuery('#translationMethodModal');

		// There isn't a modal, so we are on the installation process setting up the translation method for the source language
		if (modal.length == 0) {
			executeAjaxForTranslationMethodSelectors(listSelector, 'general', n, selected_methods_string, element, otherParams);
		}
		else {
			var run = modal.length == 0;


			modal.modal('show');
			modal.find('.yes-btn').off('click').on('click', function () {
				saveTranslationMethod(element.find(':selected').val(), lang, selector_id + 1, true);
				run = true;
				modal.modal('hide');
				apply = true;
			});

			modal.off('hide').on('hide', function () {
				if (!run) {
					saveTranslationMethod(element.find(':selected').val(), lang, selector_id + 1, false);
				}

				executeAjaxForTranslationMethodSelectors(listSelector, placement, n, selected_methods_string, element, otherParams);
			});
		}
	}
}

/**
 * Load translation method selector via AJAX
 *
 * @param listSelector jQuery selector for dropdown
 * @param placement Where the dropdown is placed
 * @param n
 * @param selected_methods_string
 * @param element
 * @param otherParams
 */
function executeAjaxForTranslationMethodSelectors(listSelector, placement, n, selected_methods_string, element, otherParams) {
	if (typeof otherParams == 'undefined') {
		otherParams = '';
	}
	jQuery.ajax({
		url    : 'index.php?option=com_neno&task=getTranslationMethodSelector&placement=' + placement + '&n=' + n + selected_methods_string + otherParams,
		success: function (html) {
			if (html !== '') {
				jQuery(element).closest(listSelector).append(html);

				if (placement == 'language') {
					jQuery(element).closest(listSelector).find('.translation-method-selector').each(function () {
						saveTranslationMethod(jQuery(this).find(':selected').val(), jQuery(this).closest(listSelector).data('language'), jQuery(this).data('selector-id') + 1, apply);
					});
				}
			}

			jQuery('select').chosen();
			jQuery('.translation-method-selector').off('change').on('change', loadMissingTranslationMethodSelectors);
			var container = element.parents('.language-configuration');
			var select1 = element.parents(listSelector).find("[data-selector-container-id='1']");
			if (select1.length) {
				if (!container.hasClass('expanded')) {
					container.css('min-height',
						parseInt(container.css('min-height')) + 60
					);
					container.addClass('expanded');
				}
			} else if (container.hasClass('expanded')) {
				container.css('min-height',
					parseInt(container.css('min-height')) - 60
				);
				container.removeClass('expanded');
			}
		}
	});
}

/**
 * Save translation method
 *
 * @param translationMethod Translation method to save
 * @param language Language to apply the translation method
 * @param ordering Ordering of this translation method
 * @param applyToElements If this translation method should be applied to groups for a particular language
 */
function saveTranslationMethod(translationMethod, language, ordering, applyToElements) {
	if (typeof applyToElements == 'undefined') {
		applyToElements = false;
	}

	applyToElements = applyToElements ? 1 : 0;

	jQuery.ajax({
		url : 'index.php?option=com_neno&task=saveTranslationMethod',
		type: 'POST',
		data: {
			translationMethod: translationMethod,
			language         : language,
			ordering         : ordering,
			applyToElements  : applyToElements
		}
	});
}

/**
 * Set wrapper height for sidebar elements.
 */
function setResultsWrapperHeight() {
	var available = jQuery(window).outerHeight() - jQuery('header').outerHeight() - jQuery('.subhead-collapse').outerHeight() - jQuery('#status').outerHeight();
	var sidebar = jQuery('#j-sidebar-container');
	sidebar.height(available);

	var results = jQuery('#results-wrapper');
	var resultsBottom = results.position().top + results.outerHeight();
	var gap = sidebar.outerHeight() - resultsBottom;
	var elements = jQuery('#elements-wrapper');
	elements.height(elements.outerHeight() + gap - 70);
}

/**
 * Set previous state for a table
 *
 * @param event
 */
function setOldTableStatus(event) {
	if (!statusChanged) {
		var modal = jQuery('#nenomodal-table-filters');
		var oldStatus = parseInt(modal.data('current-status'));
		var tableId = modal.data('table-id');

		markLabelAsActiveByStatus(tableId, 'table', oldStatus, false);
		if (event.type != 'hide') {
			modal.modal('hide');
		}
	}
}

/**
 *
 * @param event
 */
function saveFilter(event) {
	event.preventDefault();
	var filter = jQuery(this).data('filter');
	var parent = jQuery(this).closest('.btn-group');
	var fieldId = parent.data('field');

	parent.find('.filter.hide').removeClass('hide');
	parent.find(".filter[data-filter='" + filter + "']").addClass('hide');
	parent.find('.dropdown-toggle').text(filter);

	jQuery.ajax({
		url : 'index.php?option=com_neno&task=groupselements.changeFieldFilter',
		type: 'POST',
		data: {
			fieldId: fieldId,
			filter : filter
		}
	});
}

/**
 *
 */
function changeTranslateState() {
	var id = jQuery(this).parent('fieldset').attr('data-field');
	var type = jQuery(this).parent('fieldset').attr('data-type');
	var status = parseInt(jQuery(this).val());

	if (status == 0) {
		jQuery('#filter-rows-table-'+id).hide();
	} else {
		jQuery('#filter-rows-table-'+id).show();
	}

	bindGroupElementEventes();
	markLabelAsActiveByStatus(id, type, status, status == 2);
	setTranslateStatus(id, type, status);
}

/**
 *
 * @param elementId
 * @param type
 * @param status
 */
function setTranslateStatus(elementId, type, status) {
	//Show an alert that count no longer is accurate only on Groups&Elements view
	if (getViewName() != 'installation') {
		jQuery('#reload-notice').remove();
		jQuery('.navbar-fixed-top .navbar-inner').append('<div style="padding:10px 30px;" id="reload-notice"><div class="alert alert-warning">' +
			warning_message +
			'<a href="index.php?option=com_neno&view=groupselements" class="btn btn-info pull-right" style="height: 16px; font-size: 12px;margin-top:-4px">' +
			warning_button +
			'</a></div></div>'
		).height('92');
		jQuery('body').css('padding-top', '93px');
	}

	var url = null;
	switch (type) {
		case 'table':
			url = 'index.php?option=com_neno&task=groupselements.toggleContentElementTable&tableId=' + elementId + '&translateStatus=' + status;
			break;
		case 'file':
			url = 'index.php?option=com_neno&task=groupselements.toggleContentElementLanguageFile&fileId=' + elementId + '&translateStatus=' + status;
			break;
	}

	if (url !== null) {
		jQuery.ajax({
				url    : url,
				success: function () {
					if (typeof tableFiltersCallback != 'undefined') {
						tableFiltersCallback(elementId, type);
					}
				}
			}
		);
	}
}

/**
 * Get view name
 *
 * @returns {boolean|string}
 */
function getViewName() {
	var sPageURL = decodeURIComponent(window.location.search.substring(1)),
		sURLVariables = sPageURL.split('&'),
		sParameterName,
		i;

	for (i = 0; i < sURLVariables.length; i++) {
		sParameterName = sURLVariables[i].split('=');

		if (sParameterName[0] === 'view') {
			return sParameterName[1] === undefined ? false : sParameterName[1];
		}
	}
}

/**
 *
 * @param id Table Id
 * @param type Type
 * @param status Table status
 * @param showFiltersModal Whether or not the filter modal need to be shown
 *
 * @return void
 */
function markLabelAsActiveByStatus(id, type, status, showFiltersModal) {
	var row = jQuery('.row-table[data-id="' + type + '-' + id + '"]');
	var toggler = row.find('.toggle-fields');
	var translateButton = jQuery('[for="check-toggle-translate-' + type + '-' + id + '-1"]');
	var translateSomeButton = jQuery('[for="check-toggle-translate-' + type + '-' + id + '-2"]');
	var doNotTranslateButton = jQuery('[for="check-toggle-translate-' + type + '-' + id + '-0"]');
	switch (status) {
		case 1:
			row.find('.bar').removeClass('bar-disabled');
			translateButton.addClass('active btn-success');
			doNotTranslateButton.removeClass('active btn-danger');
			translateSomeButton.removeClass('active btn-warning');

			bindToggleFieldVisibilityEvent(toggler);
			break;
		case 2:
			row.find('.bar').removeClass('bar-disabled');
			var currentStatus = jQuery(".active[for|='check-toggle-translate-" + type + "-" + id + "']").attr('for').replace('check-toggle-translate-' + type + '-' + id + '-', '');
			translateButton.removeClass('active btn-success');
			doNotTranslateButton.removeClass('active btn-danger');
			translateSomeButton.addClass('active btn-warning');
			bindToggleFieldVisibilityEvent(toggler);

			if (showFiltersModal) {
				showTableFiltersModal(id, currentStatus);
			}

			break;
		case 0:
			row.find('.bar').addClass('bar-disabled');
			doNotTranslateButton.addClass('active btn-danger');
			translateButton.removeClass('active btn-success');
			translateSomeButton.removeClass('active btn-warning');

			//Remove fields
			if (toggler.hasClass('toggler-expanded')) {
				toggler.click();
			}
			toggler.off('click');
			toggler.removeClass('toggler toggler-collapsed');
			toggler.find('span').removeClass();
			break;
	}

	jQuery('#check-toggle-translate-' + type + '-' + id + '-' + status).click();
}

function bindToggleFieldVisibilityEvent(toggler) {
	if (getViewName() == 'groupselements') {
		//Add field toggler
		toggler.off('click').on('click', toggleFieldVisibility);
		toggler.addClass('toggler toggler-collapsed');
		toggler.find('span').addClass('icon-arrow-right-3');
	}
}

/**
 *
 * @param id
 * @param currentStatus
 */
function showTableFiltersModal(id, currentStatus) {
	//Load group form html
	jQuery.ajax({
			url    : 'index.php?option=com_neno&task=groupselements.getTableFilterModalLayout&tableId=' + id,
			success: function (html) {

				statusChanged = false;

				//Inject HTML into the modal
				var modal = jQuery('#nenomodal-table-filters');
				modal.data('current-status', currentStatus);
				modal.data('table-id', id);
				modal.find('.modal-body').html(html);
				modal.modal('show');

				// Bind events
				bindGroupElementEventes();

				//Handle saving and submitting the form
				jQuery('#save-filters-btn').off('click').on('click', saveTableFilters);
			}
		}
	);
}

/**
 *
 */
function saveTableFilters() {
	var filters = [];

	jQuery('tr.filter-row').each(function () {
		// Only include if the filter contains any value
		if (jQuery(this).find('.filter-value').val()) {
			var filter = {
				field   : jQuery(this).find('.filter-field option:selected').val(),
				operator: jQuery(this).find('.filter-operator option:selected').val(),
				value   : jQuery(this).find('.filter-value').val()
			};

			filters.push(filter);
		}
	});

	if (filters.length != 0) {
		jQuery.post(
			'index.php?option=com_neno&task=groupselements.saveTableFilters',
			{
				filters: filters,
				tableId: jQuery('#nenomodal-table-filters').data('table-id')
			},
			function (data) {
				if (data = 'ok') {
					var modal = jQuery('#nenomodal-table-filters');
					setTranslateStatus(modal.data('table-id'), 2);

					if (typeof tableFiltersCallback != 'undefined') {
						tableFiltersCallback(modal.data('table-id'));
					}

					statusChanged = true;
					modal.modal('hide');
				}
			}
		);
	}
}

function printMessages(messages) {
	var scroll = 1;
	var container = jQuery("#task-messages");
	for (var i = 0; i < messages.length; i++) {
		var percent = 0;
		var log_line = jQuery('#installation-status-' + messages[i].level).clone().removeAttr('id').html(messages[i].message);
		if (messages[i].level == 1) {
			log_line.addClass('alert-' + messages[i].type);
		}

		container.append(log_line);

		//Scroll to bottom
		container.stop().animate({
			scrollTop: container[0].scrollHeight - container.height()
		}, 100);

		if (messages[i].percent != 0) {
			percent = messages[i].percent;
		}
	}

	if (percent != 0) {
		jQuery('#progress-bar').find('.bar').width(percent + '%');
	}
}

function sendDiscoveringContentStep() {
	jQuery.ajax({
		url    : 'index.php?option=com_neno&task=installation.processDiscoveringStep&contentType=content&r=' + Math.random(),
		success: function (data) {
			if (data != 'ok') {
				sendDiscoveringContentStep();
			} else {
				checkStatus();
				jQuery.installation = true;
				processInstallationStep();
				window.clearInterval(interval);
			}
		},
		error  : function () {
			sendDiscoveringContentStep();
		}
	});
}

function sendDiscoveringStructureStep() {
	jQuery.ajax({
		url    : 'index.php?option=com_neno&task=installation.processDiscoveringStep&contentType=structure&r=' + Math.random(),
		success: function (data) {
			if (data != 'ok') {
				sendDiscoveringStructureStep();
			} else {
				checkStatus();
				jQuery.installation = true;
				processInstallationStep();
				window.clearInterval(interval);
			}
		},
		error  : function () {
			sendDiscoveringStructureStep();
		}
	});
}

function checkStatus() {
	jQuery.ajax({
		url     : 'index.php?option=com_neno&task=installation.getSetupStatus&r=' + Math.random(),
		dataType: 'json',
		success : printMessages
	});
}

function bindTranslateSomeButtonEvents() {
	//Attach the translate state toggler
	jQuery('.check-toggle-translate-table-radio').off('change').on('change', changeTranslateState);
	jQuery('.check-toggle-translate-file-radio').off('change').on('change', changeTranslateState);
	jQuery('.filter').off('click').on('click', saveFilter);

	jQuery('#filters-close-button').off('click').on('click', setOldTableStatus);
	jQuery('#nenomodal-table-filters').off('hide').on('hide', setOldTableStatus);
	jQuery('.add-row-button').off('click').on('click', duplicateFilterRow);
	jQuery('.remove-row-button').off('click').on('click', removeFilterRow);
	jQuery('.active.btn-warning').off('click').on('click', function () {
		var forAttribute = jQuery(this).attr('for');
		var regex = new RegExp('check-toggle-translate-table-([0-9]+)\-[0-2]', 'g');
		var result = regex.exec(forAttribute);
		showTableFiltersModal(result[1], 2);
	});

	jQuery('[data-toogle="tooltip"]').tooltip('destroy').tooltip();
}

function duplicateFilterRow() {
	jQuery(this).closest('tr').clone().appendTo('#filters-table');
	bindGroupElementEventes();
}

function removeFilterRow() {
	if (jQuery('tr.filter-row').length > 1) {
		jQuery(this).closest('tr').remove();
	}
}

function previewContent() {
	var button = jQuery(this);
	jQuery.post(
		'index.php?option=com_neno&task=installation.previewContentFromElement&r=' + Math.random(),
		{
			id  : button.data('id'),
			type: button.data('type')
		},
		function (html) {
			var modal = jQuery('#preview-modal');
			modal.find('.modal-body').empty().append(html);
			jQuery('.preview-btn').off('click').on('click', previewContent);
			modal.modal('show');
		}
	)
}

function resetDiscoveringVariables() {
	jQuery.get('index.php?option=com_neno&task=installation.resetDiscoveringVariables&r=' + Math.random());
}

/**
 *
 * @param id
 * @param type
 */
function refreshRecordCounter(id, type) {
	var fromCallback = true;
	if (typeof id == 'undefined' || typeof id == 'object') {
		id = jQuery(this).data('table-id');
		fromCallback = false;
	}

	if (typeof type == 'undefined') {
		type = 'table';
	}

	if (type == 'table') {
		jQuery.post(
			'index.php?option=com_neno&task=installation.refreshRecordCounter&r=' + Math.random(),
			{
				tableId: id
			},
			function (html) {
				jQuery('#record-count-' + id).closest('td').empty().html(html);

				if (!fromCallback) {
					jQuery('.record-refresher-btn').off('click').on('click', refreshRecordCounter);
				}
			}
		)
	}
}
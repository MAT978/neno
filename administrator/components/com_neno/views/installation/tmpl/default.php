<?php
/**
 * @package     Neno
 * @subpackage  Views
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

$document = JFactory::getDocument();
$document->addStyleSheet(JUri::root() . '/media/neno/css/progress-wizard.min.css');
$document->addStyleSheet(JUri::root() . '/media/neno/css/languageconfiguration.css');
$document->addStyleSheet(JUri::root() . '/media/neno/css/installation.css');

// Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}
?>

<script>
	notifications = false;
	jQuery(document).ready(loadInstallationStep);

	function loadInstallationStep() {
		jQuery.ajax({
			url     : 'index.php?option=com_neno&task=installation.loadInstallationStep&r=' + Math.random(),
			dataType: 'json',
			success : function (html) {
				jQuery('.installation-form').empty().append(html.installation_step);
				if (html.jsidebar !== '') {
					showNotification();
					var sidebar = jQuery('#j-sidebar-container');
					sidebar.empty().append(html.jsidebar);
					jQuery('#j-main-container-installation').prop('id', 'j-main-container');
					jQuery('#j-main-container').addClass('span10');
					toggleSidebar(false);
					sidebar.show();
				}

				bindEvents(html.step);
			}
		});
	}

	function bindEvents(step) {
		step = typeof step !== 'undefined' ? step : 5;
		if (step != 7) {
			jQuery('.next-step-button').off('click').on('click', processInstallationStep);
			// Turn radios into btn-group
			jQuery('.radio.btn-group label').addClass('btn');
			jQuery(".btn-group label:not(.active)").click(function () {
				var label = jQuery(this);
				var input = jQuery('#' + label.attr('for'));

				if (!input.prop('checked')) {
					label.closest('.btn-group').find("label").removeClass('active btn-success btn-danger btn-primary');
					if (input.val() == '') {
						label.addClass('active btn-primary');
					} else if (input.val() == 0) {
						label.addClass('active btn-danger');
					} else {
						label.addClass('active btn-success');
					}
					input.prop('checked', true);
				}
			});
			jQuery(".btn-group input[checked=checked]").each(function () {
				if (jQuery(this).val() == '') {
					jQuery("label[for=" + jQuery(this).attr('id') + "]").addClass('active btn-primary');
				} else if (jQuery(this).val() == 0) {
					jQuery("label[for=" + jQuery(this).attr('id') + "]").addClass('active btn-danger');
				} else {
					jQuery("label[for=" + jQuery(this).attr('id') + "]").addClass('active btn-success');
				}
			});

			jQuery("[data-issue]").off('click').on('click', fixIssue);
			jQuery(".remove-language-button").off('click').on('click', function () {
				var result = confirm("<?php echo JText::_('COM_NENO_DASHBOARD_REMOVING_LANGUAGE_MESSAGE_1', true) ?>\n\n<?php echo JText::_('COM_NENO_DASHBOARD_REMOVING_LANGUAGE_MESSAGE_2', true); ?>");

				if (result) {
					jQuery(this).closest('.language-wrapper').slideUp();
					jQuery.ajax({
						url: 'index.php?option=com_neno&task=removeLanguage&language=' + jQuery(this).data('language')
					});
				}

			});

			jQuery('.save-translator-comment').off('click').on('click', function () {
				var language = jQuery(this).data('language');

				jQuery.post(
					'index.php?option=com_neno&task=saveExternalTranslatorsComment&r=' + Math.random(),
					{
						placement: 'language',
						language : language,
						comment  : jQuery(".comment-to-translator[data-language='" + language + "']").val()
					},
					function (response) {

						if (response == 'ok') {
							var text = '<?php echo JText::_('COM_NENO_COMMENTS_TO_TRANSLATOR_LANGUAGE_EDIT', true); ?>';
							text = text.replace('%s', language);
							jQuery(".add-comment-to-translator-button[data-language='" + language + "']").html('<span class="icon-pencil"></span> ' + text);
						}

						jQuery('#addCommentFor' + language).modal('toggle');
					}
				);
			});

			jQuery('.hasTooltip').tooltip();
			jQuery('select').chosen();
		}
		else {
			jQuery('.preview-btn').off('click').on('click', previewContent);
			bindTranslateSomeButtonEvents();
		}
	}

	function showNotification() {
		if (notifications) {
			try {
				installationNotification = new Notification('<?php echo JText::_('COM_NENO_INSTALLATION_POPUP', true); ?>', {
					body: '<?php echo JText::_('COM_NENO_INSTALLATION_POPUP', true); ?>',
					dir : 'auto',
					lang: '',
					icon: '<?php echo JUri::root(); ?>/media/neno/images/neno_alert.png'
				});
			} catch (e) {

			}
		}
	}

	function processInstallationStep() {
		jQuery('.loading-spin').removeClass('hide');
		var allInputs = jQuery('.installation-step').find(':input');
		var data = {};

		allInputs.each(function () {
			if (!jQuery(this).hasClass('no-data')) {
				switch (jQuery(this).prop('tagName').toLowerCase()) {
					case 'select':
						data[jQuery(this).prop('name')] = jQuery(this).find('option:selected').val();
						break;
					case 'input':
						switch (jQuery(this).prop('type')) {
							case 'checkbox':
								data[jQuery(this).prop('name')] = jQuery(this).is(':checked').val();
								break;
							default:
								data[jQuery(this).prop('name')] = jQuery(this).val();
								break;
						}
						break;
				}
			}
		});
		jQuery('#system-message-container').empty();
		jQuery.ajax({
			url     : 'index.php?option=com_neno&task=installation.processInstallationStep&r=' + Math.random(),
			type    : 'POST',
			data    : data,
			dataType: "json",
			success : function (response) {
				if (response.status == 'ok') {
					loadInstallationStep();
				}
				else {
					renderErrorMessages(response.error_messages);
				}
			}
		});
	}

	function renderErrorMessages(messages) {
		var errorMessages = jQuery('.error-messages');
		errorMessages.empty();
		for (var i = 0; i < messages.length; i++) {
			errorMessages.append('<div class="alert alert-error"><button type="button" class="close" data-dismiss="alert">&times;</button>' + messages[i] + '</div>');
		}
	}
</script>

<style>
	#j-sidebar-container {
		display : none;
	}

	#nenomodal-table-filters {
		width       : 80% !important;
		margin-left : -40% !important;
	}
</style>

<div id="j-sidebar-container"></div>
<div id="j-main-container-installation">
	<div class="installation-form"></div>
</div>
<div class="modal hide fade" id="languages-modal">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3>Modal header</h3>
	</div>
	<div class="modal-body">

	</div>
	<div class="modal-footer">
		<a href="#" id="close-button" class="btn" data-dismiss="modal">Close</a>
	</div>
</div>
<!-- Empty hidden modal -->
<div class="modal fade" id="nenomodal-table-filters" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h2 class="modal-title"
					id="nenomodaltitle"><?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_MODAL_GROUPFORM_TITLE'); ?></h2>
			</div>
			<div class="modal-body">
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" id="filters-close-button">
					<?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_MODAL_GROUPFORM_BTN_CLOSE'); ?>
				</button>
				<button type="button" class="btn btn-primary" id="save-filters-btn">
					<?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_MODAL_GROUPFORM_BTN_SAVE'); ?>
				</button>
			</div>
		</div>
	</div>
</div>


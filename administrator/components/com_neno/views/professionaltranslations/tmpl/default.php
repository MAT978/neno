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

if (!empty($this->extraSidebar))
{
	$this->sidebar .= $this->extraSidebar;
}

?>

	<style>
		.translation, .information-box {
			border: 1px solid #ccc;
			margin: 10px 0;
		}

		.translation {
			padding: 20px 15px;
			min-height: 28px;
			line-height: 28px;
			background-color: #eee;
		}

		.information-box {
			padding: 20px 15px;
		}

		.modal {
			width: 580px !important;
			margin-left: -290px !important;
			left: 50% !important;
		}

		.modal-body p,
		.modal-body h3 {
			padding: 0 15px !important;
		}

		.modal-body textarea {
			width: 98%;
			height: 70px;
		}

	</style>

	<script>
		jQuery(document).ready(function () {
			jQuery('.translate_automatically_setting').off('click').on('click', function () {
				jQuery.ajax({
					type: "POST",
					url: 'index.php?option=com_neno&task=professional_translations.setAutomaticTranslationSetting',
					data: {
						setting: jQuery(this).data('setting'),
						value: +jQuery(this).is(':checked')
					},
					success: function (data) {
						if (data != 'ok') {
							alert("<?php echo JText::_('COM_NENO_EXTERNAL_TRANSLATION_ERROR_SAVING_SETTING'); ?>");
						}
					}

				});
			});

			jQuery('.order-button').off('click').on('click', function () {
				var button = jQuery(this);
				jQuery.ajax({
					type: "POST",
					url: 'index.php?option=com_neno&task=professional_translations.createJob',
					data: {
						type: jQuery(this).data('type'),
						language: jQuery(this).data('language')
					},
					success: function (data) {
						if (data == 'no_tc') {
							alert("<?php echo JText::_('COM_NENO_EXTERNAL_TRANSLATION_ERROR_ORDERING_NOT_ENOUGH_TC'); ?>");
						}
						else if (data == 'ok') {
							button.closest('.translation').slideToggle();
						}
						else {
							alert("<?php echo JText::_('COM_NENO_EXTERNAL_TRANSLATION_ERROR_ORDERING_GENERAL_ERROR'); ?>");
						}
					}
				});
			});

			if (window.location.search.toLowerCase().indexOf("open=comment") >= 0) {
				jQuery('#addCommentForTranslators').modal('show');
			}
		})
		;
	</script>

	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<div class="span9">
			<div id="elements-wrapper">
				<h1><?php echo JText::_('COM_NENO_TITLE_PROFESSIONAL_TRANSLATIONS'); ?></h1>

				<p><?php echo JText::_('COM_NENO_PROFESSIONAL_TRANSLATION_INTROTEXT'); ?></p>
				<p><?php echo JText::_('COM_NENO_PROFESSIONAL_TRANSLATION_INTROTEXT_PRICE'); ?></p>

				<?php $professionalTranslationsAvailable = false; ?>
				<?php foreach ($this->items as $key => $item): ?>
					<?php if ($item->translation_method_id == '3'): ?>
						<?php $professionalTranslationsAvailable = true; ?>
						<div class="translation">
							<div class="span3">
								<img
								  src="<?php echo JUri::root(); ?>/media/mod_languages/images/<?php echo $item->image; ?>.gif"
								  style="margin-bottom: 3px;">
								<?php echo $item->title_native; ?>
							</div>
							<div class="span3">
								<?php echo JText::sprintf('COM_NENO_PROFESSIONAL_TRANSLATION_WORDS', $item->words); ?>
							</div>
							<div class="span3">
								<?php $proPriceTc = $item->words * $item->tc_price; ?>
								<?php $proPriceEur = number_format($item->words * $item->euro_price, 2, ',', '.'); ?>
								<?php echo JText::sprintf('COM_NENO_PROFESSIONAL_TRANSLATION_PRICE'); ?>
								<?php echo $proPriceTc; ?> TC
								(€ <?php echo $proPriceEur; ?>)
							</div>
							<div class="span3">
								<button type="button"
								        class="btn order-button"
								        data-type="<?php echo $item->translation_method_id; ?>"
								        data-language="<?php echo $item->language; ?>">
									<?php echo JText::_('COM_NENO_PROFESSIONAL_TRANSLATION_ORDER_NOW'); ?>
								</button>
							</div>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
				<?php if ($professionalTranslationsAvailable === false): ?>
					<div
					  class="alert alert-info"><?php echo JText::sprintf('COM_NENO_PROFESSIONAL_TRANSLATION_NO_TRANSLATIONS_AVAILABLE', JRoute::_('index.php?option=com_neno&view=groupselements')); ?></div>
				<?php endif; ?>

				<a
				  href="#addCommentForTranslators"
				  role="button"
				  class="btn add-comment-to-translator-button"
				  title=""
				  type="button"
				  data-toggle="modal">
					<span class="icon-pencil"></span>
					<?php if (empty($this->comment)): ?>
						<?php $btnLabel = 'COM_NENO_COMMENTS_TO_TRANSLATOR_GENERAL_CREATE'; ?>
					<?php else: ?>
						<?php $btnLabel = 'COM_NENO_COMMENTS_TO_TRANSLATOR_GENERAL_EDIT'; ?>
					<?php endif; ?>
					<?php echo JText::_($btnLabel); ?>
				</a>
			</div>
		</div>
		<div class="span3">
			<div class="information-box span11 pull-right">
				<div class="center">
					<div>
						<p class="center">
							<?php if ($this->needsTc): ?>
								<?php echo JText::sprintf('COM_NENO_PROFESSIONAL_TRANSLATION_BUY_TC_TEXT', $this->tcNeeded); ?>
							<?php endif; ?>
						</p>
					</div>
					<div class="center">
						<h3>
							<?php if ($this->needsTc): ?>
								<?php echo JText::sprintf('COM_NENO_PROFESSIONAL_TRANSLATION_PRICE'); ?>
								&nbsp;€<?php echo number_format(ceil($this->tcNeeded * 0.0005), 2, ',', '.'); ?>
							<?php else: ?>
								<?php echo JText::_('COM_NENO_PROFESSIONAL_TRANSLATION_BUY_ADDITIONAL_TC_TEXT'); ?>
							<?php endif; ?>
						</h3>
					</div>
					<div class="center">
						<button
						  onclick="location.href='https://www.neno-translate.com/en/pricing/checkout'"
						  class="btn btn-primary">
							<?php echo JText::_('COM_NENO_PROFESSIONAL_TRANSLATION_ADD_FUNDS_BUTTON'); ?>
						</button>
					</div>
				</div>
			</div>

			<div class="information-box span11 pull-right">
				<div class="center">
					<p><i class="icon-user"></i> </p>
					<p><?php echo JText::_('COM_NENO_PROFESSIONAL_TRANSLATION_PROFESSIONAL_TRANSLATION_AGENCIES'); ?></p>
				</div>
			</div>

			<div class="information-box span11 pull-right">
				<div class="center">
					<p><i class="icon-clock"></i> </p>
					<p><?php echo JText::sprintf('COM_NENO_PROFESSIONAL_TRANSLATION_PROFESSIONAL_TRANSLATION_TIME', '#'); ?></p>
				</div>
			</div>

			<div class="information-box span11 pull-right">
				<div class="center">
					<p><i class="icon-loop"></i> </p>
					<input type="checkbox"
					       class="translate_automatically_setting"
					       data-setting="translate_automatically_professional"
					       name="machine_translation" <?php echo NenoSettings::get('translate_automatically_professional') ? 'checked="checked"' : ''; ?>
					       value="1"/> <?php echo JText::_('COM_NENO_PROFESSIONAL_TRANSLATION_AUTOMATICALLY_PROFESSIONAL_TRANSLATE'); ?>
				</div>
			</div>

			<?php // Only show the jobs link if there are any jobs ?>
			<?php if (NenoHelperBackend::areThereAnyJobs()): ?>
				<div class="information-box span11 pull-right alert alert-info">
					<div class="center">
						<div>
							<p class="left">
								<?php echo JText::_('COM_NENO_PROFESSIONAL_TRANSLATION_JOBS_INTRO'); ?>
								<br/>
								<a
								  href="<?php echo JRoute::_('index.php?option=com_neno&view=jobs'); ?>"><?php echo JText::_('COM_NENO_PROFESSIONAL_TRANSLATION_JOBS_LINK'); ?></a>
							</p>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<div id="addCommentForTranslators" class="modal hide fade" tabindex="-1"
	     role="dialog"
	     aria-labelledby="myModalLabel"
	     aria-hidden="true">
		<div class="modal-body">
			<h3
			  class="myModalLabel"><?php echo JText::sprintf('COM_NENO_COMMENTS_TO_TRANSLATOR_GENERAL_MODAL_ADD_TITLE'); ?></h3>

			<p><?php echo JText::_('COM_NENO_COMMENTS_TO_TRANSLATOR_MODAL_ADD_BODY_PRE'); ?></p>

			<p><?php echo JText::sprintf('COM_NENO_COMMENTS_TO_TRANSLATOR_GENERAL_MODAL_ADD_BODY', JRoute::_('index.php?option=com_neno&view=dashboard'), JRoute::_('index.php?option=com_neno&view=editor')); ?></p>

			<p><?php echo JText::sprintf('COM_NENO_COMMENTS_TO_TRANSLATOR_GENERAL_MODAL_ADD_BODY_POST', NenoSettings::get('source_language')); ?></p>

			<p>
			<textarea
			  class="comment-to-translator"><?php echo empty($this->comment) ? '' : $this->comment; ?></textarea>
			</p>
		</div>
		<div class="modal-footer">
			<a href="#" class="btn" data-dismiss="modal"
			   aria-hidden="true"><?php echo JText::_('COM_NENO_COMMENTS_TO_TRANSLATOR_MODAL_BTN_CLOSE'); ?></a>
			<a href="#"
			   class="btn btn-primary"
			   id="save-comment"><?php echo JText::_('COM_NENO_COMMENTS_TO_TRANSLATOR_MODAL_BTN_SAVE'); ?></a>
		</div>
	</div>

	<script>
		jQuery(document).ready(function () {
			jQuery('#save-comment').on('click', function () {
				jQuery.post(
				  'index.php?option=com_neno&task=saveExternalTranslatorsComment',
				  {
					  placement: 'general',
					  comment: jQuery('.comment-to-translator').val()
				  },
				  function (response) {
					  if (response == 'ok') {
						  jQuery('.add-comment-to-translator-button').html('<span class="icon-pencil"></span> <?php echo JText::_('COM_NENO_COMMENTS_TO_TRANSLATOR_GENERAL_EDIT', true); ?>');
					  }

					  jQuery('#addCommentForTranslators').modal('toggle');
				  }
				)
			});
		});
	</script>
<?php echo NenoHelperBackend::renderVersionInfoBox(); ?>
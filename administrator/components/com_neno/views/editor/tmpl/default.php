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

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('behavior.keepalive');
JHtml::_('behavior.modal', 'a.modal_jform_contenthistory');

// Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extraSidebar))
{
	$this->sidebar .= $this->extraSidebar;
}
$version  = NenoHelperBackend::getNenoVersion();
$document = JFactory::getDocument();
$document->addScript(JUri::root() . '/media/neno/js/editor.js?v=' . $version);
$document->addScript(JUri::root() . '/media/neno/js/codemirror/codemirror.js?v=' . $version);
$document->addScript(JUri::root() . '/media/neno/js/codemirror/addon/fold/xml-fold.js?v=' . $version);
$document->addScript(JUri::root() . '/media/neno/js/codemirror/addon/edit/matchtags.js?v=' . $version);
$document->addScript(JUri::root() . '/media/neno/js/codemirror/mode/css/css.js?v=' . $version);
$document->addScript(JUri::root() . '/media/neno/js/codemirror/mode/javascript/javascript.js?v=' . $version);
$document->addScript(JUri::root() . '/media/neno/js/codemirror/mode/xml/xml.js?v=' . $version);
$document->addScript(JUri::root() . '/media/neno/js/codemirror/mode/htmlmixed/htmlmixed.js?v=' . $version);
$document->addStyleSheet(JUri::root() . '/media/neno/js/codemirror/codemirror.css?v=' . $version);

?>
<script>
	jQuery(document).ready(function () {

		// Set a js variable depending on weather we are ready to translate using API
		<?php if (NenoSettings::get('translator') == '' || NenoSettings::get('translator_api_key') == ''): ?>
		ready_for_api_translation = false;
		<?php else: ?>
		ready_for_api_translation = true;
		<?php endif; ?>

		// Set a javascript variable with the id of the default action
		default_editor_load_action = '<?php echo $this->defaultAction; ?>';

		// Set language variables for use in JS
		JTEXT_COM_NENO_COMMENTS_TO_TRANSLATOR_EDITOR_DISPLAY_COMMENT_TITLE = <?php echo json_encode(JText::_('COM_NENO_COMMENTS_TO_TRANSLATOR_EDITOR_DISPLAY_COMMENT_TITLE')); ?>;
		JTEXT_COM_NENO_EDITOR_UNSAVED_CHANGES = <?php echo json_encode(JText::_('COM_NENO_EDITOR_UNSAVED_CHANGES')); ?>;
		JTEXT_COM_NENO_EDITOR_NOTICE_TAG_MISMATCH = <?php echo json_encode(JText::_('COM_NENO_EDITOR_NOTICE_TAG_MISMATCH')); ?>;
		JTEXT_COM_NENO_EDITOR_NOTICE_TAG_MISMATCH_CONFIRM = <?php echo json_encode(JText::_('COM_NENO_EDITOR_NOTICE_TAG_MISMATCH_CONFIRM')); ?>;
		JTEXT_COM_NENO_EDITOR_SET_DEFAULT_ACTION_CONFIRM = <?php echo json_encode(JText::_('COM_NENO_EDITOR_SET_DEFAULT_ACTION_CONFIRM')); ?>;
		JTEXT_COM_NENO_EDITOR_SET_DEFAULT_ACTION = {};
		JTEXT_COM_NENO_EDITOR_SET_DEFAULT_ACTION.copy = <?php echo json_encode(JText::_('COM_NENO_EDITOR_SET_DEFAULT_ACTION_1')); ?>;
		JTEXT_COM_NENO_EDITOR_SET_DEFAULT_ACTION.translate = <?php echo json_encode(JText::_('COM_NENO_EDITOR_SET_DEFAULT_ACTION_2')); ?>;

	});
</script>

<?php $document->addStyleSheet(JUri::root() . '/media/neno/css/editor.css'); ?>

<div id="j-sidebar-container" class="span2">
	<form action="<?php echo JRoute::_('index.php?option=com_neno&view=editor'); ?>" method="post"
		name="adminForm" id="adminForm">
		<?php $extraDisplayData = new stdClass; ?>
		<?php $extraDisplayData->groups = $this->groups; ?>
		<?php $extraDisplayData->statuses = $this->statuses; ?>
		<?php $extraDisplayData->methods = $this->methods; ?>
		<?php $extraDisplayData->modelState = $this->state; ?>
		<?php echo JLayoutHelper::render('editorfilters', array('view' => $this, 'extraDisplayData' => $extraDisplayData), JPATH_NENO_LAYOUTS); ?>
		<input type="hidden" name="limitstart" id="limitstart" value="0" />
		<input type="hidden" name="list_limit" id="list_limit" value="30" />
		<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
	</form>
	<div id="filter-tags-wrapper"></div>
	<div id="results-wrapper">
			<span id="editor-strings-title">
				<?php echo JText::_('COM_NENO_EDITOR_STRINGS'); ?>:
			</span>

		<div id="elements-wrapper">
			<?php echo JLayoutHelper::render('editorstrings', $this->items, JPATH_NENO_LAYOUTS); ?>
		</div>
	</div>
</div>

<?php
// If we are loading related strings then add '_ALL' to the JText of the buttons
$all = '';
if (NenoSettings::get('load_related_content'))
{
	$all = '_ALL';
}
?>
<div id="j-main-container" class="span10">

	<div class="row">
		<div class="span12 editor-buttons">
			<div class="pull-right">
				<div class="pull-right right-buttons">
					<button id="copy-btn" class="btn btn-big" type="button">
						<span class="icon-copy big-icon"></span>
						<span class="normal-text"><?php echo JText::_('COM_NENO_EDITOR_COPY_BUTTON' . $all); ?></span>
						<span class="small-text">Ctrl + <span class="arrow">&rArr;</span></span>
					</button>
					<button id="translate-btn" class="btn btn-big" type="button">
						<span class="icon-comments-2 big-icon"></span>
                        <span
							class="normal-text"><?php echo JText::_('COM_NENO_EDITOR_COPY_AND_TRANSLATE_BUTTON' . $all); ?></span>
						<span class="small-text">Ctrl + Shift + <span class="arrow">&rArr;</span></span>
					</button>
					<button id="skip-button" class="btn btn-big" type="button"
						data-id="<?php echo empty($translation) ? '' : $translation->id; ?>">
						<span class="icon-next big-icon"></span>
						<span class="normal-text"><?php echo JText::_('COM_NENO_EDITOR_SKIP_BUTTON'); ?></span>
						<span class="small-text">Ctrl + Space</span>
					</button>
					<button id="draft-button" class="btn btn-big" type="button"
						data-id="<?php echo empty($translation) ? '' : $translation->id; ?>">
						<span class="icon-box-add big-icon"></span>
						<span class="normal-text"><?php echo JText::_('COM_NENO_EDITOR_SAVE_AS_DRAFT_BUTTON' . $all); ?></span>
						<span class="small-text">Ctrl + S</span>
					</button>
					<button id="save-next-button" class="btn btn-big btn-success" type="button"
						data-id="<?php echo empty($translation) ? '' : $translation->id; ?>">
						<span class="icon-checkmark big-icon"></span>
						<span class="normal-text"><?php echo JText::_('COM_NENO_EDITOR_SAVE_AND_NEXT_BUTTON' . $all); ?></span>
						<span class="small-text">Ctrl + Enter</span>
					</button>
				</div>
			</div>
		</div>
	</div>

	<div id="editor-wrapper">
		Loading...
	</div>
</div>

<div class="modal hide fade" id="consolidate-modal">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h3><?php echo JText::_('COM_NENO_CONSOLIDATE_TRANSLATION_HEADER'); ?></h3>
			</div>
			<div class="modal-body">
				<p><?php echo JText::_('COM_NENO_EDITOR_CONSOLIDATE_MESSAGE_INTRO'); ?></p>
				<p id="consolidate-dynamic-content"></p>
				<p><?php echo JText::_('COM_NENO_EDITOR_CONSOLIDATE_MESSAGE_QUESTION'); ?></p>
			</div>
			<div class="modal-footer">
				<a href="#" class="btn" data-dismiss="modal" aria-hidden="true"><?php echo JText::_('COM_NENO_CONSOLIDATE_TRANSLATION_NO'); ?></a>
				<a href="#" class="btn btn-primary" id="consolidate-button"><?php echo JText::_('COM_NENO_CONSOLIDATE_TRANSLATION_YES'); ?></a>
			</div>
		</div>
	</div>
</div>

<div class="modal hide fade" id="consolidate-confirm-modal">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h3><?php echo JText::_('COM_NENO_CONSOLIDATE_TRANSLATION_HEADER'); ?></h3>
			</div>
			<div class="modal-body">
				<p><?php echo JText::_('COM_NENO_CONSOLIDATE_TRANSLATION_CONFIRM'); ?></p>
			</div>
			<div class="modal-footer">
				<a href="#" class="btn btn-primary" id="consolidate-button-close" data-dismiss="modal" aria-hidden="true"><?php echo JText::_('JTOOLBAR_CLOSE'); ?></a>
			</div>
		</div>
	</div>
</div>

<?php if (NenoSettings::get('translator') == '' || NenoSettings::get('translator_api_key') == ''): ?>

	<!-- Modal for translator API key -->
	<div id="translatorKeyModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="translatorKey"
		aria-hidden="true">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="myModalLabel"><?php echo JText::_('COM_NENO_EDITOR_NO_TRANSLATOR_HEADER'); ?></h3>
		</div>
		<div class="modal-body">
			<p><?php echo JText::_('COM_NENO_EDITOR_NO_TRANSLATOR_MESSAGE'); ?></p>
			<br />
			<br />
			<table class="full-width">
				<tr>
					<td class='setting-label'>
						<?php echo JText::_('COM_NENO_SETTINGS_SETTING_NAME_TRANSLATOR'); ?>
						<span class="modal-tooltip"
							data-toggle="tooltip"
							data-html="true"
							data-placement="right"
							title='<?php echo JText::_('COM_NENO_SETTINGS_SETTING_INFO_TRANSLATOR'); ?>'>[?]</span>
					</td>
					<td class=''>
						<?php echo NenoHelper::getTranslatorsSelect(); ?>
					</td>
				</tr>
				<tr>
					<td class='setting-label'>
						<?php echo JText::_('COM_NENO_SETTINGS_SETTING_NAME_TRANSLATOR_API_KEY'); ?>
					</td>
					<td class=''>
						<input type="text" name="translator_api_key" id="translator_api_key"
							class="input-setting input-large"
							value="" />
					</td>
				</tr>
			</table>
		</div>
		<div class="modal-footer">
			<div class="pull-left">
				<a href="https://www.neno-translate.com/en/help/documentation/frequently-asked-questions/installation-and-upgrade/16-how-to-get-a-google-or-yandex-api-key" target="_blank"><?php echo JText::_('COM_NENO_SETTINGS_SETTING_NAME_API_KEY_DOCS_LINK'); ?></a>
			</div>
			<button class="btn" data-dismiss="modal"
				aria-hidden="true"><?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_MODAL_GROUPFORM_BTN_CLOSE'); ?></button>
			<button class="btn btn-primary"
				id="save-api-key-btn"><?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_MODAL_GROUPFORM_BTN_SAVE'); ?></button>
		</div>
	</div>
<?php endif; ?>


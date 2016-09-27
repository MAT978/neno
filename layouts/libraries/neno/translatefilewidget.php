<?php

/**
 * @package    Neno
 *
 * @author     Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright  Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

$languageFile = $displayData;
?>

<fieldset id="check-toggle-translate-file-<?php echo $languageFile->id; ?>"
          class="radio btn-group" data-field="<?php echo $languageFile->id; ?>" data-type="file">
	<!-- Translate -->
	<input class="check-toggle-translate-file-radio" type="radio"
	       id="check-toggle-translate-file-<?php echo $languageFile->id; ?>-1"
	       name="jform[check-toggle-translate-file]"
	       value="1" <?php echo ($languageFile->translate == 1) ? 'checked="checked"' : ''; ?>>
	<label for="check-toggle-translate-file-<?php echo $languageFile->id; ?>-1"
	       class="btn btn-small <?php echo ($languageFile->translate == 1) ? 'active btn-success' : ''; ?>"
	       data-toogle="tooltip" title="<?php echo JText::_('COM_NENO_GROUPS_ELEMENTS_TRANSLATE_BUTTON_TOOLTIP'); ?>">
		<?php echo JText::_('COM_NENO_GROUPS_ELEMENTS_TRANSLATE_BUTTON'); ?>
	</label>

	<!-- Do not translate -->
	<input class="check-toggle-translate-file-radio" type="radio"
	       id="check-toggle-translate-file-<?php echo $languageFile->id; ?>-0"
	       name="jform[check-toggle-translate-file]"
	       value="0" <?php echo ($languageFile->translate == 0) ? 'checked="checked"' : ''; ?>>
	<label for="check-toggle-translate-file-<?php echo $languageFile->id; ?>-0"
	       class="btn btn-small <?php echo (!$languageFile->translate) ? 'active btn-danger' : ''; ?>"
	       data-toogle="tooltip"
	       title="<?php echo JText::_('COM_NENO_GROUPS_ELEMENTS_DO_NOT_TRANSLATE_BUTTON_TOOLTIP'); ?>"
	>
		<?php echo JText::_('COM_NENO_GROUPS_ELEMENTS_DO_NOT_TRANSLATE_BUTTON'); ?>
	</label>
</fieldset>

<button class="btn btn-small preview-btn" type="button"
        data-id="<?php echo $languageFile->id; ?>"
        data-type="file" data-toogle="tooltip"
        title="<?php echo JText::_('COM_NENO_PREVIEW_BTN_TOOLTIP_LANGUAGE_FILE'); ?>">
	<i class="icon-eye"></i> <?php echo JText::_('COM_NENO_PREVIEW_BTN'); ?>
</button>
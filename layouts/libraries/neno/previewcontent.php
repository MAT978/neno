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

?>

<style>
	.preview-box {
		overflow-y : scroll;
		height     : 250px;
		border     : 2px solid #ccc;
		padding    : 5px;
	}
</style>

<h1><?php echo JText::_('COM_NENO_INSTALLATION_INSTALLATION_STEP_5_PREVIEW_CONTENT_LAYOUT_TITLE'); ?></h1>
<h6><?php echo JText::sprintf('COM_NENO_INSTALLATION_INSTALLATION_STEP_5_PREVIEW_CONTENT_LAYOUT_SUBTITLE_' . strtoupper($displayData->type), $displayData->name); ?></h6>
<button type="button" class="btn preview-btn" data-id="<?php echo $displayData->id; ?>" data-type="<?php echo $displayData->type; ?>">
	<i class="icon-loop"></i> <?php echo JText::_('COM_NENO_INSTALLATION_INSTALLATION_STEP_5_PREVIEW_CONTENT_LAYOUT_REFRESH_BTN'); ?>
</button>
<div class="preview-box">
	<?php if (isset($displayData->fields)): ?>
		<?php foreach ($displayData->fields as $field): ?>
			<?php if (!empty($displayData->records[0]->{$field->field_name})): ?>
				<h5><?php echo JText::sprintf('COM_NENO_INSTALLATION_INSTALLATION_STEP_5_PREVIEW_CONTENT_LAYOUT_FIELD_LIST_ITEM', $field->field_name, $field->field_type); ?></h5>
				<ul>
					<?php foreach ($displayData->records as $record): ?>
						<?php if (!empty($record->{$field->field_name})): ?>
							<li><?php echo htmlentities($record->{$field->field_name}); ?></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		<?php endforeach; ?>
	<?php else: ?>
		<ul>
			<?php foreach ($displayData->records as $record): ?>
				<li><?php echo htmlentities($record); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

</div>

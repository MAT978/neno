<?php
/**
 * @package    Neno
 *
 * @author     Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright  Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

//No direct access
defined('_JEXEC') or die;

$table = $displayData;

?>

<?php echo JText::sprintf('COM_NENO_INSTALLATION_INSTALLATION_STEP_5_RECORD_COUNT', $table->id, $table->record_count); ?>
<button type="button" class="btn btn-mini record-refresher-btn" data-table-id="<?php echo $table->id; ?>"
	data-toogle="tooltip" title="<?php echo JText::_('COM_NENO_INSTALLATION_INSTALLATION_STEP_5_RECORD_COUNT_REFRESH_BTN'); ?>">
	<i class="icon-loop"></i>
</button>
<?php if ($table->record_count > 1000): ?>
	<i class="icon-warning" data-toogle="tooltip" title="<?php echo JText::_('COM_NENO_INSTALLATION_INSTALLATION_STEP_5_RECORD_COUNT_WARNING'); ?>"></i>
<?php endif; ?>

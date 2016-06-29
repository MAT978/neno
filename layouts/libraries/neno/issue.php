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

JHtml::_('bootstrap.tooltip');

?>
<?php if ($displayData->fixed) : ?>
	<li class="alert alert-success">
		<h4><?php echo $displayData->details->message; ?></h4>
		<p>
			<?php echo $displayData->details->description; ?>
		</p>
	</li>
<?php else : ?>
	<li class="alert alert-error">
		<h4><?php echo $displayData->details->message; ?></h4>
		<p>
			<?php echo $displayData->details->description; ?>
		</p>
		<p>
			<?php echo JText::_('COM_NENO_ISSUE_MESSAGE_DISCOVERED') . ': ' . $displayData->discovered; ?>
		</p>
		<?php if ($displayData->fixable) : ?>
			<?php $lang = ($displayData->viewLang == null) ? '' : '&lang=' . $displayData->viewLang; ?>
			<a href="<?php echo JRoute::_('index.php?option=com_neno&task=debug.fixIssue&id=' . (int) $displayData->id . $lang); ?>&r=<?php echo NenoHelperBackend::generateRandomString(); ?>" class="btn btn-small btn-success">
				<?php echo JText::_('COM_NENO_ISSUE_FIX'); ?>
			</a>
		<?php else : ?>
			<?php echo JText::_('COM_NENO_ISSUE_NOT_FIXABLE'); ?>
		<?php endif; ?>
	</li>
<?php endif; ?>

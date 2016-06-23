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

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('behavior.keepalive');

$listOrder     = $this->state->get('list.ordering');
$listDirection = $this->state->get('list.direction');

// Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extraSidebar))
{
	$this->sidebar .= $this->extraSidebar;
}

?>

<div id="j-sidebar-container" class="span2">
	<?php echo $this->sidebar; ?>
</div>
<div class="span11">
	<form action="<?php echo JRoute::_('index.php?option=com_neno&view=strings'); ?>" method="post" name="adminForm"
	      id="adminForm">
		<div id="j-main-container" class="span10">
			<div id="elements-wrapper">
				<table class="table table-striped table-jobs" id="table-jobs">
					<tr>
						<th>
							<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'id', $listDirection, $listOrder); ?>
						</th>
						<th>
							<?php echo JHtml::_('grid.sort', 'JSTATUS', 'state', $listDirection, $listOrder); ?>
						</th>
						<th>
							<?php echo JHtml::_('grid.sort', 'COM_NENO_JOBS_LANGUAGE', 'to_language', $listDirection, $listOrder); ?>
						</th>
						<th>
							<?php echo JHtml::_('grid.sort', 'COM_NENO_JOBS_TRANSLATION_METHOD', 'translation_method', $listDirection, $listOrder); ?>
						</th>
						<th>
							<?php echo JHtml::_('grid.sort', 'COM_NENO_JOBS_WORD_COUNT', 'word_count', $listDirection, $listOrder); ?>
						</th>
						<th>
							<?php echo JHtml::_('grid.sort', 'COM_NENO_JOBS_FUNDS', 'funds_needed', $listDirection, $listOrder); ?>
						</th>
						<th>
							<?php echo JHtml::_('grid.sort', 'COM_NENO_JOBS_CREATION_DATE', 'created_time', $listDirection, $listOrder); ?>
						</th>
						<th>
							<?php echo JHtml::_('grid.sort', 'COM_NENO_JOBS_ESTIMATED_COMPLETION', 'completion_time', $listDirection, $listOrder); ?>
						</th>
						<th></th>
					</tr>
					<?php /* @var $item stdClass */ ?>
					<?php foreach ($this->items as $item): ?>
						<tr class="row-string">
							<td class="cell-status">
								<?php echo $item->id; ?>
							</td>
							<td>
								<?php echo JText::_('COM_NENO_JOBS_STATUS_' . $item->state); ?>
							</td>
							<td>
								<?php echo JText::sprintf('COM_NENO_JOBS_STATUS_' . strtoupper($item->to_language), $item->to_language); ?>
							</td>
							<td>
								<?php echo JText::_($item->translation_method->name_constant); ?>
							</td>
							<td>
								<?php echo $item->word_count; ?>
							</td>
							<td>
								<?php echo JText::sprintf('COM_NENO_FUNDS_AMOUNT', $item->funds_needed); ?>
							</td>
							<td>
								<?php echo $item->created_time; ?>
							</td>
							<td>
								<?php echo $item->estimated_time; ?>
							</td>
							<td>
								<div class="btn-group">
									<?php if ($item->state != NenoJob::JOB_STATE_PROCESSED): ?>
										<a href="index.php?option=com_neno&task=jobs.resend&jobId=<?php echo $item->id; ?>&r=<?php echo mt_rand() / mt_getrandmax(); ?>"
										   class="btn btn-small"><?php echo JText::_('COM_NENO_JOBS_SEND_BUTTON'); ?></a>
									<?php endif; ?>
									<?php if ($item->state == NenoJob::JOB_STATE_COMPLETED || $item->state == NenoJob::JOB_STATE_PROCESSED): ?>
										<a
										  href="index.php?option=com_neno&task=jobs.fetch&jobId=<?php echo $item->id; ?>&r=<?php echo mt_rand() / mt_getrandmax(); ?>"
										  class="btn btn-small"><?php echo JText::_('COM_NENO_JOBS_FETCH_BUTTON'); ?></a>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
					<tr>
						<td colspan="9">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</form>
</div>
<?php echo NenoHelperBackend::renderVersionInfoBox(); ?>

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
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

// Include the CSS file
$version = NenoHelperBackend::getNenoVersion();
JHtml::stylesheet('media/neno/css/admin.css?v=' . $version);

// Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extraSidebar))
{
	$this->sidebar .= $this->extraSidebar;
}

$workingLanguage = NenoHelper::getWorkingLanguage();

?>

<style>
	ul.issue-list {
		margin: 20px auto;
		list-style: none;
	}
</style>

<div id="j-sidebar-container" class="span2">
	<?php echo $this->sidebar; ?>
</div>
<div id="j-main-container" class="span10">
	<h2><?php echo JText::_('COM_NENO_PENDING_ISSUES'); ?></h2>
	<ul id="pending-issues" class="issue-list">
		<?php if (is_array($this->pending)) : ?>
			<?php foreach ($this->pending as $issue) : ?>
				<?php echo NenoHelperIssue::renderIssue($issue); ?>
			<?php endforeach; ?>
		<?php else : ?>
			<?php echo $this->issues; ?>
		<?php endif; ?>
	</ul>

	<ul id="solved-issues" class="issue-list">
		<?php if (is_array($this->solved)) : ?>
			<?php foreach ($this->solved as $issue) : ?>
				<?php echo NenoHelperIssue::renderIssue($issue); ?>
			<?php endforeach; ?>
		<?php else : ?>
			<?php echo $this->issues; ?>
		<?php endif; ?>
	</ul>

	<a href="<?php echo JRoute::_('index.php?option=com_neno&view=debug'); ?>" class="btn btn-primary">
		<?php echo JText::_('COM_NENO_FIX_CONTENT_DONE'); ?>
	</a>
</div>

<?php echo NenoHelperBackend::renderVersionInfoBox(); ?>

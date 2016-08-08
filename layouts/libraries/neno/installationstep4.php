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

<style>
	#task-messages {
		height           : 200px;
		background-color : #f5f5f5;
		padding          : 20px;
		color            : #808080;
		overflow         : auto;
	}

	.log-level-2 {
		margin-left : 20px;
		font-weight : bold;
		margin-top  : 16px;
	}

	.log-level-3 {
		margin-left : 40px;
	}

	#proceed-button {
		margin-top : 15px;
	}
</style>

<div class="installation-step">
	<div class="installation-body span12">
		<div class="error-messages"></div>
		<div id="installation-wrapper">
			<h2><?php echo JText::_('COM_NENO_INSTALLATION_DISCOVERING_CONTENT_TITLE'); ?></h2>

			<div class="progress progress-striped active" id="progress-bar">
				<div class="bar"></div>
			</div>
			<p><?php echo JText::_('COM_NENO_INSTALLATION_DISCOVERING_CONTENT_MESSAGE'); ?></p>

			<div id="task-messages">

			</div>
		</div>
	</div>

	<?php echo JLayoutHelper::render('installationbottom', 4, JPATH_NENO_LAYOUTS); ?>
</div>

<script>
	jQuery.installation = false;
	interval = setInterval(checkStatus, 2000);
	sendDiscoveringStructureStep();

</script>

<div class="hidden">
	<!-- Different HTML to show depending on log level -->
	<div id="installation-status-1" class="alert"></div>
	<div id="installation-status-2" class="log-level-2"></div>
	<div id="installation-status-3" class="log-level-3"></div>
</div>

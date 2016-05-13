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

?>
<script>
	jQuery(document).ready(function () {
		selectDebugText();
		jQuery('#select-all-btn').off('click').on('click', selectDebugText);
	});

	function selectDebugText() {
		jQuery('#debug-text').select();
	}

</script>
<style>
	textarea {
		width       : 100%;
		font-family : monospace;
		white-space : pre;
		word-wrap   : normal;
		overflow-x  : scroll;
	}

	#copy-help-text {
		font-size  : 12px;
		color      : #9f9f9f;
		width      : 300px;
		text-align : right;
	}

	.debug-header {
		height : 60px;
	}
</style>

<div id="j-sidebar-container" class="span2">
	<?php echo $this->sidebar; ?>
</div>
<div id="j-main-container" class="span10">
	<div class="control-group">
		<div class="debug-header">
			<div class="pull-right">
				<div style="text-align:right;">
					<button class="btn btn-info" id="select-all-btn">Select all</button>
				</div>
				<div id="copy-help-text">Press CTRL+C to copy</div>
			</div>
			<h2>Debug report</h2>
		</div>

		<div class="controls">
			<textarea rows="40" id="debug-text"><?php echo NenoHelperBackend::printServerInformation(NenoHelperBackend::getServerInfo()); ?></textarea>
		</div>
	</div>
</div>

<form action="index.php?option=com_neno&view=debug" method="post" name="adminForm"
      id="adminForm">
	<input type="hidden" name="task" value="" />
</form>
<?php echo NenoHelperBackend::renderVersionInfoBox(); ?>

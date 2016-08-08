<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_modules
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$input     = JFactory::getApplication()->input;
$plugin    = $input->getCmd('plugin');
$plgrender = $input->getCmd('plgrender');

$formAction = JRoute::_("index.php?option=com_neno&view=plgrender&plugin=$plugin&plgrender=$plgrender");

?>

<script type="text/javascript">
	jQuery('.plgaction-btn').on('click', function () {
		jQuery('#plgaction').val(jQuery(this).data('plgaction'));
		jQuery('#task').val('plgrender.plgaction');

		jQuery('#plgrender-form').submit();
	});
</script>


<div id="j-sidebar-container" class="span2">
	<?php echo $this->sidebar; ?>
</div>
<div id="j-main-container" class="span10">
	<form
		action="<?php echo $formAction; ?>"
		method="post" enctype="multipart/form-data" name="adminForm" id="plgrender-form">
		<?php echo $this->view; ?>

		<input type="hidden" name="plgaction" value="" id="plgaction"/>
		<input type="hidden" name="task" value="" id="task"/>
	</form>
</div>

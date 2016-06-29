<?php
/**
 * @package    Neno
 *
 * @author     Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright  Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

?>
<scritp>

</scritp>
<div class="btn-wrapper pull-right">
	<button class="neno-no-button">
		<?php echo $displayData->button; ?>
	</button>
    <button onclick="location.href='<?php echo JRoute::_('index.php?option=com_neno&view=externaltranslations&r=' . NenoHelperBackend::generateRandomString()); ?>'" class="btn btn-primary">
		<span class="icon-cart"></span>
		<?php echo JText::_('COM_NENO_TRANSLATION_CREDIT_TOOLBAR_BUTTON'); ?>
	</button>

</div>
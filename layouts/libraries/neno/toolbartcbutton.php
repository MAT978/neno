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

<div class="btn-wrapper pull-right">
	<button class="neno-no-button">
		<?php echo $displayData->button; ?>
	</button>
    
    <a href="<?php echo JRoute::_('index.php?option=com_neno&view=externaltranslations'); ?>" class="btn btn-primary btn-tc"><?php echo JText::_('COM_NENO_TRANSLATION_CREDIT_TOOLBAR_BUTTON'); ?></a>
</div>
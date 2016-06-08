<?php
/**
 * @package     Neno
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

$displayData = (array) $displayData;

?>
<?php echo $displayData['message']; ?>
<a class="btn btn-link" href="index.php?option=com_neno&task=debug.listIssues&lang=<?php echo $displayData['language']; ?>">
	<?php echo JText::_('COM_NENO_FIX_IT_BUTTON_FIX_IT_TEXT'); ?>
</a>

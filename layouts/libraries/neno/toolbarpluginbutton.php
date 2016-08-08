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
<button class="btn btn-small plgaction-btn"
        data-plgaction="<?php echo $displayData->action; ?>">
	<span class="icon-<?php echo $displayData->icon; ?>"></span>
	<?php echo $displayData->title; ?>
</button>
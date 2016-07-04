<?php
/**
 * @package    Neno
 *
 * @author     Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright  Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

?>

<div class="alert alert-info" id="discover-alert">
	<?php echo JText::sprintf('COM_NENO_DISCOVER_LANGUAGE_MESSAGE', $displayData->tablesRemain); ?>

	<div class="progress progress-striped active">
		<div class="bar"
		     style="width: <?php echo(($displayData->tableDiscovered * 100) / $displayData->tableToBeDiscover); ?>%;"></div>
	</div>
</div>

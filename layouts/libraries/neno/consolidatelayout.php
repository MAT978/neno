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

<div>
	<input type="checkbox" class="consolidate-checkbox" value="<?php echo $displayData['translationId']; ?>" checked="checked" />
	<?php echo JText::sprintf(
		'COM_NENO_EDITOR_CONSOLIDATE_MESSAGE',
		$displayData['counter'],
		NenoHelper::html2text($displayData['originalText'], 200),
		NenoHelper::html2text($displayData['text'], 200)
	); ?>
</div>

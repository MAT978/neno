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

$step = $displayData;

?>
<tr class="filter-row">
	<td>
		<?php echo $displayData->fields; ?>
	</td>
	<?php if (!empty($displayData->specialFilter)) : ?>
		<td>&nbsp;</td>
		<td>
			<?php echo $displayData->specialFilter; ?>
		</td>
	<?php else : ?>
		<td>
			<?php echo $displayData->operators; ?>
		</td>
		<td>
			<input type="text" name="value[]" value="<?php echo $displayData->value; ?>" class="filter-value" />
		</td>
	<?php endif; ?>

	<td>
		<div class="btn-group">
			<button type="button" class="btn btn-primary btn-small add-row-button">
				<i class="icon-plus"></i>
			</button>
			<button type="button" class="btn btn-danger btn-small remove-row-button">
				<i class="icon-minus"></i>
			</button>
		</div>
	</td>
</tr>

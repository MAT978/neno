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

<table class="table" id="filters-table">
	<tr>
		<th><?php echo JText::_('COM_NENO_TABLE_FILTERS_FIELD_HEADER'); ?></th>
		<th><?php echo JText::_('COM_NENO_TABLE_FILTERS_COMPARAISON_OPERATOR_HEADER'); ?></th>
		<th><?php echo JText::_('COM_NENO_TABLE_FILTERS_VALUE_HEADER'); ?></th>
		<th></th>
	</tr>

	<?php if (empty($displayData->filters)): ?>
		<tr class="filter-row">
			<td><?php echo $displayData->fieldsSelect; ?></td>
			<td><?php echo $displayData->operatorsSelect; ?></td>
			<td><input type="text" name="value[]" value="" class="filter-value" /></td>
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
	<?php else: ?>
		<?php foreach ($displayData->filters as $filter) : ?>
			<tr class="filter-row">
				<td><?php echo JHtml::_('select.genericlist', $displayData->fields, 'fields[]', 'class="filter-field"', 'value', 'text', $filter['field']); ?></td>
				<td><?php echo JHtml::_('select.genericlist', $displayData->operators, 'operators[]', 'class="filter-operator"', 'value', 'text', $filter['operator']); ?></td>
				<td>
					<input type="text" name="value[]" value="<?php echo $filter['value']; ?>" class="filter-value" />
				</td>
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
		<?php endforeach; ?>
	<?php endif; ?>

</table>
<script>
	jQuery(document).ready(function(){
		jQuery('.filter-field').change(function() {
			debugger;
			var field = jQuery(this).find('option:selected').text();
			var div   = jQuery(this).parent().parent();
			var url   = '<?php echo JUri::base(); ?>index.php?option=com_neno&task=groupselements.generateFieldFilterOutput&table=<?php echo $displayData->tableId; ?>&field='+field;

			jQuery.get(url, function (data) {
				var p = div.children('td:first');
				div.children().remove();
				div.append(p);
				div.append(data);
			});
		});
	});
</script>
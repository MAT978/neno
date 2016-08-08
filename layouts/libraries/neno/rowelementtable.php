<?php
/**
 * @package     Neno
 * @subpackage  Helpers
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

if (empty($displayData['tables']) && empty($displayData['files'])): ?>

	<tr>
		<td></td>
		<td></td>
		<td>
			<a href="<?php echo JRoute::_('index.php?option=com_neno&task=groupselements.deleteGroup&id=' . $displayData['group']->id); ?>"
			   class="btn btn-mini"><?php echo JText::_('COM_NENO_GROUPS_ELEMENTS_DELETE_GROUP'); ?></a>
		</td>
	</tr>

<?php else: ?>

	<?php if (!empty($displayData['tables'])): ?>
		<?php foreach ($displayData['tables'] as $table): ?>

			<tr class="row-table" data-id="table-<?php echo $table->id; ?>"
			    data-parent="<?php echo $table->group->id; ?>">
				<td></td>
				<td class="<?php echo ($table->translate) ? 'toggler toggler-collapsed ' : '' ?>toggle-fields">
					<span class="<?php echo ($table->translate) ? 'icon-arrow-right-3' : '' ?>"></span>
				</td>
				<td class="cell-check"><input type="checkbox" name="tables[]" value="<?php echo $table->id; ?>"/></td>
				<td colspan="2"><?php echo $table->table_name; ?></td>
				<td class="type-icon"><span
						class="icon-grid-view-2"></span> <?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_TABLE'); ?>
				</td>
				<td class="translation-progress-bar">
					<?php echo NenoHelper::renderWordCountProgressBar($table->word_count, !empty($displayData['group']->assigned_translation_methods)); ?>
				</td>
				<td class="toggle-translate-table">
					<?php echo JLayoutHelper::render('translatetablewidget', $table, JPATH_NENO_LAYOUTS); ?>
				</td>
				<td>
					<a href="index.php?option=com_neno&task=groupelement.downloadContentElementFile&table_id=<?php echo $table->id; ?>"
					   class="btn btn-small">
						<span class="icon-download"></span>
						<?php echo JText::_('COM_NENO_GROUPELEMENT_DOWNLOAD_CE_FILE'); ?>
					</a>
				</td>
			</tr>

			<?php /* @var $field stdClass */ ?>
			<?php if (!empty($table->fields)): ?>
				<?php foreach ($table->fields as $field): ?>
					<tr class="row-field" data-parent="<?php echo $table->id; ?>"
					    data-grandparent="<?php echo $table->group->id; ?>" style="display:none;">
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td><?php echo $field->field_name ?></td>
						<td><?php echo strtoupper($field->field_type) ?></td>
						<td class="translation-progress-bar">
							<?php echo NenoHelper::renderWordCountProgressBar($field->word_count, !empty($displayData['group']->assigned_translation_methods) && $field->translate); ?>
						</td>
						<td class="toggle-translate">
							<fieldset id="check-toggle-translate-<?php echo $field->id; ?>"
							          class="radio btn-group btn-group-yesno" data-field="<?php echo $field->id; ?>">
								<input class="check-toggle-translate-radio" type="radio"
								       id="check-toggle-translate-<?php echo $field->id; ?>-1"
								       name="jform[check-toggle-translate]"
								       value="1" <?php echo ($field->translate) ? 'checked="checked"' : ''; ?>>
								<label for="check-toggle-translate-<?php echo $field->id; ?>-1"
								       class="btn btn-small <?php echo ($field->translate) ? 'active btn-success' : ''; ?>">Translate</label>
								<input class="check-toggle-translate-radio" type="radio"
								       id="check-toggle-translate-<?php echo $field->id; ?>-0"
								       name="jform[check-toggle-translate]"
								       value="0" <?php echo (!$field->translate) ? 'checked="checked"' : ''; ?>>
								<label for="check-toggle-translate-<?php echo $field->id; ?>-0"
								       class="btn btn-small <?php echo (!$field->translate) ? 'active btn-danger' : ''; ?>">Don't
									translate</label>
							</fieldset>
						</td>
						<td><?php echo NenoHelper::generateFilterDropDown($field->id, $field->filter); ?>
							<span class="icon-help" data-toggle="tooltip"
							      data-title="<?php echo NenoHelper::renderFilterHelperText(); ?>" data-html="true"
							      data-placement="bottom"></span></td>
						<td></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>

		<?php endforeach; ?>
	<?php endif; ?>

	<?php if (!empty($displayData['files'])): ?>
		<?php foreach ($displayData['files'] as $file): ?>

			<tr class="row-table" data-id="row-<?php echo $file->filename; ?>"
			    data-parent="<?php echo $displayData['group']->id; ?>">
				<td></td>
				<td></td>
				<td class="cell-check"><input type="checkbox" name="files[]" value="<?php echo $file->id; ?>"/></td>
				<td colspan="2" style="white-space: nowrap;"><?php echo $file->filename; ?></td>
				<td class="type-icon"><span
						class="icon-file-2"></span> <?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_FILE'); ?></td>
				<td class="translation-progress-bar">
					<?php echo NenoHelper::renderWordCountProgressBar($file->word_count, !empty($displayData['group']->assigned_translation_methods)); ?>
				</td>
				<td class="toggle-translate-file">
					<?php echo JLayoutHelper::render('translatefilewidget', $file, JPATH_NENO_LAYOUTS); ?>
				</td>
				<td></td>
			</tr>

		<?php endforeach; ?>
	<?php endif; ?>

<?php endif; ?>


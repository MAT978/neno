<?php
/**
 * @package     Neno
 * @subpackage  Views
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

JHtml::_('behavior.tooltip');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');
?>

<form action="<?php echo JRoute::_('index.php?option=com_neno&id=' . (int) $this->item->id); ?>"
      method="post" enctype="multipart/form-data" name="adminForm2" id="groupelement-form">
	<div class="row-fluid">
		<div class="span8 form-horizontal">
			<fieldset class="adminform">
				<?php echo $this->form->getInput('id'); ?>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('group_name'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('group_name'); ?></div>
				</div>
				<div class="control-group">
					<h4><?php echo JText::_('COM_NENO_GROUPELEMENT_GROUP_TRANSLATION_METHOD'); ?></h4>
					<?php echo JLayoutHelper::render('loadtranslationmethodselector', $this->item->id, JPATH_NENO_LAYOUTS); ?>
				</div>
				<?php if (!empty($this->languages)): ?>
					<div class="control-group">
						<h4><?php echo JText::_('COM_NENO_GROUPELEMENT_APPLY_OTHER_LANGUAGES'); ?></h4>
						<?php foreach ($this->languages as $language): ?>
							<div class="controls">
								<label class="checkbox">
									<input type="checkbox" name="jform[languages][]"
                                           value="<?php echo $language->lang_code; ?>" checked="checked"> <?php echo $language->title; ?>
								</label>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif ?>
			</fieldset>
		</div>
	</div>
	<input type="hidden" name="task" value="groupelement.save"/>
	<?php echo JHtml::_('form.token'); ?>
</form>

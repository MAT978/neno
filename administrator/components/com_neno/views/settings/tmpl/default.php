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

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.keepalive');

// Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extraSidebar))
{
	$this->sidebar .= $this->extraSidebar;
}

$user   = JFactory::getUser();
$userId = $user->get('id');

$options = array();

foreach ($this->items as $item)
{
	$options[$item->setting_key] = $item;
}
?>

    <style>
        .settings-tooltip {
            font-size: 0.75em;
            font-weight: bold;
            vertical-align: super;
            cursor: pointer;
        }

        td.setting-label {
            width: 40% !important;
        }

        table td {
            border: none !important;
        }
    </style>

    <script>
        jQuery(document).ready(function () {
            var options = {
                html: true,
                placement: "right"
            };
            jQuery('.settings-tooltip').tooltip(options);
        });
    </script>

    <form action="<?php echo JRoute::_('index.php?option=com_neno&view=settings'); ?>" method="post" name="adminForm"
          id="adminForm">
        <div id="j-sidebar-container" class="span2">
			<?php echo $this->sidebar; ?>
        </div>
        <div id="j-main-container" class="span10">
            <h2><?php echo JText::_('COM_NENO_SETTINGS_GENERAL'); ?></h2>
            <table class="table full-width" id="typeListGeneral">
                <tr>
					<?php $item = $options['license_code']; ?>
                    <td class='left setting-label'>
						<?php echo JText::_('COM_NENO_SETTINGS_SETTING_NAME_' . strtoupper($item->setting_key)); ?>
                        <span class="settings-tooltip" data-toggle="tooltip"
                              title='<?php echo JText::_('COM_NENO_SETTINGS_SETTING_INFO_' . strtoupper($item->setting_key)); ?>'>[?]</span>
                    </td>
                    <td class=''>
					<textarea name="jform[<?php echo $item->setting_key; ?>]"
                              class="input-setting input-xxlarge"><?php echo $item->setting_value; ?></textarea>
                    </td>
                </tr>
                <tr>
					<?php $item = $options['translation_method_1']; ?>
                    <td class='setting-label'>
						<?php echo JText::_('COM_NENO_SETTINGS_SETTING_NAME_' . strtoupper($item->setting_key)); ?>
                    </td>
                    <td class=''>
						<?php echo $item->dropdown; ?>
                    </td>
                </tr>
                <tr>
					<?php $item = $options['translator']; ?>
                    <td class='setting-label'>
						<?php echo JText::_('COM_NENO_SETTINGS_SETTING_NAME_' . strtoupper($item->setting_key)); ?>
                        <span class="settings-tooltip" data-toggle="tooltip"
                              title='<?php echo JText::_('COM_NENO_SETTINGS_SETTING_INFO_' . strtoupper($item->setting_key)); ?>'>[?]</span>
                    </td>
                    <td class=''>
						<?php echo $item->dropdown; ?>
                    </td>
                </tr>
                <tr>
					<?php $item = $options['translator_api_key']; ?>
                    <td class='setting-label'>
						<?php echo JText::_('COM_NENO_SETTINGS_SETTING_NAME_' . strtoupper($item->setting_key)); ?>
                    </td>
                    <td class=''>
                        <input type="text" name="jform[<?php echo $item->setting_key; ?>]"
                               class="input-setting input-xxlarge"
                               value="<?php echo $item->setting_value; ?>"/>
                        <br/><a
                                href="https://www.neno-translate.com/en/help/documentation/frequently-asked-questions/installation-and-upgrade/16-how-to-get-a-google-or-yandex-api-key"
                                target="_blank"><?php echo JText::_('COM_NENO_SETTINGS_SETTING_NAME_API_KEY_DOCS_LINK'); ?></a>
                    </td>
                </tr>
                <tr>
					<?php $item = $options['copy_unpublished']; ?>
                    <td class='setting-label'>
						<?php echo JText::_('COM_NENO_SETTINGS_SETTING_NAME_' . strtoupper($item->setting_key)); ?>
                    </td>
                    <td class=''>
                        <fieldset id="<?php echo $item->setting_key; ?>" class="radio btn-group btn-group-yesno">
                            <input type="radio" id="<?php echo $item->setting_key; ?>0"
                                   name="jform[<?php echo $item->setting_key; ?>]" value="1"
								<?php echo ($item->setting_value) ? 'checked="checked"' : ''; ?>>
                            <label for="<?php echo $item->setting_key; ?>0" class="btn">
								<?php echo JText::_('JYES'); ?>
                            </label>
                            <input type="radio" id="<?php echo $item->setting_key; ?>1"
                                   name="jform[<?php echo $item->setting_key; ?>]" value="0"
								<?php echo ($item->setting_value) ? '' : 'checked="checked"'; ?>>
                            <label for="<?php echo $item->setting_key; ?>1" class="btn">
								<?php echo JText::_('JNO'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
					<?php $item = $options['copy_trashed']; ?>
                    <td class='setting-label'>
						<?php echo JText::_('COM_NENO_SETTINGS_SETTING_NAME_' . strtoupper($item->setting_key)); ?>
                    </td>
                    <td class=''>
                        <fieldset id="<?php echo $item->setting_key; ?>" class="radio btn-group btn-group-yesno">
                            <input type="radio" id="<?php echo $item->setting_key; ?>0"
                                   name="jform[<?php echo $item->setting_key; ?>]" value="1"
								<?php echo ($item->setting_value) ? 'checked="checked"' : ''; ?>>
                            <label for="<?php echo $item->setting_key; ?>0" class="btn">
								<?php echo JText::_('JYES'); ?>
                            </label>
                            <input type="radio" id="<?php echo $item->setting_key; ?>1"
                                   name="jform[<?php echo $item->setting_key; ?>]" value="0"
								<?php echo ($item->setting_value) ? '' : 'checked="checked"'; ?>>
                            <label for="<?php echo $item->setting_key; ?>1" class="btn">
								<?php echo JText::_('JNO'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
					<?php $item = $options['only_prefix']; ?>
                    <td class='setting-label'>
						<?php echo JText::sprintf('COM_NENO_SETTINGS_SETTING_NAME_' . strtoupper($item->setting_key), JFactory::getDbo()->getPrefix()); ?>
                    </td>
                    <td class=''>
                        <fieldset id="<?php echo $item->setting_key; ?>" class="radio btn-group btn-group-yesno">
                            <input type="radio" id="<?php echo $item->setting_key; ?>0"
                                   name="jform[<?php echo $item->setting_key; ?>]" value="1"
								<?php echo ($item->setting_value) ? 'checked="checked"' : ''; ?>>
                            <label for="<?php echo $item->setting_key; ?>0" class="btn">
								<?php echo JText::_('JYES'); ?>
                            </label>
                            <input type="radio" id="<?php echo $item->setting_key; ?>1"
                                   name="jform[<?php echo $item->setting_key; ?>]" value="0"
								<?php echo ($item->setting_value) ? '' : 'checked="checked"'; ?>>
                            <label for="<?php echo $item->setting_key; ?>1" class="btn">
								<?php echo JText::_('JNO'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
					<?php $item = $options['save_history']; ?>
                    <td class='setting-label'>
						<?php echo JText::sprintf('COM_NENO_SETTINGS_SETTING_NAME_' . strtoupper($item->setting_key), JFactory::getDbo()->getPrefix()); ?>
                    </td>
                    <td class=''>
                        <fieldset id="<?php echo $item->setting_key; ?>" class="radio btn-group btn-group-yesno">
                            <input type="radio" id="<?php echo $item->setting_key; ?>0"
                                   name="jform[<?php echo $item->setting_key; ?>]" value="1"
								<?php echo ($item->setting_value) ? 'checked="checked"' : ''; ?>>
                            <label for="<?php echo $item->setting_key; ?>0" class="btn">
								<?php echo JText::_('JYES'); ?>
                            </label>
                            <input type="radio" id="<?php echo $item->setting_key; ?>1"
                                   name="jform[<?php echo $item->setting_key; ?>]" value="0"
								<?php echo ($item->setting_value) ? '' : 'checked="checked"'; ?>>
                            <label for="<?php echo $item->setting_key; ?>1" class="btn">
								<?php echo JText::_('JNO'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
					<?php $item = $options['log_limit']; ?>
                    <td class='setting-label'>
						<?php echo JText::sprintf('COM_NENO_SETTINGS_SETTING_NAME_' . strtoupper($item->setting_key)); ?>
                    </td>
                    <td class=''>
                        <input type="text" name="jform[<?php echo $item->setting_key; ?>]"
                               class="input-setting input-xxlarge"
                               value="<?php echo $item->setting_value; ?>"/>
                    </td>
                </tr>
            </table>
            <h2><?php echo JText::_('COM_NENO_SETTINGS_TRANSLATE'); ?></h2>
            <table class="table full-width" id="typeListTranslate">
                <tr>
					<?php $item = $options['hide_empty_strings']; ?>
                    <td class='left setting-label'>
						<?php echo JText::_('COM_NENO_SETTINGS_SETTING_NAME_' . strtoupper($item->setting_key)); ?>
                    </td>
                    <td class=''>
                        <fieldset id="<?php echo $item->setting_key; ?>" class="radio btn-group btn-group-yesno">
                            <input type="radio" id="<?php echo $item->setting_key; ?>0"
                                   name="jform[<?php echo $item->setting_key; ?>]" value="1"
								<?php echo ($item->setting_value) ? 'checked="checked"' : ''; ?>>
                            <label for="<?php echo $item->setting_key; ?>0" class="btn">
								<?php echo JText::_('JYES'); ?>
                            </label>
                            <input type="radio" id="<?php echo $item->setting_key; ?>1"
                                   name="jform[<?php echo $item->setting_key; ?>]" value="0"
								<?php echo ($item->setting_value) ? '' : 'checked="checked"'; ?>>
                            <label for="<?php echo $item->setting_key; ?>1" class="btn">
								<?php echo JText::_('JNO'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
					<?php $item = $options['load_related_content']; ?>
                    <td class='left setting-label'>
						<?php echo JText::_('COM_NENO_SETTINGS_SETTING_NAME_' . strtoupper($item->setting_key)); ?>
                    </td>
                    <td class=''>
                        <fieldset id="<?php echo $item->setting_key; ?>" class="radio btn-group btn-group-yesno">
                            <input type="radio" id="<?php echo $item->setting_key; ?>0"
                                   name="jform[<?php echo $item->setting_key; ?>]" value="1"
								<?php echo ($item->setting_value) ? 'checked="checked"' : ''; ?>>
                            <label for="<?php echo $item->setting_key; ?>0" class="btn">
								<?php echo JText::_('JYES'); ?>
                            </label>
                            <input type="radio" id="<?php echo $item->setting_key; ?>1"
                                   name="jform[<?php echo $item->setting_key; ?>]" value="0"
								<?php echo ($item->setting_value) ? '' : 'checked="checked"'; ?>>
                            <label for="<?php echo $item->setting_key; ?>1" class="btn">
								<?php echo JText::_('JNO'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
					<?php $item = $options['default_translate_action']; ?>
                    <td class='left setting-label'>
						<?php echo JText::_('COM_NENO_SETTINGS_SETTING_NAME_' . strtoupper($item->setting_key)); ?>
                    </td>
                    <td class=''>
						<?php echo $item->dropdown; ?>
                    </td>
                </tr>
            </table>
            <br/>

            <h2><?php echo JText::_('COM_NENO_SETTINGS_SCHEDULED'); ?></h2>
            <table class="table full-width" id="typeListScheduled">
                <tr>
					<?php $item = $options['schedule_task_option']; ?>
                    <td class='setting-label'>
						<?php echo JText::_('COM_NENO_SETTINGS_SETTING_NAME_' . strtoupper($item->setting_key)); ?>
                    </td>
                    <td class=''>
						<?php echo $item->dropdown; ?>
                    </td>
                </tr>
                <tr>
					<?php $item = $options['translate_automatically_professional']; ?>
                    <td class='left setting-label'>
						<?php echo JText::_('COM_NENO_SETTINGS_SETTING_NAME_' . strtoupper($item->setting_key)); ?>
                    </td>
                    <td class=''>
                        <fieldset id="<?php echo $item->setting_key; ?>" class="radio btn-group btn-group-yesno">
                            <input type="radio" id="<?php echo $item->setting_key; ?>0"
                                   name="jform[<?php echo $item->setting_key; ?>]" value="1"
								<?php echo ($item->setting_value) ? 'checked="checked"' : ''; ?>>
                            <label for="<?php echo $item->setting_key; ?>0" class="btn">
								<?php echo JText::_('JYES'); ?>
                            </label>
                            <input type="radio" id="<?php echo $item->setting_key; ?>1"
                                   name="jform[<?php echo $item->setting_key; ?>]" value="0"
								<?php echo ($item->setting_value) ? '' : 'checked="checked"'; ?>>
                            <label for="<?php echo $item->setting_key; ?>1" class="btn">
								<?php echo JText::_('JNO'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>

            <input type="hidden" name="task" value=""/>
            <input type="hidden" name="boxchecked" value="0"/>
			<?php echo JHtml::_('form.token'); ?>

        </div>
    </form>

    <div class="modal hide fade" id="saved-modal">
        <div class="modal-body">
            <h3><?php echo JText::_('COM_NENO_SETTINGS_SAVED'); ?></h3>
        </div>
    </div>

<?php echo NenoHelperBackend::renderVersionInfoBox(); ?>
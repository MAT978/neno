<?php
/**
 * @package    Neno
 *
 * @author     Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright  Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

//No direct access
defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');

?>

<div class="installation-step">
    <div class="installation-body span12">

        <div class="error-messages"></div>
        <h2><?php echo JText::_('COM_NENO_INSTALLATION_DEFAULT_SETTINGS_TITLE'); ?></h2>

        <div class="span6 default-method-selectors">
            <p><?php echo JText::_('COM_NENO_INSTALLATION_DEFAULT_SETTINGS_MESSAGE'); ?></p>

            <div id="translation-method-selectors">
                <?php $displayData = array (); ?>
                <?php $displayData['n'] = 0; ?>
                <?php $displayData['assigned_translation_methods'] = NenoHelper::getDefaultTranslationMethods(); ?>
                <?php $displayData['translation_methods'] = NenoHelper::getTranslationMethods('dropdown'); ?>
                <?php echo JLayoutHelper::render('libraries.neno.translationmethodselector', $displayData); ?>
            </div>
        </div>
        <div class="span6 doc">
            <p><?php echo JText::_('COM_NENO_INSTALLATION_DEFAULT_SETTINGS_DESCRIPTION_TEXT_P1'); ?></p>

            <p><?php echo JText::_('COM_NENO_INSTALLATION_DEFAULT_SETTINGS_DESCRIPTION_TEXT_P2'); ?></p>

            <p><?php echo JText::_('COM_NENO_INSTALLATION_DEFAULT_SETTINGS_DESCRIPTION_TEXT_P3'); ?></p>

            <h3><?php echo JText::_('COM_NENO_INSTALLATION_DEFAULT_SETTINGS_DESCRIPTION_TEXT_MANUAL_TRANSLATION_TITLE'); ?></h3>

            <p><?php echo JText::_('COM_NENO_INSTALLATION_DEFAULT_SETTINGS_DESCRIPTION_TEXT_MANUAL_TRANSLATION_MESSAGE'); ?></p>

            <h3><?php echo JText::_('COM_NENO_INSTALLATION_DEFAULT_SETTINGS_DESCRIPTION_TEXT_PROFESSIONAL_TRANSLATION_TITLE'); ?></h3>

            <p><?php echo JText::_('COM_NENO_INSTALLATION_DEFAULT_SETTINGS_DESCRIPTION_TEXT_PROFESSIONAL_TRANSLATION_MESSAGE'); ?>
                <a href="https://www.neno-translate.com/help/documentation/translating/19-professional-translation"
                   target="_blank"><?php echo JText::_('COM_NENO_INSTALLATION_DEFAULT_SETTINGS_LINK_LEARN_MORE'); ?></a>
            </p>
        </div>
        <div class="span12">
            <button type="button" class="btn btn-success next-step-button">
                <?php echo JText::_('COM_NENO_INSTALLATION_NEXT'); ?>
            </button>
            <img src="<?php echo JUri::root(); ?>/media/neno/images/loading_mini.gif" class="hide loading-spin"/>
        </div>
    </div>

    <?php echo JLayoutHelper::render('libraries.neno.installationbottom', 2); ?>
</div>

<script>
    loadMissingTranslationMethodSelectors('#translation-method-selectors', 'general');
</script>

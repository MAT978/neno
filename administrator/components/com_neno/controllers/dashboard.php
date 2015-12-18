<?php

/**
 * @package     Neno
 * @subpackage  Controllers
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;

/**
 * Manifest Groups & Elements controller class
 *
 * @since  1.0
 */
class NenoControllerDashboard extends JControllerAdmin {

    /**
     * Toggle language
     *
     * @return void
     */
    public function toggleLanguage() {
        $input = $this->input;
        $language = $input->getString('language');
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query
                ->update('#__languages')
                ->set('published = (published + 1) % 2')
                ->where('lang_code = ' . $db->quote($language));

        $db->setQuery($query);
        $db->execute();

        JFactory::getApplication()->close();
    }

    /**
     * Task to delete a language
     *
     * @return void
     */
    public function deleteLanguage() {
        $input = $this->input;
        $language = $input->getString('language');
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query
                ->select('*')
                ->update('#__languages')
                ->where('lang_code = ' . $db->quote($language));

        $db->setQuery($query);
        $languageData = $db->loadAssoc();

        $languageErrors = NenoHelper::getLanguageErrors($languageData);

        // Only execute this task if the language is error free
        if (empty($languageErrors)) {
            NenoHelper::deleteLanguage($language);
        }
    }

    /**
     * Task to get a confirmation language
     *
     * @return void
     */
    public function confirmationMessageForLanguageDeletion() {
        $input = $this->input;
        $language = $input->getString('language');
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query
                ->select('*')
                ->update('#__languages')
                ->where('lang_code = ' . $db->quote($language));

        $db->setQuery($query);
        $languageData = $db->loadAssoc();

        $languageErrors = NenoHelper::getLanguageErrors($languageData);
        $displayData = new stdClass;
        $displayData->error = false;
        $displayData->messages = $languageErrors;

        if (empty($languageErrors)) {
            // Calculate statistics
            $query
                    ->clear()
                    ->select('COUNT(*)')
                    ->from('#__neno_content_element_translations')
                    ->where('language = ' . $db->quote($language));

            $db->setQuery($query);
            $counter = $db->loadResult();

            $displayData->messages[] = JText::sprintf('COM_NENO_DASHBOARD_DELETE_LANGUAGE', $counter);
        } else {
            $displayData->error = true;
        }

        echo JLayoutHelper::render('messages', $displayData, JPATH_NENO_LAYOUTS);
    }

    /**
     * Publish language switcher
     *
     * @throws Exception
     *
     * @return void
     */
    public function publishSwitcher() {
        $input = $this->input;
        $jform = $input->post->get('jform', array(), 'ARRAY');
        $placement = $input->getWord('placement');

        if (!empty($jform)) {
            /* @var $model NenoModelDashboard */
            $model = $this->getModel('Dashboard', 'NenoModel');
            $db = JFactory::getDbo();
            $query = $db->getQuery(true);

            if ($model->isSwitcherPublished(false)) {
                $query
                        ->select('*')
                        ->from('#__modules')
                        ->where('module = ' . $db->quote('mod_languages'));
                $db->setQuery($query);
                $module = $db->loadObject();
            } else {
                $module = new stdClass;
                $module->id = 0;
                $module->language = '*';
                $module->published = 1;
                $module->title = 'Language Switcher';
                $module->module = 'mod_languages';
                $module->access = '1';
                $module->client_id = 0;

                $db->insertObject('#__modules', $module, 'id');
            }

            $module->position = $jform['position'];
            $module->published = 1;
            $db->updateObject('#__modules', $module, 'id');

            // Assigning items
            $query
                    ->clear()
                    ->insert('#__modules_menu')
                    ->columns(
                            array(
                                'menuid',
                                'moduleid'
                            )
                    )
                    ->values('0, ' . $module->id);
            $db->setQuery($query);
            $db->execute();
        }

        if ($placement == 'module') {
            JFactory::getApplication()->redirect('index.php');
        } else {
            JFactory::getApplication()->redirect('index.php?option=com_neno&dashboard');
        }
    }

    /**
     * Publish language switcher
     *
     * @throws Exception
     *
     * @return void
     */
    public function doNotShowWarningMessage() {
        $input = $this->input;
        $placement = $input->getWord('placement');
        
        NenoSettings::set('show_language_switcher_warning', 0);
        
        if ($placement == 'module') {
                    JFactory::getApplication()->redirect('index.php');
                } else {
                    JFactory::getApplication()->redirect('index.php?option=com_neno&dashboard');
                }
     
    }

}

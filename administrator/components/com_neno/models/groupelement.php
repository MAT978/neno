<?php
/**
 * @package     Neno
 * @subpackage  Models
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * NenoModelGroupsElements class
 *
 * @since  1.0
 */
class NenoModelGroupElement extends JModelAdmin
{
    /**
     * Get the JTable class related to this view
     *
     * @param   string  $type    JTable type
     * @param   string  $prefix  JTable prefix
     * @param   array   $config  JTable configuration
     *
     * @return mixed
     */
    public function getTable($type = 'GroupElement', $prefix = 'NenoTable', $config = array ())
    {
        return JTable::getInstance($type, $prefix, $config);
    }

    /**
     * Method to get the record form.
     *
     * @param   array    $data      An optional array of data for the form to interrogate.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return JForm
     */
    public function getForm($data = array (), $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_neno.groupelement', 'groupelement', array ('control' => 'jform', 'load_data' => $loadData));

        if (empty($form))
        {
            return false;
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     *
     * @param   array  $data  Data to save
     *
     * @return bool
     *
     * @throws Exception
     */
    public function save($data)
    {
        // If groups data has been saved, let's assign translation method
        if (parent::save($data))
        {
            $db              = JFactory::getDbo();
            $query           = $db->getQuery(true);
            $workingLanguage = NenoHelper::getWorkingLanguage();
            $groupId         = (int) $this->getState($this->getName() . '.id');

            $query
                ->delete('#__neno_content_element_groups_x_translation_methods')
                ->where(
                    array (
                        'group_id = ' . $groupId,
                    )
                );

            $languages = array ($workingLanguage);

            if (!empty($data['languages']))
            {
                $languages = array_merge($data['languages'], $languages);
            }

            $query->where('lang IN (' . implode(',', $db->quote($languages)) . ')');

            $db->setQuery($query);
            $db->execute();

            $query
                ->clear()
                ->insert('#__neno_content_element_groups_x_translation_methods')
                ->columns(
                    array (
                        'group_id',
                        'lang',
                        'translation_method_id',
                        'ordering'
                    )
                );

            $insert = false;

            if (!empty($data['translation_methods']))
            {
                $ordering = 1;

                foreach ($data['translation_methods'] as $translationMethodId)
                {
                    if (!empty($translationMethodId))
                    {
                        $insert = true;

                        foreach ($languages as $language)
                        {
                            $query->values($groupId . ',' . $db->quote($language) . ',' . $db->quote($translationMethodId) . ', ' . $ordering);
                        }

                        $ordering++;
                    }
                }
            }

            if ($insert)
            {
                $db->setQuery($query);
                $db->execute();
            }

            NenoHelperBackend::consolidateTranslationMethods($groupId, $languages, empty($data['translation_methods']) || $data['translation_methods'][0] == 0);

            return true;
        }

        return false;
    }

    /**
     * Get languages
     *
     * @return array
     */
    public function getLanguages()
    {
        $languages       = NenoHelper::getLanguages(false);
        $workingLanguage = NenoHelper::getWorkingLanguage();
        $defaultLanguage = NenoSettings::get('source_language');

        foreach ($languages as $key => $language)
        {
            if ($language->lang_code == $workingLanguage || $language->lang_code == $defaultLanguage)
            {
                unset($languages[$key]);
            }
        }

        return $languages;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return mixed
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = JFactory::getApplication()->getUserState('com_neno.edit.groupelement.data', array ());

        if (empty($data))
        {
            $data = $this->getItem();

        }

        return $data;
    }

    /**
     * Load item
     *
     * @param null|mixed $pk Pk data
     *
     * @return JObject
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem();

        if (!empty($item->id))
        {
            /* @var $group NenoContentElementGroup */
            $group = NenoContentElementGroup::load($item->id);
            $item  = $group->prepareDataForView();
        }

        return $item;
    }
}

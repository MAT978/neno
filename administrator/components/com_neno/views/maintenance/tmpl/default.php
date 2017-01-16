<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_modules
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;


?>

<script type="text/javascript">
    jQuery(document).on('ready', function () {
        jQuery.get('<?php echo JRoute::_('index.php?option=com_neno&task=maintenance.doMaintenance', false); ?>', function (data) {
            if (data == 'ok') {
                document.location = '<?php echo JRoute::_('index.php?option=com_neno'); ?>'
            }
        });
    });
</script>


<div class="span12">
    <div class="text-center">
        <h3><?php echo JText::_('COM_NENO_MAINTENANCE_MESSAGE'); ?></h3>
        <img src="<?php echo JUri::root() . '/media/jui/img/ajax-loader.gif'; ?>"/>
    </div>
</div>
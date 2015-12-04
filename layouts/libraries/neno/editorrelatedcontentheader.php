<?php
/**
 * @package     Neno
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
$loadRelatedContent = $displayData;
?>

<script>
    jQuery(document).ready(function(){
        jQuery('#load-related-btn-toggle').on('click', function(){
        jQuery.get(
            'index.php?option=com_neno&task=editor.toggleShowRelated',
            function () {
                location.reload();
            }
        );            
        });
    });    
</script>

<?php if ($loadRelatedContent): ?>
    <h2 id="related-content-header"><?php echo JText::_('COM_NENO_VIEW_EDITOR_RELATED_CONTENT'); ?> &nbsp; <button class="btn btn-small btn-default" id="load-related-btn-toggle"><span class="icon-eye-close"></span> <?php echo JText::_('COM_NENO_VIEW_EDITOR_RELATED_CONTENT_BTN_HIDE'); ?></button></h2>
<?php else: ?>
    <div class="alert alert-info" id="related-content-header"><h4><?php echo JText::_('COM_NENO_VIEW_EDITOR_RELATED_CONTENT_FOUND'); ?> &nbsp; <button class="btn btn-small btn-info" id="load-related-btn-toggle"><span class="icon-refresh"></span> <?php echo JText::_('COM_NENO_VIEW_EDITOR_RELATED_CONTENT_BTN_LOAD'); ?></button></h4></div>
<?php endif; ?>

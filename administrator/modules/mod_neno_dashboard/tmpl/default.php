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

// Include the CSS file
$version = NenoHelperBackend::getNenoVersion();
JHtml::stylesheet('media/neno/css/admin.css?v=' . $version);
?>

<script type="text/javascript">

	jQuery(document).ready(bindEvents);

	function isLangReady(lang) {
		jQuery.ajax({
			url: 'index.php?option=com_neno&task=dashboard.toggleLanguage&language=' + lang,
			complete: function (data) {
				setTimeout(isLangReady, 5000);
			}
		});
	}

	function bindEvents() {
		jQuery('.not-ready').off('click').on('click', function (e) {
			e.preventDefault();
			alert('<?php echo JText::_('COM_NENO_LANGUAGE_IS_NOT_READY_YET_MESSAGE', true); ?>');
		});
	}

</script>

<div class="languages-holder">
	<?php foreach ($languageData as $item): ?>
		<?php $item->placement = 'module'; ?>
		<?php echo JLayoutHelper::render('languageconfiguration', $item, JPATH_NENO_LAYOUTS); ?>
	<?php endforeach; ?>
</div>


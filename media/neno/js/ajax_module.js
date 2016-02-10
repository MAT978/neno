jQuery(document).ready(function () {

	function processTaskQueue() {
		jQuery.ajax({
			url: 'index.php?option=com_neno&task=processTaskQueue',
			complete: function () {
				setTimeout(processTaskQueue, 1000);
			}
		});
	}

	setTimeout(processTaskQueue, 10000);
});
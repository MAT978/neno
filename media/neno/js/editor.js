/**
 * Initialize the editor interface
 */
function initEditor() {

	// Setup button bindings
	bindEditorButtons();

	// Bind keyboard shortcuts
	bindEditorKeyboardShortcuts();

	// Initialize tooltips
	jQuery('.modal-tooltip').tooltip();

	// Use codemirror editor
	initCodemirror();

	// Highlight the left editor to show content was loaded
	highlightBox('.lefteditor-wrapper .CodeMirror');

	// Show last modified date on focus and hover
	showInfoOnHover();

	// Perform a default action when a string is loaded
	// This could be to copy or to copy and translate
	doDefaultAction();

	// Load the note to translators area
	loadNotesToTranslators();

	// Ensure users do not leave without saving
	bindTranslationModificationCheck();

}

// Bind the main buttons in the editor as well as the copy and translate links on each element
function bindEditorButtons() {

	jQuery('#copy-btn').off('click').on('click', copyAll);
	jQuery('#translate-btn').off('click').on('click', translateAll);
	jQuery('#skip-button').off('click').on('click', loadNextTranslation);
	jQuery('#draft-button').off('click').on('click', saveAllAsDraft);
	jQuery('#save-next-button').off('click').on('click', saveAllTranslationsAndNext);
	jQuery('#save-api-key-btn').off('click').on('click', saveAPIKeyModal);
	jQuery('.dont-translate').off().on('click', setFieldAsDoNotTranslate);

	// Bind copy and translate links on fields
	jQuery('.copy-string-link').off().on('click', copy);
	jQuery('.translate-string-link').off().on('click', translate);


}


/**
 * Use the machine translation API to translate the content
 */
function translate() {

	// If we are not ready to translate then show the modal asking for the API key instead
	if (ready_for_api_translation === false) {
		jQuery('#translatorKeyModal').modal('show');
		return false;
	}

	var translation_id = getTranslationId(jQuery(this));
	var left_content = editors[translation_id].left.getValue();

	// If there is no default action set yet then ask if the user would like to set this as the default action
	if (default_editor_load_action == '') {
		askToSetDefaultAction('translate');
	}

	//Skip if empty string
	if (left_content === '') return;

	//Show translating text
	editors[translation_id].right.setValue('Translating...');

	// Ajax
	jQuery.ajax({
		type    : 'POST',
		url     : 'index.php?option=com_neno&task=editor.translate',
		dataType: "json",
		data    : {
			text: left_content
		},
		success : function (data) {

			//Highlight the right editor to show content was loaded
			highlightBox('.main-translation-div[data-translation-id="' + translation_id + '"] .righteditor-wrapper .CodeMirror');

			//Add content to the editor
			editors[translation_id].right.setValue(data.text);

			//Show error if one occured
			if (data.status === "err") {
				jQuery('.translated-error .error-message').html(data.error);
				jQuery('.translated-error').show();
			}

			//Show what API was used for translation
			jQuery('[data-translation-id="' + translation_id + '"] .translated-by').show();
		}
	});
}

/**
 * Translate all fields
 *  - Simply simulate a click on all links
 */
function translateAll() {
	jQuery('.translate-string-link').click();
}


/**
 * Copy a string from original to translation area
 */
function copy() {

	var translation_id = getTranslationId(jQuery(this));
	var left_content = editors[translation_id].left.getValue();

	// If there is no default action set yet then ask if the user would like to set this as the default action
	if (default_editor_load_action == '') {
		askToSetDefaultAction('copy');
	}

	// Skip if empty string
	if (left_content === '') return;

	editors[translation_id].right.setValue(left_content);
	highlightBox('.main-translation-div[data-translation-id="' + translation_id + '"] .righteditor-wrapper .CodeMirror');
	return true;
}

/**
 * Copy all fields
 *  - Simply simulate a click on all links
 */
function copyAll() {
	jQuery('.copy-string-link').click();
}


/**
 * Ask the user if he would like to set the action as the default action
 * 0: No action
 * 1: Copy
 * 2: Translate
 * @param action integer
 */
function askToSetDefaultAction(action) {

	var action_string = JTEXT_COM_NENO_EDITOR_SET_DEFAULT_ACTION[action];
	var question = JTEXT_COM_NENO_EDITOR_SET_DEFAULT_ACTION_CONFIRM.replace('%s', action_string);
	default_editor_load_action = '0';
	if (confirm(question)) {
		if (action === 'copy') {
			default_editor_load_action = '1';
		} else if (action === 'translate') {
			default_editor_load_action = '2';
		}
	}

	// Save the default action answer
	jQuery.ajax('index.php?option=com_neno&task=editor.saveDefaultAction&action=' + default_editor_load_action);


}


/**
 * Initialize the CodeMirror editor
 * @returns {undefined}
 */
function initCodemirror() {

	//Use a global object that can be accesed later
	editors = {};

	jQuery('.main-translation-div').each(function () {
		addExtensions();
		var translation_id = getTranslationId(jQuery(this));
		var modeLeft = 'htmlmixed';
		var modeRight = 'htmlmixed';
		var originalTextarea = jQuery('#original-content-' + translation_id);
		var editorTextarea = jQuery('#translated-content-' + translation_id);

		// If the string is JSON, let's specify the specific mode for JSON
		if (isJSON(originalTextarea.val())) {
			modeLeft = 'application/ld+json';

			originalTextarea.val(prettyJSON(originalTextarea.val()));
		}

		// If the string is JSON, let's specify the specific mode for JSON
		if (isJSON(editorTextarea.val())) {
			modeRight = 'application/ld+json';

			editorTextarea.val(prettyJSON(editorTextarea.val()));
		}

		var editor = {};
		editor.translation_id = translation_id;
		editor.left = CodeMirror.fromTextArea(document.getElementById("original-content-" + translation_id), {
			mode        : modeLeft,
			readOnly    : true,
			lineWrapping: true,
			matchTags   : {bothTags: true}
		});
		editor.right = CodeMirror.fromTextArea(document.getElementById("translated-content-" + translation_id), {
			mode          : modeRight,
			lineWrapping  : true,
			viewportMargin: 200,
			matchTags     : {bothTags: true},
			readOnly      : typeof editorTextarea.attr('readonly') !== typeof undefined && editorTextarea.attr('readonly') !== false
		});

		if (modeLeft != 'application/ld+json') {
			formatEditor(editor.left);
		}

		if (modeRight != 'application/ld+json') {
			formatEditor(editor.right);
		}

		editors[translation_id] = editor;

		editor.right.on('scroll', mirrorscroll);

		//Check that the editors are not too high
		if (jQuery('.main-translation-div[data-translation-id="' + translation_id + '"] .CodeMirror').height() > 500) {
			editor.left.setSize(null, 350);
			editor.right.setSize(null, 350);
		}

		editor.right.markClean();
	});
}

function toggleSavingButtons(disabled) {
	jQuery('#copy-btn').attr('disabled', disabled ? true : false);
	jQuery('#translate-btn').attr('disabled', disabled  ? true : false);
	jQuery('#draft-button').attr('disabled', disabled  ? true : false);
	jQuery('#save-next-button').attr('disabled', disabled  ? true : false);
}

function isJSON(string) {
	try {
		JSON.parse(string);
	} catch (e) {
		return false;
	}
	return true;
}

function prettyJSON(string) {
	return JSON.stringify(JSON.parse(string), null, 4)
}

function formatEditor(editor) {
	var totalLines = editor.lineCount();
	var totalChars = editor.getTextArea().value.length;
	editor.autoFormatRange({line: 0, ch: 0}, {line: totalLines, ch: totalChars});
	editor.autoIndentRange({line: 0, ch: 0}, {line: totalLines, ch: totalChars});
}

function addExtensions() {
	CodeMirror.defineExtension("autoFormatRange", function (from, to) {
		var cm = this;
		var outer = cm.getMode(), text = cm.getRange(from, to).split("\n");
		var state = CodeMirror.copyState(outer, cm.getTokenAt(from).state);
		var tabSize = cm.getOption("tabSize");

		var out = "", lines = 0, atSol = from.ch == 0;

		function newline() {
			out += "\n";
			atSol = true;
			++lines;
		}

		for (var i = 0; i < text.length; ++i) {
			var stream = new CodeMirror.StringStream(text[i], tabSize);
			while (!stream.eol()) {
				var inner = CodeMirror.innerMode(outer, state);
				var style = outer.token(stream, state), cur = stream.current();
				stream.start = stream.pos;
				if (!atSol || /\S/.test(cur)) {
					out += cur;
					atSol = false;
				}
				if (!atSol && inner.mode.newlineAfterToken &&
					inner.mode.newlineAfterToken(style, cur, stream.string.slice(stream.pos) || text[i + 1] || "", inner.state))
					newline();
			}
			if (!stream.pos && outer.blankLine) outer.blankLine(state);
			if (!atSol) newline();
		}

		cm.operation(function () {
			cm.replaceRange(out, from, to);
			for (var cur = from.line + 1, end = from.line + lines; cur <= end; ++cur)
				cm.indentLine(cur, "smart");
			//cm.setSelection(from, cm.getCursor(false));
		});
	});

	// Applies automatic mode-aware indentation to the specified range
	CodeMirror.defineExtension("autoIndentRange", function (from, to) {
		var cmInstance = this;
		this.operation(function () {
			for (var i = from.line; i <= to.line; i++) {
				cmInstance.indentLine(i, "smart");
			}
		});
	});
}


// Keeps left editor in same scroll position as right one
function mirrorscroll(cm) {

	var translation_id = getTranslationId(jQuery(cm.getTextArea()));

	// If the left mirror has been hovered then disable mirror scroll for that element
	// We use hover because scroll would cause it to be disabled when mirror scrolled (inception)
	jQuery('.main-translation-div[data-translation-id="' + translation_id + '"] .lefteditor-wrapper .CodeMirror').on('mouseover', function () {
		editors[translation_id].left.disableMirrorScroll = true;
	});

	// Set the left scroll to be the same as the right
	if (typeof editors[translation_id].left.disableMirrorScroll == 'undefined') {
		var scrollinfo = cm.getScrollInfo();
		editors[translation_id].left.scrollTo(null, scrollinfo.top);
	}

}


/**
 * Save all translations and load the next one
 */
function saveAllTranslationsAndNext() {

	// Check the structure of each field for HTML issues
	// And show an alert if it is not OK
	if (checkStructure() === false) {
		return false;
	}

	// Make an array with objects with contents and translation_id's
	// And turn it into a json string for passing to php
	var strings = [];
	jQuery.each(editors, function (translation_id, editor) {
		var string = {};
		string.translation_id = translation_id;
		string.text = editor.right.getValue();
		strings.push(string);
	});
	var json_strings = JSON.stringify(strings);

	// Send an ajax request with the data
	jQuery.ajax({
		type    : 'POST',
		url     : 'index.php?option=com_neno&task=editor.saveAllAsCompleted',
		dataType: "json",
		data    : {strings: json_strings},
		success : function (messages) {

			// Mark each editor as clean
			jQuery.each(editors, function (translation_id, editor) {
				editor.right.markClean();
			});

			// If there are messages they relate to consolidating strings
			// Show the consolidation dialog
			if (messages !== null) {
				if (messages.length > 0) {
					showConsolidationModal(messages);
				}
			}

			//Mark each translated string as such visually
			jQuery.each(editors, function (translation_id, editor) {
				updateEditorStringStatus(translation_id, 'translated');
			});

			// Load the next translation
			loadNextTranslation();
		}
	});

}


/**
 * Load the next translation in the filter list
 */
function loadNextTranslation() {
	var nextString = jQuery('.string-activated').next('div').next('div');
	var afterNextString = nextString.next('div').next('div');
	if (nextString.length && !nextString.hasClass('no-results')) {
		loadTranslation(nextString.data('id'));
		if (!afterNextString.length) {
			loadStrings();
		}
	}
}

/**
 * Change the coloured status of a string in the string list
 * @param translation_id integer
 * @param new_status should be 'translated', 'queued', 'changed', 'not-translated'
 */
function updateEditorStringStatus(translation_id, new_status) {

	jQuery('#elements-wrapper .string[data-id="' + translation_id + '"] .status')
		.removeClass('translated queued changed not-translated')
		.addClass(new_status);

}

/**
 * Show the modal for consolidating strings
 * @param messages array of list of strings that should be consolidated
 */
function showConsolidationModal(messages) {

	// Build the HTML content for the modal
	var modal_html = '';
	jQuery.each(messages, function (i, message) {
		modal_html += message.message;
	});

	// Add the message to the modal
	jQuery('#consolidate-dynamic-content').html(modal_html);

	// Bind the click to save the modal
	jQuery('#consolidate-button').off('click').on('click', function () {

		// Determine what checkboxes were checked
		var translation_ids = [];
		jQuery('.consolidate-checkbox:checked').each(function () {
			translation_ids.push(jQuery(this).val());
		});
		var ids = JSON.stringify(translation_ids);

		jQuery.ajax({
				type   : 'POST',
				data   : {
					ids: ids
				},
				url    : 'index.php?option=com_neno&task=editor.consolidateTranslations',
				success: function () {

					// When consolidated, hide the main modal
					jQuery('#consolidate-modal').modal('hide');

					// And show the confirmation modal
					jQuery('#consolidate-confirm-modal').modal('show', {keyboard: true});

					jQuery('#consolidate-confirm-modal').on('shown.bs.modal', function () {
						jQuery('#consolidate-button-close').focus();
					});

				}
			}
		);
	});

	// Show the modal and focus button
	jQuery('#consolidate-modal').modal('show', {keyboard: true});
	jQuery('#consolidate-modal').on('shown.bs.modal', function () {
		jQuery('#consolidate-button').focus();
	});


}

/**
 * Save draft of all strings
 */
function saveAllAsDraft() {

	jQuery.each(editors, function (translation_id, editor) {

		var text = editor.right.getValue();

		jQuery.ajax({
				type    : 'POST',
				url     : 'index.php?option=com_neno&task=editor.saveAsDraft',
				dataType: "json",
				data    : {
					id  : translation_id,
					text: text
				},
				success : function () {
					editor.right.markClean();
					highlightBox('.main-translation-div[data-translation-id="' + translation_id + '"] .CodeMirror');
				}
			}
		);
	});
}


/**
 * Load an existing translation into the editor
 * @param id int
 */
function loadTranslation(id) {

	// Prevent loss of data on load
	if (confirmNotSavingChanges() === false) {
		return;
	}

	// Get information
	jQuery.ajax({
		url    : 'index.php?option=com_neno&task=editor.getTranslation&id=' + id,
		success: function (data) {
			jQuery('#editor-wrapper').html(data);
			initEditor();
			selectStringInStringList(id);
		}
	});
}

/**
 * Highlight a certain string in the string list
 * @param id integer
 */
function selectStringInStringList(id) {
	jQuery('.string-activated').removeClass('string-activated');
	jQuery('#elements-wrapper .string[data-id=' + id + ']').addClass('string-activated');
}


/**
 * Check the structure of HTML in all editors and show a prompt if something is wrong
 * as well as shows an error below the field
 * @returns true if everthing is OK or if users confirm else returns false
 */
function checkStructure() {

	var issues = false;

	jQuery('.mismatch-error').remove();

	jQuery.each(editors, function (translation_id, editor) {

		var left_content = jQuery('<div/>').html(editor.left.getValue()).contents().find('*');
		var right_content = jQuery('<div/>').html(editor.right.getValue()).contents().find('*');
		var left_element_count = left_content.length;
		var right_element_count = right_content.length;
		if (left_element_count !== right_element_count) {
			jQuery('.main-translation-div[data-translation-id="' + translation_id + '"] .righteditor-wrapper').append('<div class="alert alert-warning mismatch-error">' + JTEXT_COM_NENO_EDITOR_NOTICE_TAG_MISMATCH + '</div>');
			issues = true;
		}

	});

	if (issues === true) {

		return confirm(JTEXT_COM_NENO_EDITOR_NOTICE_TAG_MISMATCH_CONFIRM);

	} else {
		return true;
	}

}


/**
 * Handle clicking save on the API key modal
 */
function saveAPIKeyModal() {

	var translator = jQuery('#translator').val();
	var translatorKey = jQuery('#translator_api_key').val();

	jQuery.ajax({
			type   : 'POST',
			data   : {
				translator   : translator,
				translatorKey: translatorKey
			},
			url    : 'index.php?option=com_neno&task=editor.saveTranslatorConfig',
			success: function () {
				jQuery('#save-api-key-btn').modal('hide');
				window.location.reload();
			}
		}
	);

}

function bindEditorKeyboardShortcuts() {

	jQuery('body').off('keydown').on('keydown', function (e) {

		// Ctrl+S
		if (e.keyCode === 83 && e.ctrlKey) {
			e.preventDefault();
			saveAllAsDraft();
		}

		// Ctrl+Enter
		if (e.keyCode === 13 && e.ctrlKey) {
			e.preventDefault();
			saveAllTranslationsAndNext();
		}

		// Ctrl+Space
		if (e.keyCode === 32 && e.ctrlKey) {
			e.preventDefault();
			loadNextTranslation();
		}

		// Ctrl+→
		if (e.keyCode === 39 && e.ctrlKey && !e.shiftKey) {
			e.preventDefault();
			copyAll();
		}

		// Ctrl+Shift→
		if (e.keyCode === 39 && e.ctrlKey && e.shiftKey) {
			e.preventDefault();
			translateAll();
		}

	});
}


/**
 * Set a field to not be translated
 *  - and reload the browser window
 */
function setFieldAsDoNotTranslate() {

	var id = jQuery(this).data('id');

	jQuery.ajax({
			url    : 'index.php?option=com_neno&task=groupselements.toggleContentElementField&fieldId=' + id + '&translateStatus=0',
			success: function () {
				window.location.reload();
			}
		}
	);

}


/**
 * Bind a hover to show last modified date and note to translators
 */
function showInfoOnHover() {
	jQuery('.editor-wrapper').on('hover', function () {

		jQuery(this).find('.last-modified').toggle();
		jQuery(this).find('.add-comment-to-translator').toggle();

	});
}

/**
 * If a default action is set in settings then execute it when the page is loaded
 * - Depends on the global variable default_editor_load_action
 */
function doDefaultAction() {

	// If default_editor_load_action is set to '' and not 0 it means that the user has not set a default method yet
	if (default_editor_load_action === '') {
		return;
	}

	// We do not use the copyAll or translateAll method here because we want to skip empty fields
	jQuery.each(editors, function (translation_id, editor) {

		// If there is content in the right hand editor then skip the default action
		if (editor.right.getValue() !== '') {
			return;
		}

		// If there is NO content in the left hand editor then skip the default action
		if (editor.left.getValue() === '') {
			return;
		}

		//Simulate a click on either copy or translate depending on settings
		if (default_editor_load_action === '1') {
			jQuery('.main-translation-div[data-translation-id="' + translation_id + '"] .copy-string-link').click();
		} else if (default_editor_load_action === '2') {
			jQuery('.main-translation-div[data-translation-id="' + translation_id + '"] .translate-string-link').click();
		}

	});

}


/**
 * Load an area to add notes to translators or edit one if it is already there
 */
function loadNotesToTranslators(only_this_translation_id) {

	jQuery.each(editors, function (translation_id, editor) {

		//For some reason there are undefined values in this object so skip them
		if (typeof editor == 'undefined') {
			return;
		}

		//If the var only_this_translation_id is passed then skip loading of all other id's
		if (typeof only_this_translation_id != 'undefined' && only_this_translation_id != translation_id) {
			return;
		}

		// Get information
		jQuery.ajax({
			url    : 'index.php?option=com_neno&task=editor.getTranslationNotes&id=' + translation_id,
			success: function (data) {
				jQuery('.main-translation-div[data-translation-id=' + translation_id + ']').find('.note-to-translators').html(data);
				jQuery('.main-translation-div[data-translation-id=' + translation_id + ']').find('.save-translation-comment').on('click', {translation_id: translation_id}, saveTranslatorsNote);
				initTooltips();
			}
		});

	});

}

/**
 * Initialize tooltips
 */
function initTooltips() {
	jQuery('[data-toggle="tooltip"]').tooltip();
}

/**
 * Bind functionality to save the translators note using a modal
 */
function saveTranslatorsNote(event) {

	var translation_id = event.data.translation_id;

	var comment = jQuery('.main-translation-div[data-translation-id=' + translation_id + ']').find(".comment-to-translator").val();
	var data = {
		placement: 'string',
		stringId : translation_id,
		comment  : comment
	};

	// Check if we should also set the note for all strings
	var checkbox = jQuery('#comment-check-' + translation_id);

	if (checkbox.is(':checked')) {
		data['alltranslations'] = 1;
		data['contentId'] = checkbox.data('content-id');
	}

	jQuery.post(
		'index.php?option=com_neno&task=saveExternalTranslatorsComment',
		data,
		function (response) {
			if (response == 'ok') {
				loadNotesToTranslators(translation_id);
			}
			jQuery('.main-translation-div[data-translation-id=' + translation_id + ']').find('.comment-modal').modal('toggle');
		}
	);


}

/**
 * Get the current translation id by taking the passed jQuery
 * element and looking up in the DOM tree structure
 * @param {jQuery DOM element} element
 * @returns int
 */
function getTranslationId(element) {
	return jQuery(element).closest('.main-translation-div').data('translation-id');
}


/**
 * Bind listeners to various events to prevent that user leaves the page without saving
 */
function bindTranslationModificationCheck() {

	jQuery(window).off('beforeunload').on('beforeunload', function () {
		if (hasEditorContentChanged() === true) {
			return JTEXT_COM_NENO_EDITOR_UNSAVED_CHANGES;
		} else {
			return;
		}
	});

}

/**
 * Show a confirmation dialog if content has changed
 *  - This is called from various ajax methods that reloads the editors
 *  - Returns true if the user confirms the dialog or if nothing has changed
 * @returns {Boolean}
 */
function confirmNotSavingChanges() {

	// Lets not ask for confirmation more than once during a 4 second period
	// Has the user answered before
	if (typeof confirmNotSaveAnswer !== 'undefined' && typeof confirmNotSaveTime !== 'undefined') {
		var now = new Date();
		var cutTime = now.setSeconds(now.getSeconds() - 4);
		if (cutTime < confirmNotSaveTime) {
			return confirmNotSaveAnswer;
		}
	}

	if (hasEditorContentChanged() === true) {
		if (confirm(JTEXT_COM_NENO_EDITOR_UNSAVED_CHANGES)) {
			confirmNotSaveTime = new Date();
			confirmNotSaveAnswer = true;
			return true;
		} else {
			confirmNotSaveTime = new Date();
			confirmNotSaveAnswer = false;
			return false;
		}
	}
	return true;
}

/**
 * Determine if the content in any of the present editors has been changed
 * @returns {Boolean}
 */
function hasEditorContentChanged() {

	var contentHasChanged = false;
	if (typeof editors === 'undefined') {
		return false;
	}
	jQuery.each(editors, function (translation_id, editor) {

		if (editor.right.isClean() === false) {
			contentHasChanged = true;
			return false;
		}
	});

	return contentHasChanged;
}

// Bind click event in order to load translations
function bindStringsTranslationsLoading() {
	jQuery('.string').unbind('click').bind('click', function () {
		loadTranslation(jQuery(this).data('id'));
	});
}
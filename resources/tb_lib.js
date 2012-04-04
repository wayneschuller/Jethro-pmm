String.prototype.trim = function() {
	return this.replace(/^\s+|\s+$/g,"");
}
String.prototype.ltrim = function() {
	return this.replace(/^\s+/,"");
}
String.prototype.rtrim = function() {
	return this.replace(/\s+$/,"");
}
function getKeyCode(e)
{
	if (!e) e = window.event;
	return e.which ? e.which : e.keyCode;
}
function bam(x)
{
	var msg = '';
	for (i in x) {
		try {
			if (typeof x[i] != 'function') {
				msg += i + ' => ' + x[i] + "\n";
			}
		} catch (e) {}
	}
	alert(msg);
}

function tblog(x)
{
	var logBox = document.getElementById('tblib-log-box');
	if (logBox == null) {
		logBox = document.createElement('textarea');
		logBox.rows = 5;
		logBox.cols = 50;
		logBox.id = 'tblib-log-box';
		document.body.appendChild(logBox);
	}
	logBox.value += "\n"+x;
}

$(document).ready(function() {

	//// VALIDATION ////

	$('input.int-box')
		.focus(handleIntBoxFocus)
		.keydown(handleIntBoxKeyPress);

	$('input.exact-width').change(handleFixedWidthInputBlur);

	$('input.bible-ref').change(handleBibleRefBlur);

	$('input.valid-email').change(handleValidEmailBlur);

	$('input.day-box').change(handleDayBoxBlur);
	$('input.year-box').change(handleYearBoxBlur);

	$('.optional .compulsory').removeClass('compulsory');

	$('textarea[maxlength]').keypress(function() { var m = parseInt($(this).attr('maxlength'),10); return ((m < 1 ) || (this.value.length <= m)); });

	
	$('input[regex]').change(handleRegexInputBlur);


	// attach form submission handlers
	$('form').submit(handleTBLibFormSubmit);

	//// POPUPS ETC ////

	// handler for hidden-frame links
	var hlinks = ($('a.hidden-frame'));
	if (hlinks.size() > 0) {
		hlinks.each(function() { this.target = 'tblib-hidden-frame' });
		var iframe = document.createElement('IFRAME');
		iframe.name = 'tblib-hidden-frame';
		iframe.style.height = '0px';
		iframe.style.width = '0px';
		iframe.style.borderWidth = '0px';
		document.body.appendChild(iframe);
	}

	$('a.med-popup').click(handleMedPopupLinkClick).attr('title', '(Opens in a new window)');
	$('a.med-newwin').click(handleMedNewWinLinkClick).attr('title', '(Opens in a new window)');

	//// CLICKABLE THINGS ETC ////

	$('table.hoverable td').hover(
		function() { $(this.parentNode).addClass('tblib-hover'); },
		function() { $(this.parentNode).removeClass('tblib-hover'); }
	);

	$('tr.clickable, table.clickable-rows td').click(function() {
		var childLinks = $(this).parent('tr').find('a');
		if (childLinks.length) {
			self.location = childLinks[0].href;
		}
	});

	$('table.expandable').each(function() { setupExpandableTable(this) });

	$('#body form[method=post] input[type=submit]').attr('accesskey', 's');


	$('input.select-all').click(handleSelectAllClick);

	$('.link-button').click(handleLinkButtonClick);

	$('.delete-row').click(function() {
		$(this).parents('tr:first').remove();
	});
	$('.move-row-up').click(function() {
		$(this).parents('tr:first').prev('tr:first').before($(this).parents('tr:first'));
	});
	$('.move-row-down').click(function() {
		$(this).parents('tr:first').next('tr:first').after($(this).parents('tr:first'));
	});
	$('.insert-row-below').click(function() {
		var myClone = $(this).parents('tr:first').clone(true);
		$(this).parents('tr:first').after(myClone);
		myClone.find('input, select, textarea').val('');
	});

	$('.delete-list-item').attr('title', 'Delete this item').click(function() {
		$(this).remove();
	});

	$('.confirm-title').click(function() {
		return confirm("Are you sure you want to "+this.title.toLowerCase()+"?");
	});

	$('.toggle-next-hidden').each(function() {
		var hidden = $(this).next('input[type="hidden"]').get(0);
		if (hidden == null) {
			alert('TBLIB ERROR: Could not find next hidden input');
		} else {
			this.checked = (hidden.value == 1);
		}
	}).change(function() {
		var hidden = $(this).next('input[type="hidden"]').get(0);
		hidden.value = this.checked ? "1" : "0";
	});

	$('.bitmask-boxes input[type=checkbox]').click(handleBitmaskBoxClick);

	$('.bubble-option-props select').bind('change', function(e) {
		this.className = this.options[this.selectedIndex].className;
		this.title = this.options[this.selectedIndex].title;
	}).bind('keypress', function(e) {
		this.className = this.options[this.selectedIndex].className;
		this.title = this.options[this.selectedIndex].title;
	}).change();

	$('.modal input.close').click(function() { $(this).parents('.modal').hide(); });

	$('input.select-basename').focus(function() {
		var end = this.value.lastIndexOf('.');
		if ("selectionStart" in this) {
			this.setSelectionRange(0, end);
		} else if ("createTextRange" in this) {
			var t = this.createTextRange();
			//end -= start + o.value.slice(start + 1, end).split("\n").length - 1;
			//start -= o.value.slice(0, start).split("\n").length - 1;
			t.move("character", 0), t.moveEnd("character", end), t.select();
		}
	});

	$('label.submit').click(function() { if (!this.form.onsubmit || this.form.onsubmit()) $(this.form).submit(); });

	setTimeout('setupUnsavedWarnings()', 400);
});

var DATA_CHANGED = false;
function setupUnsavedWarnings() 
{
	var warnForms = $('form.warn-unsaved');
	if (warnForms.length) {
		warnForms.submit(function() {
			DATA_CHANGED = false;
		}).find('input, select, textarea').keypress(function() {
			DATA_CHANGED = true;
		}).change(function() {
			DATA_CHANGED = true;
		})
		window.onbeforeunload = function() {
			if (DATA_CHANGED) return 'You have unsaved changes which will be lost if you don\'t save first';
		}
	}
}

function handleLinkButtonClick()
{
	var link = this.previousSibling;
	while (link != null && link.tagName != 'A') {
		link = link.previousSibling;
	}
	if (link) {
		self.location = link.href;
	}
}


var invalidRegexInput = null;
function handleRegexInputBlur()
{
	if (r = this.getAttribute('regex')) {
		$(this).parents('TR:first').removeClass('missing');
		var ro = new RegExp(r, 'i');
		if ((this.value != '') && !ro.test(this.value)) {
			this.focus();
			$(this).parents('TR:first').addClass('missing');
			alert('The value of the highlighted field is not valid');
			invalidRegexInput = this;
			setTimeout('invalidRegexInput.select()', 100);
			return false;
		}
	}
	return true;
}

var invalidBibleBox = null;
function handleBibleRefBlur()
{
	var re=/^(genesis|gen|genes|exodus|exod|ex|leviticus|levit|lev|numbers|nums|num|deuteronomy|deut|joshua|josh|judges|judg|ruth|1samuel|1sam|1sam|2samuel|2sam|2sam|1kings|1ki|1ki|2kings|2ki|2ki|1chronicles|1chron|1chr|1chron|1chr|2chronicles|2chron|2chr|2chr|2chron|ezra|nehemiah|nehem|neh|esther|esth|est|job|psalms|psalm|pss|ps|proverbs|prov|pr|ecclesiastes|eccles|eccl|ecc|songofsolomon|songofsongs|songofsong|sos|songofsol|isaiah|isa|jeremiah|jerem|jer|lamentations|lam|ezekiel|ezek|daniel|dan|hosea|hos|joel|jl|jo|amos|am|obadiah|obd|ob|jonah|jon|micah|mic|nahum|nah|habakkuk|hab|zephaniah|zeph|haggai|hag|zechariah|zech|zec|malachi|mal|matthew|mathew|matt|mat|mark|mk|luke|lk|john|jn|actsoftheapostles|acts|ac|romans|rom|1corinthians|1cor|1cor|2corinthians|2cor|2cor|galatians|gal|ephesians|eph|philippians|phil|colossians|col|1thessalonians|1thess|1thes|1thes|2thessalonians|2thess|2thes|2thes|1timothy|1tim|1tim|2timothy|2tim|2tim|titus|tit|ti|philemon|hebrews|heb|james|jam|1peter|1pet|1pet|2peter|2pet|2pet|1john|1jn|1jn|2john|2jn|2jn|3john|3jn|3jn|jude|revelation|rev)(([0-9]+)([\-\:.])){0,1}(([0-9]+)([\-\:.])){0,1}(([0-9]+)([\-\:.])){0,1}([0-9]+)$/gi;
	this.value = this.value.trim();
	if (this.value == '') return true;
	if (!this.value.replace(/ /g, '').match(re)) {
		this.focus();
		alert("Invalid bible reference - "+this.value.replace(/ /g, ''));
		invalidBibleBox = this;
		setTimeout('invalidBibleBox.select()', 100);
		return false;
	}
	return true;
}

var invalidDayBox = null;
function handleDayBoxBlur()
{
	if (this.value == '') return true;
	var intVal = parseInt(this.value, 10);
	if (isNaN(intVal) || (intVal < 1) || (intVal > 31)) {
		this.focus();
		alert('Day of month must be between 1 and 31');
		invalidDayBox = this;
		setTimeout('invalidDayBox.select()', 100);
		return false;
	}
	return true;
}

var invalidYearBox = null;
function handleYearBoxBlur()
{
	if (this.value == '') return true;
	var intVal = parseInt(this.value, 10);
	if (isNaN(intVal) || (intVal < 1900) || (intVal > 3000)) {
		this.focus();
		alert('Year must be between 1900 and 3000');
		invalidYearBox = this;
		setTimeout('invalidYearBox.select()', 100);
		return false;
	}
	return true;
}


var medLinkPopupWindow = null;
function handleMedPopupLinkClick()
{
	medLinkPopupWindow = window.open(this.href, this.target ? this.target : 'medpopup', 'height=480,width=750,resizable=yes,scrollbars=yes');
	if (medLinkPopupWindow) {
		medLinkPopupWindow.focus();
	} else {
		alert('Jethro tried but could not open a popup window - you probably have a popup blocker enabled.  Please disable the popup blocker for this site, reload the page and try again.');
	}
	return false;
}

var medLinkNewWindow = null;
function handleMedNewWinLinkClick()
{
	medLinkNewWindow = window.open(this.href, this.target ? this.target : 'medpopup', 'height=480,width=750,resizable=yes,scrollbars=yes,toolbar=yes,menubar=yes');
	if (medLinkNewWindow) {
		medLinkNewWindow.focus();
	} else {
		alert('Jethro tried but could not open a popup window - you probably have a popup blocker enabled.  Please disable your popup blocker for this site, reload the page and try again.');
	}
	return false;
}


function setupExpandableTable(table)
{
	expandTable(table);
	$(table).find('input.compulsory').removeClass('compulsory');
}

function expandTable(table)
{
	var rows = $(table).find('tbody:first > tr');
	var index = rows.length - 1;
	var originalRow = rows[index];
	var newRow = $(originalRow).clone(true,true);
	var newRowInputs = newRow.find('input, textarea, select');
	newRowInputs.each(function() {
		if ($(this).parents('.preserve-value').length == 0 && !$(this).hasClass('preserve-value')) {
			if (this.type == 'checkbox') {
				this.checked = false;
			} else if (this.type != 'radio') {
				this.value = '';
			}
		} else {
			var correspondingElt = document.getElementsByName(this.name)[0];
			if (correspondingElt) {
				this.value = correspondingElt.value;
			}
		}
		if ($(this).hasClass('bubble-option-classes')) this.change();
		this.name = this.name.replace('_'+index+'_', '_'+rows.length+'_');
	});
	$(table).find('tbody:first').append(newRow);
	$(table).find('input, select, textarea').focus(handleTableExpansion);
}

function handleTableExpansion()
{
	var table = $(this).parents('table.expandable:first');
	var lastRow = table.find('tbody:first > tr:last');
	if ((this.tagName == 'INPUT') || (this.tagName == 'SELECT')) {
		var inLastRow = false;
		var t = this;
		lastRow.find('input, select').each(function() {
			if (this == t) {
				inLastRow = true;
				return;
			}
		});
		if (inLastRow) {
			// we are in the last row.  Expand now if we are the only empty row.
			if (!allInputsEmpty(lastRow.prev())) {
				expandTable(table);
			}
		}
	}
	if (!allInputsEmpty(lastRow)) expandTable(table);
}


function allInputsEmpty(JQElt)
{
	var res = true;
	JQElt.find('input, textarea').each(function() {
		if (0 == $(this).parents('.preserve-value').length) {
			if ((this.type != 'checkbox') && (this.type != 'hidden') && (this.value != '')) {
				res = false;
				return;
			}
		}
	}).end();
	JQElt.find('select').each(function() {
		if ((this.value != '') && (0 == $(this).parents('td.preserve-value').length)) {
			for (var j=0; j < this.options.length; j++) {
				if (this.options[j].value == '') {
					// it's not blank but it could have been blank
					res = false;
					return;
				}
			}
		}
	});
	return res;
}


var doTBLibValidation = true;

function cancelTBLibValidation()
{
	doTBLibValidation = false;
}

function handleTBLibFormSubmit()
{
	if (!doTBLibValidation) {
		doTBLibValidation = true;
		return false;
	}
	$('tr.missing').removeClass('missing');
	// Process compulsory inputs
	var compulsoryInputs = ($(this).find('input.compulsory'));
	for (var i=0; i < compulsoryInputs.size(); i++) {
		if ((compulsoryInputs.get(i).value == '') && (!compulsoryInputs.get(i).disabled)) {
			//try { compulsoryInputs.get(i).focus(); } catch(e) { }
			$($(compulsoryInputs.get(i)).parents('TR')).addClass('missing');
			alert('A mandatory field has been left blank');
			compulsoryInputs.get(i).focus();
			return false;
		}
	}
	// Check phone numbers are OK
	var phoneInputs = ($(this).find('input.phone-number'));
	for (var i=0; i < phoneInputs.size(); i++) {
		with (phoneInputs.get(i)) {
			if (value == '') continue;
			if (value.match(/[A-Za-z]/)) {
				$(phoneInputs.get(i)).parents('TR').addClass('missing');
				alert('Phone numbers cannot contain letters');
				phoneInputs.get(i).focus();
				return false;
			}
			var numVal = value.replace(/[^0-9]/g, '');
			var validLengths = getAttribute('validlengths').split(',');
			var lengthOK = false;
			var digitOptions = '';
			for (var j=0; j < validLengths.length; j++) {
				if (numVal.length == validLengths[j]) lengthOK = true;
				if (j == 0) {
					digitOptions = validLengths[0];
				} else if (j < (validLengths.length - 1)) {
					digitOptions += ', '+validLengths[j];
				} else {
					digitOptions += ' or '+validLengths[j];
				}
			}
			if (!lengthOK) {
				$(phoneInputs.get(i)).parents('TR').addClass('missing');
				alert('The highlighted phone number must have '+digitOptions+' digits');
				phoneInputs.get(i).focus();
				return false;
			}
		}
	}
	// Check passwords
	var passwordsOK = true;
	$(this).find('input[type=password]').each(function() {
		if (this.name.substr(-1) == '1' && this.value != '') {
			var other = $('input[name='+this.name.substring(0,this.name.length-1)+'2]');
			if (other.length == 1) {
				if (other.get(0).value != this.value) {
					$(this).parents('TR').addClass('missing');
					alert('The passwords you have entered don\'t match - please re-enter');
					this.select();
					passwordsOK = false;
					return;
				}
			}
			if (this.value.length < 6) {
				$(this).parents('TR').addClass('missing');
				alert('The password you entered is too short - passwords must be at least 6 characters');
				this.select();
				passwordsOK = false;
				return;
			}
			var num_nums = 0;
			var num_letters = 0;
			for (var i=0; i < this.value.length; i++) {
				if (this.value[i].match(/[0-9]/)) num_nums++;
				if (this.value[i].match(/[A-Za-z]/)) num_letters++;
			}
			if (num_nums < 2 || num_letters < 2) {
				$(this).parents('TR').addClass('missing');
				this.select();
				alert('The password you entered is too simple - passwords must contain at least 2 letters and 2 numbers');
				passwordsOK = false;
			}
		}
	});
	if (!passwordsOK) return false;

	// Check regexps
	var regexesOK = true;
	$(this).find('input').each(function() {
		if (r = this.getAttribute('regex')) {
			var ro = new RegExp(r, 'i');
			if ((this.value != '') && !ro.test(this.value)) {
				$(this).parents('TR').addClass('missing');
				alert('The value of the highlighted field is not valid');
				this.focus();
				regexesOK = false;
				return false;
			}
		}
	});
	if (!regexesOK) return false;

	var ok = true;
	$(this).find('input.exact-width').each(function() {
		if (!handleFixedWidthInputBlur.apply(this)) {
			ok = false;
			return false;
		}
	});
	if (!ok) return false;

	$(this).find('input.bible-ref').each(function() {
		if (!handleBibleRefBlur.apply(this)) {
			ok = false;
			return false;
		}
	});
	if (!ok) return false;

	$(this).find('input.valid-email').each(function() {
		if (!handleValidEmailBlur.apply(this)) {
			ok = false;
			return false;
		}
	});
	if (!ok) return false;

	$(this).find('input.day-box').each(function() {
		if (!handleDayBoxBlur.apply(this)) {
			ok = false;
			return false;
		}
	});
	if (!ok) return false;

	$(this).find('input.year-box').each(function() {
		if (!handleYearBoxBlur.apply(this)) {
			ok = false;
			return false;
		}
	});
	if (!ok) return false;

	return true;
}

var invalidEmailField = null;
function handleValidEmailBlur()
{
	var rx = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*\.(\w{2}|(com|net|org|edu|int|mil|gov|arpa|biz|aero|name|coop|info|pro|museum))$/
	this.value = this.value.trim();
	if (this.value != '' && !this.value.match(rx)) {
		alert('This field must contain a valid email address');
		invalidEmailField = this;
		setTimeout('invalidEmailField.select();', 100);
		return false;
	}
	return true;
}

var invalidFixedWidthInput = null;
function handleFixedWidthInputBlur()
{
	if (this.value == '') return;
	if (this.value.length != this.size) {
		alert('Values for this field must have '+this.size+' digits');
		invalidFixedWidthInput = this;
		setTimeout('invalidFixedWidthInput.select()', 100);
		return false;
	}
}

function handleIntBoxKeyPress(event)
{
	if (!event) event = window.event;
	if (event.altKey || event.ctrlKey || event.metaKey) return true;
	var keyCode = event.keyCode ? event.keyCode : event.which;
	if (-1 == this.className.indexOf('exact-width')) { // todo: improve this with jquery
		if ((keyCode == 107) && (this.value != '')) {
			this.value = (parseInt(this.value, 10)) + 1;
			return false;
		}
		if ((keyCode == 109) && (this.value != '')) {
			this.value = parseInt(this.value, 10) - 1;
			return false;
		}
	}
	validKeys = new Array(8, 9, 49, 50, 51, 52, 53, 54, 55, 56, 57, 48, 97, 98, 99, 100, 101, 102, 103, 104, 105, 96, 46, 36, 35);
	if (!validKeys.contains(keyCode)) {
		return false;
	}
}

function handleIntBoxFocus()
{
	this.select();
}

function handleSelectAllClick()
{
	var parentClass = this.parentNode.className;
	if (-1 != parentClass.indexOf(' ')) {
		parentClass = parentClass.split(' ').pop();
	}
	$('.'+parentClass+' input').attr('checked', this.checked);
}

// Thanks to http://www.go4expert.com/forums/showthread.php?t=606
Array.prototype.contains = function(element)
{
	for (var i = 0; i < this.length; i++)
	{
		if (this[i] == element) {
			return true;
		}
	}
	return false;
};

function parseQueryString(qs)
{
	qs = qs.replace(/\+/g, ' ')
	if (qs[0] == '?') qs = qs.substr(1);
	var args = qs.split('&') // parse out name/value pairs separated via &
	var params = {};
	for (var i=0;i<args.length;i++) {
		var value;
		var pair = args[i].split('=');
		var name = unescape(pair[0]);
		if (pair.length == 2) {
			value = unescape(pair[1]);
		} else {
			value = name;
		}
		params[name] = value
	}
	return params;
}


function setDateField(prefix, value)
{
	valueBits = value.split('-');
	document.getElementsByName(prefix+'_y')[0].value = valueBits[0];
	document.getElementsByName(prefix+'_m')[0].value = parseInt(valueBits[1], 10);
	document.getElementsByName(prefix+'_d')[0].value = parseInt(valueBits[2], 10);
}

function handleBitmaskBoxClick() {
	this.value = parseInt(this.value, 10);
	var boxes = document.getElementsByName(this.name);
	for (i in boxes) {
		ov = parseInt(boxes[i].value, 10);
		if (this.checked && (ov < this.value) && ((ov & this.value) != 0)) {
			// check a parent
			boxes[i].checked = true;
		} else if ((!this.checked) && (ov > this.value) && ((ov & this.value) != 0)) {
			// uncheck a child
			boxes[i].checked = false;
		}
	}
}

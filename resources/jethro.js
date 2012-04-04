
$(document).ready(function() {
	var selectChooserRadios = $('input.select-chooser-radio');
	if (selectChooserRadios.length) {
		selectChooserRadios.change(function() {
			$('input[name='+this.name+']').each(function() {
				$('select[name='+this.value+']').attr('disabled', !this.checked);
			});
		});
		$(selectChooserRadios.get(0)).change();
	}

	$('.radio-list input[type!=radio]').each(function() {
		$(this).focus(function() {
			$(this).parents('span.radio-list').find('input[type=radio]').attr('checked', true);
		});
	});


	$('a.envelope-popup').click(handleEnvelopeLinkClick);

	$('a.postcode-lookup').click(handlePostcodeLookupClick);
	$('a.map').click(handleMapClick);

	$('form#edit-family, form#add-family').submit(handleFamilyFormSubmit);

	$('form#add-family').submit(handleNewFamilySubmit);

	$('form#add-family input.family-name').blur(handleFamilyNameBlur);

	$('a.confirm-remove-from-group').click(function() { return confirm("Are you sure you want to remove this person from this group?"); });
	$('form.multi-remove-from-group').submit(function() { return confirm("Are you sure you want to remove all the selected persons from this group?"); });
	$('a.delete-group').click(function() { return confirm("Are you sure you want to delete this group?") && confirm("Deleting groups cannot be undone.  Are you really sure?"); });
	$('a.delete-report').click(function() { return confirm("Are you sure you want to delete this report?") && confirm("Deleting reports cannot be undone.  Are you really sure?"); });
	$('#bulk-action-chooser').change(handleBulkActionChange);

	$('form.bulk-person-action').submit(handleBulkActionFormSubmit);

	$('select.person-status').change(handlePersonStatusChange);
	$('select.person-status').change();

	$('form#add-family td.person-status select').change(handleNewPersonStatusChange);
	$('form#add-family td.congregation select').change(handleNewPersonCongregationChange);

	$('input.select-rule-toggle').click(handleSelectRuleToggleClick);

	// Attach the quick-search handlers
	$('#nav a').each(function() {
		if (this.innerHTML.toLowerCase() == 'search') {
			$(this).click(handleSearchLinkClick);
			this.accessKey = $(this).parents('ul').parents('li').find('span:first').html().toLowerCase()[0];
		}
	});

	$('input.cancel').click(function() {
		if (window.opener) {
			try {
				// If we are a popup, close ourselves if possible
				if ((window.opener.location.hostname+window.opener.location.pathname) == (window.location.hostname+window.location.pathname)) {
					window.close();
					return false;
				}
			} catch (e) {}
		}
		if ($.browser.msie) {
			this.parentNode.click();
		}
	});

	$('input.person-search-multiple').each(function() {
		var stem = this.id.substr(0, this.id.length-6);
		var options = {
			script: "?call=find_person_json&",
			varname: "search",
			json: true,
			maxresults: 10,
			delay: 300,
			cache: false,
			timeout: -1,
			callback: new Function("item",
							"$(document.getElementById('"+stem+"-list')).append('<li><div class=\"delete-list-item\" title=\"Remove this item\" onclick=\"deletePersonChooserListItem(this);\" />'+item.value+'<input type=\"hidden\" name=\""+stem+"[]\" value=\"'+item.id+'\" /></li>');" +
							"with (document.getElementById('"+stem+"-input')) {"+
								"if (typeof onchange == 'function') onchange(); " +
								"value = '';" +
								"focus();" +
							"}"
					  )
		};
		var as = new bsn.AutoSuggest(this.id, options);
		this.style.color = '#ccc';
		this.value = 'Type to search for person';
	}).focus(function() {
		this.value = '';
		this.style.color = '';
	}).blur(function() {
		this.style.color = '#ccc';
		this.value = 'Type to search for person';
	});		

	$('input.person-search-single, input.family-search-single').each(function() {
		var stem = this.id.substr(0, this.id.length-6);
		var options = {
			varname: "search",
			json: true,
			maxresults: 10,
			delay: 300,
			cache: false,
			timeout: -1,
			callback: new Function("item",
							"document.getElementsByName('"+stem+"')[0].value = item.id;" +
							"with (document.getElementById('"+stem+"-input')) {"+
								"if (typeof onchange == 'function') onchange(); " +
								"value = item.value+' (#'+item.id+')';" +
								"select();" +
								"oldValue = value;" +
							"}"
					  )
		};
		options.script = $(this).hasClass('person-search-single') ? "?call=find_person_json&" : "?call=find_family_json&";
		var as = new bsn.AutoSuggest(this.id, options);
		if (this.value == '') {
			this.value = 'Type to search';
			this.style.color = '#ccc';
		}
	}).focus(function() {
		if (this.value == 'Type to search') this.value = '';
		this.style.color = '';
		this.select();
		this.oldValue = this.value;
	}).blur(function() {
		if (this.value == '') {
			document.getElementsByName(this.id.substr(0, this.id.length-6))[0].value = 0;
		}
		else if (this.value != this.oldValue) this.value = this.oldValue;
	});

	$('#folder-tree a').click(function(){ $('#current-folder').attr('id', ''); this.id = 'current-folder'; });

	//setTimeout(initDTMFs, 200);

	if ($('.document-icons').length) {
		$('#rename-folder').click(function() { $('#rename-folder-modal').fadeIn('fast').find('input:first').select(); });
		$('#add-folder').click(function() { $('#add-folder-modal').fadeIn('fast').find('input:first').select(); });
		$('#upload-file').click(function() { $('#upload-file-modal').fadeIn('fast').find('input:first').select(); });
		$('.rename-file').click(function() {
			var filename = $(this).parents('tr:first').find('td.filename').text();
			$('#rename-file-modal')
				.fadeIn('fast')
				.find('input#rename-file')
					.attr('name', 'renamefile['+filename+']')
					.attr('value', filename)
					.focus();
		});
		$('.replace-file').click(function() {
			var filename = $(this).parents('tr:first').find('td.filename').text();
			$('#replace-file-modal')
				.fadeIn('fast')
				.find('input#replace-file')
					.attr('name', 'replacefile['+filename+']')
					.focus()
				.end()
				.find('span#replaced-filename')
					.html(filename)
				.end()
				.find('form')
					.submit(function() {
						var origname = $('span#replaced-filename').text().toLowerCase();
						var newname = $('input#replace-file').val().replace(/.+[\\\/]/, '').toLowerCase();
						if (newname != origname) {
							if (!confirm('You are uploading a file called "'+newname+'" but it will be saved as "'+origname+'"')) {
								$('#replace-file-modal').hide();
								return false;
							}
						}
						return true;
					});
		});		
		$('.move-file').click(function() {
			var filename = $(this).parents('tr:first').find('td.filename').text();
			$('#move-file-modal')
				.fadeIn('fast')
				.find('span#moving-filename')
					.html(filename)
				.end()
				.find('select#move-file')
					.attr('name', 'movefile['+filename+']')
					.focus();
		});


		$('#upload-file-modal input[type=file], #replace-file-modal input[type=file]').change(function() {
			$(this.form)
				.submit()
				.find('.upload-progress').show()
				.end()
				.find('input[type=button]').attr('disabled', true);
		});
	}
	$('.note-status select')
		.keypress(function() { handleNoteStatusChange(this); })
		.click(function() { handleNoteStatusChange(this); })
		.change(function() { handleNoteStatusChange(this); })
		.change();
//	handleNoteStatusChange($('.note-status select').get(0));
//	$('.note-status select').bind('keypress', handleNoteStatusChange).bind('click', handleNoteStatusChange).bind('change', handleNoteStatusChange);
	
	if ($('.attendance').length) {
		$(".attendance select").bind('keypress', function(e) {
			var key = getKeyCode(e);
			if ((key == 112) || (key == 97) || (key == 121) || (key == 110)) {
				if (this.options.length > 2) this.removeChild(this.options[0]);
				if ((key == 121) || (key == 112)) {
					this.value = '1';
				} else {
					this.value = '0';
				}
				$(this).change();
				$(this).parents('tr:first').removeClass('tblib-hover').next('tr').find('select').focus();
			}
		}).bind('focus', function(e) {
			$(this).parents('tr')[0].className = 'tblib-hover ';
		}).bind('blur', function(e) {
			$(this).parents('tr')[0].className = '';
		});
		$(".attendance select:first").focus();
	} else if ($('.initial-focus').length) {
		setTimeout("$('.initial-focus:first').focus()", 200);
	} else {
		// Focus the first visible input
		setTimeout("try { $('#body input:visible:first').focus(); } catch (e) {}", 200);
	}
});

function handleNoteStatusChange(elt) {
	var prefix = elt.name.replace('status', '');
	var newDisplay = (elt.value == 'no_action') ? 'none' : '';
	$('input[name='+prefix+'action_date_d]').parents('tr:first').css('display', newDisplay);
	$('select[name='+prefix+'assignee]').parents('tr:first').css('display', newDisplay);
	// the 'none' assignee should be removed when action is required
	if (elt.value == 'no_action') {
		if ($('select[name='+prefix+'assignee] option[value=""]').length == 0) {
			$('select[name='+prefix+'assignee]').prepend('<option selected="selected" value="">(None)</option>');
		}
	} else {
		$('select[name='+prefix+'assignee] option[value=""]').remove();
	}
}


function handleSearchLinkClick()
{
	var heading = $(this).parents('ul').parents('li').find('span:first').html().toLowerCase();
	if ($('#search-popup').length == 0) {
		$(document.body).append('<form id="search-popup" class="standard" action="'+this.href+'" method="get"><div>Search <strong></strong>&nbsp;for: <input id="search-name" type="text" name="name" /><div class="right"><input type="submit" value="Search" /><input type="button" value="Cancel" onclick="$(\'#search-popup\').hide()" /></div></div></form>');
	}
	$('#search-popup').find('strong').html(heading);

	// Convert query string to hidden vars, since query strings in a GET form's action are ignored
	$('#search-popup').find('input[type=hidden]').remove();
	var queryVars = parseQueryString(this.href.substr(this.href.indexOf('?')+1));
	for (varName in queryVars) {
		$('#search-popup').prepend('<input type="hidden" name="'+varName+'" value="'+queryVars[varName]+'" />');
	}
	$('#search-popup').show(50, function() {
			try {
				$('#search-popup input:visible:first').get(0).select();
			} catch(e) {}
		});
	return false;
}

var personStatusCascaded = false;
function handleNewPersonStatusChange()
{
	if (!personStatusCascaded && this.name == 'members_0_status') {
		$('form#add-family td.person-status select').attr('value', this.value);
		personStatusCascaded = true;
		$('select.person-status').change();
	}
}

var congregationCascaded = false;
function handleNewPersonCongregationChange()
{
	if (!congregationCascaded && this.name == 'members_0_congregationid') {
		$('form#add-family td.congregation select').attr('value', this.value);
		congregationCascaded = true;
	}
}

function handleNewFamilySubmit()
{
	var i = 0;
	var haveMember = false;
	while (document.getElementsByName('members_'+i+'_first_name').length != 0) {
		var memberFirstNameField = document.getElementsByName('members_'+i+'_first_name')[0];
		var memberLastNameField = document.getElementsByName('members_'+i+'_last_name')[0];
		if (memberFirstNameField.value != '') {
			if (memberLastNameField.value == '') {
				alert('You must specify a last name for each family member');
				memberLastNameField.focus();
				cancelTBLibValidation();
				return false;
			}
			haveMember = true;
		}
		i++;
	}

	if (!haveMember) {
		alert('New family must have at least one member');
		document.getElementsByName('members_0_first_name')[0].focus();
		cancelTBLibValidation();
		return false;
	}
	return true;
}

var envelopeWindow = null;
function handleEnvelopeLinkClick()
{
	envelopeWindow = window.open(this.href, 'envelopes', 'height=320,width=500,location=no,menubar=no,titlebar=no,toolbar=no,resizable=yes,statusbar=no');
	if (envelopeWindow) {
		setTimeout('envelopeWindow.print()', 750);
	} else {
		alert('Jethro tried but could not open a popup window - you probably have a popup blocker enabled.  Please disable your popup blocker for this site, reload the page and try again.');
	}
	return false;
}

function targetFormToEnvelopeWindow(form)
{
	envelopeWindow = window.open('', 'envelopes', 'height=320,width=500,location=no,menubar=no,titlebar=no,toolbar=no,resizable=yes,statusbar=no');
	if (envelopeWindow) {
		form.target = 'envelopes';
	} else {
		alert('Jethro tried but could not open a popup window - you probably have a popup blocker enabled.  Please disable your popup blocker for this site, reload the page and try again.');
	}
}



function handleFamilyNameBlur()
{
	$('form#add-family td.last_name input').each(new Function("if (this.value == '') this.value = '"+this.value.replace("'", "\\'")+"';"));
}

function handlePostcodeLookupClick()
{
	var suburb = this.parentNode.getElementsByTagName('INPUT')[0].value;
	var state = $('select[name=address_state]');
	if ((-1 != this.href.indexOf('__SUBURB__')) && (suburb == '')) {
		alert('You must enter a suburb first, then click the link to find its postcode');
		this.parentNode.getElementsByTagName('INPUT')[0].focus();
		return false;
	}
	var url = this.href.replace('__SUBURB__', suburb);
	if (state.length) url = url.replace('__STATE__', state.get(0).value);
	var postcodeWindow = window.open(url, 'postcode', 'height=320,width=650,location=no,menubar=no,titlebar=no,toolbar=no,resizable=yes,statusbar=no,scrollbars=yes');
	if (!postcodeWindow) {
		alert('Jethro tried but could not open a popup window - you probably have a popup blocker enabled.  Please disable your popup blocker for this site, reload the page and try again.');
	}
	return false;
}

function handleMapClick()
{
	var mapWindow = window.open(this.href, 'map', 'height='+parseInt($(window).height()*0.9, 10)+',width='+parseInt($(window).width()*0.9, 10)+',location=no,menubar=no,titlebar=no,toolbar=no,resizable=yes,statusbar=no');
	if (!mapWindow) {
		alert('Jethro tried but could not open a popup window - you probably have a popup blocker enabled.  Please disable your popup blocker for this site, reload the page and try again.');
	}
	return false;
}

function handleFamilyFormSubmit()
{
	if ((document.getElementsByName('address_postcode')[0].value == '') && (document.getElementsByName('address_suburb')[0].value != '')) {
		alert('If a suburb is supplied, a postcode must also be supplied');
		document.getElementsByName('address_postcode')[0].focus();
		cancelTBLibValidation();
		return false;
	}
	if ((document.getElementsByName('address_postcode')[0].value != '') && (document.getElementsByName('address_suburb')[0].value == '')) {
		alert('If a postcode is supplied, a suburb must also be supplied');
		document.getElementsByName('address_suburb')[0].focus();
		cancelTBLibValidation();
		return false;
	}
	return true;
}

// LOCKING

function showLockExpiryWarning()
{
	var div = document.createElement('DIV');
	div.id = 'lock-warning-div';
	div.className = 'popup failure';
	div.innerHTML = '<p><b>Your lock on this object will soon expire.</b></p><p>To make sure your changes get saved, you should submit the form now.<p>';
	div.innerHTML += '<input type="button" value="OK" onclick="this.parentNode.style.display = \'none\'" />';
	document.body.appendChild(div);
}

function showLockExpiredWarning()
{
	var div = document.getElementById('lock-warning-div');
	div.innerHTML = '<p><b>Your lock on this object has now expired.  You cannot save the changes you have made.  Would you like to reload the form and try again?</b></p><p></p>';
	div.innerHTML += '<input type="button" value="Yes" onclick="document.location.href = document.location" />';
	div.innerHTML += '<input type="button" value="No" onclick="this.parentNode.style.display = \'none\'; $(\'#body form[method=post] input, #body form[method=post] select, #body form[method=post] textarea\').attr(\'disabled\', true)" />';
	div.style.display = 'block';
}

// Action dates

function handleBulkActionChange()
{
	$('.bulk-action').css('display', 'none');
	$('.bulk-action input, .bulk-action select, .bulk-action textarea').attr('disabled', true);
	$('#'+this.value).css('display', 'block');
	with ($('#'+this.value+' input, #'+this.value+' select, #'+this.value+' textarea').attr('disabled', false).filter(':visible')) {
		if (length) focus();
	}

}

function doPersonSearch(elt)
{
	var prefix = elt.id.replace('-search-button', '');
	var iframe = frames['new_member_iframe'];
	var searchName = document.getElementById(prefix+'-search').value;
	document.getElementById('new_member_iframe').style.display = 'block';
	iframe.location.href = $('#iframe-url')[0].value + searchName;
}

function handleBulkActionFormSubmit()
{
	var checkboxes = document.getElementsByName('personid[]');
	for (var i=0; i < checkboxes.length; i++) {
		if (checkboxes[i].checked) return true;
	}
	alert('You must select some persons before trying to perform an action on them');
	cancelTBLibValidation();
	return false;
}

function handlePersonStatusChange()
{
	var congChooserName = this.name.replace('status', 'congregationid');
	var congChoosers = document.getElementsByName(congChooserName);
	if (congChoosers.length != 0) {
		var chooser = congChoosers[0];
		for (var i=0; i < chooser.options.length; i++) {
			if (chooser.options[i].value == '') {
				if ((this.value == 'contact') || (this.value == 'archived')) {
					// blank value allowed
					return;
				} else {
					chooser.remove(i);
					return;
				}
			}
		}
		// if we got to here, there is no blank option
		if ((this.value == 'contact') || (this.value == 'archived')) {
			// we need a blank option
			var newOption = new Option('(None)', '');
			try {
				chooser.add(newOption, chooser.options[0]); // standards compliant; doesn't work in IE
			} catch(ex) {
				chooser.add(newOption, 0); // IE only
			}
		}
	}
	return true;
}

function deletePersonChooserListItem(elt)
{
	var li = $(elt).parents('li:first');
	var input = li.find('input')[0];
	var textInput = document.getElementById(input.name.substr(0, input.name.length-2)+'-input');
	li.remove();
	if (typeof textInput.onchange == 'function') {
		textInput.onchange();
	}
}

function handleSelectRuleToggleClick()
{
	$($(this).parents('tr')[0]).find('div.select-rule-options').css('display', (this.checked ? '' : 'none'));
}

function initDTMFs()
{
	with ($('.phone-no')) {
		if (length < 10) {
			each(function() {
				initDTMF(this.innerHTML);
			});
			click(function() {
				playDTMF(this.innerHTML);
			});
		} else {
			// TODO: Print single number EMBEDs, and have the links play them as required
		}
	}
}

function initDTMF(n)
{
	if (!$.browser.safari) {
		html = '<embed autostart="False" autoplay="false" controller="false" cache="true" width="0" height="0" src="?call=dtmf&n='+n+'" id="dtmf'+n+'" />';
	}
	$(document.body).append(html);
}
function playDTMF(n)
{
	document.getElementById('dtmf'+n).Play();
	/*
	n = ""+n+"";
	for (var i=0; i < n.length; i++) {
		setTimeout('window.frames["dtmf_frame"].document.getElementById("dtmf'+n.charAt(i)+'").Play()', (i+1) * 200);
		setTimeout('window.frames["dtmf_frame"].document.getElementById("dtmf'+n.charAt(i)+'").Stop()', (i+1.5) * 200);
	}
	*/
}

/**
 *  author:		Timothy Groves - http://www.brandspankingnew.net
 *	version:	1.2 - 2006-11-17
 *              1.3 - 2006-12-04
 *              2.0 - 2007-02-07
 *              2.1.1 - 2007-04-13
 *              2.1.2 - 2007-07-07
 *              2.1.3 - 2007-07-19
 *
 */


if (typeof(bsn) == "undefined")
	_b = bsn = {};


if (typeof(_b.Autosuggest) == "undefined")
	_b.Autosuggest = {};
else
	alert("Autosuggest is already set!");












_b.AutoSuggest = function (id, param)
{
	// no DOM - give up!
	//
	if (!document.getElementById)
		return 0;
	
	
	
	
	// get field via DOM
	//
	this.fld = _b.DOM.gE(id);

	if (!this.fld)
		return 0;
	
	
	
	
	// init variables
	//
	this.sInp 	= "";
	this.nInpC 	= 0;
	this.aSug 	= [];
	this.iHigh 	= 0;
	
	
	
	
	// parameters object
	//
	this.oP = param ? param : {};
	
	// defaults	
	//
	var k, def = {minchars:1, meth:"get", varname:"input", className:"autosuggest", timeout:2500, delay:500, offsety:-5, shownoresults: true, noresults: "No results!", maxheight: 250, cache: true, maxentries: 25};
	for (k in def)
	{
		if (typeof(this.oP[k]) != typeof(def[k]))
			this.oP[k] = def[k];
	}
	
	
	// set keyup handler for field
	// and prevent autocomplete from client
	//
	var p = this;
	
	// NOTE: not using addEventListener because UpArrow fired twice in Safari
	//_b.DOM.addEvent( this.fld, 'keyup', function(ev){ return pointer.onKeyPress(ev); } );
	
	this.fld.onkeypress 	= function(ev){ return p.onKeyPress(ev); };
	this.fld.onkeyup 		= function(ev){ return p.onKeyUp(ev); };
	
	this.fld.setAttribute("autocomplete","off");
};
















_b.AutoSuggest.prototype.onKeyPress = function(ev)
{
	
	var key = (window.event) ? window.event.keyCode : ev.keyCode;



	// set responses to keydown events in the field
	// this allows the user to use the arrow keys to scroll through the results
	// ESCAPE clears the list
	// TAB sets the current highlighted value
	//
	var RETURN = 13;
	var TAB = 9;
	var ESC = 27;
	
	var bubble = true;

	switch(key)
	{
		case RETURN:
			this.setHighlightedValue();
			bubble = false;
			break;

		case ESC:
			this.clearSuggestions();
			break;
	}

	return bubble;
};



_b.AutoSuggest.prototype.onKeyUp = function(ev)
{
	var key = (window.event) ? window.event.keyCode : ev.keyCode;
	


	// set responses to keydown events in the field
	// this allows the user to use the arrow keys to scroll through the results
	// ESCAPE clears the list
	// TAB sets the current highlighted value
	//

	var ARRUP = 38;
	var ARRDN = 40;
	
	var bubble = true;

	switch(key)
	{


		case ARRUP:
			this.changeHighlight(key);
			bubble = false;
			break;


		case ARRDN:
			this.changeHighlight(key);
			bubble = false;
			break;
		
		
		default:
			this.getSuggestions(this.fld.value);
	}

	return bubble;
	

};








_b.AutoSuggest.prototype.getSuggestions = function (val)
{
	
	// if input stays the same, do nothing
	//
	if (val == this.sInp)
		return 0;
	
	
	// kill list
	//
	_b.DOM.remE(this.idAs);
	
	
	this.sInp = val;
	
	
	// input length is less than the min required to trigger a request
	// do nothing
	//
	if (val.length < this.oP.minchars)
	{
		this.aSug = [];
		this.nInpC = val.length;
		return 0;
	}
	
	
	
	
	var ol = this.nInpC; // old length
	this.nInpC = val.length ? val.length : 0;
	
	
	
	// if caching enabled, and user is typing (ie. length of input is increasing)
	// filter results out of aSuggestions from last request
	//
	var l = this.aSug.length;
	if (this.nInpC > ol && l && l<this.oP.maxentries && this.oP.cache)
	{
		var arr = [];
		for (var i=0;i<l;i++)
		{
			if (this.aSug[i].value.substr(0,val.length).toLowerCase() == val.toLowerCase())
				arr.push( this.aSug[i] );
		}
		this.aSug = arr;
		
		this.createList(this.aSug);
		
		
		
		return false;
	}
	else
	// do new request
	//
	{
		var pointer = this;
		var input = this.sInp;
		clearTimeout(this.ajID);
		this.ajID = setTimeout( function() { pointer.doAjaxRequest(input) }, this.oP.delay );
	}

	return false;
};





_b.AutoSuggest.prototype.doAjaxRequest = function (input)
{
	// check that saved input is still the value of the field
	//
	if (input != this.fld.value)
		return false;
	
	
	var pointer = this;
	
	
	// create ajax request
	//
	if (typeof(this.oP.script) == "function")
		var url = this.oP.script(encodeURIComponent(this.sInp));
	else
		var url = this.oP.script+this.oP.varname+"="+encodeURIComponent(this.sInp);
	
	if (!url)
		return false;
	
	var meth = this.oP.meth;
	var input = this.sInp;
	
	var onSuccessFunc = function (req) { pointer.setSuggestions(req, input) };
	var onErrorFunc = function (status) { alert("AJAX error: "+status); };

	var myAjax = new _b.Ajax();
	myAjax.makeRequest( url, meth, onSuccessFunc, onErrorFunc );
};





_b.AutoSuggest.prototype.setSuggestions = function (req, input)
{
	// if field input no longer matches what was passed to the request
	// don't show the suggestions
	//
	if (input != this.fld.value)
		return false;
	
	
	this.aSug = [];
	
	
	if (this.oP.json)
	{
		var jsondata = eval('(' + req.responseText + ')');
		
		for (var i=0;i<jsondata.results.length;i++)
		{
			this.aSug.push(  { 'id':jsondata.results[i].id, 'value':jsondata.results[i].value, 'info':jsondata.results[i].info }  );
		}
	}
	else
	{

		var xml = req.responseXML;
	
		// traverse xml
		//
		var results = xml.getElementsByTagName('results')[0].childNodes;

		for (var i=0;i<results.length;i++)
		{
			if (results[i].hasChildNodes())
				this.aSug.push(  { 'id':results[i].getAttribute('id'), 'value':results[i].childNodes[0].nodeValue, 'info':results[i].getAttribute('info') }  );
		}
	
	}
	
	this.idAs = "as_"+this.fld.id;
	

	this.createList(this.aSug);

};














_b.AutoSuggest.prototype.createList = function(arr)
{
	var pointer = this;
	
	
	
	
	// get rid of old list
	// and clear the list removal timeout
	//
	_b.DOM.remE(this.idAs);
	this.killTimeout();
	
	
	// if no results, and shownoresults is false, do nothing
	//
	if (arr.length == 0 && !this.oP.shownoresults)
		return false;
	
	
	// create holding div
	//
	var div = _b.DOM.cE("div", {id:this.idAs, className:this.oP.className});	
	
	var hcorner = _b.DOM.cE("div", {className:"as_corner"});
	var hbar = _b.DOM.cE("div", {className:"as_bar"});
	var header = _b.DOM.cE("div", {className:"as_header"});
	header.appendChild(hcorner);
	header.appendChild(hbar);
	div.appendChild(header);
	
	
	
	
	// create and populate ul
	//
	var ul = _b.DOM.cE("ul", {id:"as_ul"});
	
	
	
	
	// loop throught arr of suggestions
	// creating an LI element for each suggestion
	//
	for (var i=0;i<arr.length;i++)
	{
		// format output with the input enclosed in a EM element
		// (as HTML, not DOM)
		//
		var val = arr[i].value;
		var st = val.toLowerCase().indexOf( this.sInp.toLowerCase() );
		var output = val.substring(0,st) + "<em>" + val.substring(st, st+this.sInp.length) + "</em>" + val.substring(st+this.sInp.length);
		
		
		var span 		= _b.DOM.cE("span", {}, output, true);
		if (arr[i].info != "")
		{
			var br			= _b.DOM.cE("br", {});
			span.appendChild(br);
			var small		= _b.DOM.cE("small", {}, arr[i].info);
			span.appendChild(small);
		}
		
		var a 			= _b.DOM.cE("a", { href:"#" });
		
		var tl 		= _b.DOM.cE("span", {className:"tl"}, " ");
		var tr 		= _b.DOM.cE("span", {className:"tr"}, " ");
		a.appendChild(tl);
		a.appendChild(tr);
		
		a.appendChild(span);
		
		a.name = i+1;
		a.onclick = function () { pointer.setHighlightedValue(); return false; };
		a.onmouseover = function () { pointer.setHighlight(this.name); };
		
		var li = _b.DOM.cE(  "li", {}, a  );
		
		ul.appendChild( li );
	}
	
	
	// no results
	//
	if (arr.length == 0 && this.oP.shownoresults)
	{
		var li = _b.DOM.cE(  "li", {className:"as_warning"}, this.oP.noresults  );
		ul.appendChild( li );
	}
	
	
	div.appendChild( ul );
	
	
	var fcorner = _b.DOM.cE("div", {className:"as_corner"});
	var fbar = _b.DOM.cE("div", {className:"as_bar"});
	var footer = _b.DOM.cE("div", {className:"as_footer"});
	footer.appendChild(fcorner);
	footer.appendChild(fbar);
	div.appendChild(footer);
	
	
	
	// get position of target textfield
	// position holding div below it
	// set width of holding div to width of field
	//
	var pos = _b.DOM.getPos(this.fld);
	
	div.style.left 		= pos.x + "px";
	div.style.top 		= ( pos.y + this.fld.offsetHeight + this.oP.offsety ) + "px";
	div.style.width 	= this.fld.offsetWidth + "px";
	
	
	
	// set mouseover functions for div
	// when mouse pointer leaves div, set a timeout to remove the list after an interval
	// when mouse enters div, kill the timeout so the list won't be removed
	//
	div.onmouseover 	= function(){ pointer.killTimeout() };
	div.onmouseout 		= function(){ pointer.resetTimeout() };


	// add DIV to document
	//
	document.getElementsByTagName("body")[0].appendChild(div);
	
	
	
	// currently no item is highlighted
	//
	this.iHigh = 0;
	
	
	
	
	
	
	// remove list after an interval
	//
	var pointer = this;
	if (this.oP.timeout > 0) {
		this.toID = setTimeout(function () { pointer.clearSuggestions() }, this.oP.timeout);
	}
};















_b.AutoSuggest.prototype.changeHighlight = function(key)
{	
	var list = _b.DOM.gE("as_ul");
	if (!list)
		return false;
	
	var n;

	if (key == 40)
		n = this.iHigh + 1;
	else if (key == 38)
		n = this.iHigh - 1;
	
	
	if (n > list.childNodes.length)
		n = list.childNodes.length;
	if (n < 1)
		n = 1;
	
	
	this.setHighlight(n);
};



_b.AutoSuggest.prototype.setHighlight = function(n)
{
	var list = _b.DOM.gE("as_ul");
	if (!list)
		return false;
	
	if (this.iHigh > 0)
		this.clearHighlight();
	
	this.iHigh = Number(n);
	
	list.childNodes[this.iHigh-1].className = "as_highlight";


	this.killTimeout();
};


_b.AutoSuggest.prototype.clearHighlight = function()
{
	var list = _b.DOM.gE("as_ul");
	if (!list)
		return false;
	
	if (this.iHigh > 0)
	{
		list.childNodes[this.iHigh-1].className = "";
		this.iHigh = 0;
	}
};


_b.AutoSuggest.prototype.setHighlightedValue = function ()
{
	if (this.iHigh)
	{
		this.sInp = this.fld.value = this.aSug[ this.iHigh-1 ].value;
		
		// move cursor to end of input (safari)
		//
		this.fld.focus();
		if (this.fld.selectionStart)
			this.fld.setSelectionRange(this.sInp.length, this.sInp.length);
		

		this.clearSuggestions();
		
		// pass selected object to callback function, if exists
		//
		if (typeof(this.oP.callback) == "function")
			this.oP.callback( this.aSug[this.iHigh-1] );

		// Callback might have changed the field value, so refresh sInp
		this.sInp = this.fld.value
	}
};













_b.AutoSuggest.prototype.killTimeout = function()
{
	clearTimeout(this.toID);
};

_b.AutoSuggest.prototype.resetTimeout = function()
{
	clearTimeout(this.toID);
	var pointer = this;
	this.toID = setTimeout(function () { pointer.clearSuggestions() }, 1000);
};







_b.AutoSuggest.prototype.clearSuggestions = function ()
{
	
	this.killTimeout();
	
	var ele = _b.DOM.gE(this.idAs);
	var pointer = this;
	if (ele)
	{
		var fade = new _b.Fader(ele,1,0,250,function () { _b.DOM.remE(pointer.idAs) });
	}
};










// AJAX PROTOTYPE _____________________________________________


if (typeof(_b.Ajax) == "undefined")
	_b.Ajax = {};



_b.Ajax = function ()
{
	this.req = {};
	this.isIE = false;
};



_b.Ajax.prototype.makeRequest = function (url, meth, onComp, onErr)
{
	
	if (meth != "POST")
		meth = "GET";
	
	this.onComplete = onComp;
	this.onError = onErr;
	
	var pointer = this;
	
	// branch for native XMLHttpRequest object
	if (window.XMLHttpRequest)
	{
		this.req = new XMLHttpRequest();
		this.req.onreadystatechange = function () { pointer.processReqChange() };
		this.req.open("GET", url, true); //
		this.req.send(null);
	// branch for IE/Windows ActiveX version
	}
	else if (window.ActiveXObject)
	{
		this.req = new ActiveXObject("Microsoft.XMLHTTP");
		if (this.req)
		{
			this.req.onreadystatechange = function () { pointer.processReqChange() };
			this.req.open(meth, url, true);
			this.req.send();
		}
	}
};


_b.Ajax.prototype.processReqChange = function()
{
	
	// only if req shows "loaded"
	if (this.req.readyState == 4) {
		// only if "OK"
		if (this.req.status == 200)
		{
			this.onComplete( this.req );
		} else {
			this.onError( this.req.status );
		}
	}
};










// DOM PROTOTYPE _____________________________________________


if (typeof(_b.DOM) == "undefined")
	_b.DOM = {};



/* create element */
_b.DOM.cE = function ( type, attr, cont, html )
{
	var ne = document.createElement( type );
	if (!ne)
		return 0;
		
	for (var a in attr)
		ne[a] = attr[a];
	
	var t = typeof(cont);
	
	if (t == "string" && !html)
		ne.appendChild( document.createTextNode(cont) );
	else if (t == "string" && html)
		ne.innerHTML = cont;
	else if (t == "object")
		ne.appendChild( cont );

	return ne;
};



/* get element */
_b.DOM.gE = function ( e )
{
	var t=typeof(e);
	if (t == "undefined")
		return 0;
	else if (t == "string")
	{
		var re = document.getElementById( e );
		if (!re)
			return 0;
		else if (typeof(re.appendChild) != "undefined" )
			return re;
		else
			return 0;
	}
	else if (typeof(e.appendChild) != "undefined")
		return e;
	else
		return 0;
};



/* remove element */
_b.DOM.remE = function ( ele )
{
	var e = this.gE(ele);
	
	if (!e)
		return 0;
	else if (e.parentNode.removeChild(e))
		return true;
	else
		return 0;
};



/* get position */
_b.DOM.getPos = function ( e )
{
	var e = this.gE(e);

	var obj = e;

	var curleft = 0;
	if (obj.offsetParent)
	{
		while (obj.offsetParent)
		{
			curleft += obj.offsetLeft;
			obj = obj.offsetParent;
		}
	}
	else if (obj.x)
		curleft += obj.x;
	
	var obj = e;
	
	var curtop = 0;
	if (obj.offsetParent)
	{
		while (obj.offsetParent)
		{
			curtop += obj.offsetTop;
			obj = obj.offsetParent;
		}
	}
	else if (obj.y)
		curtop += obj.y;

	return {x:curleft, y:curtop};
};










// FADER PROTOTYPE _____________________________________________



if (typeof(_b.Fader) == "undefined")
	_b.Fader = {};





_b.Fader = function (ele, from, to, fadetime, callback)
{	
	if (!ele)
		return 0;
	
	this.e = ele;
	
	this.from = from;
	this.to = to;
	
	this.cb = callback;
	
	this.nDur = fadetime;
		
	this.nInt = 50;
	this.nTime = 0;
	
	var p = this;
	this.nID = setInterval(function() { p._fade() }, this.nInt);
};




_b.Fader.prototype._fade = function()
{
	this.nTime += this.nInt;
	
	var ieop = Math.round( this._tween(this.nTime, this.from, this.to, this.nDur) * 100 );
	var op = ieop / 100;
	
	if (this.e.filters) // internet explorer
	{
		try
		{
			this.e.filters.item("DXImageTransform.Microsoft.Alpha").opacity = ieop;
		} catch (e) { 
			// If it is not set initially, the browser will throw an error.  This will set it if it is not set yet.
			this.e.style.filter = 'progid:DXImageTransform.Microsoft.Alpha(opacity='+ieop+')';
		}
	}
	else // other browsers
	{
		this.e.style.opacity = op;
	}
	
	
	if (this.nTime == this.nDur)
	{
		clearInterval( this.nID );
		if (this.cb != undefined)
			this.cb();
	}
};



_b.Fader.prototype._tween = function(t,b,c,d)
{
	return b + ( (c-b) * (t/d) );
};
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
/*==================================================
  $Id: jethro_all.js,v 1.43 2012/03/26 12:13:50 tbar0970 Exp $
  tabber.js by Patrick Fitzgerald pat@barelyfitz.com

  Documentation can be found at the following URL:
  http://www.barelyfitz.com/projects/tabber/

  License (http://www.opensource.org/licenses/mit-license.php)

  Copyright (c) 2006 Patrick Fitzgerald

  Permission is hereby granted, free of charge, to any person
  obtaining a copy of this software and associated documentation files
  (the "Software"), to deal in the Software without restriction,
  including without limitation the rights to use, copy, modify, merge,
  publish, distribute, sublicense, and/or sell copies of the Software,
  and to permit persons to whom the Software is furnished to do so,
  subject to the following conditions:

  The above copyright notice and this permission notice shall be
  included in all copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
  EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
  MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
  NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
  BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
  ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
  SOFTWARE.
  ==================================================*/

function tabberObj(argsObj)
{
  if (window.location.hash && window.location.hash.match( /^#/ ))  {
    var el = document.getElementById(window.location.hash.substr(1));
    if (el) {
		if ($(el).is('.tabbertab')) {
			$(el).addClass('tabbertabdefault');
		} else {
			$(el).parents('.tabbertab:first').addClass('tabbertabdefault');
		}
		el.parentNode.insertBefore($('<a name="'+el.id+'"></a>').get(0), el);
    }
  } else {
	$('.tabbertab:first').addClass('tabbertabdefault');
  }
  
  var arg; /* name of an argument to override */

  /* Element for the main tabber div. If you supply this in argsObj,
     then the init() method will be called.
  */
  this.div = null;

  /* Class of the main tabber div */
  this.classMain = "tabber";

  /* Rename classMain to classMainLive after tabifying
     (so a different style can be applied)
  */
  this.classMainLive = "tabberlive";

  /* Class of each DIV that contains a tab */
  this.classTab = "tabbertab";

  /* Class to indicate which tab should be active on startup */
  this.classTabDefault = "tabbertabdefault";

  /* Class for the navigation UL */
  this.classNav = "tabbernav";

  /* When a tab is to be hidden, instead of setting display='none', we
     set the class of the div to classTabHide. In your screen
     stylesheet you should set classTabHide to display:none.  In your
     print stylesheet you should set display:block to ensure that all
     the information is printed.
  */
  this.classTabHide = "tabbertabhide";

  /* Class to set the navigation LI when the tab is active, so you can
     use a different style on the active tab.
  */
  this.classNavActive = "tabberactive";

  /* Elements that might contain the title for the tab, only used if a
     title is not specified in the TITLE attribute of DIV classTab.
  */
  this.titleElements = ['h2','h3','h4','h5','h6'];

  /* Should we strip out the HTML from the innerHTML of the title elements?
     This should usually be true.
  */
  this.titleElementsStripHTML = true;

  /* If the user specified the tab names using a TITLE attribute on
     the DIV, then the browser will display a tooltip whenever the
     mouse is over the DIV. To prevent this tooltip, we can remove the
     TITLE attribute after getting the tab name.
  */
  this.removeTitle = true;

  /* If you want to add an id to each link set this to true */
  this.addLinkId = false;

  /* If addIds==true, then you can set a format for the ids.
     <tabberid> will be replaced with the id of the main tabber div.
     <tabnumberzero> will be replaced with the tab number
       (tab numbers starting at zero)
     <tabnumberone> will be replaced with the tab number
       (tab numbers starting at one)
     <tabtitle> will be replaced by the tab title
       (with all non-alphanumeric characters removed)
   */
  this.linkIdFormat = '<tabberid>nav<tabnumberone>';

  /* You can override the defaults listed above by passing in an object:
     var mytab = new tabber({property:value,property:value});
  */
  for (arg in argsObj) { this[arg] = argsObj[arg]; }

  /* Create regular expressions for the class names; Note: if you
     change the class names after a new object is created you must
     also change these regular expressions.
  */
  this.REclassMain = new RegExp('\\b' + this.classMain + '\\b', 'gi');
  this.REclassMainLive = new RegExp('\\b' + this.classMainLive + '\\b', 'gi');
  this.REclassTab = new RegExp('\\b' + this.classTab + '\\b', 'gi');
  this.REclassTabDefault = new RegExp('\\b' + this.classTabDefault + '\\b', 'gi');
  this.REclassTabHide = new RegExp('\\b' + this.classTabHide + '\\b', 'gi');

  /* Array of objects holding info about each tab */
  this.tabs = new Array();

  this.onTabShow = null;

  /* If the main tabber div was specified, call init() now */
  if (this.div) {

    this.init(this.div);

    /* We don't need the main div anymore, and to prevent a memory leak
       in IE, we must remove the circular reference between the div
       and the tabber object. */
    this.div = null;
  }
  if (document.location.hash.length > 1) {
	document.location.hash = document.location.hash;
  }
}


/*--------------------------------------------------
  Methods for tabberObj
  --------------------------------------------------*/


tabberObj.prototype.init = function(e)
{
  /* Set up the tabber interface.

     e = element (the main containing div)

     Example:
     init(document.getElementById('mytabberdiv'))
   */

  var
  childNodes, /* child nodes of the tabber div */
  i, i2, /* loop indices */
  t, /* object to store info about a single tab */
  defaultTab=0, /* which tab to select by default */
  DOM_ul, /* tabbernav list */
  DOM_li, /* tabbernav list item */
  DOM_a, /* tabbernav link */
  aId, /* A unique id for DOM_a */
  headingElement; /* searching for text to use in the tab */

  /* Verify that the browser supports DOM scripting */
  if (!document.getElementsByTagName) { return false; }

  /* If the main DIV has an ID then save it. */
  if (e.id) {
    this.id = e.id;
  }

  /* Clear the tabs array (but it should normally be empty) */
  this.tabs.length = 0;

  /* Loop through an array of all the child nodes within our tabber element. */
  childNodes = e.childNodes;
  for(i=0; i < childNodes.length; i++) {

    /* Find the nodes where class="tabbertab" */
    if(childNodes[i].className &&
       childNodes[i].className.match(this.REclassTab)) {
      
      /* Create a new object to save info about this tab */
      t = new Object();
      
      /* Save a pointer to the div for this tab */
      t.div = childNodes[i];
      
      /* Add the new object to the array of tabs */
      this.tabs[this.tabs.length] = t;

      /* If the class name contains classTabDefault,
	 then select this tab by default.
      */
      if (childNodes[i].className.match(this.REclassTabDefault)) {
	defaultTab = this.tabs.length-1;
      }
    }
  }

  /* Create a new UL list to hold the tab headings */
  DOM_ul = document.createElement("ul");
  DOM_ul.className = this.classNav;
  
  /* Loop through each tab we found */
  for (i=0; i < this.tabs.length; i++) {

    t = this.tabs[i];

    /* Get the label to use for this tab:
       From the title attribute on the DIV,
       Or from one of the this.titleElements[] elements,
       Or use an automatically generated number.
     */
    t.headingText = t.div.title;

    /* Remove the title attribute to prevent a tooltip from appearing */
    if (this.removeTitle) { t.div.title = ''; }

    if (!t.headingText) {

      /* Title was not defined in the title of the DIV,
	 So try to get the title from an element within the DIV.
	 Go through the list of elements in this.titleElements
	 (typically heading elements ['h2','h3','h4'])
      */
      for (i2=0; i2<this.titleElements.length; i2++) {
	headingElement = t.div.getElementsByTagName(this.titleElements[i2])[0];
	if (headingElement) {
	  t.headingText = headingElement.innerHTML;
	  if (this.titleElementsStripHTML) {
	    t.headingText.replace(/<br>/gi," ");
	    t.headingText = t.headingText.replace(/<[^>]+>/g,"");
	  }
	  break;
	}
      }
    }

    if (!t.headingText) {
      /* Title was not found (or is blank) so automatically generate a
         number for the tab.
      */
      t.headingText = i + 1;
    }

    /* Create a list element for the tab */
    DOM_li = document.createElement("li");

    /* Save a reference to this list item so we can later change it to
       the "active" class */
    t.li = DOM_li;

    /* Create a link to activate the tab */
    DOM_a = document.createElement("a");
    DOM_a.appendChild(document.createTextNode(t.headingText));
    DOM_a.href = "javascript:void(null);";
    DOM_a.title = t.headingText;
    DOM_a.onclick = this.navClick;

    /* Add some properties to the link so we can identify which tab
       was clicked. Later the navClick method will need this.
    */
    DOM_a.tabber = this;
    DOM_a.tabberIndex = i;

    /* Do we need to add an id to DOM_a? */
    if (this.addLinkId && this.linkIdFormat) {

      /* Determine the id name */
      aId = this.linkIdFormat;
      aId = aId.replace(/<tabberid>/gi, this.id);
      aId = aId.replace(/<tabnumberzero>/gi, i);
      aId = aId.replace(/<tabnumberone>/gi, i+1);
      aId = aId.replace(/<tabtitle>/gi, t.headingText.replace(/[^a-zA-Z0-9\-]/gi, ''));

      DOM_a.id = aId;
    }

    /* Add the link to the list element */
    DOM_li.appendChild(DOM_a);

    /* Add the list element to the list */
    DOM_ul.appendChild(DOM_li);
  }

  /* Add the UL list to the beginning of the tabber div */
  e.insertBefore(DOM_ul, e.firstChild);

  /* Make the tabber div "live" so different CSS can be applied */
  e.className = e.className.replace(this.REclassMain, this.classMainLive);

  /* Activate the default tab, and do not call the onclick handler */
  this.tabShow(defaultTab);

  /* If the user specified an onLoad function, call it now. */
  if (typeof this.onLoad == 'function') {
    this.onLoad({tabber:this});
  }

  return this;
};


tabberObj.prototype.navClick = function(event)
{
  /* This method should only be called by the onClick event of an <A>
     element, in which case we will determine which tab was clicked by
     examining a property that we previously attached to the <A>
     element.

     Since this was triggered from an onClick event, the variable
     "this" refers to the <A> element that triggered the onClick
     event (and not to the tabberObj).

     When tabberObj was initialized, we added some extra properties
     to the <A> element, for the purpose of retrieving them now. Get
     the tabberObj object, plus the tab number that was clicked.
  */

  var
  rVal, /* Return value from the user onclick function */
  a, /* element that triggered the onclick event */
  self, /* the tabber object */
  tabberIndex, /* index of the tab that triggered the event */
  onClickArgs; /* args to send the onclick function */

  a = this;
  if (!a.tabber) { return false; }

  self = a.tabber;
  tabberIndex = a.tabberIndex;

  /* Remove focus from the link because it looks ugly.
     I don't know if this is a good idea...
  */
  a.blur();

  /* If the user specified an onClick function, call it now.
     If the function returns false then do not continue.
  */
  if (typeof self.onClick == 'function') {

    onClickArgs = {'tabber':self, 'index':tabberIndex, 'event':event};

    /* IE uses a different way to access the event object */
    if (!event) { onClickArgs.event = window.event; }

    rVal = self.onClick(onClickArgs);
    if (rVal === false) { return false; }
  }

  self.tabShow(tabberIndex);
  
  return false;
};


tabberObj.prototype.tabHideAll = function()
{
  var i; /* counter */

  /* Hide all tabs and make all navigation links inactive */
  for (i = 0; i < this.tabs.length; i++) {
    this.tabHide(i);
  }
};


tabberObj.prototype.tabHide = function(tabberIndex)
{
  var div;

  if (!this.tabs[tabberIndex]) { return false; }

  /* Hide a single tab and make its navigation link inactive */
  div = this.tabs[tabberIndex].div;

  /* Hide the tab contents by adding classTabHide to the div */
  if (!div.className.match(this.REclassTabHide)) {
    div.className += ' ' + this.classTabHide;
  }
  this.navClearActive(tabberIndex);

  return this;
};


tabberObj.prototype.tabShow = function(tabberIndex)
{
  /* Show the tabberIndex tab and hide all the other tabs */

  var div;

  if (!this.tabs[tabberIndex]) { return false; }

  /* Hide all the tabs first */
  this.tabHideAll();

  /* Get the div that holds this tab */
  div = this.tabs[tabberIndex].div;

  /* Remove classTabHide from the div */
  div.className = div.className.replace(this.REclassTabHide, '');

  /* Mark this tab navigation link as "active" */
  this.navSetActive(tabberIndex);

  /* If the user specified an onTabDisplay function, call it now. */
  if (typeof this.onTabDisplay == 'function') {
    this.onTabDisplay({'tabber':this, 'index':tabberIndex});
  }
  
  // TBMOD:
  if (typeof this.onTabShow == 'function') {
    onTabShowArgs = {'tabber':self, 'tab':div, event:window.event};
    this.onTabShow(onTabShowArgs);
  }
  return this;
};

tabberObj.prototype.navSetActive = function(tabberIndex)
{
  /* Note: this method does *not* enforce the rule
     that only one nav item can be active at a time.
  */

  /* Set classNavActive for the navigation list item */
  this.tabs[tabberIndex].li.className = this.classNavActive;

  return this;
};


tabberObj.prototype.navClearActive = function(tabberIndex)
{
  /* Note: this method does *not* enforce the rule
     that one nav should always be active.
  */

  /* Remove classNavActive from the navigation list item */
  this.tabs[tabberIndex].li.className = '';

  return this;
};


/*==================================================*/


function tabberAutomatic(tabberArgs)
{
  /* This function finds all DIV elements in the document where
     class=tabber.classMain, then converts them to use the tabber
     interface.

     tabberArgs = an object to send to "new tabber()"
  */
  var
    tempObj, /* Temporary tabber object */
    divs, /* Array of all divs on the page */
    i; /* Loop index */

  if (!tabberArgs) { tabberArgs = {}; }

  /* Create a tabber object so we can get the value of classMain */
  tempObj = new tabberObj(tabberArgs);

  /* Find all DIV elements in the document that have class=tabber */

  /* First get an array of all DIV elements and loop through them */
  divs = document.getElementsByTagName("div");
  for (i=0; i < divs.length; i++) {
    
    /* Is this DIV the correct class? */
    if (divs[i].className &&
	divs[i].className.match(tempObj.REclassMain)) {
      
      /* Now tabify the DIV */
      tabberArgs.div = divs[i];
      divs[i].tabber = new tabberObj(tabberArgs);
    }
  }
  
  return this;
}


/*==================================================*/


function tabberAutomaticOnLoad(tabberArgs)
{
  if (!tabberArgs) { tabberArgs = {}; }
  $(document).ready(function() { tabberAutomatic(tabberArgs); });

}


/*==================================================*/


/* Run tabberAutomaticOnload() unless the "manualStartup" option was specified */

if (typeof tabberOptions == 'undefined') {

    tabberAutomaticOnLoad();

} else {

  if (!tabberOptions['manualStartup']) {
    tabberAutomaticOnLoad(tabberOptions);
  }

}
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
/*==============================================
================================================
               TB MENUS!
================================================
===============================================*/


$(document).ready(function() {

	if (!document.getElementById) return; // avoid old browsers altogether
	var menus = document.getElementById('nav');
	if (!menus) return;
	var currentChild = menus.firstChild;
	var mouseout = new Function("menuTimeout = setTimeout('unhoverMenu()', 1000)");
	var hover = new Function("hoverMenu(this)");
	while (currentChild != null) {
		if (currentChild.tagName == 'LI') {
			currentChild.onmouseover = hover;
			currentChild.onmouseout = mouseout;
		}
		currentChild = currentChild.nextSibling;
	}
});

var currentSubMenu = null;
var menuTimeout = null;

function hoverMenu(elt)
{
	if (!browserSupported()) return;
	clearTimeout(menuTimeout);
	currentChild = elt.firstChild;
	while (currentChild != null) {
		if (currentChild.tagName == 'UL') {
			if (currentSubMenu == currentChild) return;
			unhoverMenu();
	
			if (!currentChild.style.top) {
				var parentUl = elt.parentNode;
				while (parentUl.tagName != 'UL') {
					parentUl = parentUl.parentNode;
				}
				currentChild.style.top = ((findPosY(elt) + elt.offsetHeight)) + 'px';
				/* currentChild.style.marginTop = '0px'; */
				currentChild.style.left = (findPosX(elt) - 1) + 'px';
			}
	
			currentChild.style.display = '';
			//shim(currentChild);
			currentSubMenu = currentChild;
			return;
		}
		currentChild = currentChild.nextSibling;
	}
	unhoverMenu();

}

function unhoverMenu()
{
	if (null === currentSubMenu) return;
   	currentSubMenu.style.display = 'none';
	unShim();
	currentSubMenu = null;
}

var iframeShim = null;

function shim(elt)
{
	version = navigator.userAgent.toLowerCase();
	if (-1 == version.indexOf('msie')) {
		// not IE -> no need for shim
		return;
	}
	version = version.substr(version.indexOf('msie')+4);
	version = version.substr(0, version.indexOf(';'));
	version = parseFloat(version);
	if (version < 5.5) return; // older than 5.5 -> shim won't work

	elt.style.zIndex = 100;
	if (iframeShim == null) {
		iframeShim = document.createElement('IFRAME');
		iframeShim.src = 'javascript:';
		iframeShim.style.position = 'absolute';
		document.body.appendChild(iframeShim);
	}
	iframeShim.style.display = 'block';
	iframeShim.style.width = elt.offsetWidth;
	iframeShim.style.height = elt.offsetHeight;
	iframeShim.style.top = elt.offsetTop;
	iframeShim.style.left = elt.offsetLeft;
	iframeShim.style.zIndex = 99; //elt.style.zIndex - 1;
}

function unShim()
{
	if (null !== iframeShim) {
		iframeShim.style.display = 'none';
	}
}

function browserSupported()
{
	version = navigator.userAgent.toLowerCase();
	if ((-1 != version.indexOf('msie')) && (-1 != version.indexOf('mac'))) {
		// ie mac not allowed
		return false;
	}
	return true;
}

function findPosY(obj)
{
	var curtop = 0;
	if (obj.offsetParent)
	{
		while (obj.offsetParent)
		{
			curtop += obj.offsetTop
			obj = obj.offsetParent;
		}
	}
	else if (obj.y)
		curtop += obj.y;
	return curtop;
}

function findPosX(obj)
{
	var curleft = 0;
	if (obj.offsetParent)
	{
		while (obj.offsetParent)
		{
			curleft += obj.offsetLeft
			obj = obj.offsetParent;
		}
	}
	else if (obj.x)
		curleft += obj.x;
	return curleft;
}

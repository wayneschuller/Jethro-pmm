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

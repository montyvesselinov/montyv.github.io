if (parent.location.href == self.location.href) {
	if (window.location.href.replace)
		window.location.replace('index.html');
	else
		window.location.href = 'index.html';
	// causes problems with back button, but works
}

function loadFrames(page1, page2) {
	eval("parent.mainFrame.location='" + page1 + "'");
	eval("parent.leftFrame.location='" + page2 + "'");
}

var blank = "main.html";

function getValue(varname) {
	var url = window.location.href;
	// First, we load the URL into a variable
	var qparts = url.split("?");
	// Next, split the url by the ?
	if (qparts.length == 0)
		return "";
	// Check that there is a querystring
	var query = qparts[1];
	// Then find the querystring, everything after the ?
	var vars = query.split("&");
	// Split the query string into variables (separates by &s)
	var value = "";
	// Initialize the value with "" as default
	for ( i = 0; i < vars.length; i++) {
		var parts = vars[i].split("=");
		if (parts[0] == varname) {
			value = parts[1];
			// Load value into variable
			break;
		}
	}
	value = unescape(value);
	// Convert escape code
	value.replace(/\+/g, " ");
	// Convert "+"s to " "s
	return value;
}

function getParam() {
	var url = window.location.href;
	// First, we load the URL into a variable
	var qparts = url.split("?");
	// Next, split the url by the ?
	if (qparts.length == 0)
		return "";
	// Check that there is a querystring
	var value = "";
	var value = qparts[1];
	// Then find the querystring, everything after the ?
	value = unescape(value);
	// Convert escape code
	value.replace(/\+/g, " ");
	// Convert "+"s to " "s
	return value;
}

function writeParam() {
	var url = window.location.href;
	// First, we load the URL into a variable
	var qparts = url.split("?");
	// Next, split the url by the ?
	if (qparts.length == 0)
		return "";
	// Check that there is a querystring
	var value = "";
	var value = qparts[1];
	// Then find the querystring, everything after the ?
	value = unescape(value);
	// Convert escape code
	value.replace(/\+/g, " ");
	// Convert "+"s to " "s
	document.write(value);
}

function fillFrame() {
	var name = getParam();
	parent.leftFrame.location.href = "sidebar.html";
	parent.mainFrame.location.href = "contact.html?" + name;
}

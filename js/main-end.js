if(parent.location.href == self.location.href)
{
	var hash = getHash()
	if(hash == "undefined")
	{
		var href = self.location.href;
		consolelog('href='+href);
		var hreffilename = href.substr(href.lastIndexOf('/') + 1)
		consolelog('hreffilename='+hreffilename);
		var hrefdot = hreffilename.split('.');
		var hash = hrefdot[0];
		consolelog('hash='+hash);
		if(hash != "sitemap" && hash != "index")
		{
			if(window.location.href.replace)
				window.location.replace('index.html#' + hash);
			else
				window.location.href = 'index.html#' + hash; // causes problems with back button, but works
		}
	}
	else
	{
		consolelog('gothash='+hash);
	}
}
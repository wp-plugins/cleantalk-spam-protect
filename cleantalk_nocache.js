function sendRequest(url,callback,postData) {
    var req = createXMLHTTPObject();
    if (!req) return;
    var method = (postData) ? "POST" : "GET";
    req.open(method,url,true);
    req.setRequestHeader('User-Agent','XMLHTTP/1.0');
    if (postData)
        req.setRequestHeader('Content-type','application/x-www-form-urlencoded');
    req.onreadystatechange = function () {
        if (req.readyState != 4) return;
        if (req.status != 200 && req.status != 304) {
//          alert('HTTP error ' + req.status);
            return;
        }
        callback(req);
    }
    if (req.readyState == 4) return;
    req.send(postData);
}

var XMLHttpFactories = [
    function () {return new XMLHttpRequest()},
    function () {return new ActiveXObject("Msxml2.XMLHTTP")},
    function () {return new ActiveXObject("Msxml3.XMLHTTP")},
    function () {return new ActiveXObject("Microsoft.XMLHTTP")}
];

function createXMLHTTPObject() {
    var xmlhttp = false;
    for (var i=0;i<XMLHttpFactories.length;i++) {
        try {
            xmlhttp = XMLHttpFactories[i]();
        }
        catch (e) {
            continue;
        }
        break;
    }
    return xmlhttp;
}

function ct_callback(req)
{
	ct_cookie=req.responseText;
	//alert('Key value: ' + ct_cookie);
	document.cookie = "ct_checkjs = " + ct_cookie + "; path=/";
	//alert('Set cookie: \n' + document.cookie);
	for(i=0;i<document.forms.length;i++)
	{
		f=document.forms[i];
		for(j=0;j<f.elements.length;j++)
		{
			e=f.elements[j];
			if(e.name.indexOf('ct_checkjs')!=-1)
			{
				e.value=ct_cookie;
				//alert('Form #' + i + ', field ' + e.name + ' = ' + ct_cookie);
			}
		}
	}
}
sendRequest(ajaxurl+'?'+Math.random(),ct_callback,'action=ct_get_cookie');
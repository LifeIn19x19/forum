var w;
var colors = "nav=339900&bg=E1FFCC"; // button, comments, toolbar

function openGOSWF(pSGF, pH){
	if (pH == null) pH = 480;
	var pW = pH*4/3; // 680;
	var lX = (screen.width - pW)/2;
  	var lY = (screen.height - pW)/2;
	w = window.open("", "newwin", "height=" + pH + ", width=" + pW + ", top=" + lY + ",left=" + lX +  ", status=yes, toolbar=no, menubar=no, scrollbars=no, resizable=yes, location=no, directories=no");
	w.document.write("<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'><head>\n");
	w.document.write("<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />\n");
	w.document.write("<title>" + pSGF + "</title>\n");
	w.document.write("</head><body bgcolor='#FFFFFF' marginWidth='0' marginHeight='0' leftMargin='0' topMargin='0'><center>\n");
	w.document.write("<object classid='clsid:d27cdb6e-ae6d-11cf-96b8-444553540000' codebase='http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0' width='100%' height='100%' id='goswf' align='middle'>\n");
	
	w.document.write("<param name='movie' value='goswf.swf' /><param name='quality' value='high' /><param name='bgcolor' value='#FFFFFF' />\n");
	w.document.write("<param name='flashVars' value='" + colors + "&url=" + pSGF + "' />\n");
	w.document.write("<embed src='goswf.swf' flashVars='" + colors + "&url=" + pSGF + "' quality='high' bgcolor='#FFFFFF' width='100%' height='100%' name='goswf' align='middle' type='application/x-shockwave-flash' pluginspage='http://www.macromedia.com/go/getflashplayer' />\n");
	w.document.write("</object></body></html>");
	w.document.close();
	
	w.onFocus = swfFocus;
	w.focus();
}

function swfFocus(){
	w.document.goswf.focus();
}
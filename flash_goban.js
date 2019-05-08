if ( typeof(fg_n) == "undefined" ){
	fg_n = 0;
}
function placeGoban( pId, pSgf, pSkin, pw, ph, pPath )
{
	if ( !pId.length >0 )
	{
		pId = "fgoban"+fg_n++;
	}
		simpleGoban( pId, pSgf, pSkin, pw, ph, pPath);
}

function simpleGoban( pId, pSgf, pSkin, pw, ph, pPath )
{
var zoom =  Math.min(1.5, Math.min(screen.width / 1024, screen.height / 768));
pw *= zoom;
ph *= zoom;
if (pSkin!="")
	pSkin="&skin="+pSkin;

	document.write( "\
\<OBJECT classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\"\n\r\
 codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0\"\n\r\
	ID="+ pId +"\n\r\
	WIDTH="+ pw +"\n\r\
	HEIGHT="+ ph +">\n\r\
 	\<PARAM NAME=movie VALUE=\""+pPath+"sgf_view.swf\"\> \n\r\
	\<PARAM NAME=quality VALUE=high\> \n\r\
	\<PARAM NAME=bgcolor VALUE=#FFFFFF\> \n\r\
	\<PARAM NAME=FlashVars VALUE=\"sgf="+ pSgf + pSkin +"\"\> \n\r\
	\<param name=\"salign\" value=\"lt\" /\>\n\r\
	\<param name=\"scale\" value=\"exactfit\" /\>\n\r\
<EMBED src=\""+pPath+"sgf_view.swf\"\n\r\
	quality=high \n\r\
	bgcolor=#FFFFFF \n\r\
	WIDTH="+ pw +"\n\r\
	HEIGHT="+ ph +"\n\r\
	FlashVars=\"sgf=" + pSgf + pSkin +"\"\n\r\
	swLiveConnect=false \n\r\
	NAME="+ pId +"\n\r\
	salign=\"lt\"\n\r\
	scale=\"exactfit\"\n\r\
	TYPE=\"application/x-shockwave-flash\"\n\r\PLUGINSPAGE=\"http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash\">\n\r\
</EMBED>\n\r\
\</OBJECT\>");
}

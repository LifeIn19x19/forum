<!--
var isIE = navigator.appName.indexOf("Microsoft") != -1;
var msgNum = 1;

// 	������ ��� �����

function sendGobanFile( name )
{
	gobanCommand( "START", name );
}

// 	������ ����� �����

function setGobanStyle( id )
{
	gobanCommand( "SCHEME", id );
}






//	���������� � ������� ���������:

function gobanCommand( id, id1 ) {

var SGFViewObj = isIE ? SGFView : document.SGFView;

	SGFViewObj.SetVariable("param1_" + msgNum,id1);
	SGFViewObj.SetVariable("msg" + msgNum,id);

	msgNum = (id=="") ? msgNum : msgNum+1;
}

function onGobanGotSGF() {}
//-->

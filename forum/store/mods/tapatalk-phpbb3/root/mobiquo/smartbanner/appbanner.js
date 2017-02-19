
// make sure all variables are defined
if (typeof(functionCallAfterWindowLoad) == "undefined") var functionCallAfterWindowLoad = false;
if (typeof(is_mobile_skin)  == "undefined") var is_mobile_skin = false;
if (typeof(app_ios_id)      == "undefined") var app_ios_id = '';
if (typeof(app_android_id)  == "undefined") var app_android_id = '';
if (typeof(app_kindle_url)  == "undefined") var app_kindle_url = '';
if (typeof(app_board_url)   == "undefined") var app_board_url = '';
if (typeof(app_forum_name)     == "undefined" || !app_forum_name) var app_forum_name = "this forum";
if (typeof(app_banner_message) == "undefined" || !app_banner_message) var app_banner_message = "Follow {your_forum_name} <br /> with {app_name} for [os_platform]";
if (typeof(app_location_url)   == "undefined" || !app_location_url) var app_location_url = "tapatalk://";
var app_location_url_byo = app_location_url.replace('tapatalk://', 'tapatalk-byo://');

// set default iOS app for native smart banner
var app_ios_id_default = '307880732';      // Tapatalk Free, 585178888 for Tapatalk Pro
var app_ios_hd_id_default = '307880732';   // Tapatalk Free, 481579541 for Tapatalk HD

// Support native iOS Smartbanner
var native_ios_banner = false;
if (app_ios_id != '-1' && navigator.userAgent.match(/Safari/i) != null &&
    (navigator.userAgent.match(/CriOS/i) == null && window.Number(navigator.userAgent.substr(navigator.userAgent.indexOf('OS ') + 3, 3).replace('_', '.')) >= 6))
{
    banner_location_url = app_ios_id ? app_location_url_byo : app_location_url;
    
    if (navigator.userAgent.match(/iPad/i) != null)
    {
        document.write('<meta name="apple-itunes-app" content="app-id='+(app_ios_id ? app_ios_id : app_ios_hd_id_default)+', app-argument='+banner_location_url+', affiliate-data=partnerId=30&siteID=w5J2vu1UnxA" />');
        native_ios_banner = true;
    }
    else if (navigator.userAgent.match(/iPod|iPhone/i) != null)
    {
        document.write('<meta name="apple-itunes-app" content="app-id='+(app_ios_id ? app_ios_id : app_ios_id_default)+', app-argument='+banner_location_url+', affiliate-data=partnerId=30&siteID=w5J2vu1UnxA" />');
        native_ios_banner = true;
    }
}

// initialize app download url
var app_install_url = 'http://tapatalk.com/m/?id=6';
if (app_ios_id)     app_install_url = app_install_url+'&app_ios_id='+app_ios_id;
if (app_android_id) app_install_url = app_install_url+'&app_android_id='+app_android_id;
if (app_kindle_url) app_install_url = app_install_url+'&app_kindle_url='+app_kindle_url;
if (app_board_url)  app_install_url = app_install_url+'&referer='+app_board_url;


// for those forum system which can not add js in html body
if (functionCallAfterWindowLoad) addWindowOnload(tapatalkDetect)

var bannerLoaded = false

function tapatalkDetect()
{
    var standalone = navigator.standalone // Check if it's already a standalone web app or running within a web ui view of an app (not mobile safari)
    
    // work only when browser support cookie
    if (!navigator.cookieEnabled 
        || bannerLoaded
        || standalone
        || document.cookie.indexOf("banner-closed=true") >= 0 
        || native_ios_banner)
        return
    
    bannerLoaded = true
    
    app_banner_message = app_banner_message.replace(/\{your_forum_name\}/gi, app_forum_name);
    app_banner_message = app_banner_message.replace(/\{app_name\}/gi, "Tapatalk");
    
    if (navigator.userAgent.match(/iPhone|iPod/i)) {
        if (app_ios_id == '-1') return;
        app_banner_message = app_banner_message.replace(/\[os_platform\]/gi, 'iPhone');
        banner_location_url = app_ios_id ? app_location_url_byo : app_location_url;
    }
    else if (navigator.userAgent.match(/iPad/i)) {
        if (app_ios_id == '-1') return;
        app_banner_message = app_banner_message.replace(/\[os_platform\]/gi, 'iPad');
        banner_location_url = app_ios_id ? app_location_url_byo : app_location_url;
    }
    else if (navigator.userAgent.match(/Silk|KFOT|KFTT|KFJWI|KFJWA/)) {
        if (app_kindle_url == '-1') return;
        app_banner_message = app_banner_message.replace(/\[os_platform\]/gi, 'Kindle');
        banner_location_url = app_kindle_url ? app_location_url_byo : app_location_url;
    }
    else if (navigator.userAgent.match(/Android/i)) {
        if (app_android_id == '-1') return;
        app_banner_message = app_banner_message.replace(/\[os_platform\]/gi, 'Android');
        banner_location_url = app_android_id ? app_location_url_byo : app_location_url;
    }
    else if (navigator.userAgent.match(/IEMobile|Windows Phone/i)) {
        if (app_ios_id || app_android_id || app_kindle_url) return;
        app_banner_message = app_banner_message.replace(/\[os_platform\]/gi, 'Windows Phone');
        banner_location_url = app_location_url;
    }
    /*
    else if (navigator.userAgent.match(/BlackBerry/i)) {
        app_banner_message = app_banner_message.replace(/\[os_platform\]/gi, 'BlackBerry');
        banner_location_url = app_location_url;
    }
    */
    else
        return
    
    
    htmlElement = document.getElementsByTagName("html")[0]
    origHtmlMargin = parseFloat(htmlElement.style.marginTop)
    if ( isNaN(origHtmlMargin)) origHtmlMargin = 0
    
    var bannerScale = document.body.clientWidth / window.screen.width
    
    if (bannerScale < 1 || (is_mobile_skin && navigator.userAgent.match(/mobile/i))) bannerScale = 1;
        
    // mobile portrait mode may need bigger scale
    if (window.innerWidth < window.innerHeight)
    {
        if (bannerScale < 2 && !is_mobile_skin && document.body.clientWidth > 600) {
            bannerScale = 2
        } else if (bannerScale > 2.8) {
            bannerScale = 2.8
        }
    }
    else
    {
        if (navigator.userAgent.match(/mobile/i) && bannerScale < 1.5 && !is_mobile_skin && document.body.clientWidth > 600) {
            bannerScale = 1.5
        } else if (bannerScale > 2) {
            bannerScale = 2
        }
    }
    
    
    bodyItem = document.body
    appBanner = document.createElement("div")
    appBanner.id = "mobile_banner"
    appBanner.className = "mobile_banner banner_format_handset banner_device_android banner_theme_light mobile_banner_animate"
    appBanner.innerHTML = 
                    '<div class="mobile_banner_inner">'+
                        '<span class="mobile_banner_icon"></span>'+
                        '<div class="mobile_banner_body">'+
                            '<h3 class="mobile_banner_heading">'+app_banner_message+'</h3>'+
                        '</div>'+
                        '<div class="mobile_banner_controls">'+
                            '<a class="mobile_banner_button chrome white mobile_banner_open" href="'+banner_location_url+'" id="mobile_banner_open">'+'Open in app'+'</a>'+
                            '<a class="mobile_banner_button chrome blue mobile_banner_install" href="'+app_install_url+'" id="mobile_banner_install">'+'Install'+'</a>'+
                            '<a class="mobile_banner_close" href="#" onclick="closeBanner()" id="mobile_banner_close">x</a>'+
                        '</div>'+
                    '</div>'
    bodyItem.insertBefore(appBanner, bodyItem.firstChild)
    
    if (bannerScale > 1) {
        appBanner.style.fontSize = (8*bannerScale)+"px"
    }
    
    bannerHeight = getWH(appBanner, 'height', true)
    bannerTop = (origHtmlMargin+bannerHeight)+"px"
    htmlElement.style.marginTop = bannerTop
    
    if (getComputedStyle(bodyItem, null).position !== 'static')
        appBanner.style.top = '-'+bannerTop
    
    addWindowOnload(resetBannerTop)
}

function addWindowOnload(func)
{
    if (typeof window.onload != 'function') {
        window.onload = func;
    } else {
        var oldonload = window.onload;
        window.onload = function() {
            oldonload();
            func();
        }
    }
}

function resetBannerTop()
{
    if (getComputedStyle(bodyItem, null).position !== 'static' || document.getElementById('google_translate_element'))
        appBanner.style.top = '-'+bannerTop
}

function closeBanner()
{
    bodyItem.removeChild( appBanner )
    htmlElement.style.marginTop = origHtmlMargin+"px"
    setBannerCookies('banner-closed', 'true', 90)
}

function setBannerCookies(name, value, exdays)
{
    var exdate = new Date();
    exdate.setDate(exdate.getDate()+exdays);
    value=escape(value)+((exdays==null)?'':'; expires='+exdate.toUTCString());
    document.cookie=name+'='+value+'; path=/;';
}


/* to get element outer height */

var defView = document.defaultView;

var getStyle = defView && defView.getComputedStyle ?
    function( elem ) {
      return defView.getComputedStyle( elem, null );
    }
    :
    function( elem ) {
      return elem.currentStyle;
    };

function hackPercentMargin( elem, computedStyle, marginValue ) {
    if ( marginValue.indexOf('%') === -1 ) {
        return marginValue;
    }

    var elemStyle = elem.style,
        originalWidth = elemStyle.width,
        ret;

    // get measure by setting it on elem's width
    elemStyle.width = marginValue;
    ret = computedStyle.width;
    elemStyle.width = originalWidth;

    return ret;
}

function getWH( elem, measure, isOuter )
{
    // Start with offset property
    var isWidth = measure !== 'height',
        val = isWidth ? elem.offsetWidth : elem.offsetHeight,
        dirA = isWidth ? 'Left' : 'Top',
        dirB = isWidth ? 'Right' : 'Bottom',
        computedStyle = getStyle( elem ),
        paddingA = parseFloat( computedStyle[ 'padding' + dirA ] ) || 0,
        paddingB = parseFloat( computedStyle[ 'padding' + dirB ] ) || 0,
        borderA = parseFloat( computedStyle[ 'border' + dirA + 'Width' ] ) || 0,
        borderB = parseFloat( computedStyle[ 'border' + dirB + 'Width' ] ) || 0,
        computedMarginA = computedStyle[ 'margin' + dirA ],
        computedMarginB = computedStyle[ 'margin' + dirB ],
        marginA, marginB;

    var tmpDiv = document.createElement('div');
    tmpDiv.style.marginTop = '1%';
    bodyItem.appendChild( tmpDiv );
    var supportsPercentMargin = getStyle( tmpDiv ).marginTop !== '1%';
    bodyItem.removeChild( tmpDiv );

    if ( !supportsPercentMargin ) {
        computedMarginA = hackPercentMargin( elem, computedStyle, computedMarginA );
        computedMarginB = hackPercentMargin( elem, computedStyle, computedMarginB );
    }

    marginA = parseFloat( computedMarginA ) || 0;
    marginB = parseFloat( computedMarginB ) || 0;

    if ( val > 0 ) {

        if ( isOuter ) {
            // outerWidth, outerHeight, add margin
            val += marginA + marginB;
        } else {
            // like getting width() or height(), no padding or border
            val -= paddingA + paddingB + borderA + borderB;
        }

    } else {

        // Fall back to computed then uncomputed css if necessary
        val = computedStyle[ measure ];
        if ( val < 0 || val === null ) {
            val = elem.style[ measure ] || 0;
        }
        // Normalize "", auto, and prepare for extra
        val = parseFloat( val ) || 0;
        
        if ( isOuter ) {
            // Add padding, border, margin
            val += paddingA + paddingB + marginA + marginB + borderA + borderB;
        }
    }

    return val;
}

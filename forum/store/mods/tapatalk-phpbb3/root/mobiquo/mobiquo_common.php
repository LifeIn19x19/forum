<?php
/**
*
* @copyright (c) 2009, 2010, 2011 Quoord Systems Limited
* @license http://opensource.org/licenses/gpl-2.0.php GNU Public License (GPLv2)
*
*/

defined('IN_MOBIQUO') or exit;

function process_page($start_num, $end)
{
    global $start, $limit, $page;
    
    $start = intval($start_num);
    $end = intval($end);
    $start = empty($start) ? 0 : max($start, 0);
    $end = (empty($end) || $end < $start) ? ($start + 19) : max($end, $start);
    if ($end - $start >= 50) {
        $end = $start + 49;
    }
    $limit = $end - $start + 1;
    $page = intval($start/$limit) + 1;
    
    return array($start, $limit, $page);
}

function xmlrpc_shutdown()
{
    if (function_exists('error_get_last'))
    {
        $error = error_get_last();
    
        if(!empty($error)){
            switch($error['type']){
                case E_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                case E_PARSE:
                    $xmlrpcresp = xmlresperror("Server error occurred: '{$error['message']} (".basename($error['file']).":{$error['line']})'");
                    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . $xmlrpcresp->serialize('UTF-8');
                    break;
            }
        }
    }
}

function xmlresperror($error_message)
{
    $result = new xmlrpcval(array(
        'result'        => new xmlrpcval(false, 'boolean'),
        'result_text'   => new xmlrpcval(basic_clean($error_message), 'base64')
    ), 'struct');
    
    return new xmlrpcresp($result);
}

function xmlrpc_error_handler($errno, $msg_text, $errfile, $errline)
{
    global $auth, $user, $msg_long_text;

    // Do not display notices if we suppress them via @
    if (MOBIQUO_DEBUG == 0 && $errno != E_USER_ERROR && $errno != E_USER_WARNING && $errno != E_USER_NOTICE)
    {
        return;
    }
    /*if(strpos($errfile, 'session.php') !== false)
    {
        return ;
    }*/
    if ($msg_text == 'NO_SEARCH_RESULTS')
    {
        $response = search_func();
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".$response->serialize('UTF-8');
        exit;
    }

    // Message handler is stripping text. In case we need it, we are possible to define long text...
    if (isset($msg_long_text) && $msg_long_text && !$msg_text)
    {
        $msg_text = $msg_long_text;
    }

    if (!defined('E_DEPRECATED'))
    {
        define('E_DEPRECATED', 8192);
    }

    switch ($errno)
    {
        case E_NOTICE:
        case E_WARNING:

            // Check the error reporting level and return if the error level does not match
            // If DEBUG is defined the default level is E_ALL
            if (MOBIQUO_DEBUG == 0)
            {
                return;
            }

            if (strpos($errfile, 'cache') === false && strpos($errfile, 'template.') === false)
            {
                $errfile = phpbb_filter_root_path($errfile);
                $msg_text = phpbb_filter_root_path($msg_text);
                $error_name = ($errno === E_WARNING) ? 'PHP Warning' : 'PHP Notice';
                echo '[phpBB Debug] ' . $error_name . ': in file ' . $errfile . ' on line ' . $errline . ': ' . $msg_text . "\n";
            }

            return;

        break;

        case E_USER_ERROR:

            if (!empty($user) && !empty($user->lang))
            {
                $msg_text = (!empty($user->lang[$msg_text])) ? $user->lang[$msg_text] : $msg_text;
            }
            garbage_collection();
        break;

        case E_USER_WARNING:
        case E_USER_NOTICE:

            define('IN_ERROR_HANDLER', true);

            if (empty($user->data))
            {
                $user->session_begin();
            }

            // We re-init the auth array to get correct results on login/logout
            $auth->acl($user->data);

            if (empty($user->lang))
            {
                $user->setup();
            }

            if ($msg_text == 'ERROR_NO_ATTACHMENT' || $msg_text == 'NO_FORUM' || $msg_text == 'NO_TOPIC' || $msg_text == 'NO_USER')
            {
                //send_status_line(404, 'Not Found');
            }

            $msg_text = (!empty($user->lang[$msg_text])) ? $user->lang[$msg_text] : $msg_text;
        break;

        // PHP4 compatibility
        case E_DEPRECATED:
            return true;
        break;
    }
    
    if (in_array($errno, array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE)))
    {
        $result = check_error_status($msg_text);
        if (MOBIQUO_DEBUG == -1) $msg_text .= " > $errfile, $errline";
        
        $response = new xmlrpcresp(new xmlrpcval(array(
            'result'        => new xmlrpcval($result, 'boolean'),
            'result_text'   => new xmlrpcval(basic_clean($msg_text), 'base64'),
        ),'struct'));
        
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".$response->serialize('UTF-8');
        exit;
    }
    
    // If we notice an error not handled here we pass this back to PHP by returning false
    // This may not work for all php versions
    return false;
}

function basic_clean($str)
{
    $str = preg_replace('/<br\s*\/?>/si', "\n", $str);
    $str = strip_tags($str);
    $str = trim($str);
    return html_entity_decode($str, ENT_QUOTES, 'UTF-8');
}


function mobi_parse_requrest()
{
    global $request_method, $request_params, $params_num;
    
    $ver = phpversion();
    if ($ver[0] >= 5) {
        $data = file_get_contents('php://input');
    } else {
        $data = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : '';
    }
    
    if (count($_SERVER) == 0)
    {
        $r = new xmlrpcresp('', 15, 'XML-RPC: '.__METHOD__.': cannot parse request headers as $_SERVER is not populated');
        echo $r->serialize('UTF-8');
        exit;
    }
    
    if(isset($_SERVER['HTTP_CONTENT_ENCODING'])) {
        $content_encoding = str_replace('x-', '', $_SERVER['HTTP_CONTENT_ENCODING']);
    } else {
        $content_encoding = '';
    }
    
    if($content_encoding != '' && strlen($data)) {
        if($content_encoding == 'deflate' || $content_encoding == 'gzip') {
            // if decoding works, use it. else assume data wasn't gzencoded
            if(function_exists('gzinflate')) {
                if ($content_encoding == 'deflate' && $degzdata = @gzuncompress($data)) {
                    $data = $degzdata;
                } elseif ($degzdata = @gzinflate(substr($data, 10))) {
                    $data = $degzdata;
                }
            } else {
                $r = new xmlrpcresp('', 106, 'Received from client compressed HTTP request and cannot decompress');
                echo $r->serialize('UTF-8');
                exit;
            }
        }
    }
    if(empty($data)) return false;
    $parsers = php_xmlrpc_decode_xml($data);
    $request_method = $parsers->methodname;
    $request_params = php_xmlrpc_decode(new xmlrpcval($parsers->params, 'array'));
    $params_num = count($request_params);
}

function get_short_content($post_id, $length = 200)
{
    global $db;
    
    $post_id = intval($post_id);
    if (empty($post_id)) return '';
    
    $sql = 'SELECT post_text
            FROM ' . POSTS_TABLE . '
            WHERE post_id = ' . $post_id;
    $result = $db->sql_query($sql);
    $post_text = $db->sql_fetchfield('post_text');
    $db->sql_freeresult($result);
    
    return process_short_content($post_text, 200);
}

function process_short_content($post_text, $length = 200)
{
    $post_text = censor_text($post_text);
    $array_reg = array(
        array('reg' => '/\[quote(.*?)\](.*?)\[\/quote(.*?)\]/si','replace' => '[quote]'),
        array('reg' => '/\[code(.*?)\](.*?)\[\/code(.*?)\]/si','replace' => ''),
        array('reg' => '/\[url=(.*?):(.*?)\](.*?)\[\/url(.*?)\]/sei','replace' => "mobi_url_convert('$1','$3')"),
        array('reg' => '/\[video(.*?)\](.*?)\[\/video(.*?)\]/si','replace' => '[V]'),
        array('reg' => '/\[attachment(.*?)\](.*?)\[\/attachment(.*?)\]/si','replace' => '[attach]'),
        array('reg' => '/\[url.*?\].*?\[\/url.*?\]/','replace' => '[url]'),
        array('reg' => '/\[img.*?\].*?\[\/img.*?\]/','replace' => '[img]'),
        array('reg' => '/[\n\r\t]+/','replace' => ' '),
        array('reg' => '/\[flash(.*?)\](.*?)\[\/flash(.*?)\]/si','replace' => '[V]'),
    );
    //echo $post_text;die();
    foreach ($array_reg as $arr)
    {
        $post_text = preg_replace($arr['reg'], $arr['replace'], $post_text);
    }
    strip_bbcode($post_text);
    $post_text = html_entity_decode($post_text, ENT_QUOTES, 'UTF-8');
    $post_text = function_exists('mb_substr') ? mb_substr($post_text, 0, $length) : substr($post_text, 0, $length);
    $post_text = trim(strip_tags($post_text));
    $post_text = preg_replace('/\\s+|\\r|\\n/', ' ', $post_text);
    return $post_text;
}

function mobi_url_convert($a,$b)
{
    if(html_entity_decode(trim($a)) == trim($b))
    {
        return '[url]';
    }
    else 
    {
        return $b;
    }
}
function post_html_clean($str)
{
    
    global $phpbb_root_path, $phpbb_home, $mobiquo_config,$config;
    
    $search = array(
        "/<strong>(.*?)<\/strong>/si",
        "/<em>(.*?)<\/em>/si",
        "/<img .*?src=\"(.*?)\".*?\/?>/si",
        "/<a .*?href=\"(.*?)\"(.*?)?>(.*?)<\/a>/sei",
        "/<br\s*\/?>|<\/cite>|<\/dt>|<\/dd>/si",
        "/<object .*?data=\"(http:\/\/www\.youtube\.com\/.*?)\" .*?>.*?<\/object>/si",
        "/<object .*?data=\"(http:\/\/video\.google\.com\/.*?)\" .*?>.*?<\/object>/si",
        "/<iframe .*?src=\"(http.*?)\" .*?>.*?<\/iframe>/si",
        "/<script( [^>]*)?>([^<]*?)<\/script>/si",
        "/<param name=\"movie\" value=\"(.*?)\" \/>/si"
    );
    
    $replace = array(
        '[b]$1[/b]',
        '[i]$1[/i]',
        '[img]$1[/img]',
        "'[url='.url_encode('$1').']$3[/url]'",
        "\n",
        '[url=$1]YouTube Video[/url]',
        '[url=$1]Google Video[/url]',
        '[url=$1]$1[/url]',
        '',
        '[url=$1]Flash Video[/url]',
    );
    
    $str = preg_replace('/\n|\r/si', '', $str);
    $str = preg_replace('/>\s+</si', '><', $str);
    // remove smile
    $str = preg_replace('/<img [^>]*?src=\"[^"]*?images\/smilies\/[^"]*?\"[^>]*?alt=\"([^"]*?)\"[^>]*?\/?>/', '$1', $str);
    $str = preg_replace('/<img [^>]*?alt=\"([^"]*?)\"[^>]*?src=\"[^"]*?images\/smilies\/[^"]*?\"[^>]*?\/?>/', '$1', $str);

    $str = preg_replace('/<null.*?\/>/', '', $str);
   
    $str = preg_replace($search, $replace, $str);
   
    $str = strip_tags($str);
    $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    
    // remove attach icon image
    $str = preg_replace('/\[img\][^\[\]]+icon_topic_attach\.gif\[\/img\]/si', '', $str);
    
    // change relative path to absolute URL and encode url
    $str = preg_replace('/\[img\](.*?)\[\/img\]/sei', "'[img]'.url_encode('$1').'[/img]'", $str);
    
    $str = preg_replace('/\[\/img\]\s*/si', "[/img]\n", $str);
    
    $str = preg_replace('/\[\/img\]\s+\[img\]/si', '[/img][img]', $str);
    
    // remove link on img
    //$str = preg_replace('/\[url=[^\]]*?\]\s*(\[img\].*?\[\/img\])\s*\[\/url\]/si', '$1', $str);
    
    // change url to image resource to img bbcode
    $str = preg_replace('/\[url\](http[^\[\]]+\.(jpg|png|bmp|gif))\[\/url\]/si', '[img]$1[/img]', $str);
    $str = preg_replace('/\[url=(http[^\]]+\.(jpg|png|bmp|gif))\]([^\[\]]+)\[\/url\]/si', '[img]$1[/img]', $str);
    
    // cut quote content to 100 charactors
    if (isset($mobiquo_config['shorten_quote']) && $mobiquo_config['shorten_quote'])
    {
        $str = cut_quote($str, 100);
    }
    
    return parse_bbcode($str);
}

function parse_bbcode($str)
{
    global $config;
    $search = array(
        '#\[(b)\](.*?)\[/b\]#si',
        '#\[(u)\](.*?)\[/u\]#si',
        '#\[(i)\](.*?)\[/i\]#si',
        '#\[color=(\#[\da-fA-F]{3}|\#[\da-fA-F]{6}|[A-Za-z]{1,20}|rgb\(\d{1,3}, ?\d{1,3}, ?\d{1,3}\))\](.*?)\[/color\]#sie',
    );
    
    if ($GLOBALS['return_html']) {
        $str = htmlspecialchars($str, ENT_NOQUOTES);
        $replace = array(
            '<$1>$2</$1>',
            '<$1>$2</$1>',
            '<$1>$2</$1>',
            "mobi_color_convert('$1', '$2')",
        );
        $str = str_replace("\n", '<br />', $str);
    } else {
        $replace = array('$2', '$2', '$2', "'$2'");
    }
    
    $str = preg_replace($search, $replace, $str);
    if(!empty($config['tapatalk_custom_replace']))
    {
        $replace_arr = explode("\n", $config['tapatalk_custom_replace']);
        foreach ($replace_arr as $replace)
        {
            preg_match('/^\s*(\'|")((\#|\/|\!).+\3[ismexuADUX]*?)\1\s*,\s*(\'|")(.*?)\4\s*$/', $replace,$matches);
            if(count($matches) == 6)
            {
                $temp_str = $str;
                $str = @preg_replace($matches[2], $matches[5], $str);
                if(empty($str))
                {
                    $str = $temp_str;
                }
            }   
        }
    }
   
    return $str;
}

function mobi_color_convert($color, $str)
{
    static $colorlist;
    
    if (preg_match('/#[\da-fA-F]{6}/is', $color))
    {
        if (!$colorlist)
        {
            $colorlist = array(
                '#000000' => 'Black',             '#708090' => 'SlateGray',       '#C71585' => 'MediumVioletRed', '#FF4500' => 'OrangeRed',
                '#000080' => 'Navy',              '#778899' => 'LightSlateGrey',  '#CD5C5C' => 'IndianRed',       '#FF6347' => 'Tomato',
                '#00008B' => 'DarkBlue',          '#778899' => 'LightSlateGray',  '#CD853F' => 'Peru',            '#FF69B4' => 'HotPink',
                '#0000CD' => 'MediumBlue',        '#7B68EE' => 'MediumSlateBlue', '#D2691E' => 'Chocolate',       '#FF7F50' => 'Coral',
                '#0000FF' => 'Blue',              '#7CFC00' => 'LawnGreen',       '#D2B48C' => 'Tan',             '#FF8C00' => 'Darkorange',
                '#006400' => 'DarkGreen',         '#7FFF00' => 'Chartreuse',      '#D3D3D3' => 'LightGrey',       '#FFA07A' => 'LightSalmon',
                '#008000' => 'Green',             '#7FFFD4' => 'Aquamarine',      '#D3D3D3' => 'LightGray',       '#FFA500' => 'Orange',
                '#008080' => 'Teal',              '#800000' => 'Maroon',          '#D87093' => 'PaleVioletRed',   '#FFB6C1' => 'LightPink',
                '#008B8B' => 'DarkCyan',          '#800080' => 'Purple',          '#D8BFD8' => 'Thistle',         '#FFC0CB' => 'Pink',
                '#00BFFF' => 'DeepSkyBlue',       '#808000' => 'Olive',           '#DA70D6' => 'Orchid',          '#FFD700' => 'Gold',
                '#00CED1' => 'DarkTurquoise',     '#808080' => 'Grey',            '#DAA520' => 'GoldenRod',       '#FFDAB9' => 'PeachPuff',
                '#00FA9A' => 'MediumSpringGreen', '#808080' => 'Gray',            '#DC143C' => 'Crimson',         '#FFDEAD' => 'NavajoWhite',
                '#00FF00' => 'Lime',              '#87CEEB' => 'SkyBlue',         '#DCDCDC' => 'Gainsboro',       '#FFE4B5' => 'Moccasin',
                '#00FF7F' => 'SpringGreen',       '#87CEFA' => 'LightSkyBlue',    '#DDA0DD' => 'Plum',            '#FFE4C4' => 'Bisque',
                '#00FFFF' => 'Aqua',              '#8A2BE2' => 'BlueViolet',      '#DEB887' => 'BurlyWood',       '#FFE4E1' => 'MistyRose',
                '#00FFFF' => 'Cyan',              '#8B0000' => 'DarkRed',         '#E0FFFF' => 'LightCyan',       '#FFEBCD' => 'BlanchedAlmond',
                '#191970' => 'MidnightBlue',      '#8B008B' => 'DarkMagenta',     '#E6E6FA' => 'Lavender',        '#FFEFD5' => 'PapayaWhip',
                '#1E90FF' => 'DodgerBlue',        '#8B4513' => 'SaddleBrown',     '#E9967A' => 'DarkSalmon',      '#FFF0F5' => 'LavenderBlush',
                '#20B2AA' => 'LightSeaGreen',     '#8FBC8F' => 'DarkSeaGreen',    '#EE82EE' => 'Violet',          '#FFF5EE' => 'SeaShell',
                '#228B22' => 'ForestGreen',       '#90EE90' => 'LightGreen',      '#EEE8AA' => 'PaleGoldenRod',   '#FFF8DC' => 'Cornsilk',
                '#2E8B57' => 'SeaGreen',          '#9370D8' => 'MediumPurple',    '#F08080' => 'LightCoral',      '#FFFACD' => 'LemonChiffon',
                '#2F4F4F' => 'DarkSlateGrey',     '#9400D3' => 'DarkViolet',      '#F0E68C' => 'Khaki',           '#FFFAF0' => 'FloralWhite',
                '#2F4F4F' => 'DarkSlateGray',     '#98FB98' => 'PaleGreen',       '#F0F8FF' => 'AliceBlue',       '#FFFAFA' => 'Snow',
                '#32CD32' => 'LimeGreen',         '#9932CC' => 'DarkOrchid',      '#F0FFF0' => 'HoneyDew',        '#FFFF00' => 'Yellow',
                '#3CB371' => 'MediumSeaGreen',    '#9ACD32' => 'YellowGreen',     '#F0FFFF' => 'Azure',           '#FFFFE0' => 'LightYellow',
                '#40E0D0' => 'Turquoise',         '#A0522D' => 'Sienna',          '#F4A460' => 'SandyBrown',      '#FFFFF0' => 'Ivory',
                '#4169E1' => 'RoyalBlue',         '#A52A2A' => 'Brown',           '#F5DEB3' => 'Wheat',           '#FFFFFF' => 'White',
                '#4682B4' => 'SteelBlue',         '#A9A9A9' => 'DarkGrey',        '#F5F5DC' => 'Beige',
                '#483D8B' => 'DarkSlateBlue',     '#A9A9A9' => 'DarkGray',        '#F5F5F5' => 'WhiteSmoke',
                '#48D1CC' => 'MediumTurquoise',   '#ADD8E6' => 'LightBlue',       '#F5FFFA' => 'MintCream',
                '#4B0082' => 'Indigo',            '#ADFF2F' => 'GreenYellow',     '#F8F8FF' => 'GhostWhite',
                '#556B2F' => 'DarkOliveGreen',    '#AFEEEE' => 'PaleTurquoise',   '#FA8072' => 'Salmon',
                '#5F9EA0' => 'CadetBlue',         '#B0C4DE' => 'LightSteelBlue',  '#FAEBD7' => 'AntiqueWhite',
                '#6495ED' => 'CornflowerBlue',    '#B0E0E6' => 'PowderBlue',      '#FAF0E6' => 'Linen',
                '#66CDAA' => 'MediumAquaMarine',  '#B22222' => 'FireBrick',       '#FAFAD2' => 'LightGoldenRodYellow',
                '#696969' => 'DimGrey',           '#B8860B' => 'DarkGoldenRod',   '#FDF5E6' => 'OldLace',
                '#696969' => 'DimGray',           '#BA55D3' => 'MediumOrchid',    '#FF0000' => 'Red',
                '#6A5ACD' => 'SlateBlue',         '#BC8F8F' => 'RosyBrown',       '#FF00FF' => 'Fuchsia',
                '#6B8E23' => 'OliveDrab',         '#BDB76B' => 'DarkKhaki',       '#FF00FF' => 'Magenta',
                '#708090' => 'SlateGrey',         '#C0C0C0' => 'Silver',          '#FF1493' => 'DeepPink',
            );
        }
        
        if (isset($colorlist[strtoupper($color)])) $color = $colorlist[strtoupper($color)];
    }
    
    return "<font color=\"$color\">$str</font>";
}


function parse_quote($str)
{
    $blocks = preg_split('/(<blockquote.*?>|<\/blockquote>)/i', $str, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    
    $quote_level = 0;
    $message = '';
        
    foreach($blocks as $block)
    {
        if (preg_match('/<blockquote.*?>/i', $block)) {
            if ($quote_level == 0) $message .= '[quote]';
            $quote_level++;
        } else if (preg_match('/<\/blockquote>/i', $block)) {
            if ($quote_level <= 1) $message .= '[/quote]';
            if ($quote_level >= 1) {
                $quote_level--;
                $message .= "\n";
            }
        } else {
            if ($quote_level <= 1) $message .= $block;
        }
    }
    
    return $message;
}

function process_bbcode($message, $uid)
{
    global $user;
    
    // process bbcode: code
    $message = str_replace('[code:'.$uid.']', '[quote]', $message);
    $message = str_replace('[/code:'.$uid.']', '[/quote]', $message);
    
    // process bbcode: quote
    $quote_wrote_string = $user->lang['WROTE'];
    $message = str_replace('[/quote:'.$uid.']', '[/quote]', $message);
   	$message =  preg_replace_callback(
   		'/\[quote(?:=&quot;(.*?)&quot;)?:'.$uid.'\]/is', 
   		create_function('$matches','
   			$userid = get_user_id_by_name($matches[1]);
   			return "[quote uid=".$userid." name=\"".$matches[1]."\" ]";'
   		), 
   		$message
   	);
    //$message = preg_replace('/\[quote(?:=&quot;(.*?)&quot;)?:'.$uid.'\]/ise', "'[quote uid=$uid name=\"$1\"]' . ('$1' ? '$1' . ' $quote_wrote_string:\n' : '\n')", $message);
    $blocks = preg_split('/(\[\/?quote\])/i', $message, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

    $quote_level = 0;
    $message = '';

    foreach($blocks as $block)
    {
        if ($block == '[quote]') {
            if ($quote_level == 0) $message .= $block;
            $quote_level++;
        } else if ($block == '[/quote]') {
            if ($quote_level <= 1) $message .= $block;
            if ($quote_level >= 1) $quote_level--;
        } else {
            if ($quote_level <= 1) $message .= $block;
        }
    }

   
    // prcess bbcode: list
    $message = preg_replace('/\[\*:'.$uid.'\]/si', '[*]', $message);
    $message = preg_replace('/\[\/\*:(m:)?'.$uid.'\]/si', '', $message);
    $message = tt_covert_list($message, '/\[list:'.$uid.'\](.*?)\[\/list:u:'.$uid.'\]/si', '1', $uid);
    $message = tt_covert_list($message, '/\[list=[^\]]*?:'.$uid.'\](.*?)\[\/list:o:'.$uid.'\]/si', '2', $uid);
    // process video bbcode\    
    $message = preg_replace('/\[(youtube|yt|video|googlevideo|gvideo):'.$uid.'\](.*?)\[\/\1:'.$uid.'\]/sie', "video_bbcode_format('$1', '$2')", $message);
    $message = preg_replace('/\[(BBvideo)[\d, ]+:'.$uid.'\](.*?)\[\/\1:'.$uid.'\]/si', "[url=$2]YouTube Video[/url]", $message);
    $message = preg_replace('/\[(spoil|spoiler):'.$uid.'\](.*?)\[\/\1:'.$uid.'\]/si', "[spoiler]$2[/spoiler]", $message);
    $message = preg_replace('/\[HiddenText=(.*?)\](.*?)\[\/HiddenText\]/si', '[spoiler]$2[/spoiler]', $message);
    $message = preg_replace('/\[b:'.$uid.'\](.*?)\[\/b:'.$uid.'\]/si', '[b]$1[/b]', $message);
    $message = preg_replace('/\[i:'.$uid.'\](.*?)\[\/i:'.$uid.'\]/si', '[i]$1[/i]', $message);
    $message = preg_replace('/\[u:'.$uid.'\](.*?)\[\/u:'.$uid.'\]/si', '[u]$1[/u]', $message);
    $message = preg_replace('/\[color=(\#[\da-fA-F]{3}|\#[\da-fA-F]{6}|[A-Za-z]{1,20}|rgb\(\d{1,3}, ?\d{1,3}, ?\d{1,3}\)):'.$uid.'\](.*?)\[\/color:'.$uid.'\]/si', '[color=$1]$2[/color]', $message);
    $message = preg_replace('/\[mp3preview:'.$uid.'\](.*?)\[\/mp3preview:'.$uid.'\]/si', '[url=$1]MP3 Preview[/url]', $message);
     
    return $message;
}
function tt_covert_list($message,$preg,$type,$uid)
{
    while(preg_match($preg, $message, $blocks))
    {
        $list_str = "";
        $list_arr = explode('[*]', $blocks[1]);
        foreach ($list_arr as $key => $value)
        {
            $value = trim($value);
            if(!empty($value) && $key != 0)
            {
                if($type == '1')
                {
                    $key = ' * ';
                }
                else 
                {
                    $key = $key.'.';
                }
                $list_str .= $key.$value ."\n";
            }
            else if(!empty($value))
            {
                $list_str .= $value ."\n";
            }           
        }
        $message = str_replace($blocks[0], $list_str, $message);
    }
    return $message;
}
function url_encode($url)
{
    global $phpbb_home, $phpbb_root_path;
    
	//check is domain
    $is_domain = false;
    if(preg_match('/^\//', $url) && !preg_match('/download\/file\.php/', $url))
    {
    	$is_domain = true;
    	$server_url = $_SERVER['HTTP_HOST'];
    }
    $url = rawurlencode($url);
    
    $from = array('/%3A/', '/%2F/', '/%3F/', '/%2C/', '/%3D/', '/%26/', '/%25/', '/%23/', '/%2B/', '/%3B/', '/%5C/', '/%20/');
    $to   = array(':',     '/',     '?',     ',',     '=',     '&',     '%',     '#',     '+',     ';',     '\\',    ' ');
    $url = preg_replace($from, $to, $url);
    $root_path = preg_replace('/^\//', '', $phpbb_root_path);
    if($root_path == '/')
    {
        $url = preg_replace('#^\.\./|^/#si', '', $url);
    }
    else 
    {
        $url = preg_replace('#^\.\./|^/|'.addslashes($root_path).'#si', '', $url);
    }
    
    $url = preg_replace('#^.*?(?=download/file\.php)#si', '', $url);
 	
    
    
    if (strpos($url, 'http') !== 0 && strpos($url, 'https') !== 0 && strpos($url, 'mailto') !== 0)
    {
    	if(!$is_domain)
        	$url = $phpbb_home.$url;
        else 
        	$url = "http://".$server_url.'/'.$url;
    }
    
    return htmlspecialchars_decode($url);
}

function get_user_avatar_url($avatar, $avatar_type, $ignore_config = false)
{
    global $config, $phpbb_home, $phpEx;

    if (empty($avatar) || (isset($config['allow_avatar']) && !$config['allow_avatar'] && !$ignore_config))
    {
        return '';
    }
    
    $avatar_img = '';

    switch ($avatar_type)
    {
        case AVATAR_UPLOAD:
            if (isset($config['allow_avatar_upload']) && !$config['allow_avatar_upload'] && !$ignore_config)
            {
                return '';
            }
            $avatar_img = $phpbb_home . "download/file.$phpEx?avatar=";
        break;

        case AVATAR_GALLERY:
            if (isset($config['allow_avatar_local']) && !$config['allow_avatar_local'] && !$ignore_config)
            {
                return '';
            }
            $avatar_img = $phpbb_home . $config['avatar_gallery_path'] . '/';
        break;

        case AVATAR_REMOTE:
            if (isset($config['allow_avatar_remote']) && !$config['allow_avatar_remote'] && !$ignore_config)
            {
                return '';
            }
        break;
        default:
        	return $avatar;
        	break;
    }

    $avatar_img .= $avatar;
    $avatar_img = str_replace(' ', '%20', $avatar_img);
    
    return $avatar_img;
}


function mobiquo_iso8601_encode($timet)
{
    global $user;
    
    return $user->format_date($timet);
}


function get_user_id_by_name($username)
{
    global $db;
    
    if (!$username)
    {
        return false;
    }
    $username_clean = $db->sql_escape(utf8_clean_string($username));
    $username_clean = htmlspecialchars($username_clean, ENT_COMPAT, 'UTF-8');
    $sql = 'SELECT user_id
            FROM ' . USERS_TABLE . "
            WHERE username_clean = '$username_clean'";
    $result = $db->sql_query($sql);
    $user_id = $db->sql_fetchfield('user_id');
    $db->sql_freeresult($result);
    return $user_id;
}

function cut_quote($str, $keep_size)
{
    $str_array = preg_split('/(\[quote\].*?\[\/quote\])/is', $str, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    
    $str = '';
    
    foreach($str_array as $block)
    {
        if (preg_match('/\[quote\](.*?)\[\/quote\]/is', $block, $block_matches))
        {
            $quote_array = preg_split('/(\[img\].*?\[\/img\]|\[url=.*?\].*?\[\/url\])/is', $block_matches[1], -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            $short_str = '';
            $current_size = 0;
            $img_flag = true; // just keep at most one img in the quote
            for ($i = 0, $size = sizeof($quote_array); $i < $size; $i++)
            {
                if (preg_match('/^\[img\].*?\[\/img\]$/is', $quote_array[$i]))
                {
                    if ($img_flag)
                    {
                        $short_str .= $quote_array[$i];
                        $img_flag = false;
                    }
                }
                else if (preg_match('/^\[url=.*?\](.*?)\[\/url\]$/is', $quote_array[$i], $matches))
                {
                    $short_str .= $quote_array[$i];
                    $current_size += strlen($matches[1]);
                    if ($current_size > $keep_size)
                    {
                        $short_str .= "...";
                        break;
                    }
                }
                else
                {
                    if ($current_size + strlen($quote_array[$i]) > $keep_size)
                    {
                        $short_str .= substr($quote_array[$i], 0, $keep_size - $current_size);
                        $short_str .= "...";
                        break;
                    }
                    else
                    {
                        $short_str .= $quote_array[$i];
                        $current_size += strlen($quote_array[$i]);
                    }
                }
            }
            $str .= '[quote]' . $short_str . '[/quote]';
        } else {
            $str .= $block;
        }
    }

    return $str;
}

function video_bbcode_format($type, $url)
{
    $url = trim($url);
    $url = html_entity_decode($url);
    switch (strtolower($type)) {
        case 'yt':
        case 'youtube':
            if (preg_match('#^(http://)?((www|m)\.)?(youtube\.com/(watch\?.*?v=|v/)|youtu\.be/)([-\w]+)#', $url, $matches)) {
                $message = '[url='.$url.']YouTube Video[/url]';
            } else if (preg_match('/^[-\w]+$/', $url)) {
                $url = 'http://www.youtube.com/watch?v='.$url;
                $message = '[url='.$url.']YouTube Video[/url]';
            } else {
                $message = '';
            }
            break;
        case 'video':
            if (preg_match('#^http(s)?://#', $url)) {
                $message = '[url='.$url.']Video[/url]';
            } else {
                $message = '';
            }
            break;
        case 'gvideo':
        case 'googlevideo':
            if (preg_match('#^http://video.google.com/(googleplayer.swf|videoplay)?docid=-#', $url)) {
                $message = '[url='.$url.']Google Video[/url]';
            } else if (preg_match('/^-?(\d+)/', $url, $matches)) {
                $message = '[url=http://video.google.com/videoplay?docid=-'.$matches['1'].']Google Video[/url]';
            } else {
                $message = '';
            }
            break;
        default: $message = '';
    }
    
    return $message;
}

function check_forum_password($forum_id)
{
    global $user, $db;
    
    $sql = 'SELECT forum_id
            FROM ' . FORUMS_ACCESS_TABLE . '
            WHERE forum_id = ' . $forum_id . '
                AND user_id = ' . $user->data['user_id'] . "
                AND session_id = '" . $db->sql_escape($user->session_id) . "'";
    $result = $db->sql_query($sql);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);

    if (!$row)
    {
        return false;
    }
    
    return true;
}

function get_participated_user_avatars($tids)
{
    global $db, $topic_users, $user_avatar;
    
    $topic_users = array();
    $user_avatar = array();
    if (!empty($tids))
    {
        $posters = array();
        foreach($tids as $tid)
        {
            $sql = 'SELECT poster_id, count(post_id) as num FROM ' . POSTS_TABLE . '
                    WHERE topic_id=' . $tid . '
                    GROUP BY poster_id
                    ORDER BY num DESC';
            $result = $db->sql_query_limit($sql, 10);
            while ($row = $db->sql_fetchrow($result))
            {
                $posters[$row['poster_id']] = $row['num'];
                $topic_users[$tid][] = $row['poster_id'];
            }
            $db->sql_freeresult($result);
        }
        
        if (!empty($posters))
        {
            $user_avatar = get_user_avatars(array_keys($posters));
        }
    }
}

function get_user_avatars($users, $is_username = false)
{
    global $db;
    
    if (empty($users)) return array();
    
    if (!is_array($users)) $users = array($users);
    
    if($is_username)
        foreach($users as $key => $username)
            $users[$key] = $db->sql_escape(utf8_clean_string($username));
    
    $sql = 'SELECT user_id, username, user_avatar, user_avatar_type 
            FROM ' . USERS_TABLE . '
            WHERE ' . $db->sql_in_set($is_username ? 'username_clean' : 'user_id', $users);
    $result = $db->sql_query($sql);
    $user_avatar = array();
    $user_key = $is_username ? 'username' : 'user_id';
    while ($row = $db->sql_fetchrow($result))
    {
        $user_avatar[$row[$user_key]] = get_user_avatar_url($row['user_avatar'], $row['user_avatar_type']);
    }
    $db->sql_freeresult($result);
    
    return $user_avatar;
}

function check_error_status(&$str)
{
    global $user, $request_method;
    
    switch ($request_method) {
        case 'thank_post':
            if (strpos($str, $user->lang['THANKS_INFO_GIVE']) !== false || $str == "Insert thanks") {
                $str = '';
                return true;
            } elseif (strpos($str, $user->lang['GLOBAL_INCORRECT_THANKS']) !== false) {
                $str = $user->lang['GLOBAL_INCORRECT_THANKS'];
                return false;
            } elseif (strpos($str, $user->lang['INCORRECT_THANKS']) !== false) {
                $str = $user->lang['INCORRECT_THANKS'];
                return false;
            } 
            else if(strpos($str, 'Tried to redirect to potentially insecure url')  !== false )
            {
                $str = '';
                return true;
            }
            else {
                return false;
            }
            
        case 'm_stick_topic':
            if (strpos($str, $user->lang['TOPIC_TYPE_CHANGED']) === false)
                return false;
            else {
                $str = $user->lang['TOPIC_TYPE_CHANGED'];
                return true;
            }
        case 'm_close_topic':
            if (strpos($str, $user->lang['TOPIC_LOCKED_SUCCESS']) === false && strpos($str, $user->lang['TOPIC_UNLOCKED_SUCCESS']) === false)
                return false;
            elseif (strpos($str, $user->lang['TOPIC_LOCKED_SUCCESS']) !== false) {
                $str = $user->lang['TOPIC_LOCKED_SUCCESS'];
                return true;
            } else {
                $str = $user->lang['TOPIC_UNLOCKED_SUCCESS'];
                return true;
            }
        case 'm_delete_topic':
            if (strpos($str, $user->lang['TOPIC_DELETED_SUCCESS']) === false)
                return false;
            else {
                $str = $user->lang['TOPIC_DELETED_SUCCESS'];
                return true;
            }
        case 'm_delete_post':
            if (strpos($str, $user->lang['POST_DELETED_SUCCESS']) === false && strpos($str, $user->lang['TOPIC_DELETED_SUCCESS']) === false)
                return false;
            elseif (strpos($str, $user->lang['POST_DELETED_SUCCESS']) !== false) {
                $str = $user->lang['POST_DELETED_SUCCESS'];
                return true;
            } else {
                $str = $user->lang['TOPIC_DELETED_SUCCESS'];
                return true;
            }
        case 'm_move_topic':
            if (strpos($str, $user->lang['TOPIC_MOVED_SUCCESS']) === false)
                return false;
            else {
                $str = $user->lang['TOPIC_MOVED_SUCCESS'];
                return true;
            }
        case 'm_move_post':
            if (strpos($str, $user->lang['TOPIC_SPLIT_SUCCESS']) === false && strpos($str, $user->lang['POSTS_MERGED_SUCCESS']) === false)
                return false;
            elseif (strpos($str, $user->lang['TOPIC_SPLIT_SUCCESS']) !== false) {
                $str = $user->lang['TOPIC_SPLIT_SUCCESS'];
                return true;
            } else {
                $str = $user->lang['POSTS_MERGED_SUCCESS'];
                return true;
            }
        case 'm_merge_topic':
            if (strpos($str, $user->lang['POSTS_MERGED_SUCCESS']) === false)
                return false;
            else {
                $str = $user->lang['POSTS_MERGED_SUCCESS'];
                return true;
            }
        case 'm_approve_topic':
            if (strpos($str, $user->lang['TOPIC_APPROVED_SUCCESS']) === false)
                return false;
            else {
                $str = $user->lang['TOPIC_APPROVED_SUCCESS'];
                return true;
            }
        case 'm_approve_post':
            if (strpos($str, $user->lang['POST_APPROVED_SUCCESS']) === false)
                return false;
            else {
                $str = $user->lang['POST_APPROVED_SUCCESS'];
                return true;
            }
        case 'm_ban_user':
            if (strpos($str, $user->lang['BAN_UPDATE_SUCCESSFUL']) === false)
                return false;
            else {
                $str = $user->lang['BAN_UPDATE_SUCCESSFUL'];
                return true;
            }
    }
    
    return false;
}

function tt_get_unread_topics($user_id = false, $sql_extra = '', $sql_sort = '', $sql_limit = 1001, $sql_limit_offset = 0)
{
    global $config, $db, $user;

    $user_id = ($user_id === false) ? (int) $user->data['user_id'] : (int) $user_id;

    // Data array we're going to return
    $unread_topics = array();

    if (empty($sql_sort))
    {
        $sql_sort = 'ORDER BY t.topic_last_post_time DESC';
    }

    if ($config['load_db_lastread'] && $user->data['is_registered'])
    {
        // Get list of the unread topics
        $last_mark = (int) $user->data['user_lastmark'];

        $sql_array = array(
            'SELECT'        => 't.topic_id, t.topic_last_post_time, tt.mark_time as topic_mark_time, ft.mark_time as forum_mark_time',

            'FROM'            => array(TOPICS_TABLE => 't'),

            'LEFT_JOIN'        => array(
                array(
                    'FROM'    => array(TOPICS_TRACK_TABLE => 'tt'),
                    'ON'    => "tt.user_id = $user_id AND t.topic_id = tt.topic_id",
                ),
                array(
                    'FROM'    => array(FORUMS_TRACK_TABLE => 'ft'),
                    'ON'    => "ft.user_id = $user_id AND t.forum_id = ft.forum_id",
                ),
            ),

            'WHERE'            => "
                 t.topic_last_post_time > $last_mark AND
                (
                (tt.mark_time IS NOT NULL AND t.topic_last_post_time > tt.mark_time) OR
                (tt.mark_time IS NULL AND ft.mark_time IS NOT NULL AND t.topic_last_post_time > ft.mark_time) OR
                (tt.mark_time IS NULL AND ft.mark_time IS NULL)
                )
                $sql_extra
                $sql_sort",
        );

        $sql = $db->sql_build_query('SELECT', $sql_array);
        $result = $db->sql_query_limit($sql, $sql_limit, $sql_limit_offset);

        while ($row = $db->sql_fetchrow($result))
        {
            $topic_id = (int) $row['topic_id'];
            $unread_topics[$topic_id] = ($row['topic_mark_time']) ? (int) $row['topic_mark_time'] : (($row['forum_mark_time']) ? (int) $row['forum_mark_time'] : $last_mark);
        }
        $db->sql_freeresult($result);
    }
    else if ($config['load_anon_lastread'] || $user->data['is_registered'])
    {
        global $tracking_topics;

        if (empty($tracking_topics))
        {
            $tracking_topics = request_var($config['cookie_name'] . '_track', '', false, true);
            $tracking_topics = ($tracking_topics) ? tracking_unserialize($tracking_topics) : array();
        }

        if (!$user->data['is_registered'])
        {
            $user_lastmark = (isset($tracking_topics['l'])) ? base_convert($tracking_topics['l'], 36, 10) + $config['board_startdate'] : 0;
        }
        else
        {
            $user_lastmark = (int) $user->data['user_lastmark'];
        }

        $sql = 'SELECT t.topic_id, t.forum_id, t.topic_last_post_time
            FROM ' . TOPICS_TABLE . ' t
            WHERE t.topic_last_post_time > ' . $user_lastmark . "
            $sql_extra
            $sql_sort";
        $result = $db->sql_query_limit($sql, $sql_limit, $sql_limit_offset);

        while ($row = $db->sql_fetchrow($result))
        {
            $forum_id = (int) $row['forum_id'];
            $topic_id = (int) $row['topic_id'];
            $topic_id36 = base_convert($topic_id, 10, 36);

            if (isset($tracking_topics['t'][$topic_id36]))
            {
                $last_read = base_convert($tracking_topics['t'][$topic_id36], 36, 10) + $config['board_startdate'];

                if ($row['topic_last_post_time'] > $last_read)
                {
                    $unread_topics[$topic_id] = $last_read;
                }
            }
            else if (isset($tracking_topics['f'][$forum_id]))
            {
                $mark_time = base_convert($tracking_topics['f'][$forum_id], 36, 10) + $config['board_startdate'];

                if ($row['topic_last_post_time'] > $mark_time)
                {
                    $unread_topics[$topic_id] = $mark_time;
                }
            }
            else
            {
                $unread_topics[$topic_id] = $user_lastmark;
            }
        }
        $db->sql_freeresult($result);
    }

    return $unread_topics;
}

function tp_get_forum_icon($id, $type = 'forum', $lock = false, $new = false)
{
    if ($type == 'link')
    {
        if ($filename = tp_get_forum_icon_by_name('link'))
            return $filename;
    }
    else
    {
        if ($lock && $new && $filename = tp_get_forum_icon_by_name('lock_new_'.$id))
            return $filename;
        if ($lock && $filename = tp_get_forum_icon_by_name('lock_'.$id))
            return $filename;
        if ($new && $filename = tp_get_forum_icon_by_name('new_'.$id))
            return $filename;
        if ($filename = tp_get_forum_icon_by_name($id))
            return $filename;
        
        if ($type == 'category')
        {
            if ($lock && $new && $filename = tp_get_forum_icon_by_name('category_lock_new'))
                return $filename;
            if ($lock && $filename = tp_get_forum_icon_by_name('category_lock'))
                return $filename;
            if ($new && $filename = tp_get_forum_icon_by_name('category_new'))
                return $filename;
            if ($filename = tp_get_forum_icon_by_name('category'))
                return $filename;
        }
        else
        {
            if ($lock && $new && $filename = tp_get_forum_icon_by_name('forum_lock_new'))
                return $filename;
            if ($lock && $filename = tp_get_forum_icon_by_name('forum_lock'))
                return $filename;
            if ($new && $filename = tp_get_forum_icon_by_name('forum_new'))
                return $filename;
            if ($filename = tp_get_forum_icon_by_name('forum'))
                return $filename;
        }
        
        if ($lock && $new && $filename = tp_get_forum_icon_by_name('lock_new'))
            return $filename;
        if ($lock && $filename = tp_get_forum_icon_by_name('lock'))
            return $filename;
        if ($new && $filename = tp_get_forum_icon_by_name('new'))
            return $filename;
    }
    
    return tp_get_forum_icon_by_name('default');
}

function tp_get_forum_icon_by_name($icon_name)
{
    $tapatalk_forum_icon_dir = './forum_icons/';
    
    if (file_exists($tapatalk_forum_icon_dir.$icon_name.'.png'))
        return $icon_name.'.png';
    
    if (file_exists($tapatalk_forum_icon_dir.$icon_name.'.jpg'))
        return $icon_name.'.jpg';
    
    return '';
}

function check_return_user_type($username)
{
    global $db;
    $session = new user();
    $user_id = get_user_id_by_name($username);
    if(empty($user_id))
    {
        $user_id = 0;
    }
    $sql = "SELECT group_name FROM " . USER_GROUP_TABLE . " AS ug LEFT JOIN " .GROUPS_TABLE. " AS g ON ug.group_id = g.group_id WHERE user_id = " . $user_id;
    $query = $db->sql_query($sql);
    $is_ban = $session->check_ban($user_id);
    $user_groups = array();
    while($row = $db->sql_fetchrow($query))
    {
        $user_groups[] = $row['group_name'];
    }
    if(!empty($is_ban ))
    {
        $user_type = 'banned';
    }
    else if(in_array('ADMINISTRATORS', $user_groups))
    {
        $user_type = 'admin';
    }
    else if(in_array('GLOBAL_MODERATORS', $user_groups))
    {
        $user_type = 'mod';
    }
    else
    {
        $user_type = 'normal';
    }
    return new xmlrpcval(basic_clean($user_type), 'base64');
}

function tt_register_verify($tt_token,$tt_code)
{
    global $config;
    $key = isset($config['tapatalk_push_key']) ? $config['tapatalk_push_key'] : '';
    $board_url = generate_board_url();

    $url = "http://directory.tapatalk.com/au_reg_verify.php";
    $data = array(
        'token' => $tt_token,
        'code' => $tt_code,
        'key' => $key,
        'url' => $board_url
    );
    $error_msg = '';
    $response = getContentFromRemoteServer($url, 10, $error_msg, 'POST', $data);
    
    if(!empty($error_msg))
    {
        $response = '{"result":false,"result_text":"' . $error_msg . '"}';
    }
    if(empty($response))
    {
        $response = '{"result":false,"result_text":"Contect timeout , please try again"}';
    }
    $result = json_decode($response);
    return $result;
}

/**
 * Get content from remote server
 *
 * @param string $url      NOT NULL          the url of remote server, if the method is GET, the full url should include parameters; if the method is POST, the file direcotry should be given.
 * @param string $holdTime [default 0]       the hold time for the request, if holdtime is 0, the request would be sent and despite response.
 * @param string $error_msg                  return error message
 * @param string $method   [default GET]     the method of request.
 * @param string $data     [default array()] post data when method is POST.
 *
 * @exmaple: getContentFromRemoteServer('http://push.tapatalk.com/push.php', 0, $error_msg, 'POST', $ttp_post_data)
 * @return string when get content successfully|false when the parameter is invalid or connection failed.
*/
function getContentFromRemoteServer($url, $holdTime = 0, &$error_msg, $method = 'GET', $data = array())
{
    //Validate input.
    $vurl = parse_url($url);
    if ($vurl['scheme'] != 'http' && $vurl['scheme'] != 'https')
    {
        $error_msg = 'Error: invalid url given: '.$url;
        return false;
    }
    if($method != 'GET' && $method != 'POST')
    {
        $error_msg = 'Error: invalid method: '.$method;
        return false;//Only POST/GET supported.
    }
    if($method == 'POST' && empty($data))
    {
        $error_msg = 'Error: data could not be empty when method is POST';
        return false;//POST info not enough.
    }

    if(!empty($holdTime) && function_exists('file_get_contents') && $method == 'GET')
    {
        $opts = array(
            $vurl['scheme'] => array(
                'method' => "GET",
                'timeout' => $holdTime,
            )
        );

        $context = stream_context_create($opts);
        $response = file_get_contents($url,false,$context);
    }
    else if (@ini_get('allow_url_fopen'))
    {
        if(empty($holdTime))
        {
            // extract host and path:
            $host = $vurl['host'];
            $path = $vurl['path'];

            if($method == 'POST')
            {
                $fp = @fsockopen($host, 80, $errno, $errstr, 5);

                if(!$fp)
                {
                    $error_msg = 'Error: socket open time out or cannot connet.';
                    return false;
                }

                $data =  http_build_query($data);

                fputs($fp, "POST $path HTTP/1.1\r\n");
                fputs($fp, "Host: $host\r\n");
                fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
                fputs($fp, "Content-length: ". strlen($data) ."\r\n");
                fputs($fp, "Connection: close\r\n\r\n");
                fputs($fp, $data);
                fclose($fp);
                return 1;
            }
            else
            {
                $error_msg = 'Error: 0 hold time for get method not supported.';
                return false;
            }
        }
        else
        {
            if($method == 'POST')
            {
                $params = array(
                    $vurl['scheme'] => array(
                        'method' => 'POST',
                        'content' => http_build_query($data, '', '&'),
                    )
                );
               
                $ctx = stream_context_create($params);
                $old = ini_set('default_socket_timeout', $holdTime);
                $fp = @fopen($url, 'rb', false, $ctx);
            }
            else
            {
                $fp = @fopen($url, 'rb', false);
            }
            if (!$fp)
            {
                $error_msg = 'Error: fopen failed.';
                return false;
            }
            ini_set('default_socket_timeout', $old);
            stream_set_timeout($fp, $holdTime);
            stream_set_blocking($fp, 0);

            $response = @stream_get_contents($fp);
        }
    }
    elseif (function_exists('curl_init'))
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        if($method == 'POST')
        {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        if(empty($holdTime))
        {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT,1);
        }
        $response = curl_exec($ch);
        curl_close($ch);
    }
    else
    {
        $error_msg = 'CURL is disabled and PHP option "allow_url_fopen" is OFF. You can enable CURL or turn on "allow_url_fopen" in php.ini to fix this problem.';
        return false;
    }
    if(!empty($error_msg))
    {
        return $error_msg;
    }
    return $response;
}

function tt_get_user_by_email($email)
{
    global $db;
    $sql = 'SELECT *
        FROM ' . USERS_TABLE . "
        WHERE user_email = '" . $db->sql_escape($email) . "'";
    $result = $db->sql_query($sql);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    return $row;
}

function tt_get_user_by_id($uid)
{
    global $db;
    $sql = 'SELECT *
        FROM ' . USERS_TABLE . "
        WHERE user_id = '" . $db->sql_escape($uid) . "'";
    $result = $db->sql_query($sql);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    return $row;
}

function tt_get_ignore_users($user_id)
{
	global $db;
	
	$sql_and = 'z.foe = 1';
	$sql = 'SELECT z.*
		FROM ' . ZEBRA_TABLE . ' z
		WHERE z.user_id = ' . $user_id . "
			AND $sql_and ";
	$result = $db->sql_query($sql);
	
	$ignore_users = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$ignore_users[] = $row['zebra_id'];
	}
	$db->sql_freeresult($result);
	return $ignore_users;
}
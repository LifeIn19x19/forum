<?php
    //add tapatalk thumbnail
    $message = preg_replace_callback('/(\[img(.*?)\])(http&#58;\/\/img&#46;tapatalk&#46;com\/d\/[0-9]{2}\/[0-9]{2}\/[0-9]{2})(.*?)(\[\/img\2\])/i',
            create_function(
                '$matches',
                'return \'<a target="_blank" href="http&#58;//tapatalk&#46;com/tapatalk_image&#46;php?img=\'.base64_encode($matches[3].\'/original\'.$matches[4]).\'">\'.$matches[1].$matches[3].\'/thumbnail\'.$matches[4].$matches[5].\'</a>\';'
            ),
    $message);
    
    // display emoji from app
	$protocol = ($config['cookie_secure'])  ? 'https' : 'http';
	$message = preg_replace('/\[emoji(\d+)\]/i', '<img src="'.$protocol.'://s3.amazonaws.com/tapatalk-emoji/emoji\1.png" />', $message);
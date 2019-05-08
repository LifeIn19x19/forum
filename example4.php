<?php
/**
 * SLtxt2PNG.php -- create a PNG image from Sensei's Library diagram format
 * Copyright (C) 2001-2009 by
 * Arno Hollosi <ahollosi@xmp.net>, Morten Pahle <morten@pahle.org.uk>
 *
 * Call this file with following parameters:
 * http://your.domain/example.php?type=PNG
 * http://your.domain/example.php?type=thumb
 * http://your.domain/example.php?type=SGF
 */

require 'sltxt2png.php';



/**
 * Demo function generating board position of ear-reddening move
 * including a link to Sensei's Library
 * @param string $type One of PNG, thumb, SGF
 */
function DemoEarReddeningMove()
{
    $diagramsrc = $_REQUEST['msg'];

    $diagram = new GoDiagram($diagramsrc);
    $type = $_REQUEST['request'];
    if ($diagram->isValid()) {  // check that parsing was ok
        switch ($type) {
            case 'PNG':
                $png = new GoDiagramPNG($diagram);
                $img = $png->createPNG();
                $dimensions = $png->getDimensions();
		/*if($dimensions[1] < 350) {
 			$extend = $diagram->getOffset()*4;
imagestring($img, 3, 5, $dimensions[1]-$extend, substr($png->getTitle(), 0, 30), imagecolorallocate($img, 0, 0, 0));
		imagestring($img, 3, 5, $dimensions[1]-($extend*4/8), substr($png->getTitle(), 30), imagecolorallocate($img, 0, 0, 0));
		}*/
				//if(((strlen($png->getTitle())*6)+5) > imagesx($img)) {
					$lines = array();
					$numLines = 0;
					$splitComment = wordwrap($png->getTitle(), floor((imagesx($img)+5)/imagefontwidth(3))/*/6-5*/, "~*)", false);
					$line = strtok($splitComment, "~*)");
					
					while($line !== false) {
						$lines[] = $line;
					    $numLines = $numLines + 1;
						$line = strtok("~*)");
					}
					$yLoc = imagesy($img)-(15*$numLines);
					for ($i = 0; $i < $numLines; $i++) {
						imagestring($img, 3, 5, $yLoc, str_replace("&#39;", "'", str_replace("&amp;", "&", str_replace("&gt;", ">", str_replace("&quot;", "\"", str_replace("&lt;", "<", str_replace("&amp;", "&", $lines[$i])))))), imagecolorallocate($img, 0, 0, 0));
						//str_replace("&gt;", ">", str_replace("&lt;", "<", str_replace("&amp;", "&", $lines[$i])))
						/*wordwrap($png->getTitle(), dimensions[0]/3, "", true)*/
                		$yLoc = $yLoc + 15;
                	}
              //  } else {
                //	imagestring($img, 3, 5, $dimensions[1]-15, $png->getTitle(), imagecolorallocate($img, 255, 0, 0));
                //}
                Header("Content-type: image/png");
                ImagePng($img);	    // just output the image
                ImageDestroy($img);
                break;
            case 'thumb':
                $png = new GoDiagramPNG($diagram);
                $thumb = $png->createThumbnail();
                Header("Content-type: image/jpeg");
                ImageJPEG($thumb);
                ImageDestroy($thumb);
                break;
            case 'SGF':
                $sgf = new GoDiagramSGF($diagram, 'http://lifein19x19.com/');
                $sgfdata = $sgf->createSGF();
                header('Content-Disposition: attachment;; filename="diagram.sgf"');
                Header("Content-type: application/x-go-sgf");
                echo $sgfdata;
                break;
            case 'caption':
                $png = new GoDiagramPNG($diagram);
                $cap = $png->getTitle();
                Print $cap;
                break;
            default:
                echo "Unknown type passed in. Please use ?type=PNG or ?type=thumb or ?type=SGF";
                break;
        }
    }
}

DemoEarReddeningMove();
?>

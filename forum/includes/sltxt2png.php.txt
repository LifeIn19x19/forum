<?php
/**
    SLtxt2PNG.php -- create a PNG image from Sensei's Library diagram format
    Copyright (C) 2001-2009 by
    Arno Hollosi <ahollosi@xmp.net>, Morten Pahle <morten@pahle.org.uk>
    
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the
    Free Software Foundation, Inc., 59 Temple Place, Suite 330,
    Boston, MA  02111-1307  USA
 */


/**
 * All you need to know about these classes is shown in the example.php file.
 */


/**
 * Class that translates board dimensions (points) into pixel dimensions
 */
class GoDiagramDimensions
{
    public $font_size;          // all other meassurements are based on this
    public $font_height;
    public $font_width;
    public $radius;             // in pixels (single goban point)
    public $width;              // in pixels
    public $height;

    protected $offset_x;	// origin (0,0) of board itself
    protected $offset_y;

    /**
     * Constructor
     * @param int $bsizex Width of board
     * @param int $bsizey Height of board
     * @param int $fsize Font size
     */
    function __construct($bsizex=19, $bsizey=19, $fsize=4)
    {
	$this->font_size = $fsize;
	$this->font_height = ImageFontHeight($this->font_size);
	$this->font_width = ImageFontWidth($this->font_size);
	$this->radius = ($this->font_height+$this->font_width+22) / 4;
	$this->width = 2*$this->radius*$bsizex + 4;
	$this->height = 2*$this->radius*$bsizey + 4;
	$this->offset_x = 2;
	$this->offset_y = 2;
    }

    function addCoordinates()
    {
	$x = $this->font_width*2 + 4;
	$y = $this->font_height + 2;
   	$this->width += $x;
	$this->offset_x += $x;
	$this->height += $y;
	$this->offset_y += $y;
    }

    function getArea($xpos, $ypos)
    {
	$d = $this->radius*2;
	$x = $xpos*$d + $this->offset_x;
	$y = $ypos*$d + $this->offset_y;
	return array($x, $y, $x + $d - 1, $y + $d - 1);
    }

    /**
     * transform board coordinates (offset from top left corner) to
     * image coordinates
     * @return array Array of board coordinates (numbering starts at 0)
     */
    function toImgCoords($x, $y=NULL)
    {
	if (!isset($y)) {
	    $y = $x[1];
	    $x = $x[0];
	}
	return array($x*$this->radius*2 + $this->radius + $this->offset_x,
		     $y*$this->radius*2 + $this->radius + $this->offset_y);
    }
}

/**
 * Class for parsing and analysing the diagram given in SL format.
 */
class GoDiagram
{
    // misc
    protected $content;         // raw copy of diagram contents (single string)
    protected $linkmap;         // array of imagemap links (bracketlinks)
    protected $specialMarkup;	// special markup inside curly brackets
    protected $dim;		// holds DiagramDimensions

    // values extracted from the title line
    protected $firstColor;	// 'B' or 'W'
    protected $coordinates;	// boolean
    protected $boardSize;	// integer (from title line)
    protected $title;		// raw text of title
    protected $startMoveNum;	// starting move number for moves in diagram

    // goban properties
    protected $goban;		// board (string) without borders
    protected $width;		// actual width
    protected $height;          // actual height
    protected $coordx;          // X offset for coordinate numbering
    protected $coordy;          // Y offset for coordinate numbering
    protected $moves;
    protected $markup;

    // borders (1 or 0)
    protected $topborder;
    protected $bottomborder;
    protected $leftborder;
    protected $rightborder;


    /**
     * Constructor of Diagram class
     * @param string $content String containing the diagram in SL syntax
     */
    function __construct($content)
    {
        $content = explode("\n", $content);
        $linenum = 0;
	$this->parseTitle($content[$linenum++]);
	$this->parseDiagram($content, $linenum);
	if (!$this->initBoardAndDimensions())
	    $this->content = NULL;	// indicates that diagram is invalid
	else
	    $this->specialMarkup = $this->parseSpecialMarkup($this->specialMarkup);
    }

    /**
     * Returns true, if the diagram is a valid diagram, false otherwise.
     * @return boolean
     */
    function isValid()
    {
        return !is_null($this->content);
    }

    /**
     * Parse the parameters of the first line
     * @param string $title First line of diagram
     */
    protected function parseTitle($title)
    {
	preg_match('/^(?:>\s*)*\$\$([WB])?(c)?(\d+)?(?:m(\d+))?(.*)/', $title, $match);
	$this->firstColor = ($match[1] == 'W') ? 'W' : 'B';
	$this->coordinates = !empty($match[2]);
	$this->boardSize = empty($match[3]) ? 19 : (int)$match[3];
	$this->startMoveNum = $match[4] ? (int)$match[4] : 1;
	$this->title = trim($match[5]);
    }


    /**
     * Parse diagram within $content/$linenum framework
     * @param array $content Array of diagram lines
     * @param int $linenum Current offset into array
     */
    protected function parseDiagram($content, &$linenum)
    {
	$this->content = '';
	$this->linkmap = array();
	$this->specialMarkup = array();

        while (preg_match('/^(>\s*)*\$\$/', @$content[$linenum]))
	{
	    if (preg_match('"^(?:>\s*)*\$\$\s*([^{[\s].*)"', $content[$linenum], $match))
		$this->content .= "$match[1]\n";
	    if (preg_match('"^(?:>\s*)*\$\$\s*\[(.*)\|(.*)\]"', $content[$linenum], $match))
	    {
		$anchor = trim($match[1]);
		if (preg_match('/^[a-z0-9WB@#CSTQYZPM]$/', $anchor))
		    $this->linkmap[$anchor] = trim($match[2]);
	    }
	    if (preg_match('"^(?:>\s*)*\$\$\s*{(.*)}"', $content[$linenum], $match))
		$this->specialMarkup[] = trim($match[1]);
	    $linenum++;
        }
    }


    /**
     * Calculate board dimensions and set up goban and DiagramDimensions
     * @return boolean Whether parsing was successful or not
     */
    protected function initBoardAndDimensions()
    {
	// remove unnecessary chars, replace border chars
	$diag = preg_replace("/[-|+]/", "%", $this->content);
	$diag = preg_replace("/[ \t\r\$]/", '', $diag);
	$diag = trim(preg_replace("/\n+/", "\n", $diag));
	$this->initBordersAndGoban($diag);

	if ($this->width < 1 || $this->height < 1)
	    return false;

	$this->dim = new GoDiagramDimensions($this->width, $this->height);

	if ($this->coordinates) {
	    if (($this->bottomborder || $this->topborder)
	    &&  ($this->leftborder || $this->rightborder))
	    {
		$this->dim->addCoordinates();
	    }
	    else {
               // cannot determine X *and* Y coordinates (missing borders)
               $this->coordinates = 0;
	    }
	}

	// init offsets for coordinates
	if ($this->bottomborder)
	    $this->coordy = $this->height;
	elseif ($this->topborder)
	    $this->coordy = $this->boardSize;

	if ($this->leftborder)
	    $this->coordx = 0;
	elseif ($this->rightborder) {
	    $this->coordx = $this->boardSize - $this->width - 1;
	    if ($this->coordx < 0)
		$this->coordx = 0;
	}

	return true;
    }

    /**
     * Checks for borders and sets up $goban member
     * @param array $diag Array of diagram lines
     */
    protected function initBordersAndGoban($diag)
    {
	$rows = explode("\n", $diag);

	// find borders
	$startrow = 0;
	$startcol = 0;
	$endrow = count($rows) - 1;

	$this->topborder = ($rows[0][1] == '%') ? 1 : 0;
	$startrow += $this->topborder;

	$endcol = strlen($rows[$this->topborder]) - 1;
	$this->bottomborder = ($rows[$endrow][1] == '%') ? 1 : 0;
	$endrow -= $this->bottomborder;

	$this->leftborder = ($rows[$startrow][0] == '%') ? 1 : 0;
	$startcol += $this->leftborder;

	$this->rightborder = ($rows[$endrow][$endcol] == '%') ? 1 : 0;
	$endcol -= $this->rightborder;

	// init (copy) goban without borders
	$goban = '';
	for ($y = $startrow; $y <= $endrow; $y++) {
	    $goban .= substr($rows[$y], $startcol, $endcol+1) . "\n";
        }
	$this->goban = trim($goban);

	// init size
	$this->width = $endcol - $startcol + 1;
	$this->height =  $endrow - $startrow + 1;
    }

    /**
     * Parse special markup
     * Markup consists of type (1-2 uppercase letters) and number of arguments
     * @param array $markup Array of diagram lines containing markup information
     * @return array Parsed markup information
     */
    protected function parseSpecialMarkup($markup)
    {
	$res = array();
	foreach ($markup as $mark) {
	    if (!preg_match('/^\s*([A-Z]{1,2})\s+([A-Za-z0-9: ]+)$/',$mark,$match))
	    	continue;

	    switch($match[1]) {
		case "AR":
		case "LN":  $args = $this->getMarkupArgs(trim($match[2]), 2);
			    if ($args)
			    	$res[] = array($match[1], $args);
			    break;
	    }
	}
	return $res;
    }

    /**
     * Get list of arguments to special markup
     * Currently arguments are positions in either board coordinate addressing
     * or relative addressing to upper left corner of diagram
     * @param string $txt Line containing arguments separated by space
     * @param int $minargs Minimum number of arguments needed
     * @return boolean FALSE if there is any error
     */
    private function getMarkupArgs($txt, $minargs)
    {
	$args = preg_split('/\s+/', $txt);	// args separated by space
	$res = array();

	foreach ($args as $arg) {
	    // board coordinates addressing
	    if (preg_match('/^([A-Z])([0-9]{1,2})$/', $arg, $match)) {
		if (!isset($this->coordx) || !isset($this->coordy))
		    return false;
		$x = ord($match[1]{0}) - ord('A') - $this->coordx;
		if (ord($match[1]{0}) > ord('I'))
		    $x--;
		if ($x < 0 || $x >= $this->width)
		    return false;
		$y = $this->coordy - (int)$match[2];
		if ($y < 0 || $y >= $this->height)
		    return false;
		$res[] = array($x, $y);
	    }
	    // relative addressing from top left corner "x:y"
	    elseif (preg_match('/^([0-9]{1,2}):([0-9]{1,2})$/', $arg, $match)) {
		$x = (int)$match[1] - 1;
		$y = (int)$match[2] - 1;
		if ($x < 0 || $x >= $this->width)
		    return false;
		if ($y < 0 || $y >= $this->height)
		    return false;
		$res[] = array($x, $y);
	    }
	    // unknown argument type
	    else {
	    	return false;
            }
	}
	return (count($res) >= $minargs) ? $res : false;
    }

    /**
     * Get goban position at start of diagram (before any moves, without markup)
     * @return array Array of goban lines
     */
    function getStartPosition()
    {
	$sp = preg_replace('/[0-9]/', '.', $this->goban);   // no moves
	$sp = preg_replace('/[CSTMa-z]/', '.', $sp);        // no markup
	$sp = preg_replace('/[B#YZ]/', 'X', $sp);           // no markup on B stones
	$sp = preg_replace('/[W@QP]/', 'O', $sp);           // no markup on W stones
	return explode("\n", $sp);
    }

    /**
     * Get goban position at end of diagram (after any moves, without markup)
     * @return array Array of goban lines
     */
    function getEndPosition()
    {
	$sp = preg_replace('/[CSTMa-z]/', '.', $this->goban);   // no markup
	$sp = preg_replace('/[B#YZ]/', 'X', $sp);               // no markup on B stones
	$sp = preg_replace('/[W@QP]/', 'O', $sp);               // no markup on W stones
	$col = ($this->firstColor == 'B') ? 'O' : 'X';          // even moves
	$sp = preg_replace('/[02468]/', $col, $sp);
	$col = ($this->firstColor == 'B') ? 'X' : 'O';          // odd moves
	$sp = preg_replace('/[13579]/', $col, $sp);
	return explode("\n", $sp);
    }

    /**
     * Get board markup (but not advanced markup like AR, LN)
     * @return array Array of markup info
     */
    function getMarkup()
    {
	if (isset($this->markup))
	    return $this->markup;

	$rows = split("\n", $this->goban);
	$this->markup = array('CR' => array(),
			      'SQ' => array(),
			      'TR' => array(),
			      'MA' => array(),
			      'LB' => array(),
	                      'wildcard' => array(),
	                      'ignore' => array());

	for($y = 0; $y < count($rows); $y++)
	    for($x = 0; $x < strlen($rows[$y]); $x++) {
	    	$c = $rows[$y][$x];
	    	switch ($c)
		{
		    case 'B':
		    case 'W':
		    case 'C':	$this->markup['CR'][] = array($x,$y,$c); break;
		    case '#':
		    case '@':
		    case 'S':	$this->markup['SQ'][] = array($x,$y,$c); break;
		    case 'Y':
		    case 'Q':
		    case 'T':	$this->markup['TR'][] = array($x,$y,$c); break;
		    case 'Z':
		    case 'P':
		    case 'M':	$this->markup['MA'][] = array($x,$y,$c); break;
		    case 'A':
		    case 'V':
		    case '*':   $this->markup['wildcard'][] = array($x,$y,$c);  break;
		    case '?':   $this->markup['ignore'][] = array($x,$y,$c);  break;

		    default:	if (($c >= 'a')	&& ($c <= 'z'))
				    $this->markup['LB'][] = array($x,$y,$c);
				break;
		}
	    }

	return $this->markup;
    }

    /**
     * Parse diagram title for any moves in there.
     * Adds them to $this->moves
     */
    protected function parseTitleMoves()
    {
	if (($cnt=preg_match_all('"(\d+) at [WB]?(\d+)"', $this->title, $match)))
	{
	    $mvstart = $this->startMoveNum - 1;
	    for ($i=0; $i < $cnt; $i++) {
		$new = $match[1][$i] - $mvstart;
		$old = $match[2][$i] - $mvstart;
		if ($new < 1 || $new > 15 || $old < 1 || $old > 14)
		    continue;
		if (isset($this->moves[$new])   // only set if not set on board
		||  !isset($this->moves[$old])) // but referred move must be set
		    continue;
	    	$this->moves[$new] = $this->moves[$old];
	    }
	}
    }

    /**
     * Get moves in diagram
     * @param boolean $withTitleMoves Whether or not moves in title string should be included
     * @return array Array of moves
     */
    function getMoves($withTitleMoves=false)
    {
	if (isset($this->moves)) {
	    return $this->moves;
        }
	$rows = split("\n", $this->goban);
	$this->moves = array();
	for($y = 0; $y < count($rows); $y++) {
	    for($x = 0; $x < strlen($rows[$y]); $x++) {
	    	$c = $rows[$y][$x];
		if (preg_match('/[0-9]/', $c)) {
		    $c = ($c == '0') ? 10 : (integer)$c;
		    $this->moves[$c][] = array($x, $y);
		}
	    }
        }
	if ($withTitleMoves) {
	    $this->parseTitleMoves();
        }
	return $this->moves;
    }

    /**
     * Get diagram title
     * @param boolean $plain Get title as is, or HTML escaped
     * @return string
     */
    function getTitle($plain=false)
    {
        if ($plain)
            return $this->title;
        else
            return htmlspecialchars($this->title);
    }

    /**
     * Get coordinates of linked areas in diagram.
     * @return array
     */
    protected function getLinkAreas()
    {
	$links = array();
	$rg = explode("\n", $this->goban);
	for ($ypos = 0; $ypos < $this->height; $ypos++) {
	    for ($xpos = 0; $xpos < $this->width; $xpos++) {
		$curchar = $rg[$ypos][$xpos];
		if (isset($this->linkmap[$curchar])) {
		    $destination = $this->linkmap[$curchar];
		    $title = htmlspecialchars($destination);
		    $links[] = array($this->dim->getArea($xpos, $ypos),
		    		     $title, $destination);
		}
	    }
        }
	return $links;
    }

    /**
     * Get HTML link map of linked areas in diagram
     * @param string $uriname HTML map name attribute (URL)
     * @return string HTML of link map
     */
    function getLinkMap($uriname)
    {
	if (!count($this->linkmap))
	    return NULL;

	$links = $this->getLinkAreas();
	if (!count($links))
	    return NULL;

	$html = "<map name='$uriname'>\n";
	foreach ($links as $link) {
	    list ($x, $y, $xx, $yy) = $link[0];
	    $html .= "<area shape='rect' coords='$x,$y,$xx,$yy' href='$link[2]' title=\"$link[1]\" alt=\"$link[1]\">\n";
	}
	$html .= "</map>\n";
	return $html;
    }

    /**
     * Return pixel dimensions of diagram image
     * @return array Array of width/height
     */
    function getDimensions()
    {
	return array($this->dim->width, $this->dim->height);
    }
}


/**
 * Creates PNG image and thumbnail image for a given diagram
 */
class GoDiagramPNG extends GoDiagram
{
    protected $img;
    protected $colors;

    /**
     * Set up object by cloning data from $diagram
     * @param GoDiagram $diagram Diagram object from which data gets cloned
     */
    function __construct(GoDiagram $diagram)
    {
	foreach ($diagram as $key => $val)	// 'clone'
	    $this->$key = $diagram->$key;
    }

    /**
     * Create PHP image object and draw diagram
     */
    protected function drawDiagram()
    {
	// create image
	$this->img = ImageCreate($this->dim->width, $this->dim->height);

	// set up colors
	$this->colors['black'] = ImageColorAllocate($this->img, 0, 0, 0);
	$this->colors['white'] = ImageColorAllocate($this->img, 255, 255, 255);
	$this->colors['red']   = ImageColorAllocate($this->img, 255, 55, 55);
	$this->colors['goban'] = ImageColorAllocate($this->img, 242, 176, 109);
	$this->colors['border'] = ImageColorAllocate($this->img, 150, 110, 65);
	$this->colors['border2'] = ImageColorAllocate($this->img, 210, 145, 80);
	$this->colors['open'] = ImageColorAllocate($this->img, 255, 210, 140);
	$this->colors['link']  = ImageColorAllocate($this->img, 202, 106, 69);
	$this->colors['blue']  = ImageColorAllocate($this->img, 0, 30, 180);
	$this->colors['blue2'] = ImageColorAllocate($this->img, 70, 100, 150);
	$this->colors['lightgoban'] = ImageColorAllocate($this->img, 242, 216, 190);
	$this->colors['grey'] = ImageColorAllocate($this->img, 134, 134, 134);

	$this->drawBackground();
	if ($this->coordinates)
	    $this->drawCoordinates($this->colors['black']);
	$this->drawBorder();
	$this->drawGobanAndStones();
	$this->drawSimpleMarkUp();
	$this->drawMoveMarkUp();
	$this->drawComplexMarkUp();
    }

    /**
     * Draw background of image (including link areas)
     */
    protected function drawBackground()
    {
	// create background
	ImageFill($this->img, 0, 0, $this->colors['goban']);

	$links = $this->getLinkAreas();
	foreach ($links as $link) {
	     list($x, $y, $xx, $yy) = $link[0];
	     ImageFilledRectangle($this->img, $x, $y,
	     				      $xx, $yy, $this->colors['link']);
	}
    }

    /**
     * Draw coordinates
     * @param int $color Color index in image
     */
    protected function drawCoordinates($color)
    {
	$coords = 'ABCDEFGHJKLMNOPQRSTUVWXYZabcdefghjklmnopqrstuvwxyz123456789';

	$coordy = ($this->bottomborder) ? $this->height : $this->boardSize;
	if ($this->leftborder)
	    $coordx = 0;
	elseif ($this->rightborder) {
	    $coordx = $this->boardSize - $this->width;
	    if ($coordx < 0)
		$coordx = 0;
	}

	$leftx = 2 + $this->dim->font_width;
	$img_y = 2 + $this->dim->font_height+2
		   + $this->dim->radius - ($this->dim->font_height/2);
	for ($y = 0; $y < $this->height; $y++) {
	    $xoffset = ($coordy >= 10) ? $this->dim->font_width
	   			       : $this->dim->font_width/2;
	    ImageString($this->img, $this->dim->font_size,
	   		$leftx-$xoffset, $img_y, "$coordy", $color);
	    $img_y += $this->dim->radius*2;
	    $coordy--;
	}

	$topy = 2;
	$img_x = 2 + $this->dim->font_width*2+4
		   + $this->dim->radius - $this->dim->font_width/2;
	for ($x = 0; $x < $this->width; $x++) {
	    ImageString($this->img, $this->dim->font_size, $img_x, $topy,
      			$coords[$coordx], $color);
	    $img_x += $this->dim->radius*2;
	    $coordx++;
	}
    }

    /**
     * Draw border of diagram
     */
    protected function drawBorder()
    {
	$xl1 = $xl2 = 0;	// x-offset left
	$xr1 = $xr2 = 0;	// x-offset right
	$yt1 = $yt2 = 0;	// y-offset top
	$yb1 = $yb2 = 0;	// y-offset bottom

	if ($this->topborder)	{ $yt1 = 2; $yt2 = 1; }
	if ($this->bottomborder)	{ $yb1 = 2; $yb2 = 1; }
	if ($this->leftborder)	{ $xl1 = 2; $xl2 = 1; }
	if ($this->rightborder)	{ $xr1 = 2; $xr2 = 1; }

	// some shortcuts
	$Cw =& $this->colors['white'];
	$Cb =& $this->colors['border'];
	$Cb2 =& $this->colors['border2'];
	$Co =& $this->colors['open'];
	$w1 = $this->dim->width - 1;
	$h1 = $this->dim->height - 1;

	if ($this->topborder) {
	    ImageSetPixel($this->img, 0, 0, $Cw);
	    ImageSetPixel($this->img, $w1, 0, $Cw);
	    ImageLine($this->img, $xl1, 0, $w1-$xr1, 0, $Cb);
	    ImageLine($this->img, $xl2, 1, $w1-$xr2, 1, $Cb2);
	}
	else
            ImageLine($this->img, 0, 0, $w1, 0, $Co);

	if ($this->bottomborder) {
	    ImageSetPixel($this->img, 0, $h1, $Cw);
	    ImageSetPixel($this->img, $w1, $h1, $Cw);
	    ImageLine($this->img, $xl1, $h1, $w1-$xr1, $h1, $Cb);
	    ImageLine($this->img, $xl2, $h1-1, $w1-$xr2, $h1-1, $Cb2);
	}
	else
	    ImageLine($this->img, 0, $h1, $w1, $h1, $Co);

	if ($this->leftborder) {
	    ImageSetPixel($this->img, 0, 0, $Cw);
	    ImageSetPixel($this->img, 0, $h1, $Cw);
	    ImageLine($this->img, 0, $yt1, 0, $h1-$yb1, $Cb);
	    ImageLine($this->img, 1, $yt2, 1, $h1-$yb2, $Cb2);
	}
	else
	    ImageLine($this->img, 0, 0, 0, $h1, $Co);

	if ($this->rightborder) {
	    ImageSetPixel($this->img, $w1, 0, $Cw);
	    ImageSetPixel($this->img, $w1, $h1, $Cw);
	    ImageLine($this->img, $w1, $yt1, $w1, $h1-$yb1, $Cb);
	    ImageLine($this->img, $w1-1, $yt2, $w1-1, $h1-$yb2, $Cb2);
	}
	else
	    ImageLine($this->img, $w1, 0, $w1, $h1, $Co);
    }

    /**
     * Draw lines, stones, hoshi points
     */
    protected function drawGobanAndStones()
    {
	$Cb =& $this->colors['black'];

	$pos = $this->getEndPosition();
	for ($ypos=0; $ypos < $this->height; $ypos++) {
	    for ($xpos=0; $xpos < $this->width; $xpos++)
	    {
        	list ($x, $y) = $this->dim->toImgCoords($xpos, $ypos);
        	$curchar = $pos[$ypos][$xpos];

		if ($curchar == 'X')
		    $this->drawStone($x, $y, $Cb, $Cb);
		elseif ($curchar == 'O')
		    $this->drawStone($x, $y, $Cb, $this->colors['white']);
		elseif (!preg_match('/[%_AV*]/', $curchar))
		{
		    $type = $this->getIntersectionType($pos, $xpos, $ypos);
		    if ($curchar != '?')
		        $this->drawIntersection($x, $y, $type, $this->colors['black']);
		    else
		        $this->drawIgnoredPoint($xpos, $ypos, $type);
		    if ($curchar == ',')
	        	$this->drawHoshi($x, $y);
		}
	    }
        }
    }

    /**
     * Draw ignored points (pattern match) greyed out.
     * @param int $xpos X position
     * @param int $ypos Y position
     * @param string $type Border type
     */
    protected function drawIgnoredPoint($xpos, $ypos, $type)
    {
        list($x1, $y1, $x2, $y2) = $this->dim->getArea($xpos, $ypos);
        if (strpos($type, 'U') === false && !$ypos)
	    $y1 -= 2;
	if (strpos($type, 'B') === false && $ypos == $this->height-1)
	    $y2 += 2;
	if (strpos($type, 'L') === false && !$xpos)
	    $x1 -= 2;
	if (strpos($type, 'R') === false && $xpos == $this->width-1)
	    $x2 += 2;
        ImageFilledRectangle($this->img, $x1, $y1, $x2, $y2,
                             $this->colors['lightgoban']);
	list ($x, $y) = $this->dim->toImgCoords($xpos, $ypos);
	$this->drawIntersection($x, $y, $type, $this->colors['grey']);
    }

    /**
     * Draw simple (single point) markup
     */
    protected function drawSimpleMarkup()
    {
	$dim =& $this->dim;		// shortcuts
	$r =& $dim->radius;
	$Cr = $this->colors['red'];

	$ma = $this->getMarkup();

	foreach($ma['CR'] as $pos) {
	    list($x, $y) = $dim->toImgCoords($pos);
	    $this->drawCircle($x, $y, $r, $Cr);
	}
	foreach($ma['SQ'] as $pos) {
	    list($x, $y) = $dim->toImgCoords($pos);
	    $this->drawSquare($x, $y, $r, $Cr);
	}
	foreach($ma['TR'] as $pos) {
	    list($x, $y) = $dim->toImgCoords($pos);
	    $this->drawTriangle($x, $y, $r, $Cr);
	}
	foreach($ma['MA'] as $pos) {
	    list($x, $y) = $dim->toImgCoords($pos);
	    $this->drawMarker($x, $y, $r, $Cr);
	}
	foreach($ma['LB'] as $pos) {
	    $bgcol = (isset($this->linkmap[$pos[2]])) ? $this->colors['link']
						      : $this->colors['goban'];
	    list($x, $y) = $dim->toImgCoords($pos);
	    $this->drawSquare($x, $y, $r+4, $bgcol);
	    $this->drawLetter($x, $y, $pos[2], $this->colors['black'], true);
	}
	foreach ($ma['wildcard'] as $pos) {
	    list($x, $y) = $dim->toImgCoords($pos);
	    $this->drawWildcard($x, $y, $pos[2]);
	}
    }

    /**
     * Draw move numbers onto stones
     */
    protected function drawMoveMarkup()
    {
	$mv = $this->getMoves();

	if ($this->firstColor == 'W') {
	    $col[0] = $this->colors['white'];
	    $col[1] = $this->colors['black'];
	}
        else {
	    $col[0] = $this->colors['black'];
	    $col[1] = $this->colors['white'];
	}
	for ($i = 1; $i <= 10; $i++) {
	    if (isset($mv[$i])) {
	        foreach ($mv[$i] as $coord) {
	            $move = ($i + $this->startMoveNum - 1) % 100;
	            if ($move < 0)
            	        continue;
            	    elseif ($move == 0)	// = 100 % 100
            	        $move = '00';
            	    list($x, $y) = $this->dim->toImgCoords($coord);
        	    $this->drawLetter($x, $y, $move, $col[$i%2], true);
	        }
	    }
	}
    }

    /**
     * Draw complex markup (AR, LN)
     */
    protected function drawComplexMarkup()
    {
	foreach ($this->specialMarkup as $markup) {
	    switch($markup[0])
	    {
		case "AR":
		case "LN":  list($x1,$y1) = $this->dim->toImgCoords($markup[1][0]);
			    list($x2,$y2) = $this->dim->toImgCoords($markup[1][1]);
			    $this->drawLine($x1, $y1, $x2, $y2,
				     $this->colors['blue'],
				     $this->colors['blue2'],
				     ($markup[0] == 'AR'));
			    break;
	    }
	}
    }

    /**
     * Get border type of point (for drawing intersection lines)
     * @param array $position Array of diagram lines; (0,0)=UL
     * @param int $x X offset into position
     * @param int $y Y offset into position
     * @return string Combination of: 'U', 'L', 'R', 'B'
     */
    protected function getIntersectionType($position, $x, $y)
    {
	$type = '';
	if (($y > 0 && $position[$y-1][$x] == '%')
	|| ($y == 0 && $this->topborder))
	    $type = 'U';
	if (($y < $this->height-1 && $position[$y+1][$x] == '%')
	|| ($y == $this->height-1 && $this->bottomborder))
	    $type .= 'B';
	if (($x > 0 && $position[$y][$x-1] == '%')
	|| ($x == 0 && $this->leftborder))
	    $type .= 'L';
	if (($x < $this->width-1 && $position[$y][$x+1] == '%')
	|| ($x == $this->width-1 && $this->rightborder))
	    $type .= 'R';
	return $type;
    }

    /**
     * Draw lines of single intersection.
     * @param int $x X position in pixel
     * @param int $y Y position in pixel
     * @param string $type Border type (combination of U,B,L,R)
     * @param int $color Color index
     */
    protected function drawIntersection($x, $y, $type, $color)
    {
	$r =& $this->dim->radius;

	if (strpos($type, 'U') === false)
	    ImageLine($this->img, $x, $y-$r, $x, $y, $color);
	if (strpos($type, 'B') === false)
	    ImageLine($this->img, $x, $y+$r, $x, $y, $color);
	if (strpos($type, 'L') === false)
	    ImageLine($this->img, $x-$r, $y, $x, $y, $color);
	if (strpos($type, 'R') === false)
	    ImageLine($this->img, $x+$r, $y, $x, $y, $color);

	// linear board?
	if ((strpos($type, 'UB') !== false) || (strpos($type, 'LR') !== false))
    	    $this->drawHoshi($x, $y);
    }

    /**
     * Draw a single stone.
     * @param int $x X position in pixel
     * @param int $y Y position in pixel
     * @param int $Crim Color index of rim
     * @param int $Cinside Color index of stone
     */
    protected function drawStone ($x, $y, $Crim, $Cinside)
    {
       $d = $this->dim->radius*2;
       ImageArc($this->img, $x, $y, $d, $d, 0, 360, $Crim);
       ImageFill($this->img, $x, $y, $Cinside);
    }

    /**
     * Draw a circle with a given radius.
     * @param int $x X position in pixel
     * @param int $y Y position in pixel
     * @param int $radius Radius of circle in pixel
     * @param int $color Color index
     */
    protected function drawCircle($x, $y, $radius, $color)
    {
	ImageArc($this->img, $x, $y, $radius-2, $radius-2 , 0, 360, $color);
	ImageArc($this->img, $x, $y, $radius-1, $radius-1 , 0, 360, $color);
	ImageArc($this->img, $x, $y, $radius, $radius, 0, 360, $color);
    }

    /**
     * Draw a square with a side length of 2*$radius
     * @param int $x X position in pixel
     * @param int $y Y position in pixel
     * @param int $radius Radius
     * @param int $color Color index
     */
    protected function drawSquare($x, $y, $radius, $color)
    {
	ImageFilledRectangle($this->img, $x-$radius/2+2, $y-$radius/2+2,
                                         $x+$radius/2-2, $y+$radius/2-2, $color);
    }

    /**
     * Draw a triangle with a base side length of 2*$radius
     * @param int $x X position in pixel
     * @param int $y Y position in pixel
     * @param int $radius Radius
     * @param int $color Color index
     */
    protected function drawTriangle($x, $y, $radius, $color)
    {
	$points = array($x, $y-$radius/2,
			$x-$radius/2, $y+$radius/2-2,
			$x+$radius/2, $y+$radius/2-2);
	ImageFilledPolygon($this->img, $points, 3, $color);
    }

    /**
     * Draw an X marker
     * @param int $x X position in pixel
     * @param int $y Y position in pixel
     * @param int $radius Radius
     * @param int $color Color index
     */
    protected function drawMarker($x, $y, $radius, $color)
    {
	$r = $this->dim->radius / 2;
	$this->drawLine($x-$r, $y-$r, $x+$r, $y+$r, $color, $color, false, $r/4);
	$this->drawLine($x+$r, $y-$r, $x-$r, $y+$r, $color, $color, false, $r/4);
    }

    /**
     * Draw a hoshi marker on the intersection
     * @param int $x X position in pixel
     * @param int $y Y position in pixel
     */
    protected function drawHoshi($x, $y)
    {
	$Cb =& $this->colors['black'];
	ImageArc($this->img, $x, $y, 6,6, 0,360, $Cb);
	ImageArc($this->img, $x, $y, 5,5, 0,360, $Cb);
	ImageArc($this->img, $x, $y, 4,4, 0,360, $Cb);
    }

    /**
     * Draw wildcard stone/marker (pattern match wildcards)
     * @param int $x X position in pixel
     * @param int $y Y position in pixel
     * @param string $type 'A' (black or empty), 'V' (white or empty), '*' (black or white)
     */
    protected function drawWildcard($x, $y, $type)
    {
       $r = $this->dim->radius;
       ImageArc($this->img, $x, $y, $r*2, $r*2, 0, 360, $this->colors['black']);
       switch ($type) {
           case 'A': $left = 0; $right = $this->colors['black']; break;
           case 'V': $left = 0; $right = $this->colors['white']; break;
           case '*': $left = $this->colors['white'];
                     $right = $this->colors['black'];
                     break;
       }
       ImageLine($this->img, $x, $y-$r+1, $x, $y+$r-2, $right);
       ImageFill($this->img, $x+1, $y, $right);
       if ($left) {
           ImageFill($this->img, $x-1, $y, $left);
       }
    }

    /**
     * Draw letter markup (at most 2 letters)
     * @param int $x X position in pixel
     * @param int $y Y position in pixel
     * @param string $text Letter(s) to draw
     * @param int $color Color index
     * @param boolean $bold Larger font size / bold font face
     */
    protected function drawLetter($x, $y, $text, $color, $bold=false)
    {
	$font = $bold ? $this->dim->font_size+1 : $this->dim->font_size;
	$text = "$text";	// force string
	$xoffset = (strlen($text)==2) ? $this->dim->font_width
				      : $this->dim->font_width/2;
	ImageString($this->img, $font, $x-$xoffset,
		    $y-($this->dim->font_height/2), $text, $color);
    }

    /**
     * Draw a line or arrow
     * @param int $x1 start X position in pixel
     * @param int $y1 start Y position in pixel
     * @param int $x2 end X position in pixel
     * @param int $y2 end Y position in pixel
     * @param int $fillcolor Color index for line and arrow body
     * @param int $color Color index for border of line and arrow
     * @param boolean $arrow Whether to draw an arrow or not
     * @param int $r Radius; if 0, then default radius is used
     */
    protected function drawLine($x1,$y1, $x2,$y2, $fillcolor, $color, $arrow, $r=0)
    {
	if (!$r)
	    $r = $this->dim->radius/4 - 1;

	$alpha = atan2($y2-$y1,$x2-$x1);
	$points = array($x1+$r*sin($alpha), $y1-$r*cos($alpha),
			$x1-$r*sin($alpha), $y1+$r*cos($alpha));
	if (!$arrow)
	{
	    array_push($points, $x2-$r*sin($alpha), $y2+$r*cos($alpha),
				$x2+$r*sin($alpha), $y2-$r*cos($alpha));
	}
	else
	{
	    $x3 = $x2-($this->dim->radius*1.3) * cos($alpha);
	    $y3 = $y2-($this->dim->radius*1.3) * sin($alpha);
	    $ar = $this->dim->radius/2-1;
	    $x4 = $x3-$r*sin($alpha);
	    $y4 = $y3+$r*cos($alpha);
	    $x3 += $r*sin($alpha);
	    $y3 -= $r*cos($alpha);
	    array_push($points, $x4, $y4,
				$x4-$ar*sin($alpha), $y4+$ar*cos($alpha),
				$x2, $y2,
				$x3+$ar*sin($alpha), $y3-$ar*cos($alpha),
				$x3, $y3);
	}
	ImageFilledPolygon($this->img, $points, count($points)/2, $fillcolor);
	ImagePolygon($this->img, $points, count($points)/2, $color);
    }

    /**
     * Create the diagram image file.
     * @return object PHP image object
     */
    function createPNG()
    {
        if (empty($this->img))
            $this->drawDiagram();
	return $this->img;
    }

    /**
     * Create the diagram thumbnail image.
     * @return object PHP image object
     */
    function createThumbnail()
    {
        if (empty($this->img))
            $this->drawDiagram();
	$thumb = ImageCreateTrueColor($this->dim->width/2,
				      $this->dim->height/2);
	ImageCopyResampled($thumb, $this->img, 0,0,0,0,
			   $this->dim->width/2, $this->dim->height/2,
			   $this->dim->width, $this->dim->height);
	return $thumb;
    }
}


/**
 * Creates SGF file for given diagram.
 */
class GoDiagramSGF extends GoDiagram
{
    protected $heightdefined = false;	// board parameters
    protected $widthdefined = false;
    protected $sgfwidth;
    protected $sgfheight;
    protected $offsetX;	// SGF position offset
    protected $offsetY;
    protected $url;

    /**
     * Set up object by cloning data from $diagram
     * @param GoDiagram $diagram Diagram object from which data gets cloned
     * @param string $url URL to insert into SGF root node
     */
    public function __construct(GoDiagram $diagram, $url='')
    {
	foreach ($diagram as $key => $val)	// clone
	    $this->$key = $diagram->$key;
        $this->url = $url;
	$this->initSGFOffsets();
    }

    /**
     * Set board dimensions and offsets for SGF file.
     */
    protected function initSGFOffsets()
    {
	if ($this->topborder && $this->bottomborder) {
	    $this->heightdefined = true;
	    $this->sgfheight = $this->height;
	    $this->offsetY = 0;
	}
	else {
	    $this->heightdefined = false;
	    $this->sgfheight = max($this->boardSize, $this->height);
	    if ($this->topborder)
	    	$this->offsetY = 0;
	    elseif ($this->bottomborder)
	    	$this->offsetY = $this->sgfheight-$this->height;
	    else
	    	$this->offsetY = (int)(($this->sgfheight-$this->height)/2);
	}

	if ($this->leftborder && $this->rightborder) {
	    $this->widthdefined = true;
	    $this->sgfwidth = $this->width;
	    $this->offsetX = 0;
	}
	else {
	    $this->widthdefined = false;
	    $this->sgfwidth = max($this->boardSize, $this->width);
	    if ($this->leftborder)
	    	$this->offsetX = 0;
	    elseif ($this->rightborder)
	    	$this->offsetX = $this->sgfwidth-$this->width;
	    else
	    	$this->offsetX = (int)(($this->sgfwidth-$this->width)/2);
	}
    }

    /**
     * Create SGF data from diagram.
     * @return string SGF data as string
     */
    public function createSGF()
    {
        if ($this->hasMultipleMoves())
            return $this->getErrorSGF();
        else
            return $this->getValidSGF();
    }

    /**
     * Check whether the diagram contains moves that have the same move number.
     * @return boolean
     */
    protected function hasMultipleMoves()
    {
        $moves = $this->getMoves(true);
        foreach ($moves as $mv) {
            if (count($mv) > 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * Create an SGF file that just displays an error, nothing else
     * @return string SGF data
     */
    protected function getErrorSGF()
    {
	$sgf = "(;GM[1]FF[4]SZ[19]AP[Gowiki:2009]\n"
	     . "C[I'm sorry, but the SGF file could not be created.])\n";
	return $sgf;
    }

    /**
     * Create the SGF data
     * @return string SGF data
     */
    protected function getValidSGF()
    {
	$sgf = '(' . $this->getRootNode();
	$sgf .= $this->getSetupPosition();
	$markup = $this->getSGFMarkup() . $this->getSpecialSGFMarkup();
	$sgf .= $markup;
	$sgf .= "\n" . $this->getSGFMoves();
	$sgf .= "\n\n;" . trim($markup);
	$sgf .= "\n)\n";
	return $sgf;
    }

    /**
     * Build data for root node
     * @return string SGF data for root node
     */
    protected function getRootNode()
    {
	$title = str_replace(']', '\]', $this->title);
	$size = ($this->sgfwidth == $this->sgfheight) ? $this->sgfheight
	                                              : "$this->sgfwidth:$this->sgfheight";
        $PC = empty($this->url) ? '' : "PC[$this->url]\n";
        $C = empty($this->url) ? $title : "Diagram from $this->url\n\n$title";
	$node = ";GM[1]FF[4]SZ[$size]\n"
	      . "GN[$title]\n"
	      . $PC
	      . "AP[GoWiki:2009]\n"
	      . "DT[".date("Y-m-d")."]\n"
	      . "C[$C]\n"
	      . "PL[$this->firstColor]\n";
	return $node;
    }

    /**
     * Transform internal coordinates to SGF coordinate characters
     * @param int|array $x Either the X coordinate, or if $y is not set, then an array with X&Y
     * @param int $y Y coordinate or NULL
     * @return string SGF coordinate characters for X and Y
     */
    protected function toSGFCoords($x, $y=NULL)
    {
	if (!isset($y))	{
	    $y = $x[1];
	    $x = $x[0];
	}
	return chr(97+$x+$this->offsetX) . chr(97+$y+$this->offsetY);
    }

    /**
     * Builds data for how to set up inital board position
     * @return string SGF data with AB[] and AW[] properties
     */
    protected function getSetupPosition()
    {
	$pos = $this->getStartPosition();
	$AB = array();
	$AW = array();
	for ($ypos = 0; $ypos < $this->height; $ypos++) {
	   for ($xpos = 0; $xpos < $this->width; $xpos++) {
              $curchar = $pos[$ypos][$xpos];
	      $position = $this->toSGFCoords($xpos, $ypos);
	      if ($curchar == 'X')
		 $AB[] = "[$position]";
	      elseif ($curchar == 'O')
		 $AW[] = "[$position]";
	   }
	}
	$ret = '';
	if (count($AB))
	    $ret .= "\nAB" . join('', $AB);
	if (count($AW))
	    $ret .= "\nAW" . join('', $AW);
	return $ret;
    }

    /**
     * Build properties for board and stone markup.
     * @return string SGF data with CR[], SQ[], TR[], MA[], LB[] properties
     */
    protected function getSGFMarkup()
    {
	$sgf = '';
	$markup = $this->getMarkup();
	foreach ($markup as $prop => $values) {
	    if (count($values) && ctype_upper($prop)) {
	        $sgf .= "\n$prop";
		foreach ($values as $v) {
		    $pos = $this->toSGFCoords($v);
		    if ($prop == 'LB')
			$sgf .= "[$pos:$v[2]]";
		    else
			$sgf .= "[$pos]";
		}
	    }
	}
	return $sgf;
    }

    /**
     * Build properties for arrow and lines
     * @return string SGF data with AR[] and LN[] properties
     */
    protected function getSpecialSGFMarkup()
    {
	$sgf = '';
	$AR = array();
	$LN = array();
	foreach ($this->specialMarkup as $markup) {
	    if ($markup[0] == 'AR') {
	    	$AR[] = '[' . $this->toSGFCoords($markup[1][0])
		      . ':' . $this->toSGFCoords($markup[1][1]) . ']';
	    }
	    elseif ($markup[0] == 'LN') {
	    	$LN[] = '[' . $this->toSGFCoords($markup[1][0])
		      . ':' . $this->toSGFCoords($markup[1][1]) . ']';
	    }
	}
	if (count($AR))
	    $sgf = "\nAR" . join('', $AR);
	if (count($LN))
	    $sgf .= "\nLN" . join('', $LN);
	return $sgf;
    }

    /**
     * Build SGF data (nodes and properties) for moves on board
     * @return string SGF data for moves
     */
    protected function getSGFMoves()
    {
	$sgf = '';
	$moves = $this->getMoves(true);
	$mv = $this->startMoveNum;
	$prop[0] = ($this->firstColor == 'W') ? 'B' : 'W';
	$prop[1] = ($this->firstColor == 'W') ? 'W' : 'B';

	for ($i = 1; $i <= 15; $i++, $mv++) {
	    if (isset($moves[$i])) {
		$pos = $this->toSGFCoords($moves[$i][0]);
	    	$sgf .= "\n;" . $prop[$i%2] . "[$pos]";
		$sgf .= 'C[' . $prop[$i%2] . $mv . ']';
		$sgf .= "MN[$mv]";
	    }
	}
	return $sgf;
    }
}

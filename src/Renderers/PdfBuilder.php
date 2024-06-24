<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Renderers;
use Lemonade\Pdf\Generator\BaseFPDF;


final class PdfBuilder extends BaseFPDF
{

    /**
     * @var array
     */
    protected array $filterAlphaChannel = [];

    /**
     * @param string $orientation
     * @param string $unit
     * @param string $size
     */
	public function __construct(string $orientation = "P", string $unit = "mm", string $size = "A4") {
	    
		$px = false;

		if ($unit === "px") {

            $unit = "pt";
			$px = true;
		}

		parent::__construct($orientation, $unit, $size);

		if ($px) {

			$this->k = 72 / 96;

			$this->wPt = $this->w * $this->k;
			$this->hPt = $this->h * $this->k;
		}
	}

    /**
     * @param $fontPath
     * @return void
     */
	function SetFontPath($fontPath): void
    {
	    
		$this->fontpath = $fontPath;
	}

    /**
     * @param array $points
     * @param string $style
     * @return void
     */
	function Polygon(array $points = [], string $style = "D"): void
    {
	    
		//Draw a polygon
        $op = match ($style) {
            "F" => "f",
            "FD", "DF" => "b",
            default => "s",
        };

		$h = $this->h;
		$k = $this->k;

		$points_string = "";

		for ($i = 0; $i < count($points); $i += 2) {

			$points_string .= sprintf('%.2F %.2F', $points[$i] * $k, ($h - $points[$i + 1]) * $k);

			if ($i == 0) {

				$points_string .= ' m ';

			} else {

				$points_string .= ' l ';
			}
		}

		$this->_out($points_string . $op);
	}

    /**
     * @param $alpha
     * @return void
     */
	public function addFilterAplha($alpha): void
    {
	    
	    $this->_addAlphaChannel($this->_registerAplhaChannel(["ca" => $alpha, "CA" => $alpha, "BM" => "/Normal"]));
	}

    /**
     * @param $gs
     * @return void
     */
	private function _addAlphaChannel($gs): void
    {
	    
	    $this->_out(sprintf('/GS%d gs', $gs));
	}

    /**
     * @param $parms
     * @return int
     */
	private function _registerAplhaChannel($parms): int
    {
	    
	    $n = count($this->filterAlphaChannel) + 1;
	    
	    $this->filterAlphaChannel[$n]["parms"] = $parms;

	    return $n;
	}

    /**
     * @return void
     */
	function _enddoc(): void
    {
	    
	    if(!empty($this->filterAlphaChannel) && $this->PDFVersion < "1.4") {
	        
	        $this->PDFVersion = "1.4";	        
	    }
	    
	    parent::_enddoc();
	}

    /**
     * @return void
     */
	function _putAlphaChannel(): void
    {
	    
	    for ($i = 1; $i <= count($this->filterAlphaChannel); $i++) {
	        
	        $this->_newobj();
	        $this->filterAlphaChannel[$i]["n"] = $this->n;
	        $this->_put("<</Type /ExtGState");
	        
	        $param = $this->filterAlphaChannel[$i]['parms'];
	        
	        $this->_put(sprintf("/ca %.3F", $param["ca"]));
	        $this->_put(sprintf("/CA %.3F", $param["CA"]));
	        $this->_put("/BM " . $param["BM"]);
	        $this->_put(">>");
	        $this->_put("endobj");
	    }
	}

    /**
     * @return void
     */
	function _putresourcedict(): void
    {
	    
	    parent::_putresourcedict();
	    
	    if(!empty($alpha = $this->filterAlphaChannel)) {
	        
	        $this->_put("/ExtGState <<");
	        
	        foreach($alpha as $k => $alphaState)
	            $this->_put('/GS'.$k.' '.$alphaState['n'].' 0 R');
	            $this->_put(">>");
	        
	    }	    
	}

    /**
     * @return void
     */
	function _putresources(): void
    {
	    
	    $this->_putAlphaChannel();
	    
	    parent::_putresources();
	}
	

}

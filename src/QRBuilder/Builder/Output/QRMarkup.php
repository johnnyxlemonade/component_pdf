<?php

/**
 * Class QRMarkup
 *
 * @filesource   QRMarkup.php
 * @created      06.07.2019
 * @package      Lemonade\Pdf\QRBuilder\Builder\Output
 * @author       Johnny X. Lemonade <honzamudrak@gmail.com>
 * @copyright    2019 Johnny X. Lemonade
 * @license      MIT
 */
namespace Lemonade\Pdf\QRBuilder\Builder\Output;
use Lemonade\Pdf\QRBuilder\Exceptions\QRCodeOutputException;

/**
 * Converts the matrix into markup types: HTML, SVG, ...
 */
class QRMarkup extends QROutputAbstract{

	/**
	 * @return string
	 * @throws \Lemonade\Pdf\QRBuilder\Exceptions\QRCodeOutputException
	 */
	public function dump(){

		if($this->options->cachefile !== null && !is_writable(dirname($this->options->cachefile))){
			throw new QRCodeOutputException('Could not write data to cache file: '.$this->options->cachefile);
		}

		$data = $this->toSVG();

		if($this->options->cachefile !== null){
			$this->saveToFile($data);
		}

		return $data;
	}
	
	/**
	 * SVG support
	 * @return string|bool
	 */
	protected function toSVG(){
		$scale  = $this->options->scale;
		$length = $this->moduleCount * $scale;
		$matrix = $this->matrix->matrix();

		$svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="'.$length.'px" height="'.$length.'px">'
		       .$this->options->eol
		       .'<defs>'.$this->options->svgDefs.'</defs>'
		       .$this->options->eol;

		foreach($this->options->moduleValues as $M_TYPE => $value){

			// fallback
			if(is_bool($value)){
				$value = $value ? '#000' : '#fff';
			}

			$path = $this->options->eol;

			foreach($matrix as $y => $row){
				//we'll combine active blocks within a single row as a lightweight compression technique
				$start = null;
				$count = 0;

				foreach($row as $x => $module){

					if($module === $M_TYPE){
						$count++;

						if($start === null){
							$start = $x * $scale;
						}

						if(isset($row[$x + 1]) && $row[$x + 1] === $M_TYPE){
							continue;
						}
					}

					if($count > 0){
						$len = $count * $scale;
						$path .= 'M' .$start. ' ' .($y * $scale). ' h'.$len.' v'.$scale.' h-'.$len.'Z '.$this->options->eol;

						// reset count
						$count = 0;
						$start = null;
					}

				}

			}

			if(!empty($path)){
				$svg .= '<path class="qr-'.$M_TYPE.' '.$this->options->cssClass.'" stroke="transparent" fill="'.$value.'" fill-opacity="'.$this->options->svgOpacity.'" d="'.$path.'" />';
			}

			break;
		}

		// close svg
		$svg .= '</svg>'.$this->options->eol;

		// if saving to file, append the correct headers
		if($this->options->cachefile){
			return '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">'.$this->options->eol.$svg;
		}

		return $svg;
	}

}

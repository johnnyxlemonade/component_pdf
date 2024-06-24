<?php

/**
 * Class QRImage
 *
 * @filesource   QRImage.php
 * @created      06.07.2019
 * @package      Lemonade\Pdf\QRBuilder\Builder\Output
 * @author       Johnny X. Lemonade <honzamudrak@gmail.com>
 * @copyright    2019 Johnny X. Lemonade
 * @license      MIT
 */

namespace Lemonade\Pdf\QRBuilder\Builder\Output;
use Lemonade\Pdf\QRBuilder\Builder\QRCode;
use Lemonade\Pdf\QRBuilder\Builder\Data\QRMatrix;
use Lemonade\Pdf\QRBuilder\Exceptions\QRCodeOutputException;

/**
 * Converts the matrix into images, raw or base64 output
 */
class QRImage extends QROutputAbstract{

	const transparencyTypes = [
		QRCode::OUTPUT_IMAGE_PNG,
		QRCode::OUTPUT_IMAGE_GIF,
		QRCode::OUTPUT_IMAGE_WEBP
	];

	protected $moduleValues = [
		// light
		QRMatrix::M_DATA            => [255, 255, 255],
		QRMatrix::M_FINDER          => [255, 255, 255],
		QRMatrix::M_SEPARATOR       => [255, 255, 255],
		QRMatrix::M_ALIGNMENT       => [255, 255, 255],
		QRMatrix::M_TIMING          => [255, 255, 255],
		QRMatrix::M_FORMAT          => [255, 255, 255],
		QRMatrix::M_VERSION         => [255, 255, 255],
		QRMatrix::M_QUIETZONE       => [255, 255, 255],
		QRMatrix::M_TEST            => [255, 255, 255],
		// dark
		QRMatrix::M_DARKMODULE << 8 => [0, 0, 0],
		QRMatrix::M_DATA << 8       => [0, 0, 0],
	    QRMatrix::M_FINDER << 8     => [226, 11, 26], // finder
	    //QRMatrix::M_FINDER << 8     => [95, 155, 119], // finder	    
		QRMatrix::M_ALIGNMENT << 8  => [0, 0, 0],
		QRMatrix::M_TIMING << 8     => [0, 0, 0],
		QRMatrix::M_FORMAT << 8     => [0, 0, 0],
		QRMatrix::M_VERSION << 8    => [0, 0, 0],
		QRMatrix::M_TEST << 8       => [0, 0, 0],
	];

	/**
	 * @see imagecreatetruecolor()
	 * @var resource
	 */
	protected $image;

	/**
	 * @var int
	 */
	protected $scale;

	/**
	 * @var int
	 */
	protected $length;

	/**
	 * @see imagecolorallocate()
	 * @var int
	 */
	protected $background;

	/**
	 * @return string
	 * @throws \Lemonade\Pdf\QRBuilder\Exceptions\QRCodeOutputException
	 */
	public function dump() {
	    
		if($this->options->cachefile !== null) {
		    if(!is_dir(dirname($this->options->cachefile)) 
		        && !mkdir(dirname($this->options->cachefile), 0777, TRUE)  
		              && !is_writable(dirname($this->options->cachefile))) { // @ - dir may already exist
		        throw new QRCodeOutputException('Could not write data to cache file: '.$this->options->cachefile);
		    }			
		}

		$this->setImage();

		$moduleValues = is_array($this->options->moduleValues[QRMatrix::M_DATA])
			? $this->options->moduleValues // @codeCoverageIgnore
			: $this->moduleValues;

		foreach($this->matrix->matrix() as $y => $row){
			foreach($row as $x => $pixel){
				$this->setPixel($x, $y, imagecolorallocate($this->image, ...$moduleValues[$pixel]));
			}
		}

		$imageData = $this->dumpImage();

		if((bool)$this->options->imageBase64){
			$imageData = 'data:image/'.$this->options->outputType.';base64,'.base64_encode($imageData);
		}

		return $imageData;
	}

	/**
	 * @return void
	 */
	protected function setImage() {
	    
		$this->scale        = $this->options->scale;
		$this->length       = $this->moduleCount * $this->scale;
		$this->image        = imagecreatetruecolor($this->length, $this->length);
		$this->background   = imagecolorallocate($this->image, ...$this->options->imageTransparencyBG);

		if((bool)$this->options->imageTransparent && in_array($this->options->outputType, $this::transparencyTypes, true)){
			imagecolortransparent($this->image, $this->background);
		}

		imagefilledrectangle($this->image, 0, 0, $this->length, $this->length, $this->background);
	}

	/**
	 * @param $x
	 * @param $y
	 * @param $color
	 * @return void
	 */
	protected function setPixel($x, $y, $color){
		imagefilledrectangle(
			$this->image,
			$x * $this->scale,
			$y * $this->scale,
			($x + 1) * $this->scale - 1,
			($y + 1) * $this->scale - 1,
			$color
		);
	}

	/**
	 * @return string
	 * @throws \Lemonade\Pdf\QRBuilder\Exceptions\QRCodeOutputException
	 */
	protected function dumpImage() {
		ob_start();

		try{
			call_user_func([$this, $this->options->outputType !== null ? $this->options->outputType : QRCode::OUTPUT_IMAGE_PNG]);
		}
		// not going to cover edge cases
		// @codeCoverageIgnoreStart
		catch(\Exception $e){
			throw new QRCodeOutputException($e->getMessage());
		}
		// @codeCoverageIgnoreEnd

		$imageData = ob_get_contents();
		imagedestroy($this->image);

		ob_end_clean();

		return $imageData;
	}

	/**
	 * @return void
	 */
	protected function png() {
		imagepng(
			$this->image,
			$this->options->cachefile,
			in_array($this->options->pngCompression, range(-1, 9), true)
				? $this->options->pngCompression
				: -1
		);
	}

	/** 
	 * @return void
	 */
	protected function webp() {
		imagewebp($this->image, $this->options->cachefile);
	}
	
	/**
	 * @return void
	 */
	protected function gif() {
		imagegif($this->image, $this->options->cachefile);
	}	

	/**
	 * @return void
	 */
	protected function jpg() {
		imagejpeg(
			$this->image,
			$this->options->cachefile,
			in_array($this->options->jpegQuality, range(0, 100), true)
				? $this->options->jpegQuality
				: 85
		);
	}

}

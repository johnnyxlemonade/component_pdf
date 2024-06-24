<?php

/**
 * Class QRCode
 *
 * @filesource   QRCode.php
 * @created      06.07.2019
 * @package      Lemonade\Pdf\QRBuilder\Builder
 * @author       Johnny X. Lemonade <honzamudrak@gmail.com>
 * @copyright    2019 Johnny X. Lemonade
 * @license      MIT
 */
namespace Lemonade\Pdf\QRBuilder\Builder;
use Lemonade\Pdf\QRBuilder\Traits\ClassLoader;
use Lemonade\Pdf\QRBuilder\Exceptions\QRCodeDataException;
use Lemonade\Pdf\QRBuilder\Exceptions\QRCodeOutputException;
use Lemonade\Pdf\QRBuilder\Exceptions\QRCodeException;
use Lemonade\Pdf\QRBuilder\Builder\Data\AlphaNum;
use Lemonade\Pdf\QRBuilder\Builder\Data\Byte;
use Lemonade\Pdf\QRBuilder\Builder\Data\Kanji;
use Lemonade\Pdf\QRBuilder\Builder\Data\MaskPatternTester;
use Lemonade\Pdf\QRBuilder\Builder\Data\Number;
use Lemonade\Pdf\QRBuilder\Builder\Data\QRDataInterface;
use Lemonade\Pdf\QRBuilder\Builder\Output\QRImage;
use Lemonade\Pdf\QRBuilder\Builder\Output\QRMarkup;
use Lemonade\Pdf\QRBuilder\Builder\Output\QROutputInterface;
use Lemonade\Pdf\QRBuilder\Builder\Output\QRString;


/**
 * Turns a text string into a Model 2 QR Code
 * @link https://github.com/kazuhikoarase/qrcode-generator/tree/master/php
 * @link http://www.qrcode.com/en/codes/model12.html
 * @link http://www.thonky.com/qr-code-tutorial/
 */
class QRCode{
    
	use ClassLoader;

	/**
	 * API constants
	 */
	const OUTPUT_MARKUP_SVG   = 'svg';

	const OUTPUT_IMAGE_PNG    = 'png';
	const OUTPUT_IMAGE_JPG    = 'jpg';
	const OUTPUT_IMAGE_GIF    = 'gif';
	const OUTPUT_IMAGE_WEBP   = 'webp';
	
	const OUTPUT_HEADER_PNG   = 'Content-type: image/png';
	const OUTPUT_HEADER_JPG   = 'Content-type: image/jpeg';
	const OUTPUT_HEADER_GIF   = 'Content-type: image/gif';
	const OUTPUT_HEADER_WEBP  = 'Content-type: image/webp';

	const OUTPUT_STRING_JSON  = 'json';
	const OUTPUT_STRING_TEXT  = 'text';

	const OUTPUT_CUSTOM       = 'custom';

	const VERSION_AUTO        = -1;
	const MASK_PATTERN_AUTO   = -1;

	const ECC_L         = 0b01; // 7%.
	const ECC_M         = 0b00; // 15%.
	const ECC_Q         = 0b11; // 25%.
	const ECC_H         = 0b10; // 30%.

	const DATA_NUMBER   = 0b0001;
	const DATA_ALPHANUM = 0b0010;
	const DATA_BYTE     = 0b0100;
	const DATA_KANJI    = 0b1000;

	const ECC_MODES = [
		self::ECC_L => 0,
		self::ECC_M => 1,
		self::ECC_Q => 2,
		self::ECC_H => 3,
	];

	const DATA_MODES = [
		self::DATA_NUMBER   => 0,
		self::DATA_ALPHANUM => 1,
		self::DATA_BYTE     => 2,
		self::DATA_KANJI    => 3,
	];

	const OUTPUT_MODES = [
		QRMarkup::class => [
			self::OUTPUT_MARKUP_SVG
		],
		QRImage::class => [
			self::OUTPUT_IMAGE_PNG,
			self::OUTPUT_IMAGE_GIF,
			self::OUTPUT_IMAGE_JPG,
		    self::OUTPUT_IMAGE_WEBP
		],
		QRString::class => [
			self::OUTPUT_STRING_JSON,
			self::OUTPUT_STRING_TEXT,
		]
	];
		
	const OUTPUT_IMAGE_DATA = [
			self::OUTPUT_IMAGE_PNG  => self::OUTPUT_HEADER_PNG,
			self::OUTPUT_IMAGE_GIF  => self::OUTPUT_HEADER_GIF,
			self::OUTPUT_IMAGE_JPG  => self::OUTPUT_HEADER_JPG,
			self::OUTPUT_IMAGE_WEBP	=> self::OUTPUT_HEADER_WEBP
	];

	/**
	 * @var \Lemonade\Pdf\QRBuilder\Builder\QROptions
	 */
	protected $options;

	/**
	 * @var \Lemonade\Pdf\QRBuilder\Builder\Data\QRDataInterface
	 */
	protected $dataInterface;

	/**
	 * QRCode constructor.
	 * @param \Lemonade\Pdf\QRBuilder\Builder\QROptions|null $options
	 */
	public function __construct(QROptions $options = null){
		mb_internal_encoding('UTF-8');

		$this->setOptions($options instanceof QROptions ? $options : new QROptions);
	}

	/**
	 * Sets the options, called internally by the constructor
	 * @param \Lemonade\Pdf\QRBuilder\Builder\QROptions $options
	 * @return \Lemonade\Pdf\QRBuilder\Builder\QRCode
	 * @throws \Lemonade\Pdf\QRBuilder\Exceptions\QRCodeException
	 */
	public function setOptions(QROptions $options){

		if(!array_key_exists($options->eccLevel, $this::ECC_MODES)){
			throw new QRCodeException('Invalid error correct level: '.$options->eccLevel);
		}

		if(!is_array($options->imageTransparencyBG) || count($options->imageTransparencyBG) < 3){
			$options->imageTransparencyBG = [255, 255, 255];
		}

		$options->version = (int)$options->version;

		// clamp min/max version number
		$options->versionMin = (int)min($options->versionMin, $options->versionMax);
		$options->versionMax = (int)max($options->versionMin, $options->versionMax);

		$this->options = $options;

		return $this;
	}

	/**
	 * Renders a QR Code for the given $data and QROptions
	 * @param string $data
	 * @return mixed
	 */
	public function render($data) {
		return $this->initOutputInterface($data)->dump();
	}

	/**
	 * Returns a QRMatrix object for the given $data and current QROptions
	 * @param string $data
	 * @return \Lemonade\Pdf\QRBuilder\Builder\Data\QRMatrix
	 * @throws \Lemonade\Pdf\QRBuilder\Exceptions\QRCodeDataException
	 */
	public function getMatrix($data) {
	    
		$data = trim($data);
		
		if(empty($data)){
			throw new QRCodeDataException('QRCode::getMatrix() No data given.');
		}

		$this->dataInterface = $this->initDataInterface($data);

		$maskPattern = $this->options->maskPattern === $this::MASK_PATTERN_AUTO ? $this->getBestMaskPattern() : max(7, min(0, (int)$this->options->maskPattern));
		$matrix = $this->dataInterface->initMatrix($maskPattern);
		
		if((bool)$this->options->addQuietzone){
			$matrix->setQuietZone($this->options->quietzoneSize);
		}

		return $matrix;
	}

	/**
	 * shoves a QRMatrix through the MaskPatternTester to find the lowest penalty mask pattern
	 * @see \Lemonade\Pdf\QRBuilder\Builder\Data\MaskPatternTester
	 * @return int
	 */
	protected function getBestMaskPattern(){
		$penalties = [];

		$tester = new MaskPatternTester;

		for($testPattern = 0; $testPattern < 8; $testPattern++){
			$matrix = $this->dataInterface->initMatrix($testPattern, true);
			$tester->setMatrix($matrix);
			$penalties[$testPattern] = $tester->testPattern();
		}

		return array_search(min($penalties), $penalties, true);
	}

	/**
	 * returns a fresh QRDataInterface for the given $data
	 * @param string $data
	 * @return \Lemonade\Pdf\QRBuilder\Builder\Data\QRDataInterface
	 * @throws \Lemonade\Pdf\QRBuilder\Exceptions\QRCodeDataException
	 */
	public function initDataInterface($data){

		$DATA_MODES = [
			Number::class   => 'Number',
			AlphaNum::class => 'AlphaNum',
			Kanji::class    => 'Kanji',
			Byte::class     => 'Byte',
		];

		foreach($DATA_MODES as $dataInterface => $mode){

			if(call_user_func_array([$this, 'is'.$mode], [$data]) === true){
				return $this->loadClass($dataInterface, QRDataInterface::class, $this->options, $data);
			}

		}

		throw new QRCodeDataException('invalid data type'); // @codeCoverageIgnore
	}

	/**
	 * returns a fresh (built-in) QROutputInterface
	 * @param string $data
	 * @return \Lemonade\Pdf\QRBuilder\Builder\Output\QROutputInterface
	 * @throws \Lemonade\Pdf\QRBuilder\Exceptions\QRCodeOutputException
	 */
	protected function initOutputInterface($data){

		if($this->options->outputType === $this::OUTPUT_CUSTOM && $this->options->outputInterface !== null) {
			return $this->loadClass($this->options->outputInterface, QROutputInterface::class, $this->options, $this->getMatrix($data));
		}

		foreach($this::OUTPUT_MODES as $outputInterface => $modes) {		    
			if(in_array($this->options->outputType, $modes, true)) {
				return $this->loadClass($outputInterface, QROutputInterface::class, $this->options, $this->getMatrix($data));
			}

		}

		throw new QRCodeOutputException('invalid output type');
	}

	/**
	 * checks if a string qualifies as numeric
	 * @param string $string
	 * @return bool
	 */
	public function isNumber($string) {
		$len = strlen($string);
		$map = str_split('0123456789');

		for($i = 0; $i < $len; $i++){
			if(!in_array($string[$i], $map, true)){
				return false;
			}
		}

		return true;
	}

	/**
	 * checks if a string qualifies as alphanumeric
	 * @param string $string
	 * @return bool
	 */
	public function isAlphaNum($string) {
		$len = strlen($string);

		for($i = 0; $i < $len; $i++){
			if(!in_array($string[$i], AlphaNum::CHAR_MAP, true)){
				return false;
			}
		}

		return true;
	}

	/**
	 * checks if a string qualifies as Kanji
	 * @param string $string
	 * @return bool
	 */
	public function isKanji($string) {
		$i   = 0;
		$len = strlen($string);

		while($i + 1 < $len){
			$c = ((0xff&ord($string[$i])) << 8)|(0xff&ord($string[$i + 1]));

			if(!($c >= 0x8140 && $c <= 0x9FFC) && !($c >= 0xE040 && $c <= 0xEBBF)){
				return false;
			}

			$i += 2;
		}

		return !($i < $len);
	}

	/**
	 * a dummy
	 * @param $data	 
	 * @return bool
	 */
	protected function isByte($data){
		return !empty($data);
	}

}

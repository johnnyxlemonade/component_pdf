<?php

/**
 * Class QROutputAbstract
 *
 * @filesource   QROutputAbstract.php
 * @created      06.07.2019
 * @package      Lemonade\Pdf\QRBuilder\Builder\Output
 * @author       Johnny X. Lemonade <honzamudrak@gmail.com>
 * @copyright    2019 Johnny X. Lemonade
 * @license      MIT
 */
namespace Lemonade\Pdf\QRBuilder\Builder\Output;
use Lemonade\Pdf\QRBuilder\Builder\Data\QRMatrix;
use Lemonade\Pdf\QRBuilder\Builder\QROptions;

/**
 *
 */
abstract class QROutputAbstract implements QROutputInterface{

	/**
	 * @var int
	 */
	protected $moduleCount;

	/**
	 * @param \Lemonade\Pdf\QRBuilder\Builder\Data\QRMatrix $matrix
	 */
	protected $matrix;

	/**
	 * @var \Lemonade\Pdf\QRBuilder\Builder\QROptions
	 */
	protected $options;

	/**
	 * QROutputAbstract constructor.
	 * @param \Lemonade\Pdf\QRBuilder\Builder\QROptions     $options
	 * @param \Lemonade\Pdf\QRBuilder\Builder\Data\QRMatrix $matrix
	 */
	public function __construct(QROptions $options, QRMatrix $matrix){
	    
		$this->options     = $options;
		$this->matrix      = $matrix;
		$this->moduleCount = $this->matrix->size();
	}

	/**
	 * @see file_put_contents()
	 * @param string $data
	 * @return bool|int
	 */
	protected function saveToFile($data) {
		return file_put_contents($this->options->cachefile, $data);
	}

}

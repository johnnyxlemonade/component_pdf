<?php

/**
 * Class Byte
 *
 * @filesource   Byte.php
 * @created      06.07.2019
 * @package      Lemonade\Pdf\QRBuilder\Builder\Data
 * @author       Johnny X. Lemonade <honzamudrak@gmail.com>
 * @copyright    2019 Johnny X. Lemonade
 * @license      MIT
 */
namespace Lemonade\Pdf\QRBuilder\Builder\Data;
use Lemonade\Pdf\QRBuilder\Builder\QRCode;

/**
 * Byte mode, ISO-8859-1 or UTF-8
 */
class Byte extends QRDataAbstract{

	/**
	 * @inheritdoc
	 */
	protected $datamode = QRCode::DATA_BYTE;

	/**
	 * @inheritdoc
	 */
	protected $lengthBits = [8, 16, 16];

	/**
	 * @inheritdoc
	 */
	protected function write($data){
		$i = 0;

		while($i < $this->strlen){
			$this->bitBuffer->put(ord($data[$i]), 8);
			$i++;
		}

	}

}

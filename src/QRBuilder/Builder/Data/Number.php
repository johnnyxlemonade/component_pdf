<?php

/**
 * Class Number
 *
 * @filesource   Number.php
 * @created      06.07.2019
 * @package      QRCode
 * @author       Johnny X. Lemonade <honzamudrak@gmail.com>
 * @copyright    2019 Johnny X. Lemonade
 * @license      MIT
 */
namespace Lemonade\Pdf\QRBuilder\Builder\Data;
use Lemonade\Pdf\QRBuilder\Builder\QRCode;
use Lemonade\Pdf\QRBuilder\Exceptions\QRCodeDataException;

/**
 * Numeric mode: decimal digits 0 through 9
 */
class Number extends QRDataAbstract{

	/**
	 * @inheritdoc
	 */
	protected $datamode = QRCode::DATA_NUMBER;

	/**
	 * @inheritdoc
	 */
	protected $lengthBits = [10, 12, 14];

	/**
	 * @inheritdoc
	 */
	protected function write($data){
		$i = 0;

		while($i + 2 < $this->strlen){
			$this->bitBuffer->put($this->parseInt(substr($data, $i, 3)), 10);
			$i += 3;
		}

		if($i < $this->strlen){

			if($this->strlen - $i === 1){
				$this->bitBuffer->put($this->parseInt(substr($data, $i, $i + 1)), 4);
			}
			// @codeCoverageIgnoreStart
			elseif($this->strlen - $i === 2){
				$this->bitBuffer->put($this->parseInt(substr($data, $i, $i + 2)), 7);
			}
			// @codeCoverageIgnoreEnd

		}

	}

	/**
	 * @param string $string
	 * @return int
	 * @throws QRCodeDataException
	 */
	protected function parseInt($string) {
		$num = 0;
		$map = str_split('0123456789');

		$len = strlen($string);
		for($i = 0; $i < $len; $i++){
			$c = ord($string[$i]);

			if(!in_array($string[$i], $map, true)){
				throw new QRCodeDataException('illegal char: "'.$string[$i].'" ['.$c.']');
			}

			$c = $c - ord('0');

			$num = $num * 10 + $c;
		}

		return $num;
	}

}

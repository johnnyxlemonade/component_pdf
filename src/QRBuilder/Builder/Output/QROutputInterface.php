<?php

/**
 * Interface QROutputInterface,
 *
 * @filesource   QROutputInterface.php
 * @created      06.07.2019
 * @package      Lemonade\Pdf\QRBuilder\Builder\Output
 * @author       Johnny X. Lemonade <honzamudrak@gmail.com>
 * @copyright    2019 Johnny X. Lemonade
 * @license      MIT
 */

namespace Lemonade\Pdf\QRBuilder\Builder\Output;

/**
 * Converts the data matrix into readable output
 */
interface QROutputInterface{

	/**
	 * @return mixed
	 */
	public function dump();

}

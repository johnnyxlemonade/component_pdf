<?php

/**
 * Class QRString
 *
 * @filesource   QRString.php
 * @created      06.07.2019
 * @package      Lemonade\Pdf\QRBuilder\Builder\Output
 * @author       Johnny X. Lemonade <honzamudrak@gmail.com>
 * @copyright    2019 Johnny X. Lemonade
 * @license      MIT
 */
namespace Lemonade\Pdf\QRBuilder\Builder\Output;
use Lemonade\Pdf\QRBuilder\Builder\QRCode;
use Lemonade\Pdf\QRBuilder\Exceptions\QRCodeOutputException;

/**
 * Converts the matrix data into string types
 */
class QRString extends QROutputAbstract {

	/**
	 * @return string
	 * @throws \Lemonade\Pdf\QRBuilder\Exceptions\QRCodeOutputException
	 */
	public function dump(){

		$data = $this->options->outputType === QRCode::OUTPUT_STRING_JSON ? json_encode($this->matrix->matrix()) : $this->toString();

		if($this->options->cachefile !== null){

			if(!is_writable(dirname($this->options->cachefile))){
				throw new QRCodeOutputException('Could not write data to cache file: '.$this->options->cachefile);
			}

			$this->saveToFile($data);
		}

		return $data;
	}

	/**
	 * @return string
	 */
	protected function toString(){
		$str = [];

		foreach($this->matrix->matrix() as $row){
			$r = [];

			foreach($row as $col){
				$col = $this->options->moduleValues[$col];

				// fallback
				if(is_bool($col) || !is_string($col)){
					$col = $col
						? $this->options->textDark
						: $this->options->textLight;
				}

				$r[] = $col;
			}

			$str[] = implode('', $r);
		}

		return implode($this->options->eol, $str);
	}

}

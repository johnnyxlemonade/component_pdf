<?php declare(strict_types = 1);

namespace Lemonade\Pdf\QRBuilder\Builder;
use Lemonade\Pdf\QRBuilder\Builder\Data\QRMatrix;
use Lemonade\Pdf\QRBuilder\Builder\QRCode;
use Lemonade\Pdf\Renderers\Color;
use function base64_encode;
use function file_get_contents;
use function rtrim;
use function chunk_split;
use function str_pad;
use function dechex;
use function is_file;
use function file_exists;

/**
 * ImageQrInterface
 * \Lemonade\Pdf\ImageQrInterface
 */
abstract class QRImageAbstract
{

    /**
     * @var array
     */
    protected array $outputOptions = [
        "imageBase64" => false,
        "imageTransparent" => true,
        "scale" => 3,
        "outputType" => QRCode::OUTPUT_IMAGE_PNG,
        "quietzoneSize" => 0
    ];

    /**
     * @param Color $color
     * @return array|array[]
     */
    public function setModuleValues(Color $color): array
    {

        $this->outputOptions = $this->outputOptions + array("moduleValues" => [
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
                QRMatrix::M_FINDER << 8     => [$color->getRed(), $color->getGreen(), $color->getBlue()], // finder
                QRMatrix::M_ALIGNMENT << 8  => [0, 0, 0],
                QRMatrix::M_TIMING << 8     => [0, 0, 0],
                QRMatrix::M_FORMAT << 8     => [0, 0, 0],
                QRMatrix::M_VERSION << 8    => [0, 0, 0],
                QRMatrix::M_TEST << 8       => [0, 0, 0],
            ]);

        return $this->outputOptions;
    }

    /**
     * @param bool $asString
     * @return string
     */
    abstract function generateImage(bool $asString = false): string;

    /**
     * @param string|int $numberId
     * @return void
     */
    protected function _setOutputDirectory(string|int $numberId): void
    {

        $this->outputDir = sprintf("%s/%s", $this->defaultDir, rtrim(string: chunk_split(string: str_pad(string: dechex(num: (int) $numberId), length: 8, pad_string: "0", pad_type: STR_PAD_LEFT), length: 2, separator: "/"), characters: "/"));
    }

    /**
     * @param string|null $generatedFile
     * @param bool $toString
     * @return string
     */
    protected function returnGeneratedFile(string $generatedFile = null, bool $toString = false): string
    {

        $file = "";

        if($this->_existFileImage(generatedFile: $generatedFile)) {

            if($toString) {

                $file = base64_encode(file_get_contents($this->_locationFile(generatedFile: $generatedFile)));

            } else {

                $file = $this->_locationFile(generatedFile: $generatedFile);
            }

        }

        return $file;
    }

    /**
     * @param string|null $generatedFile
     * @return string
     */
    protected function _locationFile(string $generatedFile = null): string
    {

        return $this->outputDir . DIRECTORY_SEPARATOR . $generatedFile;
    }

    /**
     * @param string|null $generatedFile
     * @return bool
     */
    protected function _existFileImage(string $generatedFile = null): bool
    {

        return is_file(filename:  $this->_locationFile(generatedFile: $generatedFile)) && file_exists(filename: $this->_locationFile(generatedFile: $generatedFile));
    }
}

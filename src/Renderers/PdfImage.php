<?php declare(strict_types=1);

namespace Lemonade\Pdf\Renderers;
use Lemonade\Pdf\QRBuilder\Builder\Data\QRMatrix;
use Lemonade\Pdf\QRBuilder\Builder\QRCode;
use Lemonade\Pdf\QRBuilder\Builder\QROptions;
use Lemonade\Pdf\Renderers\Color;
use Nette\Utils\FileSystem;
use Nette\Utils\Image;
use Nette\Utils\ImageException;
use Nette\Utils\UnknownImageFileException;
use ReflectionException;
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
abstract class PdfImage
{

    /**
     * @var string
     */
    protected string $defaultDir = "./storage/0/export/pdf_image";

    /**
     * @var string
     */
    protected string $defaultType = "default";

    /**
     * @var string
     */
    protected string $outputDir = "./storage/0/export/pdf_image/default";

    /**
     * @var string
     */
    protected string $defaultImg = __DIR__ . "/../../assets/qr_mask.png";

    /**
     * @var string
     */
    protected string $defaultTtf = __DIR__ . "/../../assets/OpenSans-Regular.ttf";

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
     * @param bool $toString
     * @return string
     */
    abstract function generateImage(bool $toString = false): string;

    /**
     * @param string|int $fileId
     * @param string|null $fileCurrency
     * @param string|null $fileText
     * @param string $fileDesc
     * @return string
     */
    protected function _generateImageFile(string|int $fileId, string $fileCurrency = null, string $fileText = null, string $fileDesc = ""): string
    {

        $hash = substr(sha1((string) microtime(true)), 0, 6);
        $genFile = sprintf("%s_%s_%s.png", $fileId, $fileCurrency ?? "no_currency", $hash);

        try {

            // vystupni adresar
            $this->_setOutputDirectory(numberId: $fileId);

            if(!is_dir(filename: $this->outputDir)) {

                FileSystem::createDir(dir: $this->outputDir);
            }

            // qr
            $genApi = new QRCode(options: new QROptions(properties: $this->outputOptions));
            $getTxt = $genApi->render(data: $fileText);
            $genImg = Image::fromString(s: $getTxt);

            // ramecek
            $genRam = Image::fromFile(file: $this->defaultImg);
            $genRam->place(image: $genImg, left: "50%", top: "50%", opacity: 100);

            // empty img
            $thumb = Image::fromBlank(width: 141, height: 141, color: Image::rgb(red:255, green: 255, blue: 255));
            $thumb->place(image: $genRam);
            $thumb->ttfText(size: 8, angle: 0, x:  25, y: 135, color: Image::rgb(red: 125, green: 125, blue: 125), font_filename: $this->defaultTtf, text: $fileDesc);

            // ulozeni
            $thumb->save(file: $this->outputDir .DIRECTORY_SEPARATOR. $genFile, quality: 100, type: Image::PNG);


        } catch (ImageException|UnknownImageFileException|ReflectionException|Exception) {

            return "";
        }

        return $this->returnGeneratedFile(generatedFile: $genFile, toString: false);
    }

    /**
     * @param string|int $numberId
     * @return void
     */
    protected function _setOutputDirectory(string|int $numberId): void
    {

        $this->outputDir = sprintf("%s/%s/%s", $this->defaultDir, $this->defaultType, rtrim(string: chunk_split(string: str_pad(string: dechex(num: (int) $numberId), length: 8, pad_string: "0", pad_type: STR_PAD_LEFT), length: 2, separator: "/"), characters: "/"));
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

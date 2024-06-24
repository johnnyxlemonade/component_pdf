<?php declare(strict_types=1);

namespace Lemonade\Pdf\Data;

use Lemonade\Pdf\Renderers\PdfImage;

/**
 * ImageQrStatus
 * \Lemonade\Pdf\Data\ImageStatus
 */
final class ImageStatus extends PdfImage
{

    /**
     * @var string
     */
    protected string $defaultType = "status";

    /**
     * @param string|int|null $statusId
     * @param string|null $statusUrl
     */
    public function __construct(

        protected readonly string|int|null $statusId = null,
        protected readonly ?string $statusUrl = null

    ) {}


    /**
     * @return string
     */
    public function generateImage(bool $toString = false): string
    {

        return $this->_generateImageFile(fileId: $this->statusId,  fileText: $this->statusUrl, fileDesc: "Stav online");

    }


}

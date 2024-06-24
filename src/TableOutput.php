<?php declare(strict_types = 1);

namespace Lemonade\Pdf;

use Exception;
use Lemonade\Pdf\Data\Table;
use Lemonade\Pdf\Renderers\PDFRenderer;
use Lemonade\Pdf\Templates\OutputTable;
use Nette\Utils\FileSystem;


/**
 * TableOutput
 * \Lemonade\Pdf\TableOutput
 */
final class TableOutput
{

    /**
     * @var Table|null
     */
    protected ?Table $meta = null;

    /**
     * @var Table|null
     */
    protected ?Table $data = null;

    /**
     * @var Table|null
     */
    protected ?Table $property = null;


    /**
     * @param OutputTable $template
     * @param PDFRenderer $renderer
     */
    public function __construct(

        protected readonly OutputTable $template,
        protected readonly PDFRenderer $renderer = new PDFRenderer(),

    ) {}


    /**
     * @param array $meta
     * @return void
     */
    public function addMeta(array $meta = []): void
    {

        $this->meta = new Table(data: $meta);
    }

    /**
     * @param array $data
     * @return void
     */
    public function addData(array $data = []): void
    {

        $this->data = new Table(data: $data);
    }

    /**
     * @param array $property
     * @return void
     */
    public function addProperty(array $property = []): void
    {

        $this->property = new Table(data: $property);
    }

    /**
     * @return Table|null
     */
    public function getMeta(): ?Table
    {

        return $this->meta;
    }

    /**
     * @return Table|null
     */
    public function getData(): ?Table
    {

        return $this->data;
    }

    /**
     * @return Table|null
     */
    public function getProperty(): ?Table
    {

        return $this->property;
    }


    /**
     * @param string $save
     * @return array|string[]
     */
    public function save(string $save): array
    {
        
        try {

            if(!empty($pdf = $this->_processGenerate())) {

                FileSystem::write(file: $save, content: $pdf);
                
                return [
                    "full" => realpath(path: $save),
                    "path" => $save
                ];
            }
            
            return [
                "error" => "warning",
                "message" => "empty file"
            ];
            
        } catch (Exception $e) {
            
            return [
                "error" => "exception",
                "message" => $e->getMessage()
            ];
        }
        
    }

    /**
     * @return void
     */
    public function display(): void
    {
        
        if(!empty($pdf = $this->_processGenerate())) {
            
            header(header: "Content-type: application/pdf");
            
            echo $pdf;
            exit();
            
            
        } else {
            
            header(header: "Content-Type:text/html; charset=UTF-8");
            
            echo "<pre>Chyba renderování pdf souboru</pre>";
            exit;
            
        }
        
    }

    /**
     * @return string|null
     */
    protected function _processGenerate(): ?string
    {

        $data = null;

        try {

            $data = $this->template->generateOutput(renderer: $this->renderer, meta: $this->meta, data: $this->data, property: $this->property);

        } catch (Exception $e) {

        }

        return $data;
    }

        
}

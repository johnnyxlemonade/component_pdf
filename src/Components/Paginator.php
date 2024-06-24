<?php declare(strict_types = 1);

namespace Lemonade\Pdf\Components;
use Lemonade\Pdf\Data\Item;

/**
 * Paginator
 * \Lemonade\Pdf\Components\Paginator
 */
class Paginator {

    /**
     * @var array<Item>
     */
	protected array $items = [];

    /**
     * @var int
     */
	protected int $currentPage = 0;

    /**
     * @var int
     */
	protected int $totalPages = 0;

    /**
     * @var int
     */
	protected int $itemsPerPage = 15;

    /**
     * @param array $items
     * @param int $itemsPerPage
     */
	public function __construct(array $items, int $itemsPerPage) {
	    
		$this->items = $items;
		$this->totalPages = (int) ceil(count($this->items) / $itemsPerPage);
		$this->itemsPerPage = $itemsPerPage;			
	}

    /**
     * @return int
     */
	public function getTotalPages(): int
    {

		return $this->totalPages;
	}

    /**
     * @return array
     */
	public function getItems(): array
    {
	    
		$page = $this->currentPage - 1;

		return array_slice($this->items, $page * $this->itemsPerPage, $this->itemsPerPage );
	}

    /**
     * @return bool
     */
	public function isFirstPage(): bool
    {
	    
		return $this->currentPage === 1;
	}

    /**
     * @return bool
     */
	public function isLastPage(): bool
    {
	    
		return $this->currentPage >= $this->getTotalPages();
	}

    /**
     * @return int
     */
	public function getCurrentPage(): int
    {
	    
		return $this->currentPage;
	}

    /**
     * @return bool
     */
	public function nextPage(): bool
    {
	    
		if ($this->isLastPage()) {

			return false;
		}
		
		$this->currentPage++;

		return true;
	}

}

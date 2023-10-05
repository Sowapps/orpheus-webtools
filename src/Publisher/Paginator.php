<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Publisher;

use Exception;
use IteratorAggregate;
use Orpheus\InputController\HttpController\HttpResponse;
use Orpheus\InputController\HttpController\RedirectHttpResponse;
use Orpheus\Rendering\HtmlRendering;
use Orpheus\SqlRequest\SqlSelectRequest;
use Traversable;

/**
 * Class Paginator
 *
 * Tool to paginate result from query through multiple pages
 *
 * TODO This tool is outdated, it was not recently tested
 */
class Paginator implements IteratorAggregate {
	
	protected string $url;
	
	protected SqlSelectRequest $query;
	
	protected int $page;
	
	protected int $rowCount;
	
	protected string $layout;
	
	protected ?string $rendered = null;
	
	protected int $pageDelta = 3;
	
	protected int $rowPerPage = 100;
	
	protected bool $displayEmpty = false;
	
	public function __construct(string $route, SqlSelectRequest $query, int $page = 1, int $rowPerPage = 100) {
		$this->setUrl(strpos($route, '://') ? $route : u($route));
		$this->setLayout('paginator-pagination');
		$this->setQuery($query);
		$this->setPage($page);
		$this->setRowPerPage($rowPerPage);
		$this->verify();
	}
	
	public function verify(): ?HttpResponse {
		// Redirect all invalid page count
		//			if( !is_ID($this->page) ) {
		//				return new RedirectHttpResponse($this->getPageLink(0));
		//			}
		if( $this->page ) {
			if( $this->page > $this->getLastPage() ) {
				return new RedirectHttpResponse($this->getPageLink($this->getLastPage()));
			} else if( $this->page < 0 ) {
				return new RedirectHttpResponse($this->getPageLink(0));
			}
		}
		
		return null;
	}
	
	public function getPageLink($page): string {
		return $this->getUrl() . ($page ? '?p=' . $page : '');
	}
	
	public function getUrl(): string {
		return $this->url;
	}
	
	public function setUrl(string $url): static {
		$this->url = $url;
		return $this;
	}
	
	public function getLastPage(): int {
		return intval($this->getRowCount() / $this->getRowPerPage());
	}
	
	public function getRowCount(): int {
		return $this->rowCount;
	}
	
	public function getRowPerPage(): int {
		return $this->rowPerPage;
	}
	
	public function setRowPerPage(int $rowPerPage): static {
		$this->rowPerPage = $rowPerPage;
		return $this;
	}
	
	public function __toString(): string {
		return $this->displayEmpty || $this->getRowCount() ? $this->render() : '';
	}
	
	public function render(): ?string {
		if( $this->rendered === null ) {
			// If not generated yet
			// Could be an empty string
			try {
				$this->calculate();
				$this->rendered = HtmlRendering::getCurrent()->render($this->layout, [
					'paginator' => $this,
				]);
			} catch( Exception $exception ) {
				log_error($exception, 'paginator-pagination');
			}
		}
		return $this->rendered;
	}
	
	protected function calculate(): void {
		$this->query
			->asObjectList()
			->number($this->getRowPerPage())
			->fromOffset($this->rowPerPage * $this->page)
			->setUsingCache(false);
	}
	
	public function next(): mixed {
		return $this->query->fetch();
	}
	
	public function getPage(): int {
		return $this->page;
	}
	
	public function setPage(int $page): static {
		$this->page = $page;
		return $this;
	}
	
	public function getLayout(): string {
		return $this->layout;
	}
	
	public function setLayout(string $layout): static {
		$this->layout = $layout;
		return $this;
	}
	
	public function getRendered(): string {
		return $this->rendered;
	}
	
	public function getPageDelta(): int {
		return $this->pageDelta;
	}
	
	public function setPageDelta(int $pageDelta): static {
		$this->pageDelta = $pageDelta;
		return $this;
	}
	
	public function getMinorPage(): int {
		return max($this->page - $this->pageDelta, 0);
	}
	
	public function getMajorPage(): int {
		return min($this->page + $this->pageDelta, $this->getLastPage());
	}
	
	public function getDisplayEmpty(): bool {
		return $this->displayEmpty;
	}
	
	public function setDisplayEmpty(bool $displayEmpty): static {
		$this->displayEmpty = $displayEmpty;
		return $this;
	}
	
	public function getIterator(): Traversable {
		return $this->getQuery();
	}
	
	public function getQuery(): SqlSelectRequest {
		return $this->query;
	}
	
	public function setQuery(SqlSelectRequest $query): static {
		$this->query = $query;
		$this->rowCount = $this->query->count();
		
		return $this;
	}
}

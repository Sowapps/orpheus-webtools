<?php

namespace Orpheus\Publisher;

use Exception;
use Orpheus\Rendering\HTMLRendering;
use Orpheus\SQLRequest\SQLSelectRequest;

/**
 * Class Paginator
 *
 * Tool to paginate result from query through multiple pages
 *
 * @author Florent Hazard <contact@sowapps.com>
 */
class Paginator implements \IteratorAggregate {
	
	/** @var string */
	protected $url;
	
	/** @var SQLSelectRequest */
	protected $query;
	
	/** @var int */
	protected $page;
	
	/** @var int */
	protected $rowCount;
	
	/** @var string */
	protected $layout;
	
	/** @var string */
	protected $rendered;
	
	/** @var int */
	protected $pageDelta = 3;
	
	/** @var int */
	protected $rowPerPage = 100;
	
	/** @var bool */
	protected $displayEmpty = false;
	
	public function __construct($route, $query = null, $rowPerPage = 100) {
		$this->setUrl(strpos($route, '://') ? $route : u($route));
		$this->setLayout('paginator-pagination');
		$this->setQuery($query);
		if( function_exists('GET') ) {
			$this->setPage(GET('p'));
		}
		$this->setRowPerPage($rowPerPage);
		$this->verify();
	}
	
	public function verify() {
		// Redirect all invalid page count
		if( !$this->page ) {
			$this->page = 0;
		} else {
			if( !is_ID($this->page) ) {
				redirectTo($this->getPageLink(0));
			}
			$this->page = intval($this->page);
			if( $this->page ) {
				if( $this->page > $this->getLastPage() ) {
					redirectTo($this->getPageLink($this->getLastPage()));
				} elseif( $this->page < 0 ) {
					redirectTo($this->getPageLink(0));
				}
			}
		}
	}
	
	public function getPageLink($page) {
		return $this->getUrl() . ($page ? '?p=' . $page : '');
	}
	
	public function getUrl() {
		return $this->url;
	}
	
	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}
	
	public function getLastPage() {
		return intval($this->getRowCount() / $this->getRowPerPage());
	}
	
	/**
	 * @return int
	 * @throws Exception
	 */
	public function getRowCount() {
		return $this->rowCount;
	}
	
	public function getRowPerPage() {
		return $this->rowPerPage;
	}
	
	public function setRowPerPage($rowPerPage) {
		$this->rowPerPage = $rowPerPage;
		return $this;
	}
	
	public function __toString() {
		return $this->displayEmpty || $this->getRowCount() ? $this->render() : '';
	}
	
	public function render() {
		if( $this->rendered === null ) {
			// If not generated yet
			// Could be an empty string
			try {
				$this->calculate();
				$this->rendered = HTMLRendering::getCurrent()->render($this->layout, [
					'paginator' => $this,
				]);
			} catch( Exception $e ) {
				log_error($e, 'paginator-pagination', false);
			}
		}
		return $this->rendered;
	}
	
	protected function calculate() {
		if( !$this->query ) {
			throw new Exception('Invalid query');
		}
		$this->query
			->asObjectList()
			->number($this->getRowPerPage())
			->fromOffset($this->rowPerPage * $this->page)
			->setUsingCache(false);
	}
	
	public function next() {
		return $this->query->fetch();
	}
	
	public function getPage() {
		return $this->page;
	}
	
	public function setPage($page) {
		$this->page = $page;
		return $this;
	}
	
	public function getLayout() {
		return $this->layout;
	}
	
	public function setLayout($layout) {
		$this->layout = $layout;
		return $this;
	}
	
	public function getRendered() {
		return $this->rendered;
	}
	
	public function getPageDelta() {
		return $this->pageDelta;
	}
	
	public function setPageDelta($pageDelta) {
		$this->pageDelta = $pageDelta;
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getMinorPage() {
		return (int) max($this->page - $this->pageDelta, 0);
	}
	
	/**
	 * @return int
	 */
	public function getMajorPage() {
		return (int) min($this->page + $this->pageDelta, $this->getLastPage());
	}
	
	public function getDisplayEmpty() {
		return $this->displayEmpty;
	}
	
	public function setDisplayEmpty($displayEmpty) {
		$this->displayEmpty = $displayEmpty;
		return $this;
	}
	
	public function getIterator() {
		return $this->getQuery();
	}
	
	/**
	 * @return SQLSelectRequest
	 */
	public function getQuery() {
		return $this->query;
	}
	
	/**
	 * @param SQLSelectRequest
	 */
	public function setQuery(SQLSelectRequest $query) {
		$this->query = $query;
		$this->rowCount = $this->query->count();
		return $this;
	}
}

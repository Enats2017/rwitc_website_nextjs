<?php 
class Pagination {
    
    var $lastPage;
    var $firstPage = 1;
    var $itemsPerPage = 10;
    var $currPage;
    var $totalItems;
    var $seperator = " | ";
    /**
    * Controls whether to show the Pagination summary or hide it
    * true shows the summary, false hides the summary
    * @example (Page 2 of 5)
    * @var boolean
    */
    var $summary = true;

    /**
    * How many page numbers to show on each side of the current page
    * before collapsing the rest into a "..." separator.
    * @example range=2 with currPage=10 shows 8,9,10,11,12
    * @var integer
    */
    var $range = 2;

    /**
    * Ensures the embedded <style> block is only ever printed once,
    * even if multiple Pagination instances render on the same page.
    * @var boolean
    */
    static $styleEchoed = false;
    
    function __construct($currPageNo,$itemsPerPage,$totalItems){
        $this->setCurrPage($currPageNo);
        $this->setItemsPerPage($itemsPerPage);
        $this->totalItems = $totalItems;
        $this->computeLastPage();
    }
    
    function setItemsPerPage($itemsPerPage) {
        $this->itemsPerPage = $itemsPerPage;
    }
    
    function setCurrPage($currPageNo) {
        $this->currPage = $currPageNo;
    }
    
    function computeLastPage(){
        $this->lastPage = ceil($this->totalItems/$this->itemsPerPage);
    }

    function writeStyles() {
        if (self::$styleEchoed) {
            return;
        }
        self::$styleEchoed = true;
        echo <<<STYLES
<style type="text/css">
.rw-pagination-wrap { margin: 18px 0; }
.rw-pagination-wrap, .rw-pagination-wrap * { box-sizing: border-box; font-family: 'Inter','Segoe UI',Arial,sans-serif; }
.rw-pagination-wrap ul.pagination {
    list-style: none;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    margin: 0;
    padding: 0;
}
.rw-pagination-wrap ul.pagination li {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    height: 38px;
    padding: 0;
    border: 1px solid #d7e4dc;
    border-radius: 8px;
    background: #ffffff;
    color: #0f5c33;
    font-size: 14px;
    font-weight: 500;
    line-height: 1;
    overflow: hidden;
}
.rw-pagination-wrap ul.pagination li:not(.currPage):not(.dots):not(.summary):not(.disabled) {
    padding: 0 12px;
}
.rw-pagination-wrap ul.pagination li.currPage,
.rw-pagination-wrap ul.pagination li.dots,
.rw-pagination-wrap ul.pagination li.summary,
.rw-pagination-wrap ul.pagination li.disabled {
    padding: 0 12px;
}
.rw-pagination-wrap ul.pagination li a {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    height: 100%;
    margin: 0 -12px;
    padding: 0 12px;
    color: inherit;
    text-decoration: none;
}
.rw-pagination-wrap ul.pagination li a:hover {
    background: #0f5c33;
    color: #ffffff;
}
.rw-pagination-wrap ul.pagination li.currPage {
    background: #0f5c33;
    border-color: #0f5c33;
    color: #ffffff;
    font-weight: 700;
}
.rw-pagination-wrap ul.pagination li.dots {
    border: none;
    background: transparent;
    min-width: auto;
    padding: 0 2px;
    color: #9fb3a8;
    font-weight: 700;
}
.rw-pagination-wrap ul.pagination li.summary {
    border: none;
    background: transparent;
    color: #6c7d73;
    font-weight: 400;
    min-width: auto;
    padding: 0 6px;
}
.rw-pagination-wrap ul.pagination li.disabled {
    color: #b9c6bf;
    background: #f4f8f6;
    border-color: #e6efe9;
}
@media (max-width: 480px) {
    .rw-pagination-wrap ul.pagination li.summary { width: 100%; order: 99; text-align: center; padding-top: 6px; }
}
</style>
STYLES;
    }
    
    function writePagination() {
        $this->writeStyles();

        echo "<div class='rw-pagination-wrap'>";
        echo "<ul class='pagination'>";

        if ($this->currPage == 1) {
            echo "<li class='disabled'>&laquo;&laquo;</li>";
            echo "<li class='disabled'>&lsaquo;</li>";
        } else {
           echo "<li><a href='{$_SERVER['PHP_SELF']}?pageno=1' class='nodeco'>&laquo;&laquo;</a></li>";
           $prevPage = $this->currPage - 1;
           echo "<li><a href='{$_SERVER['PHP_SELF']}?pageno=$prevPage' class='nodeco'>&lsaquo;</a></li>";
        }

        // Build a truncated list of page numbers: always show first page,
        // last page, and a small range around the current page; collapse
        // any gap in between into a single "..." entry.
        $lastPrinted = 0;
        for ($i=1;$i<=$this->lastPage;$i++) {
            $isEdge = ($i == $this->firstPage || $i == $this->lastPage);
            $isNearCurrent = ($i >= $this->currPage - $this->range && $i <= $this->currPage + $this->range);

            if (!$isEdge && !$isNearCurrent) {
                continue;
            }

            if ($lastPrinted && ($i - $lastPrinted) > 1) {
                echo "<li class='dots'>&hellip;</li>";
            }

            if ($i == $this->currPage) {
                echo "<li class='currPage'>$i</li>";
            } else {
                echo "<li><a href='{$_SERVER['PHP_SELF']}?pageno=$i'>$i</a></li>";
            }

            $lastPrinted = $i;
        }

        if ($this->currPage == $this->lastPage) {
            echo "<li class='disabled'>&rsaquo;</li>";
            echo "<li class='disabled'>&raquo;&raquo;</li>";
        } else {
           $nextPage = $this->currPage + 1;
           echo "<li><a href='{$_SERVER['PHP_SELF']}?pageno=$nextPage' class='nodeco'>&rsaquo;</a></li>";
           echo "<li><a href='{$_SERVER['PHP_SELF']}?pageno={$this->lastPage}' class='nodeco'>&raquo;&raquo;</a></li>";
        }

        if ($this->summary) {
            echo "<li class='summary'>Page {$this->currPage} of {$this->lastPage}</li>";
        }

        echo "</ul>";
        echo "</div>";
    }
    
}
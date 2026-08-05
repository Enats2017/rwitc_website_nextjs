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
    
    
    function writePagination() {
        echo "<ul class='pagination'>";
        if ($this->currPage == 1) {
            echo "<li>&laquo;&nbsp;FIRST</li>";            
            echo "<li>&lsaquo;&nbsp;PREV</li>";                        
        } else {
           echo "<li><a href='{$_SERVER['PHP_SELF']}?pageno=1' class='nodeco'>&laquo;&nbsp;FIRST</a></li>";           
           $prevPage = $this->currPage - 1;
            echo "<li><a href='{$_SERVER['PHP_SELF']}?pageno=$prevPage' class='nodeco'>&lsaquo;&nbsp;PREV</a></li>";            
        }
        for ($i=1;$i<=$this->lastPage;$i++) {
            if ($i == $this->currPage) {
                echo "<li class='currPage'>$i</li>";
            } else {
                   echo "<li><a href='{$_SERVER['PHP_SELF']}?pageno=$i'>$i</a></li>";
            }
            // show seperator except after the last page
            if ($i != $this->lastPage) {
                echo "<li>".$this->seperator."</li>";
            }
        }
        if ($this->currPage == $this->lastPage) {
            echo "<li>NEXT&nbsp;&rsaquo;</li>";
            echo "<li>LAST&nbsp;&raquo;</li>";
        } else {
           $nextPage = $this->currPage + 1;
           echo "<li><a href='{$_SERVER['PHP_SELF']}?pageno=$nextPage' class='nodeco'>NEXT&nbsp;&rsaquo;</a></li>";           
           echo "<li><a href='{$_SERVER['PHP_SELF']}?pageno={$this->lastPage}' class='nodeco'>LAST&nbsp;&raquo;</a></li>";
        }        
        if ($this->summary) {
            echo "<li>(Page {$this->currPage} of {$this->lastPage})</li>";
        }
        echo "</ul>";        
    }
    
}
<?php
Class Articles {
	var $db;
	function __construct($dbobj) {
	    $this->db = $dbobj;    
	}
	
	
	function insertArticle($title,$body,$published,$new,$pgArticleID) {
		$this->db->insert(self::sqlInsertArticle($title,$body,$published,$new,$pgArticleID));
	}
	
	private static function sqlInsertArticle($title,$body,$published,$new,$pgArticleID) {
        if ($pgArticleID  <= 0) {
          $pgArticleID = 0;  
        }		
        return "INSERT INTO articles (title,body,created,published,new,pgArticleID) VALUES
			   ('$title','$body',now(),'$published','$new','$pgArticleID');
			   ";
	}
	
	function updateArticle($articleID,$title,$body,$published,$new) {
		return $this->db->update(self::sqlUpdateArticle($articleID,$title,$body,$published,$new));
	}
	
	private static function sqlUpdateArticle($articleID,$title,$body,$published,$new) {
      
		return "UPDATE articles
				SET title='$title',
				body='$body',
				published='$published',
                new='$new'                
				WHERE id=$articleID
			   ";
	}
    
    function updateArticleByPGArticleID($title,$body,$published,$pgArticleID) {
        return $this->db->update(self::sqlUpdateArticleByPGArticleID($title,$body,$published,$pgArticleID));
    }
    
    private static function sqlUpdateArticleByPGArticleID($title,$body,$published,$pgArticleID) {       
        return "UPDATE articles
                SET title='$title',
                body='$body'                
                WHERE pgArticleID=$pgArticleID
               ";
    }
	
	
	function deleteArticle($articleID) {
		$this->db->query(self::sqlDeleteArticle($articleID));
	}
	
	private static function sqlDeleteArticle($articleID) {
		return "DELETE FROM articles WHERE id=$articleID";
	}
	
	function getAllArticles() {
		return $this->db->getMultiDimensionalArray(self::sqlGetAllArticles());
	}
	
	private static function sqlGetAllArticles() {
		return "SELECT id,title,UNIX_TIMESTAMP(created) as created,body,published,new 
				FROM articles ORDER BY created DESC";
	}
	
    
    function getAllArticlesCount() {
        return $this->db->getSingleValue(self::sqlGetAllArticlesCount());
    }
    
    private static function sqlGetAllArticlesCount() {
        return "SELECT COUNT(*)
                FROM articles ORDER BY created DESC";
    }
    
    
    function getArticlesPageWise($currPage,$itemsPerPage){
        return $this->db->getMultiDimensionalArray(self::sqlGetArticlesPageWise($currPage,$itemsPerPage));
    }
    
    private static function sqlGetArticlesPageWise($currPage,$itemsPerPage){        
        return "SELECT id,title,UNIX_TIMESTAMP(created) as created,body,published,new
                FROM articles ORDER BY created DESC LIMIT ".($currPage - 1)*$itemsPerPage . "," .$itemsPerPage;
    }
    
	function getArticleByID($articleID) {
		// $this->db->query("ALTER TABLE `admins` ADD `workingManager` ENUM(  'Y',  'N' ) NOT NULL DEFAULT 'N' ");
		// $this->db->query("CREATE TABLE IF NOT EXISTS `working_group` (`id` int(11) NOT NULL AUTO_INCREMENT, `body` text NOT NULL, `date_updated` date NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1");
		// $this->db->query("INSERT INTO `working_group` (`id`, `body`, `date_updated`) VALUES (1, '', '2016-12-04')");

		return $this->db->getSingleRowAssoc(self::sqlGetArticleByID($articleID));
	}
	
	private static function sqlGetArticleByID($articleID) {				
        return "SELECT title,created,body,published,new 
				FROM articles
				WHERE id=$articleID";
	}
    
    public function getPublishedArticles() {
        return $this->db->getMultiDimensionalArray(self::sqlGetPublishedArticles());
    }
    
    private static function sqlGetPublishedArticles(){
        return "SELECT id,title,UNIX_TIMESTAMP(created) as created,body,published,new 
                FROM articles
                WHERE published='Y' ORDER BY created DESC";
    }
     public function gethomepopup() {
        return $this->db->getMultiDimensionalArray(self::sqlGethomepopup());
    }
    
    private static function sqlGethomepopup(){
        return "SELECT * FROM home_popup 
                WHERE `status`='1' ";
    }
    
    
    
    
    function getPublishedArticlesCount() {
        return $this->db->getSingleValue(self::sqlGetPublishedArticlesCount());
    }
    
    private static function sqlGetPublishedArticlesCount() {
        return "SELECT COUNT(*)
                FROM articles 
                WHERE published='Y' ORDER BY created DESC";
    }
    
    
    
    public function getPublishedArticlesPageWise($currPage,$itemsPerPage) {
        return $this->db->getMultiDimensionalArray(self::sqlGetPublishedArticlesPageWise($currPage,$itemsPerPage));
    }
    
    private static function sqlGetPublishedArticlesPageWise($currPage,$itemsPerPage){
        return "SELECT id,title,created,body,published,new 
                FROM articles
                WHERE published='Y' ORDER BY created DESC LIMIT ".($currPage - 1)*$itemsPerPage . "," .$itemsPerPage;
    }

    function updateWorking($workingID,$body) {
        return $this->db->update(self::sqlupdateWorking($workingID,$body));
    }

    private static function sqlupdateWorking($workingID,$body) {       
        return "UPDATE `working_group` SET `body` = '".$body."', `date_updated` = NOW() WHERE `id`= '".$workingID."' ";
    }

    function getWorkingByID($WorkingID) {
		return $this->db->getSingleRowAssoc(self::sqlgetWorkingByID($WorkingID));
	}
	
	private static function sqlgetWorkingByID($WorkingID) {				
        return "SELECT * FROM `working_group` WHERE `id`= '".$WorkingID."' ";
	}

	function insertTicker($body,$published,$sort_order) {
		$this->db->insert(self::sqlInsertTicker($body,$published,$sort_order));
	}
	
	private static function sqlInsertTicker($body,$published, $sort_order) {
        return "INSERT INTO tickers (body,created,published,sort_order) VALUES
			   ('$body',now(),'$published','$sort_order');
			   ";
	}

	function updateTicker($tickerID,$body,$published,$sort_order) {
		return $this->db->update(self::sqlUpdateTicker($tickerID,$body,$published,$sort_order));
	}
	
	private static function sqlUpdateTicker($tickerID,$body,$published,$sort_order) {
		return "UPDATE `tickers` SET `body` = '".$body."', `published` = '".$published."', `sort_order` = '".$sort_order."' WHERE `id` = '".$tickerID."' ";
	}

	function deleteTicker($tickerID) {
		$this->db->query(self::sqlDeleteTicker($tickerID));
	}
	
	private static function sqlDeleteTicker($tickerID) {
		return "DELETE FROM tickers WHERE `id` = '".$tickerID."' ";
	}
	
	function getAllTickers() {
		return $this->db->getMultiDimensionalArray(self::sqlGetAllTickers());
	}
	
	private static function sqlGetAllTickers() {
		return "SELECT `id`, UNIX_TIMESTAMP(`created`) as `created`, `body`, `published`, `sort_order` FROM `tickers` ORDER BY `created` DESC";
	}
    
    function getAllTickersCount() {
        return $this->db->getSingleValue(self::sqlGetAllTickersCount());
    }
    
    private static function sqlGetAllTickersCount() {
        return "SELECT COUNT(*) FROM `tickers` ORDER BY `created` DESC";
    }
    
    
    function getTickersPageWise($currPage,$itemsPerPage){
        return $this->db->getMultiDimensionalArray(self::sqlGetTickersPageWise($currPage,$itemsPerPage));
    }
    
    private static function sqlGetTickersPageWise($currPage,$itemsPerPage){        
        return "SELECT `id`, UNIX_TIMESTAMP(`created`) as `created`, `body`, `published`, `sort_order` FROM `tickers` ORDER BY `created` DESC LIMIT ".($currPage - 1)*$itemsPerPage . "," .$itemsPerPage;
    }
    
	function getTickerByID($tickerID) {
		return $this->db->getSingleRowAssoc(self::sqlGetTickerByID($tickerID));
	}
	
	private static function sqlGetTickerByID($tickerID) {				
        return "SELECT UNIX_TIMESTAMP(`created`) as `created`, `body`, `published`, `sort_order` FROM `tickers` WHERE `id` = '".$tickerID."' ";
	}
    
}
?>
<?php
Class CSRArticles {
	var $db;
	function __construct($dbobj) {
		$this->db = $dbobj;    
	}
	
	
	function insertArticle($title,$body,$published) {
		$this->db->insert(self::sqlInsertArticle($title,$body,$published));
	}
	
	private static function sqlInsertArticle($title,$body,$published) {
        		
        return "INSERT INTO csr_articles (title,body,created,published) VALUES
			   ('$title','$body',now(),'$published');
			   ";
	}
	
	function updateArticle($articleID,$title,$body,$published) {
		return $this->db->update(self::sqlUpdateArticle($articleID,$title,$body,$published));
	}
	
	private static function sqlUpdateArticle($articleID,$title,$body,$published) {
		return "UPDATE csr_articles
				SET title='$title',
				body='$body',
				published='$published'     
				WHERE id=$articleID
			   ";
	}
    
    
	
	function deleteArticle($articleID) {
		$this->db->query(self::sqlDeleteArticle($articleID));
	}
	
	private static function sqlDeleteArticle($articleID) {
		return "DELETE FROM csr_articles WHERE id=$articleID";
	}
	
	function getAllArticles() {
		return $this->db->getMultiDimensionalArray(self::sqlGetAllArticles());
	}
	
	private static function sqlGetAllArticles() {
		return "SELECT id,title,UNIX_TIMESTAMP(created) as created,body,published 
				FROM csr_articles ORDER BY created DESC";
	}
	
    
    function getAllArticlesCount() {
        return $this->db->getSingleValue(self::sqlGetAllArticlesCount());
    }
    
    private static function sqlGetAllArticlesCount() {
        return "SELECT COUNT(*)
                FROM csr_articles ORDER BY created DESC";
    }
    
    
    function getArticlesPageWise($currPage,$itemsPerPage){
        return $this->db->getMultiDimensionalArray(self::sqlGetArticlesPageWise($currPage,$itemsPerPage));
    }
    
    private static function sqlGetArticlesPageWise($currPage,$itemsPerPage){        
        return "SELECT id,title,UNIX_TIMESTAMP(created) as created,body,published
                FROM csr_articles ORDER BY created DESC LIMIT ".($currPage - 1)*$itemsPerPage . "," .$itemsPerPage;
    }
    
	function getArticleByID($articleID) {
		return $this->db->getSingleRowAssoc(self::sqlGetArticleByID($articleID));
	}
	
	private static function sqlGetArticleByID($articleID) {				
        return "SELECT title,UNIX_TIMESTAMP(created) as created,body,published 
				FROM csr_articles                 
				WHERE id=$articleID";
	}
    
    public function getPublishedArticles() {
        return $this->db->getMultiDimensionalArray(self::sqlGetPublishedArticles());
    }
    
    private static function sqlGetPublishedArticles(){
        return "SELECT id,title,UNIX_TIMESTAMP(created) as created,body,published 
                FROM csr_articles
                WHERE published='Y' ORDER BY created DESC";
    }
    
    
    
    
    function getPublishedArticlesCount() {
        return $this->db->getSingleValue(self::sqlGetPublishedArticlesCount());
    }
    
    private static function sqlGetPublishedArticlesCount() {
        return "SELECT COUNT(*)
                FROM csr_articles 
                WHERE published='Y' ORDER BY created DESC";
    }
    
    
    
    public function getPublishedArticlesPageWise($currPage,$itemsPerPage) {
        return $this->db->getMultiDimensionalArray(self::sqlGetPublishedArticlesPageWise($currPage,$itemsPerPage));
    }
    
    private static function sqlGetPublishedArticlesPageWise($currPage,$itemsPerPage){
        return "SELECT id,title,UNIX_TIMESTAMP(created) as created,body,published 
                FROM csr_articles ca                    
                WHERE published='Y' ORDER BY created DESC LIMIT ".($currPage - 1)*$itemsPerPage . "," .$itemsPerPage;
    }

    public function getArticleImages($articleID) {
        return $this->db->getMultiDimensionalArray(self::sqlGetArticleImages($articleID));
    }
    
    private static function sqlGetArticleImages($articleID){      
        return "SELECT title,UNIX_TIMESTAMP(created) as created,body,published,cai.image 
                FROM csr_articles ca
                LEFT JOIN csr_article_images cai ON cai.csr_article_id=ca.id
                WHERE ca.id=$articleID";
    }
    
    
    
}
?>
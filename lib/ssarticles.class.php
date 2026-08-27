<?php
Class SSArticles {
    var $db;
    function __construct($dbobj) {
        $this->db = $dbobj;    
    }
    
    
    function insertArticle($title,$body,$articleType,$date) {
        return $this->db->insert(self::sqlInsertArticle($title,$body,$articleType,$date));
    }
    
    private static function sqlInsertArticle($title,$body,$articleType,$date) {
        return "INSERT INTO shiven_surendranath (title,body,created,article_type) VALUES
               ('$title','$body','$date','$articleType');
               ";
    }
    
    function updateArticle($articleID,$title,$body,$articleType,$date) {
        return $this->db->update(self::sqlUpdateArticle($articleID,$title,$body,$articleType,$date));
    }
    
    private static function sqlUpdateArticle($articleID,$title,$body,$articleType,$date) {
        return "UPDATE shiven_surendranath
                SET title='$title',
                body='$body',
                article_type='$articleType',
                created ='$date'
                WHERE id=$articleID
               ";
    }
    
    
    function deleteArticle($articleID) {
        $this->db->query(self::sqlDeleteArticle($articleID));
    }
    
    private static function sqlDeleteArticle($articleID) {
        return "DELETE FROM shiven_surendranath WHERE id=$articleID";
    }
    
    function getAllArticles() {
        return $this->db->getMultiDimensionalArray(self::sqlGetAllArticles());
    }
    
    private static function sqlGetAllArticles() {
        return "SELECT id,title,UNIX_TIMESTAMP(created) as created,body,article_type 
                FROM shiven_surendranath ORDER BY created DESC";
    }
    
    function getLatestArticle() {
        return $this->db->getSingleRowAssoc(self::sqlGetLatestArticle());
    }
    
    private static function sqlGetLatestArticle() {
        return "SELECT id,title,UNIX_TIMESTAMP(created) as created,body,article_type 
                FROM shiven_surendranath ORDER BY created DESC,article_type DESC LIMIT 1";
    }
    
    function getAllArticlesCount() {
        return $this->db->getSingleValue(self::sqlGetAllArticlesCount());
    }
    
    private static function sqlGetAllArticlesCount() {
        return "SELECT COUNT(*)
                FROM shiven_surendranath ORDER BY created DESC";
    }
    
    
    function getArticlesPageWise($currPage,$itemsPerPage){
        return $this->db->getMultiDimensionalArray(self::sqlGetArticlesPageWise($currPage,$itemsPerPage));
    }
    
    private static function sqlGetArticlesPageWise($currPage,$itemsPerPage){        
        return "SELECT id,title,UNIX_TIMESTAMP(created) as created,body,article_type
                FROM shiven_surendranath ORDER BY created DESC LIMIT ".($currPage - 1)*$itemsPerPage . "," .$itemsPerPage;
    }
    
    function getArticleByID($articleID) {
        return $this->db->getSingleRowAssoc(self::sqlGetArticleByID($articleID));
    }
    
    private static function sqlGetArticleByID($articleID) {                
        return "SELECT title,UNIX_TIMESTAMP(created) as created,body,article_type 
                FROM shiven_surendranath
                WHERE id=$articleID";
    }
    
    public function getPublishedArticles() {
        return $this->db->getMultiDimensionalArray(self::sqlGetPublishedArticles());
    }
    
    private static function sqlGetPublishedArticles(){
        return "SELECT id,title,UNIX_TIMESTAMP(created) as created,body,a 
                FROM shiven_surendranath
                WHERE published='Y' ORDER BY created DESC";
    }
    
    
    
    
    function getPublishedArticlesCount() {
        return $this->db->getSingleValue(self::sqlGetPublishedArticlesCount());
    }
    
    private static function sqlGetPublishedArticlesCount() {
        return "SELECT COUNT(*)
                FROM shiven_surendranath 
                WHERE published='Y' ORDER BY created DESC";
    }
    
    
    
    public function getPublishedArticlesPageWise($currPage,$itemsPerPage) {
        return $this->db->getMultiDimensionalArray(self::sqlGetPublishedArticlesPageWise($currPage,$itemsPerPage));
    }
    
    private static function sqlGetPublishedArticlesPageWise($currPage,$itemsPerPage){
        return "SELECT id,title,UNIX_TIMESTAMP(created) as created,body,published 
                FROM shiven_surendranath
                WHERE published='Y' ORDER BY created DESC LIMIT ".($currPage - 1)*$itemsPerPage . "," .$itemsPerPage;
    }
    
    function getDistinctArticledatesByRange($start,$end) {
      return  $this->db->getSingleValueArray(self::sqlGetDistinctArticledatesByRange($start,$end));
    }
    
    private static function sqlGetDistinctArticledatesByRange($start,$end) {               
        return "SELECT DISTINCT(`created`) FROM shiven_surendranath
                WHERE `created`>='$start' AND `created`<='$end';
                ";
    }
    
    function getArticleIdByDateAndType($date,$articleType) {
        return $this->db->getSingleValue(self::sqlGetArticleIdByDateAndType($date,$articleType));
    }
    
    private static function sqlGetArticleIdByDateAndType($date,$articleType) {        
        return "SELECT id
                FROM shiven_surendranath 
                WHERE created='$date' AND article_type='$articleType'";
    }    
}

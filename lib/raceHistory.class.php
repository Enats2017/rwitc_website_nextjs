<?php
Class RaceHistory {
	var $db;
	function __construct($dbobj) {
		$this->db = $dbobj;    
	}
	
	
	function insertRace($title,$body) {
		$this->db->insert(self::sqlInsertRace($title,$body));
	}
	
	private static function sqlInsertRace($title,$body) {
		return "INSERT INTO race_history (title,body) VALUES
			   ('$title','$body');
			   ";
	}
	
	function updateRace($raceID,$title,$body) {
		return $this->db->update(self::sqlUpdateRace($raceID,$title,$body));
	}
	
	private static function sqlUpdateRace($raceID,$title,$body) {
		return "UPDATE race_history
				SET title='$title',
				body='$body'
				WHERE id=$raceID
			   ";
	}
	
	
	function deleteRace($raceID) {
		$this->db->query(self::sqlDeleteRace($raceID));
	}
	
	private static function sqlDeleteRace($raceID) {
		return "DELETE FROM race_history WHERE id=$raceID";
	}
	
	function getAllRaces() {
		return $this->db->getMultiDimensionalArray(self::sqlGetAllRaces());
	}
	
	private static function sqlGetAllRaces() {
		return "SELECT id,title,body 
				FROM race_history ORDER BY title ASC";
	}
	
    
    function getAllRacesCount() {
        return $this->db->getSingleValue(self::sqlGetAllRacesCount());
    }
    
    private static function sqlGetAllRacesCount() {
        return "SELECT COUNT(*)
                FROM race_history ORDER BY title ASC";
    }
    
    /*
    function getArticlesPageWise($currPage,$itemsPerPage){
        return $this->db->getMultiDimensionalArray(self::sqlGetArticlesPageWise($currPage,$itemsPerPage));
    }
    
    private static function sqlGetArticlesPageWise($currPage,$itemsPerPage){        
        return "SELECT id,title,body
                FROM race_history ORDER BY title ASC LIMIT ".($currPage - 1)*$itemsPerPage . "," .$itemsPerPage;
    }
    */
    
	function getRaceByID($raceID) {
		return $this->db->getSingleRowAssoc(self::sqlGetRaceByID($raceID));
	}
	
	private static function sqlGetRaceByID($raceID) {				
        return "SELECT title,body 
				FROM race_history
				WHERE id=$raceID";
	}
    
    /*
    public function getPublishedArticles() {
        return $this->db->getMultiDimensionalArray(self::sqlGetPublishedArticles());
    }
    
    private static function sqlGetPublishedArticles(){
        return "SELECT id,title,UNIX_TIMESTAMP(created) as created,body,published 
                FROM articles
                WHERE published='Y' ORDER BY created DESC";
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
        return "SELECT id,title,UNIX_TIMESTAMP(created) as created,body,published 
                FROM articles
                WHERE published='Y' ORDER BY created DESC LIMIT ".($currPage - 1)*$itemsPerPage . "," .$itemsPerPage;
    }
    */
}

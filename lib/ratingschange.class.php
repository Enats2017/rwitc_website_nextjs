<?php
Class RatingsChange {
    var $db;
    function __construct($dbobj) {
        $this->db = $dbobj;    
    }
    
    
    function insertRatingsChange($date,$filename) {
        $this->db->insert(self::sqlInsertRatingsChange($date,$filename));
    }
    
    private static function sqlInsertRatingsChange($date,$filename) {
        return "INSERT INTO ratings_change(racedate,filename) VALUES
               ('$date','$filename');
               ";
    }
    
       
    function getAllRatingsChange() {
        return $this->db->getMultiDimensionalArray(self::sqlGetAllRatingsChange());
    }
    
    private static function sqlGetAllRatingsChange(){
        return "SELECT id,racedate,filename 
                FROM ratings_change ORDER BY racedate DESC
                ";
    }
    
    function getRatingsChangeById($id) {
        return $this->db->getSingleRowAssoc(self::sqlGetRatingsChangeById($id));
    }
    
    private static function sqlGetRatingsChangeById($id){
        return "SELECT id,racedate,filename 
                FROM ratings_change
                WHERE id=$id
                ";
    }
    
    function getRatingsChangeByDate($date) {
        return $this->db->getSingleRowAssoc(self::sqlGetRatingsChangeByDate($date));
    }
    
    private static function sqlGetRatingsChangeByDate($date){
        return "SELECT id,racedate,filename 
                FROM ratings_change
                WHERE racedate='$date'
                ";
    }
    
    function deleteRatingsChangeByID($id) {
        return $this->db->query(self::sqlDeleteRatingsChangeByID($id));
    }
    
    private static function sqlDeleteRatingsChangeByID($id) {
        return "DELETE FROM ratings_change 
                WHERE id=$id
               ";
    }
    
    function checkDateInRatingChange($date) {
        return $this->db->getSingleValue(self::sqlCheckDateInRatingChange($date));
    }
    
    private static function sqlCheckDateInRatingChange($date) {
        return "SELECT DISTINCT(1) FROM ratings_change WHERE racedate='$date'
               ";
    }
    
}

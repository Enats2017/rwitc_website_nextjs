<?php
Class StewardsReport {
    var $db;
    function __construct($dbobj) {
        $this->db = $dbobj;    
    }
    
    
    function insertStewardsReport($date,$title,$filename) {
        $this->db->insert(self::sqlInsertStewardsReport($date,$title,$filename));
    }
    
    private static function sqlInsertStewardsReport($date,$title,$filename) {
        return "INSERT INTO stewards_report(racedate,title,filename) VALUES
               ('$date','$title','$filename');
               ";
    }
    
       
    function getAllStewardsReports() {
        return $this->db->getMultiDimensionalArray(self::sqlGetAllStewardsReports());
    }
    
    private static function sqlGetAllStewardsReports(){
        return "SELECT id,title,racedate,filename 
                FROM stewards_report ORDER BY racedate DESC
                ";
    }
    
    function getStewardsReportById($id) {
        return $this->db->getSingleRowAssoc(self::sqlGetStewardsReportById($id));
    }
    
    private static function sqlGetStewardsReportById($id){
        return "SELECT id,title,racedate,filename 
                FROM stewards_report
                WHERE id=$id
                ";
    }
    
    function getStewardsReportByDate($date) {
        return $this->db->getSingleRowAssoc(self::sqlGetStewardsReportByDate($date));
    }
    
    private static function sqlGetStewardsReportByDate($date){
        return "SELECT id,title,racedate,filename 
                FROM stewards_report
                WHERE racedate='$date'
                ";
    }
    
    function deleteStewardsReportByID($id) {
        return $this->db->query(self::sqlDeleteStewardsReportByID($id));
    }
    
    private static function sqlDeleteStewardsReportByID($id) {
        return "DELETE FROM stewards_report
                WHERE id=$id
               ";
    }
    function checkDateStewardsDayReport($date) {
        return $this->db->getSingleValue(self::sqlCheckDateInStewardsReport($date));
    }
    
    private static function sqlCheckDateInStewardsReport($date) {
        return "SELECT DISTINCT(1) FROM stewards_report WHERE racedate='$date'
               ";
    }
    
}

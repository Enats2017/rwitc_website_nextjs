<?php
Class RaceReport {
    var $db;
    function __construct($dbobj) {
        $this->db = $dbobj;    
    }
    
    
    function insertRaceReport($date,$filename) {
        $this->db->insert(self::sqlInsertRaceReport($date,$filename));
    }
    
    private static function sqlInsertRaceReport($date,$filename) {
        return "INSERT INTO raceday_report(racedate,filename) VALUES
               ('$date','$filename');
               ";
    }
    
       
    function getAllRaceReports() {
        return $this->db->getMultiDimensionalArray(self::sqlGetAllRaceReports());
    }
    
    private static function sqlGetAllRaceReports(){
        return "SELECT id,racedate,filename 
                FROM raceday_report ORDER BY racedate DESC
                ";
    }
    
    /* Pagination */
    
    function getAllRaceReportsCount() {
        return $this->db->getSingleValue(self::sqlGetAllRaceReportsCount());
    }
    
    private static function sqlGetAllRaceReportsCount(){
        return "SELECT COUNT(*) 
                FROM raceday_report ORDER BY racedate DESC
                ";
    }
    
    function getRaceRecordsPageWise($currPage,$itemsPerPage){
        return $this->db->getMultiDimensionalArray(self::sqlGetRaceRecordsPageWise($currPage,$itemsPerPage));
    }
    
    private static function sqlGetRaceRecordsPageWise($currPage,$itemsPerPage){        
        return "SELECT id,racedate,filename
                FROM raceday_report ORDER BY racedate DESC LIMIT ".($currPage - 1)*$itemsPerPage . "," .$itemsPerPage;
    }
    
    /* Pagination */
    
    function getRaceReportById($id) {
        return $this->db->getSingleRowAssoc(self::sqlGetRaceReportById($id));
    }
    
    private static function sqlGetRaceReportById($id){
        return "SELECT id,racedate,filename 
                FROM raceday_report
                WHERE id=$id
                ";
    }
    
    function getRaceReportByDate($date) {
        return $this->db->getSingleRowAssoc(self::sqlGetRaceReportByDate($date));
    }
    
    private static function sqlGetRaceReportByDate($date){
        return "SELECT id,racedate,filename 
                FROM raceday_report
                WHERE racedate='$date'
                ";
    }
    
    function deleteRaceReportByID($id) {
        return $this->db->query(self::sqlDeleteRaceReportByID($id));
    }
    
    private static function sqlDeleteRaceReportByID($id) {
        return "DELETE FROM raceday_report 
                WHERE id=$id
               ";
    }
    function checkDateRaceDayReport($date) {
        return $this->db->getSingleValue(self::sqlCheckDateInRaceDayReport($date));
    }
    
    private static function sqlCheckDateInRaceDayReport($date) {
        return "SELECT DISTINCT(1) FROM raceday_report WHERE racedate='$date'
               ";
    }
    
}

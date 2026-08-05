<?php
Class AvailibilityCalendar {
    var $db;
    function __construct($dbobj) {
        $this->db = $dbobj;    
    }
    
    
    function insertRaceDate($date) {
        $this->db->insert(self::sqlInsertRaceDate($date));
    }
    
    private static function sqlInsertRaceDate($date) {
        return "INSERT INTO availibility_calendar(racedate) VALUES
               ('$date');
               ";
    }
    
    function updateRaceDate($id,$date) {
        return $this->db->update(self::sqlUpdateRaceDate($id,$date));
    }
    
    private static function sqlUpdateRaceDate($id,$date){
            return "UPDATE availibility_calendar
                    SET racedate='$date'
                    WHERE id=$id
                    ";
    }
    
    function getAllCalendar() {
        return $this->db->getMultiDimensionalArray(self::sqlGetAllCalendar());
    }
    
    private static function sqlGetAllCalendar(){
        return "SELECT id,racedate 
                FROM availibility_calendar ORDER BY racedate DESC
                ";
    }
    
    function getCalendarById($id) {
        return $this->db->getSingleRowAssoc(self::sqlGetCalendarById($id));
    }
    
    private static function sqlGetCalendarById($id){
        return "SELECT id,racedate 
                FROM availibility_calendar
                WHERE id=$id
                ";
    }
    
    function deleteCalendarByID($id) {
        return $this->db->query(self::sqlDeleteCalendarByID($id));
    }
    
    private static function sqlDeleteCalendarByID($id) {
        return "DELETE FROM availibility_calendar 
                WHERE id=$id
               ";
    }
    
    function getCentresList(){
         return $this->db->getMultiDimensionalArray(self::sqlGetCentresList());
    }
    
    private static function sqlGetCentresList() {
        return "SELECT id,centre FROM centres ORDER BY id ASC";
    }
    
}

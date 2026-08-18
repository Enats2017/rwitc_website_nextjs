<?php
Class Calendar {
    var $db;
    function __construct($dbobj) {
        $this->db = $dbobj;    
    }
    
    
    function insertCalendar($date,$centreid) {
        $this->db->insert(self::sqlInsertCalendar($date,$centreid));
    }
    
    private static function sqlInsertCalendar($date,$centreid) {
        return "INSERT INTO racing_calendar(racedate,centreid) VALUES
               ('$date',$centreid);
               ";
    }
    
    function updateCalendar($id,$centreid,$date) {
        return $this->db->update(self::sqlUpdateCalendar($id,$centreid,$date));
    }
    
    private static function sqlUpdateCalendar($id,$centreid,$date){
            return "UPDATE racing_calendar
                    SET racedate='$date', 
                    centreid=$centreid                
                    WHERE id=$id
                    ";
    }
    
    function getAllCalendar($data = array()) {
        return $this->db->getMultiDimensionalArray(self::sqlGetAllCalendar($data));
    }
    
    private static function sqlGetAllCalendar($data){
      $sql = "SELECT id,racedate,centreid FROM racing_calendar WHERE 1=1 ";
      if(isset($data['start'])){
        $sql .= " AND racedate >= '".$data['start']."' ";
      }
      if(isset($data['end'])){
        $sql .= " AND racedate <= '".$data['end']."' ";
      }
      $sql .= " ORDER BY centreid ASC,racedate DESC";
      if(isset($data['setLimit'])){
        $sql .= " LIMIT ".$data['pageLimit']." , ".$data['setLimit'];
      }
      //echo $sql;exit;
      return $sql;
    }
    
    function getCalendarById($id) {
        return $this->db->getSingleRowAssoc(self::sqlGetCalendarById($id));
    }
    
    private static function sqlGetCalendarById($id){
        return "SELECT id,racedate,centreid 
                FROM racing_calendar
                WHERE id=$id
                ";
    }
    
    function deleteCalendarByID($id) {
        return $this->db->query(self::sqlDeleteCalendarByID($id));
    }
    
    private static function sqlDeleteCalendarByID($id) {
        return "DELETE FROM racing_calendar 
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

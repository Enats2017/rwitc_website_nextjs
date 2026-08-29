<?php
Class Trackwork {
    var $db;
    function __construct($dbobj) {
        $this->db = $dbobj;    
    }
    
    
    function insertTrackwork($trackwork,$trackworkDate,$published) {
        $this->db->insert(self::sqlInsertTrackwork($trackwork,$trackworkDate,$published));
    }
    
    private static function sqlInsertTrackwork($trackwork,$trackworkDate,$published) {
        return "INSERT INTO trackwork (trackwork,trackwork_date,published) VALUES
               ('$trackwork','$trackworkDate','$published');
               ";
    }
    
    function updateTrackwork($trackworkID,$trackwork,$trackworkDate,$published) {
        return $this->db->update(self::sqlUpdateTrackwork($trackworkID,$trackwork,$trackworkDate,$published));
    }
    
    private static function sqlUpdateTrackwork($trackworkID,$trackwork,$trackworkDate,$published) {
        return "UPDATE trackwork
                SET trackwork='$trackwork',
                trackwork_date='$trackworkDate', 
                published='$published'
                WHERE id=$trackworkID
               ";
    }
    
    
    function deleteTrackwork($trackworkID) {
        $this->db->query(self::sqlDeleteTrackwork($trackworkID));
    }
    
    private static function sqlDeleteTrackwork($trackworkID) {
        return "DELETE FROM trackwork WHERE id=$trackworkID";
    }
    
    function getAllTrackwork() {
        return $this->db->getMultiDimensionalArray(self::sqlGetAllTrackwork());
    }
    
    private static function sqlGetAllTrackwork() {
        return "SELECT id,trackwork_date,trackwork,published
                FROM trackwork ORDER BY trackwork_date DESC";
    }
    
    function getTrackworkByID($trackworkID) {
        return $this->db->getSingleRowAssoc(self::sqlGetTrackworkByID($trackworkID));
    }
    
    private static function sqlGetTrackworkByID($trackworkID) {                
        return "SELECT trackwork_date,trackwork,published
                FROM trackwork
                WHERE id=$trackworkID";
    }
    
    function getTrackworkByDate($trackworkDate) {
        return $this->db->getSingleRowAssoc(self::sqlGetTrackworkByDate($trackworkDate));
    }
    
    private static function sqlGetTrackworkByDate($trackworkDate) {                
        return "SELECT trackwork_date,trackwork,published
                FROM trackwork
                WHERE trackwork_date='$trackworkDate'";
    }
    
    public function getPublishedTrackwork() {
        return $this->db->getMultiDimensionalArray(self::sqlGetPublishedTrackwork());
    }
    
    private static function sqlGetPublishedTrackwork(){
        return "SELECT id,trackwork_date,trackwork,published
                FROM trackwork
                WHERE published='Y' ORDER BY trackwork_date DESC";
    }
    
    function getTrackworkIDsByDateRange($start,$end) {
        return $this->db->getMultiDimensionalArray(self::sqlGetTrackworkIDsByDateRange($start,$end));
    }
    
    private static function sqlGetTrackworkIDsByDateRange($start,$end) {        
        return "SELECT id,trackwork_date FROM trackwork t
WHERE trackwork_date>='$start' AND trackwork_date<='$end' AND published='Y'";
    }
    
    function searchTrackworkByHorse($horseName) { 
        return $this->db->getMultiDimensionalArray(self::sqlSearchTrackworkByHorse($horseName));
    }    
    
    private static function sqlSearchTrackworkByHorse($horseName) {        
       return  "SELECT id,trackwork_date FROM trackwork WHERE MATCH(trackwork) AGAINST ('$horseName' IN BOOLEAN MODE) ORDER BY trackwork_date DESC";   
    }
}

?>

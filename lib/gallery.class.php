<?php
Class Image {
    var $db;
    function __construct($dbobj) {
        $this->db = $dbobj;    
    }
    
    
    function insertImage($date,$caption,$filename,$sponsorID) {
        $this->db->insert(self::sqlInsertImage($date,$caption,$filename,$sponsorID));
    }
    
    private static function sqlInsertImage($date,$caption,$filename,$sponsorID) {
        return "INSERT INTO gallery(racedate,caption,filename,sponsor_id) VALUES
               ('$date','$caption','$filename',$sponsorID);
               ";
    }

     function updateImage($caption,$image_id) {
        $this->db->update(self::sqlupdateImage($caption,$image_id));
    }
    
    private static function sqlupdateImage($caption,$image_id) {
        return "UPDATE gallery SET `caption` = '".$caption."' WHERE `id` = '".$image_id."' ";
    }
    
       
    function getAllImages() {
        return $this->db->getMultiDimensionalArray(self::sqlGetAllImages());
    }
    
    private static function sqlGetAllImages(){
        return "SELECT id,racedate,caption,filename
                FROM gallery ORDER BY racedate DESC
                ";
    }
    
   
    function getAllDates() {
        return $this->db->getMultiDimensionalArray(self::sqlGetAllDates());        
    }
    
    private static function sqlGetAllDates(){
        /*return "SELECT DISTINCT(racedate) as racedate
                FROM gallery ORDER BY racedate DESC
                ";*/        
        return " SELECT DISTINCT racedate,sponsor_id
                FROM gallery
                ";
    } 
    
    function getAllImagesByDateAndSponsorID($date,$sponsorID) {
        return $this->db->getMultiDimensionalArray(self::sqlGetAllImagesByDateAndSponsorID($date,$sponsorID));
    }
    
    private static function sqlGetAllImagesByDateAndSponsorID($date,$sponsorID)  {
        return "SELECT id,racedate,caption,filename,sponsor_id
                FROM gallery 
                WHERE racedate='$date' AND sponsor_id=$sponsorID ORDER BY racedate DESC
                ";
    }
    
    function getImageById($id) {
        return $this->db->getSingleRowAssoc(self::sqlGetImageById($id));
    }
    
    private static function sqlGetImageById($id){
        return "SELECT id,racedate,caption,filename,sponsor_id 
                FROM gallery
                WHERE id=$id
                ";
    }
    
    function deleteImageByID($id) {
        return $this->db->query(self::sqlDeleteImageByID($id));
    }
    
    private static function sqlDeleteImageByID($id) {
        return "DELETE FROM gallery 
                WHERE id=$id
               ";
    }
    
    
    function updateImageCaption($id,$caption) {
        return $this->db->update(self::sqlUpdateImageCaption($id,$caption));
    }
    
    private static function  sqlUpdateImageCaption($id,$caption) {
        return "UPDATE gallery
                SET caption='$caption'
                WHERE id=$id 
        
               ";
    }
    
    
    /*------------------------- Sponsors DB Querys ******************/
    function insertSponsor($date,$name,$logo) {
        return $this->db->insert(self::sqlInsertSponsor($date,$name,$logo));
    }
    
    private static function sqlInsertSponsor($date,$name,$logo) {
        return "INSERT INTO sponsors_list(sponsor_name,racedate,sponsor_logo) 
                VALUES('$name','$date','$logo')
                ";
    }
    
    
    function updateSponsor($sponsorID,$date,$name,$logo="") {
       return $this->db->update(self::sqlUpdateSponsor($sponsorID,$date,$name,$logo)); 
    }
    
    private static function sqlUpdateSponsor($sponsorID,$date,$name,$logo) {
        if ($logo != "") {
            return "UPDATE sponsors_list SET
                    racedate='$date',
                    sponsor_name='$name',
                    sponsor_logo='$logo'
                    WHERE id=$sponsorID
                   ";
        } else if ($logo == "") {
             return "UPDATE sponsors_list SET 
                    racedate='$date',
                    sponsor_name='$name'
                    WHERE id=$sponsorID
                   ";
        }
    }
    
    
    function deleteSponsor($sponsorID) {
        return $this->db->query(self::sqlDeleteSponsor($sponsorID));
    }
    
    private static function sqlDeleteSponsor($sponsorID) {
        return "DELETE FROM sponsors_list WHERE id=$sponsorID";
    }
    
    function getSponsorDetails($sponsorID) {
        return $this->db->getSingleRowAssoc(self::sqlGetSponsorDetails($sponsorID));
    }
    
    private static function sqlGetSponsorDetails($sponsorID) {
        return "SELECT * FROM sponsors_list
                 WHERE id=$sponsorID
               ";
    }
    
    function getAllSponsors() {
        return $this->db->getMultiDimensionalArray(self::sqlGetAllSponsors());
    }
    
    private static function sqlGetAllSponsors() {
        return "SELECT * FROM sponsors_list";
    }
    
    function getAllSponsorsAlphaSorted() {
        return $this->db->getMultiDimensionalArray(self::sqlGetAllSponsorsAlphaSorted());
    }
    
    private static function sqlGetAllSponsorsAlphaSorted() {
        return "SELECT * FROM sponsors_list WHERE id>1 ORDER BY sponsor_name ASC";
    }
    
    function getSponsorsByRacadate($date) {
       return  $this->db->getMultiDimensionalArray(self::sqlGetSponsorsByRacedate($date));
    }
    
    private static function sqlGetSponsorsByRacedate($date) {
        return "SELECT id,sponsor_name
                FROM sponsors_list
                WHERE racedate='$date' OR id=1
               ";
    }
    
    function checkDateByDateListAndSponsorID($sponsorID,$dateList) {
         return $this->db->getSingleValueArray(self::sqlCheckDateByDateListAndSponsorID($sponsorID,$dateList));
    }
    
    private static function sqlCheckDateByDateListAndSponsorID($sponsorID,$dateList){
       return "SELECT DISTINCT d.RACEDATE
                FROM gallery d
                WHERE d.RACEDATE='{$dateList[0]}' OR d.RACEDATE='{$dateList[1]}' OR d.RACEDATE='{$dateList[2]}' OR d.RACEDATE='{$dateList[3]}' AND sponsor_id=$sponsorID";
    }
    
           
}


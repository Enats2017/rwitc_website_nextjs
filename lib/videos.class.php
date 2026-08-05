<?php

Class Videos {

    var $db;
    function __construct($db) {
        $this->db = $db;
    }

    function addVideo($racedate,$channel,$category) {
        return $this->db->insert(self::sqlAddVideo($racedate,$channel,$category));
    }

    private static function sqlAddVideo($racedate,$channel,$category) {
        return "INSERT INTO videos(racedate,chan,cat)
                    VALUES ('$racedate',$channel,$category)
                   ";
    }

    function getVideoDataByDate($racedate) {
        return $this->db->getSingleRowAssoc(self::sqlGetVideoDataByDate($racedate));
    }

    private static function sqlGetVideoDataByDate($racedate) {
        return "SELECT chan,cat FROM videos WHERE racedate='$racedate'";
    }

    function getVideos(){
        return $this->db->getMultiDimensionalArray(self::sqlGetVideos());
    }

    private static function sqlGetVideos(){
        return "SELECT * FROM videos order by racedate DESC LIMIT 0,50";
    }
    
    public function fetchTop10Categories() { 
        return $this->db->getMultiDimensionalArray(self::sqlFetchTop10Categories());
    }
    
    private static function sqlFetchTop10Categories(){        
        return "SELECT * FROM top10_video_categories";
    }
    
    public function fetchTop10CatVideo($catID) {
       return $this->db->getMultiDimensionalArray(self::sqlFetchTop10CatVideo($catID)); 
    }
    
    private static function sqlFetchTop10CatVideo($catID){        
        return "SELECT * FROM top10_videos WHERE top10_video_category_id='$catID' ORDER BY position ASC";
    }
    
    public function fetchEventVideos($categoryName){
       return $this->db->getMultiDimensionalArray(self::sqlFetchEventVideos($categoryName));  
    }
    
    private static function sqlFetchEventVideos($categoryName){        
        return "SELECT * FROM event_videos WHERE category_name='$categoryName' ORDER BY position ASC,id DESC";
    }
    
    public function fetchEventDetailsByEventID($eventID){
       return $this->db->getSingleRowAssoc(self::sqlFetchEventDetailsByEventID($eventID));  
    }
    
    private static function sqlFetchEventDetailsByEventID($eventID){        
        return "SELECT * FROM event_videos WHERE id='$eventID'";
    }
    
    public function fetchClassicVideos(){
       return $this->db->getMultiDimensionalArray(self::sqlFetchClassicVideos());  
    }
    
    private static function sqlFetchClassicVideos(){        
        return "SELECT cv.id,cv.classic_video_category_id,cv.title,cv.winner,cv.year,cv.filename,cvc.name,cvc.cdn_foldername,cvc.cdn_posterimage FROM classic_videos cv
                INNER JOIN classic_video_categories cvc ON cv.classic_video_category_id=cvc.id
                WHERE cv.classic_video_category_id!=6
                ORDER BY cvc.position ASC,cv.classic_video_category_id ASC,cv.year DESC,cv.title ASC
                ";
    }
    
    public function fetchClassicVideosByCatId($catID){
       return $this->db->getMultiDimensionalArray(self::sqlFetchClassicVideosByCatId($catID));  
    }
    
    private static function sqlFetchClassicVideosByCatId($catID){        
        return "SELECT cv.id,cv.classic_video_category_id,cv.title,cv.winner,cv.year,cv.filename,cvc.name,cvc.cdn_foldername,cvc.cdn_posterimage FROM classic_videos cv
                INNER JOIN classic_video_categories cvc ON cv.classic_video_category_id=cvc.id
                WHERE cv.classic_video_category_id='$catID'
                ORDER BY cv.classic_video_category_id ASC,cv.year DESC,cv.title ASC
                ";
    }
    
    public function fetchClassicVideosById($videoID){
       return $this->db->getSingleRowAssoc(self::sqlFetchClassicVideosById($videoID));  
    }
    
    private static function sqlFetchClassicVideosById($videoID){        
        return "SELECT cv.id,cv.classic_video_category_id,cv.title,cv.winner,cv.year,cv.filename,cvc.name,cvc.cdn_foldername FROM classic_videos cv
                INNER JOIN classic_video_categories cvc ON cv.classic_video_category_id=cvc.id
                WHERE cv.id='$videoID'
                ORDER BY cv.classic_video_category_id ASC,cv.year DESC,cv.title ASC
                ";
    } 
    
     public function fetchClassicCategories(){
       return $this->db->getMultiDimensionalArray(self::sqlFetchClassicCategories());  
    }
    
    private static function sqlFetchClassicCategories(){        
        return "SELECT * FROM classic_video_categories WHERE id!=6 ORDER by position ASC";
    }   
}
?>

<?php
class Admin {

public $db;
function  __construct($db) {
        $this->db = $db;
}

function getAllAdmins(){
    return $this->db->getMultiDimensionalArray(self::sqlGetAllAdmins());
}

private static function sqlGetAllAdmins(){
    return "SELECT id, username,CONCAT(firstname,' ',lastname) as `name`,role,active FROM admins";
}


function getRoles(){
    return $this->db->getMultiDimensionalArray(self::sqlGetRoles());    
}

private static function sqlGetRoles(){
    return "SELECT DISTINCT(role) as role from admins";
}    


function insertAdmin($username,$password,$firstname,$lastname,$active,$articles,$race_history,$send_mailer,$rating_change,$gallery,$video,$dividends,$steward_report,$race_day_report,$calendar,$prakash_gosavi,$polls,$adminusers,$workingManager,$bannerManager,$tickerManager,$sponsorManager, $sponsorofthedayManager, $horseweightManager, $racedataManager, $configManager, $mailManager) {
   return $this->db->insert(self::sqlInsertAdmin($username,$password,$firstname,$lastname,$active,$articles,$race_history,$send_mailer,$rating_change,$gallery,$video,$dividends,$steward_report,$race_day_report,$calendar,$prakash_gosavi,$polls,$adminusers,$workingManager,$bannerManager,$tickerManager,$sponsorManager, $sponsorofthedayManager, $horseweightManager, $racedataManager, $configManager, $mailManager));
}
private static function sqlInsertAdmin($username,$password,$firstname,$lastname,$active,$articles,$race_history,$send_mailer,$rating_change,$gallery,$video,$dividends,$steward_report,$race_day_report,$calendar,$prakash_gosavi,$polls,$adminusers,$workingManager,$bannerManager,$tickerManager,$sponsorManager, $sponsorofthedayManager, $horseweightManager, $racedataManager, $configManager) {
    return "INSERT INTO admins(username,password,firstname,lastname,active,created,articles,race_history,send_mailer,rating_change,gallery,video,dividends,stewards_report,race_day_report,calendar,prakash_gosavi,polls,adminusers,workingManager,bannerManager,tickerManager,sponsorManager, sponsorofthedayManager, horseweightManager, racedataManager, configManager, mailManager) 
            VALUES ('$username',PASSWORD('$password'),'$firstname','$lastname','$active',now(),'$articles','$race_history','$send_mailer','$rating_change','$gallery','$video','$dividends','$steward_report','$race_day_report','$calendar','$prakash_gosavi','$polls','$adminusers','$workingManager', '$bannerManager','$tickerManager','$sponsorManager','$sponsorofthedayManager', '$horseweightManager', '$racedataManager', '$configManager', ', $mailManager')";
}

function getAdminById($adminID) {
   return $this->db->getSingleRowAssoc(self::sqlGetAdminById($adminID));  
}
private static function sqlGetAdminById($adminID) {
   return "SELECT id,username,firstname,lastname,role,active,articles,race_history,send_mailer,rating_change,gallery,video,dividends,stewards_report,race_day_report,calendar,prakash_gosavi,polls,adminusers,workingManager,bannerManager,tickerManager,sponsorManager,sponsorofthedayManager,configManager,racedataManager,horseweightManager, mailManager  FROM admins where id=$adminID"; 
}

function updateAdmin($adminID,$username,$firstname,$lastname,$active,$articles,$race_history,$send_mailer,$rating_change,$gallery,$video,$dividends,$steward_report,$race_day_report,$calendar,$prakash_gosavi,$polls,$adminusers,$workingManager,$bannerManager,$tickerManager,$sponsorManager, $sponsorofthedayManager, $horseweightManager, $racedataManager, $configManager, $mailManager) {
   return $this->db->update(self::sqlUpdateAdmin($adminID,$username,$firstname,$lastname,$active,$articles,$race_history,$send_mailer,$rating_change,$gallery,$video,$dividends,$steward_report,$race_day_report,$calendar,$prakash_gosavi,$polls,$adminusers,$workingManager,$bannerManager,$tickerManager,$sponsorManager, $sponsorofthedayManager, $horseweightManager, $racedataManager, $configManager, $mailManager));
}

private static function sqlUpdateAdmin($adminID,$username,$firstname,$lastname,$active,$articles,$race_history,$send_mailer,$rating_change,$gallery,$video,$dividends,$steward_report,$race_day_report,$calendar,$prakash_gosavi,$polls,$adminusers,$workingManager,$bannerManager,$tickerManager,$sponsorManager, $sponsorofthedayManager, $horseweightManager, $racedataManager, $configManager, $mailManager) {
   return "UPDATE admins
            SET username='$username',
                firstname='$firstname',
                lastname='$lastname',
                active='$active',
                articles='$articles',
                race_history='$race_history',
                send_mailer='$send_mailer',
                rating_change='$rating_change',
                gallery='$gallery',
                video='$video',
                dividends='$dividends',
                stewards_report='$steward_report',
                race_day_report='$race_day_report',
                calendar='$calendar',
                prakash_gosavi='$prakash_gosavi',
                polls='$polls',
                adminusers='$adminusers',
                workingManager = '$workingManager',
                bannerManager = '$bannerManager',                
                tickerManager = '$tickerManager',                
                sponsorManager = '$sponsorManager',               
                sponsorofthedayManager = '$sponsorofthedayManager',
                racedataManager = '$racedataManager',
                horseweightManager = '$horseweightManager',
                configManager = '$configManager',
                mailManager = '$mailManager'
            WHERE id='$adminID';
            ";    
    
}

function deleteAdmin($adminID){
    $this->db->query(self::sqlDeleteAdmin($adminID));
}

private static function sqlDeleteAdmin($adminID) {
    return "DELETE from admins where id='$adminID'";
}

function updatepassword($adminID,$password){
    return $this->db->update(self::sqlUpdatePassword($adminID,$password));
}

private static function sqlUpdatePassword($adminID,$password){
    return "UPDATE admins SET `password`=PASSWORD('$password') WHERE id=$adminID";
}


}

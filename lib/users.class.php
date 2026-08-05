<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class Users {

public $db;
function  __construct($db) {
        $this->db = $db;
}

function insertNewSiteuser($uemail, $password,$firstname,$lastname,$phoneno,$role) {
    return $this->db->insert(self::sqlInsertNewSiteUser($uemail, $password,$firstname,$lastname,$phoneno, $role));
}

private static function sqlInsertNewSiteUser($uemail,$password,$firstname,$lastname,$phoneno,$role) {    
    return "INSERT INTO users(email,password,firstname,lastname,phoneno,role,created) VALUES
            ('$uemail',PASSWORD('$password'),'$firstname','$lastname','$phoneno','$role',now())
           ";
}

private static function mysql41Password($password) {
    return '*' . strtoupper(sha1(sha1($password, true)));
}

function checkUser($email,$password) {
    return $this->db->getSingleRowAssoc(self::sqlCheckUser($email, $password));
}

private static function sqlCheckUser($email,$password) {    
    return "SELECT * FROM users WHERE email='$email' AND `password`=PASSWORD('$password')";
}


function setMailers($uid,$handicaps,$acceptances,$declarations,$racecard,$raceresults) {
    return $this->db->insert(self::sqlSetMailers($uid,$handicaps,$acceptances,$declarations,$racecard,$raceresults));
}

private static function sqlSetMailers($uid,$handicaps,$acceptances,$declarations,$racecard,$raceresults) {
    return "INSERT INTO reg_user_mailer(uid,mail_handicap,mail_acceptance,mail_declaration,mail_racecard,mail_result)
            VALUES ($uid,'$handicaps','$acceptances','$declarations','$racecard','$raceresults') ON DUPLICATE KEY UPDATE
            mail_handicap='$handicaps',
            mail_acceptance='$acceptances',
            mail_declaration='$declarations',
            mail_racecard='$racecard',
            mail_result='$raceresults'
           ";
}

function getMailerSettings($uid) {
    return $this->db->getSingleRowAssoc(self::sqlgetMailerSettings($uid));
}
private static function sqlgetMailerSettings($uid) {
    return "SELECT * FROM reg_user_mailer WHERE uid=$uid";
}

function setConfirmCode($uid,$code) {
    return $this->db->update(self::sqlSetConfirmCode($uid, $code));
}

private static function sqlSetConfirmCode($uid,$code) {
    return "UPDATE users
            SET confirm_code='$code'
            WHERE id=$uid
           ";
}

function checkConfirmCode($uid,$confirmCode) {
    return $this->db->getSingleValue(self::sqlCheckConfirmCode($uid, $confirmCode));
}

private static  function sqlCheckConfirmCode($uid,$confirmCode) {
    return "SELECT id FROM users
                id=$uid AND confirm_code='$confirmCode'
               ";

}

function setUserActive($uid,$confirmCode) {
    return $this->db->update(self::sqlSetUserActive($uid,$confirmCode));
}

private static function sqlSetUserActive($uid,$confirmCode) {
    return "UPDATE users
                SET verified='Y'
                WHERE id=$uid AND confirm_code='$confirmCode'
               ";
}

function getUserlistByMailer($mailerField) {
    switch($mailerField) {
        case "handicap":
        $field = "mail_handicap";
        break;
        case "declaration":
        $field = "mail_declaration";
        break;
        case "acceptance":
        $field = "mail_acceptance";
        break;
        case "racecard":
        $field = "mail_racecard";
        break;
        case "result":
        $field = "mail_result";
        break;
                
    }
    return $this->db->getMultiDimensionalArray(self::sqlGetUserlistByMailer($field));
}

private static function  sqlGetUserlistByMailer($field) {
       return "SELECT u.id,u.email,u.firstname,u.lastname 
                FROM users u,reg_user_mailer m
                WHERE u.id=m.uid AND m.$field='Y'";
}

function getUserListByType($userType) {
     return $this->db->getMultiDimensionalArray(self::sqlGetUserListByType($userType));
}

private static function sqlGetUserListByType($userType) {
    $cond ='';    
    switch ($userType) {
        case "all":
            break;
        case "verified":
            $cond = 'WHERE verified="Y"';
            break;    
        case "unverified":
            $cond = 'WHERE verified="N"';
            break;    
    }    
    return "SELECT email,firstname,lastname FROM users $cond";
}


function getUserDetailsById($uid) {
    return $this->db->getSingleRowAssoc(self::sqlGetUserDetailsById($uid));
}

private static function sqlGetUserDetailsById($uid) {
    return "SELECT email,firstname,lastname,phoneno FROM users WHERE id=$uid";
}


function updateUserDetails($uid,$firstname,$lastname,$phoneno) {
    return $this->db->update(self::sqlUpdateUserDetails($uid,$firstname,$lastname,$phoneno));  
}

private static function sqlUpdateUserDetails($uid,$firstname,$lastname,$phoneno) {
    return "UPDATE users
            SET firstname='$firstname', lastname='$lastname', phoneno='$phoneno'
            WHERE id=$uid
           ";
}

function checkAdminUser($username,$password) {
    return $this->db->getSingleRowAssoc(self::sqlCheckAdminUser($username, $password));
}

private static function sqlCheckAdminUser($username,$password) {  
    $hashedPassword = self::mysql41Password($password);
    //return "SELECT * FROM admins WHERE username='$username' AND `password`=PASSWORD('$password')";
    return "SELECT * FROM admins WHERE username='$username' AND `password`=('$hashedPassword')";

    //return "SELECT * FROM admins WHERE id='3' ";
}

function updateForgotPassCode($email,$md5Code) {
    return $this->db->update(self::sqlUpdateForgotPassCode($email,$md5Code));  
}

private static function sqlUpdateForgotPassCode($email,$md5Code) {
    return "UPDATE users
            SET reset_code='$md5Code'
            WHERE email='$email'
           ";
}

function checkResetCode($md5Code) {
    return $this->db->getSingleRowAssoc(self::sqlCheckResetCode($md5Code));  
}

private static function sqlCheckResetCode($md5Code) {
    return "SELECT email,reset_code,firstname,lastname
            FROM users
            WHERE reset_code='$md5Code' 
           ";
}

function updatePassword($email,$md5Code,$password) {
    return $this->db->update(self::sqlUpdatePassword($email,$md5Code,$password));  
}

private static function sqlUpdatePassword($email,$md5Code,$password) {
    return "UPDATE users
            SET password=PASSWORD('$password'), reset_code=''
            WHERE email='$email' AND reset_code='$md5Code'
           ";
}

}
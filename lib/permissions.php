<?php

function loadUserPermissions($db, $userId)
{
    $userId = (int)$userId;

    $_SESSION['user_group_id'] = 0;
    $_SESSION['user_group_name'] = '';

    $_SESSION['permissions'] = array(
        'access' => array(),
        'modify' => array()
    );

    if ($userId <= 0) {
        return;
    }

    $sql = "
        SELECT
            u.user_group_id,
            ug.name AS group_name,
            ug.permission
        FROM users u
        LEFT JOIN user_group ug
            ON u.user_group_id = ug.user_group_id
        WHERE u.id = $userId
        LIMIT 1
    ";

    $row = $db->getSingleRowAssoc($sql);

    if (!$row) {
        return;
    }

    $_SESSION['user_group_id'] =
        isset($row['user_group_id'])
        ? (int)$row['user_group_id']
        : 0;

    $_SESSION['user_group_name'] =
        isset($row['group_name'])
        ? $row['group_name']
        : '';

    $_SESSION['permissions'] = parseGroupPermissions(
        isset($row['permission'])
            ? $row['permission']
            : ''
    );
}


/*
|--------------------------------------------------------------------------
| LOAD ADMIN PERMISSIONS
|--------------------------------------------------------------------------
*/

function loadAdminPermissions($db, $userId)
{
    $userId = (int)$userId;

    $_SESSION['user_group_id'] = 0;
    $_SESSION['user_group_name'] = '';

    $_SESSION['permissions'] = array(
        'access' => array(),
        'modify' => array()
    );

    if ($userId <= 0) {
        return;
    }

    $sql = "
        SELECT
            a.user_group_id,
            ug.name AS group_name,
            ug.permission
        FROM admins a
        LEFT JOIN user_group ug
            ON a.user_group_id = ug.user_group_id
        WHERE a.id = $userId
        LIMIT 1
    ";

    $row = $db->getSingleRowAssoc($sql);

    if (!$row) {
        return;
    }

    $_SESSION['user_group_id'] =
        isset($row['user_group_id']) &&
        $row['user_group_id'] !== null
        ? (int)$row['user_group_id']
        : 0;

    $_SESSION['user_group_name'] =
        isset($row['group_name']) &&
        $row['group_name'] !== null
        ? $row['group_name']
        : '';


    /*
     * ---------------------------------------------------------------
     * SUPER ADMIN
     * ---------------------------------------------------------------
     *
     * Admin table ID 1 = Super Admin
     *
     * user_group_id ka yahan koi role nahi hai.
     */

    if ($userId === 1) {

        $_SESSION['permissions'] = array(
            'access' => array('*'),
            'modify' => array('*')
        );

        return;
    }


    /*
     * ---------------------------------------------------------------
     * NORMAL ADMIN
     * ---------------------------------------------------------------
     *
     * Normal admin ka access selected user group se aayega.
     */

    $_SESSION['permissions'] = parseGroupPermissions(
        isset($row['permission'])
            ? $row['permission']
            : ''
    );
}


/*
|--------------------------------------------------------------------------
| PARSE GROUP PERMISSIONS
|--------------------------------------------------------------------------
*/

function parseGroupPermissions($permission)
{
    $permissionData = array();

    if (
        $permission !== null &&
        trim($permission) !== ''
    ) {

        $permissionData = @unserialize($permission);

        if (!is_array($permissionData)) {
            $permissionData = array();
        }
    }

    return array(

        'access' => (
            isset($permissionData['access']) &&
            is_array($permissionData['access'])
        )
            ? array_values($permissionData['access'])
            : array(),

        'modify' => (
            isset($permissionData['modify']) &&
            is_array($permissionData['modify'])
        )
            ? array_values($permissionData['modify'])
            : array()
    );
}


/*
|--------------------------------------------------------------------------
| MODULE ACCESS
|--------------------------------------------------------------------------
*/

function hasModuleAccess($module)
{
    /*
     * Super Admin = admins.id 1
     */
    if (isSuperAdmin()) {
        return true;
    }


    /*
     * Normal admin
     */
    if (
        !isset($_SESSION['permissions']) ||
        !isset($_SESSION['permissions']['access']) ||
        !is_array($_SESSION['permissions']['access'])
    ) {
        return false;
    }

    return in_array(
        $module,
        $_SESSION['permissions']['access'],
        true
    );
}


/*
|--------------------------------------------------------------------------
| MODULE MODIFY
|--------------------------------------------------------------------------
*/

function hasModuleModify($module)
{
    /*
     * Super Admin = admins.id 1
     */
    if (isSuperAdmin()) {
        return true;
    }


    /*
     * Normal admin
     */
    if (
        !isset($_SESSION['permissions']) ||
        !isset($_SESSION['permissions']['modify']) ||
        !is_array($_SESSION['permissions']['modify'])
    ) {
        return false;
    }

    return in_array(
        $module,
        $_SESSION['permissions']['modify'],
        true
    );
}


/*
|--------------------------------------------------------------------------
| PAGE ACCESS
|--------------------------------------------------------------------------
*/

function requireModuleAccess(
    $module,
    $redirect = 'dashboard.php'
) {

    if (!hasModuleAccess($module)) {

        header(
            'Location: ' .
                $redirect .
                '?msg=no_access'
        );

        exit;
    }
}
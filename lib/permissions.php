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

    $permissionData = array();

    if (
        isset($row['permission']) &&
        trim($row['permission']) !== ''
    ) {

        $permissionData = @unserialize(
            $row['permission']
        );

        if (!is_array($permissionData)) {
            $permissionData = array();
        }
    }

    $_SESSION['permissions'] = array(

        'access' =>
            (
                isset($permissionData['access']) &&
                is_array($permissionData['access'])
            )
                ? array_values($permissionData['access'])
                : array(),

        'modify' =>
            (
                isset($permissionData['modify']) &&
                is_array($permissionData['modify'])
            )
                ? array_values($permissionData['modify'])
                : array()

    );
}


function hasModuleAccess($module)
{
    /*
     * Existing super admin.
     */
    if (
        isset($_SESSION['uid']) &&
        (int)$_SESSION['uid'] === 19
    ) {
        return true;
    }

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


function hasModuleModify($module)
{
    /*
     * Existing super admin.
     */
    if (
        isset($_SESSION['uid']) &&
        (int)$_SESSION['uid'] === 19
    ) {
        return true;
    }

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
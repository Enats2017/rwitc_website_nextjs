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

    if ($userId === 19) {

        $_SESSION['permissions'] = array(
            'access' => array('*'),
            'modify' => array('*')
        );

        return;
    }


//     /*
//      * ---------------------------------------------------------------
//      * NORMAL ADMIN
//      * ---------------------------------------------------------------
//      *
//      * Normal admin ka access selected user group se aayega.
//      */

//     $_SESSION['permissions'] = parseGroupPermissions(
//         isset($row['permission'])
//             ? $row['permission']
//             : ''
//     );
// }
    /*
     * ---------------------------------------------------------------
     * NORMAL ADMIN
     * ---------------------------------------------------------------
     *
     * Access comes from admin_group_modules (modules ticked per group).
     * Legacy fallback: if the admin has no rows there, use the old single group.
     */

    $access = array();

    $agmRows = $db->getMultiDimensionalArray(
        "SELECT agm.user_group_id, agm.module_key, ug.permission
         FROM admin_group_modules agm
         INNER JOIN user_group ug ON ug.user_group_id = agm.user_group_id
         WHERE agm.admin_id = $userId"
    );

    if (is_array($agmRows) && count($agmRows) > 0) {

        foreach ($agmRows as $r) {

            $groupPerm = parseGroupPermissions($r['permission']);

            // Only count the module if the group really contains it
            if (in_array($r['module_key'], $groupPerm['access'], true)) {
                $access[] = $r['module_key'];
            }
        }
    } else {

        $legacy = parseGroupPermissions(
            isset($row['permission']) ? $row['permission'] : ''
        );
        $access = $legacy['access'];
    }

    $access = array_values(array_unique($access));

    $_SESSION['permissions'] = array(
        'access' => $access,
        'modify' => $access
    );

    // Old pages still check $_SESSION['bannerManager'] == "Y" etc.
    syncLegacyModuleSessionFlags($access);
}
/*
|--------------------------------------------------------------------------
| SYNC OLD SESSION FLAGS
|--------------------------------------------------------------------------
| Sets $_SESSION['<module_key>'] = 'Y' / 'N' from the permission list.
*/

function syncLegacyModuleSessionFlags($access)
{
    $keys = array(
        'articles', 'race_history', 'send_mailer', 'rating_change', 'gallery',
        'video', 'dividends', 'stewards_report', 'race_day_report', 'calendar',
        'prakash_gosavi', 'shiven_surendranath', 'polls', 'adminusers',
        'workingManager', 'bannerManager', 'tickerManager', 'sponsorManager',
        'sponsorofthedayManager', 'horseweightManager', 'racedataManager',
        'configManager', 'mailManager', 'homepopup'
    );

    // Also include every key from the module catalog, if Design class is loaded
    if (class_exists('Design')) {
        $keys = array_unique(array_merge($keys, array_keys(Design::moduleCatalog())));
    }

    foreach ($keys as $k) {
        $_SESSION[$k] = in_array($k, $access, true) ? 'Y' : 'N';
    }
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
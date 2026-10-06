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
| ADMIN GROUP ROWS (admin_group_modules based,)
|--------------------------------------------------------------------------
*/

function getAdminGroupRowsDb($db, $adminId)
{
    $adminId = (int)$adminId;
    $out = array();

    $rows = $db->getMultiDimensionalArray(
        "SELECT agm.user_group_id, agm.module_key, ug.name, ug.permission
         FROM admin_group_modules agm
         INNER JOIN user_group ug ON ug.user_group_id = agm.user_group_id
         WHERE agm.admin_id = $adminId
         ORDER BY ug.name ASC"
    );

    if (is_array($rows) && count($rows) > 0) {
        $tmp = array();
        foreach ($rows as $r) {
            $gid  = (int)$r['user_group_id'];
            $perm = @unserialize($r['permission']);
            if (!is_array($perm)) {
                $perm = array();
            }
            $groupAccess = (!empty($perm['access']) && is_array($perm['access'])) ? $perm['access'] : array();
            if (!isset($tmp[$gid])) {
                $tmp[$gid] = array(
                    'name' => $r['name'],
                    'icon' => isset($perm['icon']) ? $perm['icon'] : 'fa-folder-open',
                    'mods' => array()
                );
            }
            if (in_array($r['module_key'], $groupAccess, true)) {
                $tmp[$gid]['mods'][] = $r['module_key'];
            }
        }
        foreach ($tmp as $gid => $g) {
            if (empty($g['mods'])) {
                continue;
            }
            $out[] = array(
                'user_group_id' => $gid,
                'name'          => $g['name'],
                'permission'    => serialize(array('access' => $g['mods'], 'modify' => $g['mods'], 'icon' => $g['icon']))
            );
        }
        return $out;
    }


    $legacy = $db->getMultiDimensionalArray(
        "SELECT ug.user_group_id, ug.name, ug.permission
         FROM user_group ug
         WHERE ug.user_group_id = (SELECT user_group_id FROM admins WHERE id = $adminId)
            OR ug.user_group_id IN (SELECT user_group_id FROM admin_user_group WHERE admin_id = $adminId)
         ORDER BY ug.name ASC"
    );
    return is_array($legacy) ? $legacy : array();
}


/*
|--------------------------------------------------------------------------
| ALIASES: catalog keys to module keys
|--------------------------------------------------------------------------
*/

function expandModuleAliases($access)
{
    $aliases = array(
        'csr_articles'          => 'articles',
        'availability_calendar' => 'calendar',
        'media_tips'            => 'race_results',
    );

    foreach ($aliases as $from => $to) {
        if (in_array($from, $access, true) && !in_array($to, $access, true)) {
            $access[] = $to;
        }
    }
    return array_values(array_unique($access));
}


/*
|--------------------------------------------------------------------------
|SESSION FLAGS ($_SESSION['articles'] = 'Y' / 'N')
|--------------------------------------------------------------------------
*/

function applyLegacySessionFlags($access, $isSuper = false)
{
    $legacyKeys = array(
        'articles', 'race_history', 'send_mailer', 'rating_change',
        'gallery', 'video', 'dividends', 'stewards_report',
        'race_day_report', 'race_results', 'calendar', 'prakash_gosavi',
        'shiven_surendranath', 'polls', 'adminusers', 'workingManager',
        'bannerManager', 'tickerManager', 'sponsorManager',
        'sponsorofthedayManager', 'horseweightManager', 'racedataManager',
        'configManager', 'mailManager', 'homepopup', 'erp_prerace',
        'erp_postrace', 'trackworkManager', 'suggestion_feedback',
        'youtube_upload', 'chairman_email', 'image_upload',
        'notice_agm', 'annual_report'
    );

    //
    foreach ($legacyKeys as $k) {
        $_SESSION[$k] = $isSuper ? 'Y' : 'N';
    }

    foreach ($access as $k) {
        if (is_string($k) && preg_match('/^[A-Za-z0-9_]+$/', $k) && $k !== 'uid' && $k !== 'role' && $k !== 'username') {
            $_SESSION[$k] = 'Y';
        }
    }
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
     * user_group_id 
     */

    if ($userId === 19) {

        $_SESSION['permissions'] = array(
            'access' => array('*'),
            'modify' => array('*')
        );

        applyLegacySessionFlags(array(), true);

        return;
    }


    /*
     * ---------------------------------------------------------------
     * NORMAL ADMIN
     * ---------------------------------------------------------------
     *
     * Normal admin 
     */

    $acc = array();
    foreach (getAdminGroupRowsDb($db, $userId) as $gr) {
        $p = parseGroupPermissions($gr['permission']);
        $acc = array_merge($acc, $p['access']);
    }
    $acc = expandModuleAliases(array_values(array_unique($acc)));

    $_SESSION['permissions'] = array('access' => $acc, 'modify' => $acc);

    applyLegacySessionFlags($acc, false);
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
     * Normal admin: modify = access (same rule)
     */
    return hasModuleAccess($module);
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
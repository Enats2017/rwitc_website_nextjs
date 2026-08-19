<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("../bootstrap.php");
require_once("../lib/dbTools.php");

session_start();

$db = new dbTool();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';


/* ================================================================
   LOAD USER GROUPS
   ================================================================ */

$user_groups = $db->getMultiDimensionalArray(
    "SELECT user_group_id, name
     FROM user_group
     ORDER BY user_group_id ASC"
);


/* ================================================================
   DELETE SINGLE ADMIN
   ================================================================ */

if ($action == 'delete' && isset($_GET['id'])) {

    $id = (int)$_GET['id'];

    try {

        $db->query(
            "DELETE FROM admins
             WHERE id = $id"
        );

        header("Location: admin/users.php?msg=deleted");
        exit;
    } catch (Exception $e) {

        header(
            "Location: admin/users.php?msg=error&err=" .
                urlencode($e->getMessage())
        );

        exit;
    }
}


/* ================================================================
   BULK DELETE
   ================================================================ */

if (
    $_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['bulk_ids']) &&
    isset($_POST['bulk_delete'])
) {

    $ids = array_filter(
        array_map('intval', $_POST['bulk_ids'])
    );

    if (!empty($ids)) {

        try {

            $db->query(
                "DELETE FROM admins
                 WHERE id IN (" .
                    implode(',', $ids) .
                    ")"
            );

            header("Location: admin/users.php?msg=bulk_deleted");
            exit;
        } catch (Exception $e) {

            header(
                "Location: admin/users.php?msg=error&err=" .
                    urlencode($e->getMessage())
            );

            exit;
        }
    } else {

        header(
            "Location: admin/users.php?msg=error&err=" .
                urlencode("No admins selected.")
        );

        exit;
    }
}


/* ================================================================
   SAVE ADMIN - INSERT / UPDATE
   ================================================================ */

$form_error = null;

if (
    $_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['firstname']) &&
    !isset($_POST['bulk_delete'])
) {

    $username = trim(
        isset($_POST['username'])
            ? $_POST['username']
            : ''
    );

    $firstname = trim(
        isset($_POST['firstname'])
            ? $_POST['firstname']
            : ''
    );

    $lastname = trim(
        isset($_POST['lastname'])
            ? $_POST['lastname']
            : ''
    );

    $email = trim(
        isset($_POST['email'])
            ? $_POST['email']
            : ''
    );

    $phoneno = trim(
        isset($_POST['phoneno'])
            ? $_POST['phoneno']
            : ''
    );

    $role = trim(
        isset($_POST['role'])
            ? $_POST['role']
            : ''
    );

    $group_id = isset($_POST['user_group_id'])
        ? (int)$_POST['user_group_id']
        : 0;

    $active =
        (
            isset($_POST['status']) &&
            $_POST['status'] == 'Y'
        )
        ? 'Y'
        : 'N';

    $password = isset($_POST['password'])
        ? $_POST['password']
        : '';

    $confirm = isset($_POST['confirm'])
        ? $_POST['confirm']
        : '';

    $edit_id = isset($_POST['id'])
        ? (int)$_POST['id']
        : 0;


    /* ============================================================
       VALIDATION
       ============================================================ */

    if ($username === '') {

        $form_error = "Username is required.";
    } elseif ($firstname === '' || $lastname === '') {

        $form_error =
            "First name and last name are required.";
    } elseif (
        $email === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $form_error =
            "A valid email address is required.";
    } elseif (!$edit_id && $password === '') {

        $form_error =
            "Password is required for a new admin.";
    } elseif (
        $password !== '' &&
        $password !== $confirm
    ) {

        $form_error =
            "Password and confirm password do not match.";
    } elseif (
        $password !== '' &&
        strlen($password) < 8
    ) {

        $form_error =
            "Password must be at least 8 characters.";
    } else {

        try {

            /* ====================================================
               DUPLICATE USERNAME
               ==================================================== */

            $escaped_username =
                $db->escape($username);

            $usernameSql =
                "SELECT id
                 FROM admins
                 WHERE username = '$escaped_username'";

            if ($edit_id) {

                $usernameSql .=
                    " AND id != $edit_id";
            }

            $duplicateUsername =
                $db->getSingleRowAssoc(
                    $usernameSql
                );


            if ($duplicateUsername) {

                $form_error =
                    "This username is already used by another admin.";
            } else {


                /* =================================================
                   DUPLICATE EMAIL
                   ================================================= */

                $escaped_email =
                    $db->escape($email);

                $emailSql =
                    "SELECT id
                     FROM admins
                     WHERE email = '$escaped_email'";

                if ($edit_id) {

                    $emailSql .=
                        " AND id != $edit_id";
                }

                $duplicateEmail =
                    $db->getSingleRowAssoc(
                        $emailSql
                    );


                if ($duplicateEmail) {

                    $form_error =
                        "This email is already used by another admin.";
                } else {


                    /* =============================================
                       UPDATE ADMIN
                       ============================================= */

                    if ($edit_id) {

                        $sql =
                            "UPDATE admins SET
                                username='" .
                            $db->escape($username) .
                            "',
                                firstname='" .
                            $db->escape($firstname) .
                            "',
                                lastname='" .
                            $db->escape($lastname) .
                            "',
                                email='" .
                            $db->escape($email) .
                            "',
                                phoneno='" .
                            $db->escape($phoneno) .
                            "',
                                role='" .
                            $db->escape($role) .
                            "',
                                user_group_id=" .
                            (
                                $group_id
                                ? $group_id
                                : 'NULL'
                            ) .
                            ",
                                active='" .
                            $active .
                            "'";


                        /* -----------------------------------------
                           Password only changes when entered
                           ----------------------------------------- */

                        if ($password !== '') {

                            $hashedPassword =
                                '*' .
                                strtoupper(
                                    sha1(
                                        sha1(
                                            $password,
                                            true
                                        )
                                    )
                                );

                            $sql .=
                                ",
                                password='" .
                                $db->escape(
                                    $hashedPassword
                                ) .
                                "'";
                        }


                        $sql .=
                            " WHERE id = $edit_id";


                        $db->update($sql);


                        header(
                            "Location: users.php?msg=updated"
                        );

                        exit;
                    } else {


                        /* =========================================
                           INSERT NEW ADMIN
                           ========================================= */

                        $hashedPassword =
                            '*' .
                            strtoupper(
                                sha1(
                                    sha1(
                                        $password,
                                        true
                                    )
                                )
                            );


                        $sql =
                            "INSERT INTO admins
                            (
                                username,
                                password,
                                firstname,
                                lastname,
                                email,
                                phoneno,
                                role,
                                user_group_id,
                                created,
                                active
                            )
                            VALUES
                            (
                                '" .
                            $db->escape($username) .
                            "',
                                '" .
                            $db->escape($hashedPassword) .
                            "',
                                '" .
                            $db->escape($firstname) .
                            "',
                                '" .
                            $db->escape($lastname) .
                            "',
                                '" .
                            $db->escape($email) .
                            "',
                                '" .
                            $db->escape($phoneno) .
                            "',
                                '" .
                            $db->escape($role) .
                            "',
                                " .
                            (
                                $group_id
                                ? $group_id
                                : 'NULL'
                            ) .
                            ",
                                NOW(),
                                '" .
                            $active .
                            "'
                            )";


                        $db->insert($sql);


                        header(
                            "Location: admin/users.php?msg=added"
                        );

                        exit;
                    }
                }
            }
        } catch (Exception $e) {

            $form_error =
                "Save failed: " .
                $e->getMessage();
        }
    }
}


/* ================================================================
   FETCH ADMIN LIST
   ================================================================ */

$users = array();

$search = '';

$status_filter = '';

$page = 1;

$per_page = 20;

$total_pages = 1;

$filtered_count = 0;

$total_count = 0;

$active_count = 0;

$inactive_count = 0;


if ($action == 'list') {

    $search =
        isset($_GET['q'])
        ? trim($_GET['q'])
        : '';

    $status_filter =
        isset($_GET['status_filter'])
        ? $_GET['status_filter']
        : '';


    if (
        $status_filter !== 'Y' &&
        $status_filter !== 'N'
    ) {

        $status_filter = '';
    }


    $page =
        isset($_GET['page'])
        ? (int)$_GET['page']
        : 1;


    if ($page < 1) {

        $page = 1;
    }


    /* ============================================================
       ADMIN STATISTICS
       ============================================================ */

    $all_admins =
        $db->getMultiDimensionalArray(
            "SELECT active FROM admins"
        );


    $total_count =
        count($all_admins);


    $active_count =
        count(
            array_filter(
                $all_admins,
                function ($admin) {

                    return (
                        isset($admin['active']) &&
                        $admin['active'] == 'Y'
                    );
                }
            )
        );


    $inactive_count =
        $total_count -
        $active_count;


    /* ============================================================
       SEARCH
       ============================================================ */

    $where = array();


    if ($search !== '') {

        $s =
            $db->escape($search);

        $where[] =
            "(a.username LIKE '%$s%'
              OR a.firstname LIKE '%$s%'
              OR a.lastname LIKE '%$s%'
              OR a.email LIKE '%$s%'
              OR a.phoneno LIKE '%$s%'
              OR a.role LIKE '%$s%'
              OR CONCAT(
                    a.firstname,
                    ' ',
                    a.lastname
                 ) LIKE '%$s%')";
    }


    /* ============================================================
       STATUS FILTER
       ============================================================ */

    if ($status_filter !== '') {

        $where[] =
            "a.active = '" .
            $db->escape($status_filter) .
            "'";
    }


    $whereSql =
        !empty($where)
        ? " WHERE " .
        implode(" AND ", $where)
        : "";


    /* ============================================================
       COUNT
       ============================================================ */

    $countRow =
        $db->getSingleRowAssoc(
            "SELECT COUNT(*) AS cnt
             FROM admins a
             $whereSql"
        );


    $filtered_count =
        $countRow
        ? (int)$countRow['cnt']
        : 0;


    $total_pages =
        max(
            1,
            (int)ceil(
                $filtered_count /
                    $per_page
            )
        );


    if ($page > $total_pages) {

        $page =
            $total_pages;
    }


    $offset =
        ($page - 1) *
        $per_page;


    /* ============================================================
       FETCH ADMINS + GROUP
       ============================================================ */

    $sql =
        "SELECT
            a.*,
            ug.name AS group_name
         FROM admins a
         LEFT JOIN user_group ug
            ON a.user_group_id =
               ug.user_group_id
         $whereSql
         ORDER BY a.id DESC
         LIMIT $per_page
         OFFSET $offset";


    $users =
        $db->getMultiDimensionalArray($sql);
}


/* ================================================================
   FETCH SINGLE ADMIN FOR EDIT
   ================================================================ */

$edit_user = null;


if (
    $action == 'form' &&
    $_SERVER['REQUEST_METHOD'] !== 'POST' &&
    isset($_GET['id'])
) {

    $edit_id =
        (int)$_GET['id'];


    $edit_user =
        $db->getSingleRowAssoc(
            "SELECT *
             FROM admins
             WHERE id = $edit_id"
        );
}


/* ================================================================
   REPOPULATE AFTER ERROR
   ================================================================ */

if (
    $form_error &&
    $_SERVER['REQUEST_METHOD'] == 'POST'
) {

    $edit_user = array(

        'id' =>
        isset($_POST['id'])
            ? $_POST['id']
            : null,

        'username' =>
        isset($_POST['username'])
            ? $_POST['username']
            : '',

        'firstname' =>
        $_POST['firstname'],

        'lastname' =>
        $_POST['lastname'],

        'email' =>
        $_POST['email'],

        'phoneno' =>
        $_POST['phoneno'],

        'role' =>
        $_POST['role'],

        'user_group_id' =>
        isset($_POST['user_group_id'])
            ? $_POST['user_group_id']
            : null,

        'active' => (
            isset($_POST['status']) &&
            $_POST['status'] == 'Y'
        )
            ? 'Y'
            : 'N'
    );
}


/* ================================================================
   HELPERS
   ================================================================ */

function initials($first, $last)
{
    $first = $first ?: '';

    $last = $last ?: '';

    $i =
        strtoupper(
            mb_substr($first, 0, 1) .
                mb_substr($last, 0, 1)
        );

    return
        $i !== ''
        ? $i
        : '?';
}


function fmtDate($val)
{
    if (
        !$val ||
        $val === '0000-00-00 00:00:00'
    ) {

        return '-';
    }

    return date(
        'd M Y',
        strtotime($val)
    );
}


function buildPageUrl(
    $p,
    $search,
    $status_filter
) {

    $params = array(
        'action' => 'list'
    );


    if ($search !== '') {

        $params['q'] =
            $search;
    }


    if ($status_filter !== '') {

        $params['status_filter'] =
            $status_filter;
    }


    if ($p > 1) {

        $params['page'] =
            $p;
    }


    return
        'admin/users.php?' .
        http_build_query($params);
}


$msg =
    isset($_GET['msg'])
    ? $_GET['msg']
    : null;


/* ================================================================
   PAGE DESIGN
   ================================================================ */

$design = new Design();

$design->css = '
<style>

:root{
    --green-900:#0d3b28;
    --green-700:#14532d;
    --green-600:#1a6b3c;
    --gold-500:#c9a227;
    --gold-400:#d9b84a;
    --ink-900:#1a211d;
    --ink-600:#5b655f;
    --ink-400:#8a938d;
    --line:#e4e8e4;
    --bg:#f6f7f5;
    --surface:#ffffff;
    --danger:#c0392b;
    --danger-bg:#fbeae8;
    --success-bg:#e9f3ec;
    --radius:10px;
    --shadow:0 1px 2px rgba(20,30,24,.04),
             0 4px 16px rgba(20,30,24,.05);
}

*{
    box-sizing:border-box;
}

html,
body{
    margin:0;
    padding:0;
}

body{
    background:var(--bg);
    color:var(--ink-900);
    font-family:Inter,-apple-system,sans-serif;
    font-size:14.5px;
    line-height:1.5;
}

a{
    text-decoration:none;
    color:inherit;
}

button{
    font-family:inherit;
    cursor:pointer;
}

.page-wrap{
    max-width:1180px;
    margin:0 auto;
    padding:32px 28px 60px;
}

.eyebrow{
    font-size:11.5px;
    font-weight:600;
    letter-spacing:.1em;
    text-transform:uppercase;
    color:var(--gold-500);
    margin:0 0 6px;
}

.page-head{
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:16px;
    margin-bottom:22px;
    padding-bottom:20px;
    border-bottom:1px solid var(--line);
}

.page-head h1{
    font-family:Georgia,serif;
    font-weight:600;
    font-size:30px;
    margin:0;
    color:var(--green-900);
}

.page-head p{
    margin:4px 0 0;
    color:var(--ink-600);
    font-size:13.5px;
}

.btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 18px;
    border-radius:8px;
    border:1px solid transparent;
    font-size:13.5px;
    font-weight:600;
}

.btn-primary{
    background:var(--green-700);
    color:#fff;
}

.btn-ghost{
    background:#fff;
    color:var(--ink-900);
    border-color:var(--line);
}

.btn-danger-ghost{
    background:#fff;
    color:var(--danger);
    border-color:var(--line);
}

.alert{
    padding:12px 18px;
    border-radius:8px;
    font-size:13.5px;
    font-weight:500;
    margin-bottom:18px;
}

.alert-success{
    background:var(--success-bg);
    color:var(--green-700);
    border:1px solid #cfe6d6;
}

.alert-danger{
    background:var(--danger-bg);
    color:var(--danger);
    border:1px solid #f1c9c3;
}

.stat-strip{
    display:flex;
    background:#fff;
    border:1px solid var(--line);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    margin-bottom:24px;
    overflow:hidden;
}

.stat{
    flex:1;
    padding:16px 22px;
    border-right:1px solid var(--line);
}

.stat:last-child{
    border-right:0;
}

.stat .num{
    font-family:Georgia,serif;
    font-size:25px;
    font-weight:600;
    color:var(--green-900);
}

.stat .lbl{
    font-size:12px;
    color:var(--ink-600);
}

.toolbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    flex-wrap:wrap;
    margin-bottom:14px;
}

.search-box{
    position:relative;
    flex:1;
    max-width:320px;
    min-width:220px;
}

.search-box input{
    width:100%;
    padding:9px 12px;
    border:1px solid var(--line);
    border-radius:8px;
    font-size:13.5px;
    background:#fff;
}

.toolbar-actions{
    display:flex;
    gap:10px;
    align-items:center;
}

.status-filter{
    padding:9px 12px;
    border:1px solid var(--line);
    border-radius:8px;
    font-size:13.5px;
    background:#fff;
}

.card,
.form-card{
    background:#fff;
    border:1px solid var(--line);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    overflow:hidden;
}

table{
    width:100%;
    border-collapse:collapse;
}

thead th{
    text-align:left;
    font-size:11.5px;
    font-weight:600;
    text-transform:uppercase;
    letter-spacing:.05em;
    color:var(--ink-600);
    padding:13px 18px;
    background:#fafbfa;
    border-bottom:1px solid var(--line);
}

tbody td{
    padding:13px 18px;
    border-bottom:1px solid var(--line);
    font-size:13.5px;
    vertical-align:middle;
}

.user-cell{
    display:flex;
    align-items:center;
    gap:11px;
}

.avatar-ring{
    width:34px;
    height:34px;
    border-radius:50%;
    background:var(--green-700);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:12px;
    font-weight:700;
    border:2px solid var(--gold-400);
    flex-shrink:0;
}

.user-name{
    font-weight:600;
}

.user-sub{
    font-size:12px;
    color:var(--ink-400);
}

.pill-group{
    display:inline-block;
    padding:3px 10px;
    border-radius:20px;
    font-size:11.5px;
    font-weight:600;
    background:#f1f0e8;
    color:var(--green-900);
    border:1px solid #e3e1d2;
}

.pill-none{
    color:var(--ink-400);
}

.status{
    display:inline-flex;
    align-items:center;
    gap:6px;
    font-size:12.5px;
    font-weight:600;
}

.status .dot{
    width:7px;
    height:7px;
    border-radius:50%;
}

.status.enabled{
    color:var(--green-600);
}

.status.enabled .dot{
    background:var(--green-600);
}

.status.disabled{
    color:var(--danger);
}

.status.disabled .dot{
    background:var(--danger);
}

.row-actions{
    display:flex;
    gap:6px;
    justify-content:flex-end;
}

.icon-btn{
    width:30px;
    height:30px;
    border-radius:7px;
    display:flex;
    align-items:center;
    justify-content:center;
    border:1px solid var(--line);
    background:#fff;
    color:var(--ink-600);
}

.checkbox{
    width:16px;
    height:16px;
}

.empty{
    padding:50px 20px;
    text-align:center;
    color:var(--ink-400);
}

.results-note{
    font-size:12.5px;
    color:var(--ink-600);
    margin-bottom:10px;
}

.pagination{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    flex-wrap:wrap;
    margin-top:18px;
}

.page-link{
    min-width:34px;
    height:34px;
    padding:0 10px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border:1px solid var(--line);
    border-radius:7px;
    background:#fff;
    font-size:13px;
    font-weight:600;
}

.page-link.active{
    background:var(--green-700);
    border-color:var(--green-700);
    color:#fff;
}

.page-link.disabled{
    opacity:.4;
    pointer-events:none;
}

.form-section{
    padding:26px 30px;
    border-bottom:1px solid var(--line);
}

.section-title{
    font-family:Georgia,serif;
    font-size:16px;
    font-weight:600;
    color:var(--green-900);
    margin:0 0 3px;
}

.section-desc{
    font-size:12.5px;
    color:var(--ink-600);
    margin:0 0 18px;
}

.grid-2{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
}

.field{
    margin-bottom:16px;
}

.field label{
    display:block;
    font-size:12.5px;
    font-weight:600;
    margin-bottom:6px;
}

.field input,
.field select{
    width:100%;
    padding:10px 13px;
    border:1px solid var(--line);
    border-radius:8px;
    font-size:13.5px;
    background:#fff;
}

.form-footer{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    padding:18px 30px;
    background:#fafbfa;
}

@media(max-width:720px){

    thead{
        display:none;
    }

    table,
    tbody,
    tr,
    td{
        display:block;
        width:100%;
    }

    tbody tr{
        border-bottom:1px solid var(--line);
        padding:14px 16px;
    }

    tbody td{
        border:0;
        padding:6px 0;
        display:flex;
        justify-content:space-between;
        gap:10px;
    }

    .grid-2{
        grid-template-columns:1fr;
    }

    .page-head{
        flex-direction:column;
        align-items:flex-start;
    }

    .page-wrap{
        padding:22px 16px 40px;
    }

    .form-section{
        padding:20px;
    }

    .form-footer{
        padding:16px 20px;
        flex-direction:column-reverse;
    }

    .form-footer .btn{
        width:100%;
        justify-content:center;
    }
}

</style>
';


$design->startPage("RWITC | Admins");
$design->writeLogoTickerMenu();

?>

<div class="page-wrap">

    <?php if ($msg == 'added'): ?>

        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i>
            Admin added successfully.
        </div>

    <?php elseif ($msg == 'updated'): ?>

        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i>
            Admin updated successfully.
        </div>

    <?php elseif ($msg == 'deleted'): ?>

        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i>
            Admin deleted.
        </div>

    <?php elseif ($msg == 'bulk_deleted'): ?>

        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i>
            Selected admins deleted.
        </div>

    <?php elseif ($msg == 'error'): ?>

        <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i>
            Something went wrong
            <?php
            echo isset($_GET['err'])
                ? ': ' . htmlspecialchars($_GET['err'])
                : '.';
            ?>
        </div>

    <?php endif; ?>


    <?php if ($form_error): ?>

        <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($form_error); ?>
        </div>

    <?php endif; ?>


    <?php if ($action == 'list'): ?>


        <!-- =========================================================
         PAGE HEADER
         ========================================================= -->

        <div class="page-head">

            <div>

                <p class="eyebrow">
                    Admin Management
                </p>

                <h1>
                    System Admins
                </h1>

                <p>
                    Manage administrators and their user group access.
                </p>

            </div>


            <a
                href="admin/users.php?action=form"
                class="btn btn-primary">
                <i class="fa fa-plus"></i>
                Add Admin
            </a>

        </div>


        <!-- =========================================================
         STATISTICS
         ========================================================= -->

        <div class="stat-strip">

            <div class="stat">

                <div class="num">
                    <?php echo $total_count; ?>
                </div>

                <div class="lbl">
                    Total Admins
                </div>

            </div>


            <div class="stat">

                <div class="num">
                    <?php echo $active_count; ?>
                </div>

                <div class="lbl">
                    Active
                </div>

            </div>


            <div class="stat">

                <div class="num">
                    <?php echo $inactive_count; ?>
                </div>

                <div class="lbl">
                    Disabled
                </div>

            </div>


            <div class="stat">

                <div class="num">
                    <?php echo count($user_groups); ?>
                </div>

                <div class="lbl">
                    User Groups
                </div>

            </div>

        </div>


        <!-- =========================================================
         SEARCH / FILTER
         ========================================================= -->

        <form
            method="get"
            action="admin/users.php"
            class="toolbar"
            id="filter-form">

            <input
                type="hidden"
                name="action"
                value="list">


            <div class="search-box">

                <input
                    type="text"
                    name="q"
                    id="searchInput"
                    placeholder="Search username, name or email..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    oninput="debouncedSubmit()">

            </div>


            <div class="toolbar-actions">

                <select
                    name="status_filter"
                    class="status-filter"
                    onchange="document.getElementById('filter-form').submit()">

                    <option
                        value=""
                        <?php
                        echo $status_filter === ''
                            ? 'selected'
                            : '';
                        ?>>
                        All Status
                    </option>

                    <option
                        value="Y"
                        <?php
                        echo $status_filter === 'Y'
                            ? 'selected'
                            : '';
                        ?>>
                        Active
                    </option>

                    <option
                        value="N"
                        <?php
                        echo $status_filter === 'N'
                            ? 'selected'
                            : '';
                        ?>>
                        Disabled
                    </option>

                </select>


                <button
                    type="submit"
                    class="btn btn-ghost">
                    <i class="fa fa-search"></i>
                    Search
                </button>

            </div>

        </form>


        <?php if ($filtered_count > 0): ?>

            <div class="results-note">

                Showing
                <?php
                echo (($page - 1) * $per_page) + 1;
                ?>
                –
                <?php
                echo min(
                    $page * $per_page,
                    $filtered_count
                );
                ?>

                of
                <?php echo $filtered_count; ?>

                admin<?php echo $filtered_count == 1 ? '' : 's'; ?>

            </div>

        <?php endif; ?>


        <!-- =========================================================
         BULK DELETE
         ========================================================= -->

        <form
            method="post"
            action="admin/users.php"
            id="bulk-form">

            <div
                class="toolbar-actions"
                style="justify-content:flex-end;margin-bottom:12px;">

                <button
                    type="submit"
                    name="bulk_delete"
                    value="1"
                    class="btn btn-danger-ghost"
                    onclick="return confirm('Delete all selected admins? This cannot be undone.');">

                    <i class="fa fa-trash-o"></i>

                    Delete Selected

                </button>

            </div>


            <!-- =====================================================
             ADMIN TABLE
             ===================================================== -->

            <div class="card">

                <table id="usersTable">

                    <thead>

                        <tr>

                            <th style="width:1px;">

                                <input
                                    type="checkbox"
                                    class="checkbox"
                                    onclick="
                                    document
                                    .querySelectorAll('.row-check')
                                    .forEach(
                                        c => c.checked = this.checked
                                    )
                                ">

                            </th>

                            <th>
                                Admin
                            </th>

                            <th>
                                Username
                            </th>

                            <th>
                                Group
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Added
                            </th>

                            <th>
                                Last Login
                            </th>

                            <th style="text-align:right;">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (empty($users)): ?>

                            <tr>

                                <td colspan="8">

                                    <div class="empty">

                                        <i class="fa fa-user"></i>

                                        No admins found.

                                    </div>

                                </td>

                            </tr>

                        <?php endif; ?>


                        <?php foreach ($users as $u): ?>

                            <tr>

                                <td>

                                    <input
                                        type="checkbox"
                                        class="checkbox row-check"
                                        name="bulk_ids[]"
                                        value="<?php echo (int)$u['id']; ?>">

                                </td>


                                <!-- ADMIN NAME -->

                                <td>

                                    <div class="user-cell">

                                        <div class="avatar-ring">

                                            <?php
                                            echo initials(
                                                $u['firstname'],
                                                $u['lastname']
                                            );
                                            ?>

                                        </div>


                                        <div>

                                            <div class="user-name">

                                                <?php

                                                echo htmlspecialchars(
                                                    trim(
                                                        $u['firstname'] .
                                                            ' ' .
                                                            $u['lastname']
                                                    )
                                                ) ?: '-';

                                                ?>

                                            </div>


                                            <div class="user-sub">

                                                <?php
                                                echo htmlspecialchars(
                                                    $u['email']
                                                );
                                                ?>

                                            </div>

                                        </div>

                                    </div>

                                </td>


                                <!-- USERNAME -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $u['username']
                                    );
                                    ?>

                                </td>


                                <!-- GROUP -->

                                <td>

                                    <?php if (!empty($u['group_name'])): ?>

                                        <span class="pill-group">

                                            <?php
                                            echo htmlspecialchars(
                                                $u['group_name']
                                            );
                                            ?>

                                          <?php if ((int)$u['id'] === 1): ?>

    <i
        class="fa fa-star"
        title="Super Admin"></i>

<?php endif; ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="pill-none">
                                            No group
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php if ($u['active'] == 'Y'): ?>

                                        <span class="status enabled">

                                            <span class="dot"></span>

                                            Active

                                        </span>

                                    <?php else: ?>

                                        <span class="status disabled">

                                            <span class="dot"></span>

                                            Disabled

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- CREATED -->

                                <td>

                                    <?php
                                    echo fmtDate(
                                        $u['created']
                                    );
                                    ?>

                                </td>


                                <!-- LAST LOGIN -->

                                <td>

                                    <?php
                                    echo fmtDate(
                                        $u['last_login']
                                    );
                                    ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="row-actions">

                                        <a
                                            href="admin/users.php?action=form&id=<?php echo (int)$u['id']; ?>"
                                            class="icon-btn"
                                            title="Edit">
                                            <i class="fa fa-pencil"></i>
                                        </a>


                                        <a
                                            href="admin/users.php?action=delete&id=<?php echo (int)$u['id']; ?>"
                                            class="icon-btn"
                                            title="Delete"
                                            onclick="return confirm('Delete this admin? This cannot be undone.');">
                                            <i class="fa fa-trash-o"></i>
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </form>


        <!-- =========================================================
         PAGINATION
         ========================================================= -->

        <?php if ($total_pages > 1): ?>

            <div class="pagination">

                <a
                    href="<?php
                            echo htmlspecialchars(
                                buildPageUrl(
                                    max(1, $page - 1),
                                    $search,
                                    $status_filter
                                )
                            );
                            ?>"
                    class="page-link <?php
                                        echo $page <= 1
                                            ? 'disabled'
                                            : '';
                                        ?>">
                    « Prev
                </a>


                <?php

                $window = 1;

                $pagesToShow = array(
                    1,
                    $total_pages
                );


                for (
                    $p = $page - $window;
                    $p <= $page + $window;
                    $p++
                ) {

                    if (
                        $p >= 1 &&
                        $p <= $total_pages
                    ) {

                        $pagesToShow[] = $p;
                    }
                }


                $pagesToShow =
                    array_unique($pagesToShow);

                sort($pagesToShow);


                $prevShown = 0;


                foreach ($pagesToShow as $p) {

                    if (
                        $prevShown &&
                        $p - $prevShown > 1
                    ) {

                        echo
                        '<span class="page-link disabled">...</span>';
                    }


                    echo
                    '<a href="' .
                        htmlspecialchars(
                            buildPageUrl(
                                $p,
                                $search,
                                $status_filter
                            )
                        ) .
                        '" class="page-link ' .
                        ($p == $page
                            ? 'active'
                            : '') .
                        '">' .
                        $p .
                        '</a>';


                    $prevShown = $p;
                }

                ?>


                <a
                    href="<?php
                            echo htmlspecialchars(
                                buildPageUrl(
                                    min(
                                        $total_pages,
                                        $page + 1
                                    ),
                                    $search,
                                    $status_filter
                                )
                            );
                            ?>"
                    class="page-link <?php
                                        echo $page >= $total_pages
                                            ? 'disabled'
                                            : '';
                                        ?>">
                    Next »
                </a>

            </div>

        <?php endif; ?>


        <script>
            var searchTimer;

            function debouncedSubmit() {

                clearTimeout(searchTimer);

                searchTimer =
                    setTimeout(
                        function() {

                            document
                                .getElementById('filter-form')
                                .submit();

                        },
                        500
                    );
            }
        </script>


    <?php else: ?>


        <!-- =========================================================
         ADD / EDIT ADMIN
         ========================================================= -->

        <div class="page-head">

            <div>

                <p class="eyebrow">

                    <?php
                    echo $edit_user
                        ? 'Edit Admin'
                        : 'New Admin';
                    ?>

                </p>


                <h1>

                    <?php

                    echo (
                        $edit_user &&
                        !empty($edit_user['firstname'])
                    )
                        ? htmlspecialchars(
                            $edit_user['firstname'] .
                                ' ' .
                                $edit_user['lastname']
                        )
                        : 'Add Admin';

                    ?>

                </h1>


                <p>
                    Manage administrator details and user group access.
                </p>

            </div>


            <a
                href="admin/users.php"
                class="btn btn-ghost">

                <i class="fa fa-arrow-left"></i>

                Back to list

            </a>

        </div>


        <form
            method="post"
            action="admin/users.php?action=form"
            id="form-user">


            <?php if (
                $edit_user &&
                !empty($edit_user['id'])
            ): ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?php
                            echo (int)$edit_user['id'];
                            ?>">

            <?php endif; ?>


            <div class="form-card">


                <!-- =================================================
                 BASIC DETAILS
                 ================================================= -->

                <div class="form-section">

                    <h3 class="section-title">
                        Basic Details
                    </h3>

                    <p class="section-desc">
                        Login identity and administrator details.
                    </p>


                    <div class="grid-2">


                        <div class="field">

                            <label>
                                Username
                                <span class="req">*</span>
                            </label>

                            <input
                                type="text"
                                name="username"
                                placeholder="e.g. test"
                                value="<?php
                                        echo htmlspecialchars(
                                            $edit_user['username'] ?? ''
                                        );
                                        ?>">

                        </div>


                        <div class="field">

                            <label>
                                Email Address
                                <span class="req">*</span>
                            </label>

                            <input
                                type="text"
                                name="email"
                                placeholder="name@rwitc.com"
                                value="<?php
                                        echo htmlspecialchars(
                                            $edit_user['email'] ?? ''
                                        );
                                        ?>">

                        </div>


                    </div>


                    <div class="grid-2">


                        <div class="field">

                            <label>
                                First Name
                                <span class="req">*</span>
                            </label>

                            <input
                                type="text"
                                name="firstname"
                                placeholder="e.g. Rohan"
                                value="<?php
                                        echo htmlspecialchars(
                                            $edit_user['firstname'] ?? ''
                                        );
                                        ?>">

                        </div>


                        <div class="field">

                            <label>
                                Last Name
                                <span class="req">*</span>
                            </label>

                            <input
                                type="text"
                                name="lastname"
                                placeholder="e.g. Sharma"
                                value="<?php
                                        echo htmlspecialchars(
                                            $edit_user['lastname'] ?? ''
                                        );
                                        ?>">

                        </div>


                    </div>


                    <div class="grid-2">


                        <div class="field">

                            <label>
                                Phone Number
                            </label>

                            <input
                                type="text"
                                name="phoneno"
                                placeholder="e.g. 9999999999"
                                value="<?php
                                        echo htmlspecialchars(
                                            $edit_user['phoneno'] ?? ''
                                        );
                                        ?>">

                        </div>


                        <div class="field">

                            <label>
                                Role
                            </label>

                            <input
                                type="text"
                                name="role"
                                placeholder="e.g. ADMIN"
                                value="<?php
                                        echo htmlspecialchars(
                                            $edit_user['role'] ?? ''
                                        );
                                        ?>">

                        </div>


                    </div>

                </div>


                <!-- =================================================
                 USER GROUP
                 ================================================= -->

                <div class="form-section">

                    <h3 class="section-title">
                        Access & Permissions
                    </h3>

                    <p class="section-desc">
                        Select the user group for this administrator.
                        The group controls module permissions.
                    </p>


                    <div class="grid-2">


                        <div class="field">

                            <label>
                                User Group
                            </label>

                            <select name="user_group_id">

                                <option value="">
                                    - No group -
                                </option>


                                <?php foreach (
                                    $user_groups as $ug
                                ): ?>

                                    <option
                                        value="<?php
                                                echo (int)
                                                $ug['user_group_id'];
                                                ?>"
                                        <?php

                                        echo (
                                            isset(
                                                $edit_user['user_group_id']
                                            ) &&
                                            $edit_user['user_group_id'] ==
                                            $ug['user_group_id']
                                        )
                                            ? 'selected'
                                            : '';

                                        ?>>

                                        <?php
                                        echo htmlspecialchars(
                                            $ug['name']
                                        );
                                        ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="field">

                            <label>
                                Status
                            </label>

                            <select name="status">

                                <option
                                    value="Y"
                                    <?php

                                    echo (
                                        isset(
                                            $edit_user['active']
                                        ) &&
                                        $edit_user['active'] == 'Y'
                                    )
                                        ? 'selected'
                                        : '';

                                    ?>>
                                    Active
                                </option>


                                <option
                                    value="N"
                                    <?php

                                    echo (
                                        !isset(
                                            $edit_user['active']
                                        ) ||
                                        $edit_user['active'] == 'N'
                                    )
                                        ? 'selected'
                                        : '';

                                    ?>>
                                    Disabled
                                </option>

                            </select>

                        </div>


                    </div>

                </div>


                <!-- =================================================
                 SECURITY
                 ================================================= -->

                <div class="form-section">

                    <h3 class="section-title">
                        Security
                    </h3>


                    <p class="section-desc">

                        <?php

                        echo $edit_user
                            ? 'Leave password blank to keep the current password.'
                            : 'Set a password for this administrator.';

                        ?>

                    </p>


                    <div class="grid-2">


                        <div class="field">

                            <label>

                                Password

                                <?php if (!$edit_user): ?>

                                    <span class="req">*</span>

                                <?php endif; ?>

                            </label>


                            <input
                                type="password"
                                name="password"
                                placeholder="********"
                                autocomplete="new-password">

                        </div>


                        <div class="field">

                            <label>

                                Confirm Password

                                <?php if (!$edit_user): ?>

                                    <span class="req">*</span>

                                <?php endif; ?>

                            </label>


                            <input
                                type="password"
                                name="confirm"
                                placeholder="********"
                                autocomplete="new-password">

                        </div>


                    </div>


                    <div class="section-desc">

                        <i class="fa fa-info-circle"></i>

                        Minimum 8 characters.

                    </div>

                </div>


                <!-- =================================================
                 FOOTER
                 ================================================= -->

                <div class="form-footer">

                    <a
                        href="admin/users.php"
                        class="btn btn-ghost">
                        Cancel
                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary">

                        <i class="fa fa-save"></i>

                        Save Admin

                    </button>

                </div>


            </div>

        </form>


    <?php endif; ?>

</div>


<?php

$design->endPage();

$design = NULL;

?>
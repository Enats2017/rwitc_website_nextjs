<?php
include_once('../bootstrap.php');
require_once('../lib/users.class.php');
require_once('../lib/userchecks.php');
require_once('../lib/permissions.php');

session_start();
$userObj = new Users($db);

$pageTitle  = 'App Download Links';
$moduleKey  = 'app_links';
$pageFile   = 'Applinks.php';
$maxUrlLen  = 500;
$sessionKey = 'Applinks';

// Platforms managed from this page (key is stored in app_links.type, the url in app_links.url)
$platforms = array(
    'app_android_url' => array('title' => 'Android App (Google Play)', 'icon' => 'fab fa-android', 'placeholder' => 'https://play.google.com/store/apps/details?id=...'),
    'app_ios_url'     => array('title' => 'iOS App (Apple App Store)', 'icon' => 'fab fa-apple',   'placeholder' => 'https://apps.apple.com/in/app/...')
);

// Security checks
$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = 'You do not have access to this page.';
} elseif (!hasModuleAccess($moduleKey)) {
    $secmsg = 'You do not have access to this module.';
}

// CSRF token, flash message and the values to show again after a validation error
if (empty($_SESSION[$sessionKey . '_csrf'])) {
    $_SESSION[$sessionKey . '_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION[$sessionKey . '_csrf'];
$flash = isset($_SESSION[$sessionKey . '_flash']['text']) ? $_SESSION[$sessionKey . '_flash']['text'] : '';
$flashType = isset($_SESSION[$sessionKey . '_flash']['type']) ? $_SESSION[$sessionKey . '_flash']['type'] : '';
$oldValues = isset($_SESSION[$sessionKey . '_old']) && is_array($_SESSION[$sessionKey . '_old']) ? $_SESSION[$sessionKey . '_old'] : null;
unset($_SESSION[$sessionKey . '_flash'], $_SESSION[$sessionKey . '_old']);

function alSetFlash($text, $type) {
    global $sessionKey;
    $_SESSION[$sessionKey . '_flash'] = array('text' => $text, 'type' => $type);
}

// Returns an error message, or an empty string when the url is valid (an empty url is valid: it hides the button)
function alCheckUrl($url, $title, $maxUrlLen) {
    if ($url === '') {
        return '';
    }
    if (strlen($url) > $maxUrlLen) {
        return $title . ' link is too long (maximum ' . $maxUrlLen . ' characters).';
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return $title . ' link is not a valid URL.';
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    if ($scheme !== 'http' && $scheme !== 'https') {
        return $title . ' link must start with http:// or https://';
    }
    return '';
}

// Handle save
if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf']) || !hash_equals($csrf, (string) $_POST['csrf'])) {
        alSetFlash('Invalid request. Please try again.', 'error');
        header('Location: ' . $pageFile);
        exit;
    }

    $newValues = array();
    $error = '';

    foreach ($platforms as $key => $info) {
        $value = isset($_POST[$key]) ? trim((string) $_POST[$key]) : '';
        $newValues[$key] = $value;
        if ($error === '') {
            $error = alCheckUrl($value, $info['title'], $maxUrlLen);
        }
    }

    if ($error !== '') {
        // Keep what the admin typed so nothing has to be entered again
        $_SESSION[$sessionKey . '_old'] = $newValues;
        alSetFlash($error, 'error');
        header('Location: ' . $pageFile);
        exit;
    }

    try {
        foreach ($platforms as $key => $info) {
            $value = $newValues[$key];
            $existing = $db->getMultiDimensionalArray("SELECT id FROM app_links WHERE `type` = '" . $key . "' ORDER BY id ASC");

            if ($value === '') {
                // Empty field: remove the row so the button is hidden on the website
                if (!empty($existing)) {
                    $db->query("DELETE FROM app_links WHERE `type` = '" . $key . "'");
                }
                continue;
            }

            if (!empty($existing)) {
                $keepId = (int) $existing[0]['id'];
                $db->update("UPDATE app_links SET url = '" . $db->escape($value) . "', `updated` = NOW() WHERE id = " . $keepId . " AND `type` = '" . $key . "'");
                // Remove duplicate rows of this type, only one row is kept
                foreach ($existing as $i => $old) {
                    if ($i > 0) {
                        $db->query("DELETE FROM app_links WHERE id = " . (int) $old['id'] . " AND `type` = '" . $key . "'");
                    }
                }
            } else {
                $db->insert("INSERT INTO app_links (`type`, url, `created`, `updated`) VALUES ('" . $key . "', '" . $db->escape($value) . "', NOW(), NOW())");
            }
        }
        alSetFlash('App download links were saved successfully.', 'success');
    } catch (Exception $e) {
        error_log('App links database save failed: ' . $e->getMessage());
        $_SESSION[$sessionKey . '_old'] = $newValues;
        alSetFlash('Database error. Could not save the links.', 'error');
    }

    header('Location: ' . $pageFile);
    exit;
}

// Load the saved links
$rows = array();
if (empty($secmsg)) {
    foreach ($platforms as $key => $info) {
        $rows[$key] = $db->getSingleRowAssoc("SELECT id, url, `created`, `updated` FROM app_links WHERE `type` = '" . $key . "' ORDER BY id ASC LIMIT 1");
    }
}

$design = new Design();
$design->js = '';
$design->css = '';
$design->jqueryJs = '';
$design->startPage($pageTitle);
$design->writeLogoTickerMenu();
$design->openDiv('contentWrapper');
$design->openDiv('infoWrapper', 'col-lg-12');
$design->openDiv('leftArea', 'col-lg-9');
$design->writeContentPageStyles();
?>
<style>
.message{padding:12px 16px;border-radius:8px;margin-bottom:15px;font-size:15px;background:#fff3cd;border:1px solid #ffe08a}.message.success{background:#e3f6ea;border-color:#a9dcbd;color:#0f5c33}.message.error{background:#fde8e8;border-color:#f5b5b5;color:#a12626}
.ar-header{margin-bottom:20px}.ar-title{font-size:24px;font-weight:700;color:#2b332f;margin:0}.ar-subtitle{font-size:14px;color:#7a8c84;margin:6px 0 0}.ar-card{background:#fff;border:1px solid #e2e6e4;border-radius:12px;padding:24px;box-shadow:0 1px 2px rgba(0,0,0,.03);max-width:100%;margin-bottom:26px}.ar-card-title{font-size:17px;font-weight:600;color:#2b332f;margin:0 0 20px;padding-bottom:14px;border-bottom:1px solid #eef1ef}.ar-field{margin-bottom:20px}.ar-field label{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#2b332f;margin-bottom:6px}.ar-field label i{font-size:18px;color:#0f5c33}.ar-input-row{display:flex;gap:8px;align-items:stretch}.ar-input{flex:1;min-width:0;padding:11px 12px;font-size:14px;color:#2b332f;border:1px solid #d3d9d6;border-radius:8px;background:#fff;box-sizing:border-box;font-family:inherit}.ar-input:focus{outline:none;border-color:#1a7a45;box-shadow:0 0 0 3px rgba(26,122,69,.12)}.ar-hint{font-size:12px;color:#7a8c84;margin:6px 0 0}.ar-field-error{display:none;color:#c0392b;font-size:12px;margin-top:6px}.ar-actions{display:flex;gap:10px;margin-top:8px;padding-top:18px;border-top:1px solid #eef1ef;flex-wrap:wrap}.ar-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 22px;font-size:15px;font-weight:600;border-radius:8px;border:1px solid transparent;cursor:pointer;text-decoration:none}.ar-btn-primary{background:#0f5c33;color:#fff}.ar-btn-primary:hover{background:#1a7a45;color:#fff}.ar-btn-secondary{background:#fff;color:#2b332f;border-color:#d3d9d6}.ar-btn-clear{padding:0 14px;font-size:13px;font-weight:600;border-radius:8px;border:1px solid #d3d9d6;background:#fff;color:#2b332f;cursor:pointer}.ar-btn-clear:hover{border-color:#c0392b;color:#c0392b}.ar-table-wrap{width:100%;overflow-x:auto}.ar-table{width:100%;border-collapse:collapse;min-width:520px}.ar-table th{text-align:left;font-size:12px;color:#0f5c33;background:#f3f6f4;padding:11px 12px;border-bottom:1px solid #e2e6e4}.ar-table td{font-size:14px;color:#2b332f;padding:12px;border-bottom:1px solid #eef1ef;vertical-align:middle}.ar-plat{display:flex;align-items:center;gap:10px;font-weight:600}.ar-plat i{color:#0f5c33;font-size:20px}.ar-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:#e6f4ec;color:#0f5c33}.ar-badge.none{background:#f1f3f2;color:#7a8c84}.ar-url{overflow-wrap:anywhere;color:#0f5c33}.ar-icon-btn{width:34px;height:34px;border-radius:8px;border:1px solid #d3d9d6;background:#fff;color:#2b332f;display:inline-flex;align-items:center;justify-content:center;text-decoration:none}.ar-icon-btn:hover{border-color:#1a7a45;color:#0f5c33;background:#eef7f1}html,body{scrollbar-width:none;-ms-overflow-style:none}html::-webkit-scrollbar,body::-webkit-scrollbar{display:none}
@media(min-width:1920px){.ar-title{font-size:30px}.ar-card{padding:30px}}@media(max-width:700px){.ar-title{font-size:20px}.ar-card{padding:16px;border-radius:10px}}@media(max-width:560px){.ar-actions{flex-direction:column}.ar-btn{width:100%;box-sizing:border-box}.ar-input-row{flex-direction:column}.ar-btn-clear{padding:9px 14px}}
</style>
<?php if ($secmsg !== '') { ?><div class="message"><?php echo htmlspecialchars($secmsg); ?></div><?php } ?>
<?php if ($secmsg === '') { ?>
<div class="main-content">
    <div class="ar-header">
        <h1 class="ar-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
        <p class="ar-subtitle">Set the app download links shown as buttons on the website. A button is shown only when its link is saved; leave a field empty to hide that button.</p>
    </div>
    <?php if ($flash !== '') { ?><div class="message <?php echo htmlspecialchars($flashType); ?>"><?php echo htmlspecialchars($flash); ?></div><?php } ?>

    <div class="ar-card">
        <div class="ar-card-title">App Links</div>
        <form id="linkForm" method="post" action="turf-console/<?php echo htmlspecialchars($pageFile); ?>">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">

            <?php foreach ($platforms as $key => $info) {
                // After a validation error show what was typed, otherwise the saved value
                if ($oldValues !== null && isset($oldValues[$key])) {
                    $current = $oldValues[$key];
                } else {
                    $current = !empty($rows[$key]) ? $rows[$key]['url'] : '';
                }
            ?>
                <div class="ar-field">
                    <label for="f_<?php echo htmlspecialchars($key); ?>"><i class="<?php echo htmlspecialchars($info['icon']); ?>"></i> <?php echo htmlspecialchars($info['title']); ?> link</label>
                    <div class="ar-input-row">
                        <input type="text" class="ar-input js-link" id="f_<?php echo htmlspecialchars($key); ?>" name="<?php echo htmlspecialchars($key); ?>" maxlength="<?php echo (int) $maxUrlLen; ?>" placeholder="<?php echo htmlspecialchars($info['placeholder']); ?>" value="<?php echo htmlspecialchars($current); ?>" autocomplete="off">
                        <button type="button" class="ar-btn-clear js-clear" data-target="f_<?php echo htmlspecialchars($key); ?>">Clear</button>
                    </div>
                    <div class="ar-field-error js-error"></div>
                </div>
            <?php } ?>

            <p class="ar-hint">Tip: clearing a field and saving removes that button from the website.</p>

            <div class="ar-actions"><button type="submit" class="ar-btn ar-btn-primary"><i class="fas fa-save"></i> Save Links</button><button type="button" class="ar-btn ar-btn-secondary" id="linkReset"><i class="fas fa-undo"></i> Reset</button></div>
        </form>
    </div>

    <div class="ar-card">
        <div class="ar-card-title">Currently Saved</div>
        <div class="ar-table-wrap">
            <table class="ar-table">
                <thead><tr><th style="width:240px">Platform</th><th style="width:110px">Website Button</th><th>Link</th><th style="width:130px">Updated</th><th style="width:70px;text-align:center">Open</th></tr></thead>
                <tbody>
                <?php foreach ($platforms as $key => $info) {
                    $r = isset($rows[$key]) ? $rows[$key] : null;
                ?>
                    <tr>
                        <td><div class="ar-plat"><i class="<?php echo htmlspecialchars($info['icon']); ?>"></i> <?php echo htmlspecialchars($info['title']); ?></div></td>
                        <?php if (!$r || $r['url'] === '') { ?>
                            <td><span class="ar-badge none">Hidden</span></td>
                            <td colspan="3"><span style="color:#7a8c84">No link saved.</span></td>
                        <?php } else { ?>
                            <td><span class="ar-badge">Shown</span></td>
                            <td class="ar-url"><?php echo htmlspecialchars($r['url']); ?></td>
                            <td><?php echo !empty($r['updated']) ? htmlspecialchars(date('d M Y', strtotime($r['updated']))) : '&mdash;'; ?></td>
                            <td style="text-align:center"><a class="ar-icon-btn" title="Open link" target="_blank" rel="noopener noreferrer" href="<?php echo htmlspecialchars($r['url']); ?>"><i class="fas fa-up-right-from-square"></i></a></td>
                        <?php } ?>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
(function(){
    var form=document.getElementById('linkForm'),reset=document.getElementById('linkReset');
    var inputs=form.querySelectorAll('.js-link'),clears=form.querySelectorAll('.js-clear');

    // Returns an error message, or an empty string when the value is valid (empty is allowed)
    function check(value){
        if(value==='')return '';
        if(!/^https?:\/\/[^\s]+$/i.test(value))return 'Enter a valid link that starts with http:// or https://';
        return '';
    }
    function showError(input,msg){
        var box=input.closest('.ar-field').querySelector('.js-error');
        box.textContent=msg;box.style.display=msg?'block':'none';
    }

    for(var i=0;i<clears.length;i++){
        clears[i].addEventListener('click',function(){
            var input=document.getElementById(this.getAttribute('data-target'));
            input.value='';showError(input,'');input.focus();
        });
    }
    for(var j=0;j<inputs.length;j++){
        inputs[j].addEventListener('input',function(){showError(this,'');});
    }

    reset.addEventListener('click',function(){
        form.reset();
        for(var k=0;k<inputs.length;k++){showError(inputs[k],'');}
    });

    form.addEventListener('submit',function(e){
        var ok=true;
        for(var n=0;n<inputs.length;n++){
            inputs[n].value=inputs[n].value.trim();
            var msg=check(inputs[n].value);
            showError(inputs[n],msg);
            if(msg)ok=false;
        }
        if(!ok)e.preventDefault();
    });
})();
</script>
<?php } ?>
<?php
$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->closeDiv();
$design->endPage();
$design = null;
?>
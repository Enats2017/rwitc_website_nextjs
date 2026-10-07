<?php

include_once('../bootstrap.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once("../lib/permissions.php");

session_start();

$userObj = new Users($db);

// LOGIN + MODULE ACCESS CHECK
if (!isAdminlogin()) {
    $secmsg = "You do not have access to this page.";
} elseif (!hasModuleAccess('notice_agm')) {
    $secmsg = "You do not have access to this module.";
}

// PAGE TITLE
$pageTitle = 'Notice for the AGM';

// DESIGN OBJECT
$design = new Design();
$design->js = '';
$design->css = '';
$design->jqueryJs = "";
$design->startPage("$pageTitle");
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper", "col-lg-12");
$design->openDiv("leftArea", 'col-lg-9');
$design->writeContentPageStyles();

?>
<style type="text/css">
    .message { background: #fff3cd; border: 1px solid #ffe08a; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; font-size: 15px; }
    .message.success { background: #e3f6ea; border-color: #a9dcbd; color: #0f5c33; }
    .message.error { background: #fde8e8; border-color: #f5b5b5; color: #a12626; }
    .submenu { display: none; }

    .agm-header { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
    .agm-header > div { display: flex; align-items: baseline; flex-wrap: wrap; gap: 10px; }
    .agm-title { font-size: 24px; font-weight: 700; color: #2b332f; margin: 0; }
    .agm-subtitle { font-size: 14px; color: #7a8c84; margin: 0; }

    .agm-card { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 24px; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03); max-width: 820px; }
    .agm-card-title { font-family: inherit; font-size: 17px; font-weight: 600; color: #2b332f; margin: 0 0 20px; padding-bottom: 14px; border-bottom: 1px solid #eef1ef; white-space: nowrap; }
    .agm-card-title::before, .agm-card-title::after { display: none; content: none; }

    .agm-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .agm-field { margin-bottom: 18px; min-width: 0; }
    .agm-field label { display: block; font-size: 13px; font-weight: 600; color: #2b332f; margin-bottom: 6px; }
    .agm-field label .req { color: #c0392b; }
    .agm-input, .agm-select, .agm-textarea { width: 100%; box-sizing: border-box; padding: 10px 12px; font-size: 15px; font-family: inherit; color: #2b332f; background: #fff; border: 1px solid #d3d9d6; border-radius: 8px; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
    .agm-input::placeholder, .agm-textarea::placeholder { color: #9aa9a2; }
    .agm-input:focus, .agm-select:focus, .agm-textarea:focus { border-color: #1a7a45; box-shadow: 0 0 0 3px rgba(26, 122, 69, 0.15); }
    .agm-textarea { min-height: 220px; resize: vertical; line-height: 1.5; }
    .agm-select { cursor: pointer; appearance: none; -webkit-appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%237a8c84' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; padding-right: 38px; }
    .agm-hint { font-size: 12px; color: #7a8c84; margin-top: 6px; display: flex; justify-content: space-between; gap: 10px; }
    .agm-input.invalid, .agm-select.invalid, .agm-textarea.invalid, .agm-drop.invalid { border-color: #c0392b; }
    .agm-err { color: #c0392b; font-size: 12px; margin-top: 5px; display: none; }
    .agm-err.show { display: block; }

    .agm-section { display: none; }
    .agm-section.active { display: block; animation: agmFade .2s ease; }
    @keyframes agmFade { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }

    .agm-drop { border: 2px dashed #b9c4be; border-radius: 12px; padding: 32px 16px; text-align: center; cursor: pointer; background: #f8faf9; transition: all .15s ease; }
    .agm-drop:hover, .agm-drop.dragover { border-color: #1a7a45; background: #eef7f1; }
    .agm-drop-icon { font-size: 32px; color: #0f5c33; margin-bottom: 10px; }
    .agm-drop-text { font-size: 15px; font-weight: 500; color: #2b332f; margin: 0 0 4px; }
    .agm-drop-text span { color: #0f5c33; text-decoration: underline; }
    .agm-drop-sub { font-size: 12px; color: #7a8c84; margin: 0; }
    .agm-file-input { display: none; }

    .agm-file-box { display: none; align-items: center; gap: 12px; margin-top: 12px; padding: 12px 14px; border: 1px solid #e2e6e4; border-radius: 10px; background: #fff; }
    .agm-file-box.show { display: flex; }
    .agm-file-ico { width: 38px; height: 38px; border-radius: 9px; background: #0f5c33; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
    .agm-file-info { flex: 1; min-width: 0; }
    .agm-file-name { font-size: 14px; font-weight: 500; color: #2b332f; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .agm-file-size { font-size: 12px; color: #7a8c84; }
    .agm-file-remove { border: none; background: #fde8e8; color: #a12626; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; font-size: 13px; flex-shrink: 0; }
    .agm-file-remove:hover { background: #f9d0d0; }

    .agm-actions { display: flex; gap: 10px; margin-top: 8px; padding-top: 18px; border-top: 1px solid #eef1ef; flex-wrap: wrap; }
    .agm-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 22px; font-size: 15px; font-weight: 600; font-family: inherit; border-radius: 8px; border: 1px solid transparent; cursor: pointer; transition: all .15s ease; }
    .agm-btn-primary { background: #0f5c33; color: #fff; }
    .agm-btn-primary:hover { background: #1a7a45; box-shadow: 0 4px 10px rgba(15, 92, 51, 0.25); }
    .agm-btn-secondary { background: #fff; color: #2b332f; border-color: #d3d9d6; }
    .agm-btn-secondary:hover { border-color: #7a8c84; }

    html, body { scrollbar-width: none; -ms-overflow-style: none; }
    html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

    @media (min-width: 1920px) {
        .agm-title { font-size: 30px; }
        .agm-card { max-width: 980px; padding: 30px; }
    }

    @media (max-width: 700px) {
        .agm-title { font-size: 20px; }
        .agm-card { padding: 16px; border-radius: 10px; }
        .agm-row { grid-template-columns: 1fr; gap: 0; }
        .agm-drop { padding: 24px 12px; }
    }

    @media (max-width: 560px) {
        .agm-header { flex-direction: column; align-items: flex-start; margin-bottom: 14px; gap: 8px; }
        .agm-subtitle { font-size: 13px; }
        .agm-actions { flex-direction: column; }
        .agm-btn { width: 100%; }
        .agm-textarea { min-height: 180px; }
    }
</style>

<?php if (!empty($secmsg)) { ?>
    <div class="message">
        <?php echo $secmsg; ?>
    </div>
<?php } ?>


<?php if (empty($secmsg)) { ?>

    <div class="submenu">
        <div style="float:right;">
            <a style="float:left;" href="../dashboard.php">Dashboard</a>
            <a style="float:left; margin-left:5px;" href="../index.php?q=logout">Logout</a>
        </div>
    </div>

    <div class="main-content">

        <div class="agm-header">
            <div>
                <h1 class="agm-title">NOTICE FOR THE AGM</h1>
                <p class="agm-subtitle">Upload a file or write a notice</p>
            </div>
        </div>

        <div id="agmMsg" class="message" style="display:none;"></div>

        <div class="agm-card">

            <div class="agm-card-title">Add New Notice</div>

            <form id="agmForm" method="post" action="" enctype="multipart/form-data" novalidate>

                <div class="agm-row">

                    <div class="agm-field">
                        <label for="agmDate">Date <span class="req">*</span></label>
                        <input type="text" id="agmDate" name="notice_date" class="agm-input" placeholder="YYYY-MM-DD" maxlength="10" inputmode="numeric" autocomplete="off">
                        <div class="agm-hint"><span>Format: YYYY-MM-DD (e.g. 2026-10-07)</span></div>
                        <div class="agm-err" id="errDate">Please enter a valid date in YYYY-MM-DD format.</div>
                    </div>

                    <div class="agm-field">
                        <label for="agmTitle">Title <span class="req">*</span></label>
                        <input type="text" id="agmTitle" name="notice_title" class="agm-input" maxlength="200" placeholder="Enter notice title">
                        <div class="agm-err" id="errTitle">Please enter a title.</div>
                    </div>

                </div>

                <div class="agm-field">
                    <label for="agmType">Notice Type <span class="req">*</span></label>
                    <select id="agmType" name="notice_type" class="agm-select">
                        <option value="">-- Select --</option>
                        <option value="text">Write Text</option>
                        <option value="file">Upload File</option>
                    </select>
                    <div class="agm-err" id="errType">Please select a notice type.</div>
                </div>


                <!-- TEXT SECTION -->
                <div class="agm-section" id="secText">
                    <div class="agm-field">
                        <label for="agmText">Notice Text <span class="req">*</span></label>
                        <textarea id="agmText" name="notice_text" class="agm-textarea" placeholder="Write your notice here..."></textarea>
                        <div class="agm-hint"><span>Plain text / HTML allowed</span><span id="agmCount">0 characters</span></div>
                        <div class="agm-err" id="errText">Please write some text.</div>
                    </div>
                </div>


                <!-- FILE SECTION -->
                <div class="agm-section" id="secFile">
                    <div class="agm-field">
                        <label>Upload File <span class="req">*</span></label>

                        <div class="agm-drop" id="agmDrop">
                            <div class="agm-drop-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                            <p class="agm-drop-text">Drag &amp; drop file here or <span>browse</span></p>
                            <p class="agm-drop-sub">Any file type allowed &mdash; HTML, DOC, DOCX, PDF, ZIP, images, etc.</p>
                        </div>

                        <input type="file" id="agmFile" name="notice_file" class="agm-file-input">

                        <div class="agm-file-box" id="agmFileBox">
                            <div class="agm-file-ico"><i class="fas fa-file" id="agmFileIcon"></i></div>
                            <div class="agm-file-info">
                                <div class="agm-file-name" id="agmFileName"></div>
                                <div class="agm-file-size" id="agmFileSize"></div>
                            </div>
                            <button type="button" class="agm-file-remove" id="agmFileRemove" title="Remove file">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="agm-err" id="errFile">Please choose a file.</div>
                    </div>
                </div>


                <div class="agm-actions">
                    <button type="submit" class="agm-btn agm-btn-primary">
                        <i class="fas fa-save"></i> Save Notice
                    </button>
                    <button type="button" class="agm-btn agm-btn-secondary" id="agmReset">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>

            </form>

        </div>

    </div>


    <script type="text/javascript">
        (function() {

            var form     = document.getElementById('agmForm');
            var dateIn   = document.getElementById('agmDate');
            var titleIn  = document.getElementById('agmTitle');
            var typeSel  = document.getElementById('agmType');
            var secText  = document.getElementById('secText');
            var secFile  = document.getElementById('secFile');
            var txtArea  = document.getElementById('agmText');
            var counter  = document.getElementById('agmCount');
            var drop     = document.getElementById('agmDrop');
            var fileIn   = document.getElementById('agmFile');
            var fileBox  = document.getElementById('agmFileBox');
            var fileName = document.getElementById('agmFileName');
            var fileSize = document.getElementById('agmFileSize');
            var fileIcon = document.getElementById('agmFileIcon');
            var fileRem  = document.getElementById('agmFileRemove');
            var msgBox   = document.getElementById('agmMsg');
            var msgTimer = null;

            function $(id) {
                return document.getElementById(id);
            }

            // Show a temporary message box at the top of the page
            function showMsg(text, type) {
                msgBox.className = 'message ' + (type || '');
                msgBox.textContent = text;
                msgBox.style.display = 'block';
                clearTimeout(msgTimer);
                msgTimer = setTimeout(function() {
                    msgBox.style.display = 'none';
                }, 4000);
            }

            // Show or hide an error message and mark the input as invalid
            function setErr(errId, inputEl, show) {
                var e = $(errId);
                if (show) {
                    e.classList.add('show');
                } else {
                    e.classList.remove('show');
                }
                if (inputEl) {
                    if (show) {
                        inputEl.classList.add('invalid');
                    } else {
                        inputEl.classList.remove('invalid');
                    }
                }
            }

            // Convert bytes into a readable size
            function formatSize(b) {
                if (b < 1024) return b + ' B';
                if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
                if (b < 1073741824) return (b / 1048576).toFixed(2) + ' MB';
                return (b / 1073741824).toFixed(2) + ' GB';
            }

            // Pick a FontAwesome icon based on file extension
            function iconFor(name) {
                var ext = (name.split('.').pop() || '').toLowerCase();
                if (ext === 'pdf') return 'fas fa-file-pdf';
                if (ext === 'doc' || ext === 'docx') return 'fas fa-file-word';
                if (ext === 'xls' || ext === 'xlsx' || ext === 'csv') return 'fas fa-file-excel';
                if (ext === 'zip' || ext === 'rar' || ext === '7z') return 'fas fa-file-archive';
                if (ext === 'htm' || ext === 'html') return 'fas fa-file-code';
                if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].indexOf(ext) !== -1) return 'fas fa-file-image';
                return 'fas fa-file';
            }

            // Show selected file details
            function showFile(file) {
                fileName.textContent = file.name;
                fileSize.textContent = formatSize(file.size);
                fileIcon.className = iconFor(file.name);
                fileBox.classList.add('show');
                setErr('errFile', drop, false);
            }

            // Clear the selected file
            function clearFile() {
                fileIn.value = '';
                fileBox.classList.remove('show');
            }

            // Check that the value is a real calendar date in YYYY-MM-DD format
            function isValidDate(str) {
                if (!/^\d{4}-\d{2}-\d{2}$/.test(str)) return false;
                var y = parseInt(str.substr(0, 4), 10);
                var m = parseInt(str.substr(5, 2), 10);
                var d = parseInt(str.substr(8, 2), 10);
                if (y < 1900 || y > 2100) return false;
                var dt = new Date(y, m - 1, d);
                return dt.getFullYear() === y && dt.getMonth() === m - 1 && dt.getDate() === d;
            }

            // ---------- Date field: auto-format while typing ----------
            dateIn.addEventListener('input', function() {
                var digits = this.value.replace(/\D/g, '').slice(0, 8);
                var out = digits;
                if (digits.length > 6) {
                    out = digits.slice(0, 4) + '-' + digits.slice(4, 6) + '-' + digits.slice(6);
                } else if (digits.length > 4) {
                    out = digits.slice(0, 4) + '-' + digits.slice(4);
                }
                this.value = out;
                if (isValidDate(out)) setErr('errDate', this, false);
            });

            // ---------- Type dropdown: switch between text and file sections ----------
            function applyType() {
                var v = typeSel.value;
                secText.classList.toggle('active', v === 'text');
                secFile.classList.toggle('active', v === 'file');
                setErr('errText', txtArea, false);
                setErr('errFile', drop, false);
                if (v) setErr('errType', typeSel, false);
            }
            typeSel.addEventListener('change', applyType);

            // ---------- Text counter ----------
            txtArea.addEventListener('input', function() {
                counter.textContent = txtArea.value.length + ' characters';
                if (txtArea.value.trim()) setErr('errText', txtArea, false);
            });

            // ---------- File: click and drag/drop ----------
            drop.addEventListener('click', function() {
                fileIn.click();
            });

            fileIn.addEventListener('change', function() {
                if (fileIn.files && fileIn.files[0]) {
                    showFile(fileIn.files[0]);
                }
            });

            ['dragenter', 'dragover'].forEach(function(ev) {
                drop.addEventListener(ev, function(e) {
                    e.preventDefault();
                    drop.classList.add('dragover');
                });
            });
            ['dragleave', 'drop'].forEach(function(ev) {
                drop.addEventListener(ev, function(e) {
                    e.preventDefault();
                    drop.classList.remove('dragover');
                });
            });
            drop.addEventListener('drop', function(e) {
                if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                    try {
                        fileIn.files = e.dataTransfer.files;
                    } catch (err) {}
                    showFile(e.dataTransfer.files[0]);
                }
            });

            fileRem.addEventListener('click', clearFile);

            // ---------- Clear title error while typing ----------
            titleIn.addEventListener('input', function() {
                if (this.value.trim()) setErr('errTitle', this, false);
            });

            // ---------- Reset button ----------
            $('agmReset').addEventListener('click', function() {
                form.reset();
                clearFile();
                counter.textContent = '0 characters';
                applyType();
                ['errDate', 'errTitle', 'errType', 'errText', 'errFile'].forEach(function(id) {
                    setErr(id, null, false);
                });
                [dateIn, titleIn, typeSel, txtArea, drop].forEach(function(el) {
                    el.classList.remove('invalid');
                });
            });

            // ---------- Submit: frontend validation ----------
            form.addEventListener('submit', function(e) {
                var ok = true;

                if (!isValidDate(dateIn.value)) {
                    setErr('errDate', dateIn, true);
                    ok = false;
                } else {
                    setErr('errDate', dateIn, false);
                }
                if (!titleIn.value.trim()) {
                    setErr('errTitle', titleIn, true);
                    ok = false;
                } else {
                    setErr('errTitle', titleIn, false);
                }
                if (!typeSel.value) {
                    setErr('errType', typeSel, true);
                    ok = false;
                } else {
                    setErr('errType', typeSel, false);
                }

                if (typeSel.value === 'text') {
                    if (!txtArea.value.trim()) {
                        setErr('errText', txtArea, true);
                        ok = false;
                    } else {
                        setErr('errText', txtArea, false);
                    }
                }
                if (typeSel.value === 'file') {
                    if (!fileIn.files || !fileIn.files.length) {
                        setErr('errFile', drop, true);
                        ok = false;
                    } else {
                        setErr('errFile', drop, false);
                    }
                }

                if (!ok) {
                    e.preventDefault();
                    showMsg('Please fill all required fields correctly.', 'error');
                    return;
                }

                // TODO (when backend is ready): remove the preventDefault and the demo
                // message below. The form will then POST normally (multipart/form-data
                // is already set on the form).
                e.preventDefault();
                showMsg('Form is valid. Save logic will be connected to the backend later.', 'success');
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
$design = NULL;

?>
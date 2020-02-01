<?php
require_once 'UserUploader.php';
$u = new UserUploader();
exit($u->main($argc, $argv));
?>

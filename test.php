<?php
// This file requires assertions to be enabled, so zend.assertions must be set
// to 1 in php.ini. It cannot be set with ini_set().

require 'UserUploader.php';

$uploader = new UserUploader();
$ret = $uploader->main(["user_upload.php"]);
assert($ret == UserUploader::EXIT_BAD_INVOCATION);

$ret = $uploader->main(explode(" ",
        "user_upload.php -u phpapp -p phpapp -h localhost -d appdb"));
assert($ret == UserUploader::EXIT_NO_FILE);

?>

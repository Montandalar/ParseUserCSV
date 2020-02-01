<?php
// This file requires assertions to be enabled, so zend.assertions must be set
// to 1 in php.ini. It cannot be set with ini_set().

require 'UserUploader.php';

$uploader = new UserUploader();

// TODO: Would be moved into config for a production application
// OR a smarter process would automate creating a MySQL user and db during testing
$user = "phpapp";
$pass = "phpapp";
$database = "appdb";
$host = "localhost";

// No args -> Bad invocation
$ret = $uploader->main(["user_upload.php"]);
assert($ret == UserUploader::EXIT_BAD_INVOCATION);

// Bad invocation - invalid arg
$ret = $uploader->main(["user_upload.php", "--foo"]);
assert($ret = UserUploader::EXIT_BAD_INVOCATION);

// Help -> Success
$ret = $uploader->main(["user_upload.php", "--help"]);
assert($ret == UserUploader::EXIT_SUCCESS);

// Try database connection with bad details - host
$ret = $uploader->main(explode(" ",
        "user_upload.php -u nope -p nope -h nope -d nope --create_table"));
assert($ret == UserUploader::EXIT_DATABASE_ERROR);

// Try database connection with bad details - credentials
$ret = $uploader->main(explode(" ",
        "user_upload.php -u nope -p nope -h $host -d nope --create_table"));
assert($ret == UserUploader::EXIT_DATABASE_ERROR);

// Try database connection with bad details - database
$ret = $uploader->main(explode(" ",
        "user_upload.php -u $user -p $pass -h $host -d this_db_does_not_exist --create_table"));
assert($ret == UserUploader::EXIT_DATABASE_ERROR);

// Create table should work
$ret = $uploader->main(explode(" ",
        "user_upload.php -u $user -p $pass -h $host -d $database --create_table"));
assert($ret == UserUploader::EXIT_SUCCESS);

// File required -> Exit 'no file'
$ret = $uploader->main(explode(" ",
        "user_upload.php -u $user -p $pass -h $host -d $database"));
assert($ret == UserUploader::EXIT_NO_FILE);

// Create a temporary file, then delete it, to ensure it does not exist.
$fname = tempnam(".", "");
assert($fname !== FALSE);
assert(unlink($fname));
// Try to read the temp file as input
$ret = $uploader->main(explode(" ",
        "user_upload.php -u $user -p $pass -h $host -d appdb --file $fname"));
assert($ret == UserUploader::EXIT_NO_FILE);

// Try to read file without permission
$fname = tempnam(".", "");
assert($fname !== FALSE);
// Untested on windows:
// Make file un-readable,writeable,executable by everyone
assert(chmod($fname, 0));
$ret = $uploader->main(explode(" ",
        "user_upload.php -u $user -p $pass -h $host -d appdb --file $fname"));
assert($ret == UserUploader::EXIT_NO_FILE);
assert(unlink($fname));

// Empty file should be a no-op
$fname = tempnam(".", "");
assert($fname !== FALSE);
$ret = $uploader->main(explode(" ",
        "user_upload.php -u $user -p $pass -h $host -d appdb --file $fname"));
assert($ret == UserUploader::EXIT_SUCCESS);
assert(unlink($fname));


?>

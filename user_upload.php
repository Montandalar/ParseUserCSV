<?php
use Garden\Cli\Cli;

require_once 'vendor/autoload.php';
require_once 'UserUploader.php';

global $argv;

$cli = new Cli();
$cli->description("Parse a CSV of users into the database")
    ->opt('file', 'Path of the CSV to be parsed', false, 'string')
    ->opt('create_table', 'Make the MySQL users table, then quit')
    ->opt('dry_run', "Don't alter the database", false, 'boolean')
    ->opt('user:u', 'MySQL username', true, 'string')
    ->opt('password:p', 'MySQL password', true, 'string')
    ->opt('host:h', 'MySQL host', true, 'string')
    ->opt('database:d', 'MySQL database', true, 'string');
    // --help is implicit

$args = $cli->parse($argv, true);
//var_dump($args);
//var_dump($cli);

$uploader = new UserUploader($cli, $args);
exit($uploader->main());
?>

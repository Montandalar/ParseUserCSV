<?php

function print_usage() {

?>
user_upload.php - Parse a CSV of users into the database

    --file [csv file name] - path of the CSV to be parsed
    --create_table - this will cause the MySQL users table to be built
    (and no further action will be taken)
    --dry_run - don't alter the database
    -u - MySQL database username
    -p - MySQL database password
    -h - MySQL database host
    --help - show this help
<?php    
}

function main() {
    global $argv;
    global $argc;

    $opts = getopt("u:p:h:", ["file::", "create_table", "dry_run::", "help::"],
        $optind);

    //var_dump($opts);
    //echo "\$argv[$optind]=", $argv[$optind], "\n";
    if (isset($opts["help"])) {
        print_usage();
        exit(1);
    }
    if (($optind < $argv)) {
        printf("Unrecognised option: %s\n\n", $argv[$optind-1]);
        print_usage();
        exit(1);
    }

    foreach (["u" => "username", "p" => "password", "h" => "host"] as $opt => $msg) {
        if (!isset($opts[$opt])) {
            printf("Please specify the %s for the database\n", $msg);
            exit(1);
        }
    }
}
main();
?>

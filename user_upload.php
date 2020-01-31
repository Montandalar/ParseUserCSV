<?php

function main() {
    global $argv;

    $opts = getopt("u:p:h:", ["file::", "create_table", "dry_run::", "help::"],
        $optind);

    foreach (["u" => "username", "p" => "password", "h" => "host"] as $opt => $msg) {
        if (!isset($opts[$opt])) {
            printf("Please specify the %s for the database\n", $msg);
            exit(1);
        }
    }
}
main();
?>

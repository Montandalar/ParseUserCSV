<?php

function main() {
    global $argv;

    $opts = getopt("u:p:h:", ["file::", "create_table", "dry_run::", "help::"],
        $optind);

    var_dump($opts);
    print($optind); print("\n");
    print($argv[10]);
}
main();
?>

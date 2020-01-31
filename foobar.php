<?php
for ($i = 1; $i <= 100; ++$i) {
    $a = ($i%3);
    if (!$a) {
        echo "foo";
    }
    if (($i%5 == 0)) {
        echo "bar";
        goto nextitem;
    }
    if ($a) {
        echo $i;
    }
nextitem:
    if ($i < 100) {
        echo ", ";
    }
}
echo "\n";

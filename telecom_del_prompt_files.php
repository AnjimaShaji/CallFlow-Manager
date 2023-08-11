<?php

$dir = "/var/www/production/TPCallFlowManager/storage/app/promptFilesCopy";
$now   = time();

foreach (glob("$dir/*") as $file) {
        if (is_file($file)) {
                if ($now - filemtime($file) >= 60 * 60 * 24 * 5) { // 5 days
                        print_r($file);
                        unlink($file);
                }
    }
}

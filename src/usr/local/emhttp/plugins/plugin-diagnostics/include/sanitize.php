<?php

function run($cmd, &$save=null, $timeout=30) {
    global $cli,$diag;
    // execute command with timeout of 30s
    exec("timeout -s9 $timeout $cmd", $save);
    return implode("\n",$save);
  }

function sanitizeFile($file, $customFilters = array()) {
    $defaultFilters = [
        "s/([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/\\1\.aaa\.aaa\.\\4/g",
        "s/([\"\[ ]([0-9a-f]{1,4}:){4})(([0-9a-f]{1,4}:){3}|:)([0-9a-f]{1,4})([/\" .]|$)/\\1XXXX:XXXX:XXXX:\\5\\6/g"
    ];

    $filters = array_merge($defaultFilters, $customFilters);

    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $gz = false;

    if($ext == "gz") {
        $gz = true;
    }

    foreach($filters as $filter) {
        if($gz) {
            copy($file, "{$file}~");
            run("gzip -cd " . escapeshellarg("{$file}~") . " | sed -r '{$filter}' | gzip > " . escapeshellarg($file));
            unlink("{$file}~");
        } else {
            run("sed -ri '{$filter}' ".escapeshellarg($file)." 2>/dev/null");
        }
    }
}
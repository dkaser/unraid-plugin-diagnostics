<?php

function run($cmd, &$save=null, $timeout=30) {
    global $cli,$diag;
    // execute command with timeout of 30s
    exec("timeout -s9 $timeout $cmd", $save);
    return implode("\n",$save);
  }

function sanitizeFile($file) {
    $rfc1918 = "(127|10|172\.1[6-9]|172\.2[0-9]|172\.3[0-1]|192\.168)((\.[0-9]{1,3}){2,3}([/\" .]|$))";

    $filters = [
        "s/([\"\[ ]){$rfc1918}/\\1@@@\\2\\3/g; s/([\"\[ ][0-9]{1,3}\.)([0-9]{1,3}\.){2}([0-9]{1,3})([/\" .]|$)/\\1XXX.XXX.\\3\\4/g; s/@@@//g",
        "s/([\"\[ ]([0-9a-f]{1,4}:){4})(([0-9a-f]{1,4}:){3}|:)([0-9a-f]{1,4})([/\" .]|$)/\\1XXXX:XXXX:XXXX:\\5\\6/g"
    ];

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
<?php

function run(string $cmd): void
{
    exec("timeout -s9 30 {$cmd}");
}

/**
 * @param array<string> $customFilters
 */
function sanitizeFile(string $file, array $customFilters = array()): void
{
    $defaultFilters = [
        "s/([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/\\1\.aaa\.aaa\.\\4/g",
        "s/([\"\[ ]([0-9a-f]{1,4}:){4})(([0-9a-f]{1,4}:){3}|:)([0-9a-f]{1,4})([/\" .]|$)/\\1XXXX:XXXX:XXXX:\\5\\6/g"
    ];

    $filters = array_merge($defaultFilters, $customFilters);

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    foreach ($filters as $filter) {
        switch ($ext) {
            case "gz":
                copy($file, "{$file}~");
                run("gzip -cd " . escapeshellarg("{$file}~") . " | sed -r '{$filter}' | gzip > " . escapeshellarg($file));
                unlink("{$file}~");
                break;
            default:
                run("sed -ri '{$filter}' " . escapeshellarg($file) . " 2>/dev/null");
        }
    }
}

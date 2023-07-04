<?php

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';

$configFile = realpath("{$docroot}/plugins/{$_GET['plugin']}/diagnostics.json");
if ( ! str_starts_with($configFile, "{$docroot}/plugins/")) {
    echo "Bad Request";
    exit;
}

$config    = json_decode(file_get_contents($configFile), true);
$timestamp = date("Ymd-His");
$filename  = gethostname() . "-{$_GET['plugin']}-diag-{$timestamp}";

$diagnosticsFile       = "/tmp/{$filename}.zip";
$systemDiagnosticsFile = "/tmp/system-diagnostics-{$timestamp}.zip";

// Create the diagnostic archive
$zip = new ZipArchive();
if ($zip->open($diagnosticsFile, ZipArchive::CREATE) !== true) {
    exit("cannot create diagnostic file {$diagnosticsFile}\n");
}

// Add system diagnostics
if (array_key_exists("system_diagnostics", $config) ? $config["system_diagnostics"] : false) {
    exec("diagnostics '{$systemDiagnosticsFile}'");
    if (file_exists($systemDiagnosticsFile)) {
        $zip->addFile($systemDiagnosticsFile, "{$filename}/system-diagnostics.zip");
    }
}

// Run commands and save output
if (array_key_exists("commands", $config)) {
    foreach ($config["commands"] as $command) {
        $zip->addFromString("{$filename}/commands/{$command['file']}", shell_exec($command["command"]));
    }
}

// Collect files
if (array_key_exists("files", $config)) {
    foreach ($config["files"] as $file) {
        if (file_exists($file)) {
            $zip->addFile($file, "{$filename}/files/" . ltrim($file, '/'));
        }
    }
}

// Close the archive
$zip->close();

// Send the file
header('Content-type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '.zip"');
header("Pragma: no-cache");
header("Expires: 0");
readfile($diagnosticsFile);

if (file_exists($diagnosticsFile)) {
    unlink($diagnosticsFile);
}
if (file_exists($systemDiagnosticsFile)) {
    unlink($systemDiagnosticsFile);
}

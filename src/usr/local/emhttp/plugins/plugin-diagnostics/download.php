<?php

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
require_once "{$docroot}/plugins/plugin-diagnostics/include/helpers.php";

$pluginName = isset($diag_plugin_name) ? $diag_plugin_name : $_GET['plugin'];

$configFile = realpath("{$docroot}/plugins/{$pluginName}/diagnostics.json");
if ( ! str_starts_with($configFile, "{$docroot}/plugins/")) {
    echo "Bad Request";
    exit;
}

$config    = json_decode(file_get_contents($configFile), true);
$timestamp = date("Ymd-His");
$filename  = gethostname() . "-{$pluginName}-diag-{$timestamp}";

if(http_response_code()) {
    $diagnosticsFile       = "/tmp/{$filename}.zip";
} else {
    $diagnosticsFile       = "/boot/logs/{$filename}.zip";
}
$diagnosticsFolder     = "/tmp/{$filename}";
$systemDiagnosticsFile = "{$diagnosticsFolder}/system-diagnostics.zip";

mkdir($diagnosticsFolder, 0755);

// Add system diagnostics
if (array_key_exists("system_diagnostics", $config) ? $config["system_diagnostics"] : false) {
    exec("diagnostics '{$systemDiagnosticsFile}'");
}

// Run commands and save output
if (array_key_exists("commands", $config)) {
    mkdir("{$diagnosticsFolder}/commands", 0755);
    foreach ($config["commands"] as $command) {
        file_put_contents("{$diagnosticsFolder}/commands/{$command['file']}", shell_exec($command["command"]));
    }
}

// Collect files
if (array_key_exists("files", $config)) {
    mkdir("{$diagnosticsFolder}/files", 0755);
    foreach ($config["files"] as $file) {
        if (file_exists($file)) {
            $destFile = "{$diagnosticsFolder}/files{$file}";
            if ( ! is_dir(dirname($destFile))) {
                mkdir(dirname($destFile), 0755, true);
            }
            copy($file, $destFile);
        }
    }
}

exec("cd /tmp && zip -qmr " . escapeshellarg($diagnosticsFile) . " " . escapeshellarg("{$filename}/"));

if (file_exists($diagnosticsFile)) {
    if (http_response_code()) {
        // Send the file
        header('Content-type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '.zip"');
        header("Pragma: no-cache");
        header("Expires: 0");
        readfile($diagnosticsFile);

        unlink($diagnosticsFile);
    } else {
        echo "Diagnostics file written to {$diagnosticsFile}" . PHP_EOL;
    }
} else {
    echo "Could not generate diagnostic package.";
}

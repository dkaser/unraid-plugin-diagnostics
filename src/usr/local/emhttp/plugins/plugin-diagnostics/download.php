<?php

namespace EDACerton\PluginDiagnostics;

require_once dirname(__FILE__) . "/include/common.php";

$pluginName      = isset($diag_plugin_name) ? $diag_plugin_name : $_GET['plugin'];
$diagnosticsFile = Diagnostics::createDiagnostics($pluginName);

if (file_exists($diagnosticsFile)) {
    if (http_response_code()) {
        // Send the file
        header('Content-type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($diagnosticsFile) . '"');
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

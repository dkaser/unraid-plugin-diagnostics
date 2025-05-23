<?php

namespace PluginDiagnostics;

require_once dirname(__FILE__) . "/include/common.php";
$pluginName = isset($diag_plugin_name) ? $diag_plugin_name : $_POST['plugin'];
$config     = (array) json_decode(Diagnostics::getDiagnosticsConfig($pluginName), true);

if ( ! isset($config['upload']) || empty($config['upload'])) {
    throw new \RuntimeException("Upload URL not set in diagnostics config.");
}

if ( ! is_string($config['upload'])) {
    throw new \RuntimeException("Upload URL is not a string.");
}

$diagnosticsFile = Diagnostics::createDiagnostics($pluginName);

if (file_exists($diagnosticsFile)) {
    $result = Utils::send_file($config['upload'], $diagnosticsFile);
    echo $result;
} else {
    echo "Could not generate diagnostic package.";
}

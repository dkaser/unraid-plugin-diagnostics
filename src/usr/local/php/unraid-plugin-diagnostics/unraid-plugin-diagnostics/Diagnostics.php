<?php

namespace EDACerton\PluginDiagnostics;

/*
    Copyright (C) 2025  Derek Kaser

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

class Diagnostics
{
    public static function getDiagnosticsConfig(string $pluginName): string
    {
        $configFile = realpath("/usr/local/emhttp/plugins/{$pluginName}/diagnostics.json");
        if ( ! $configFile || ! str_starts_with($configFile, "/usr/local/emhttp/plugins/")) {
            throw new \InvalidArgumentException("Bad Request");
        }

        $configContents = file_get_contents($configFile);
        if ( ! $configContents) {
            throw new \RuntimeException("Could not read configuration file.");
        }

        return $configContents;
    }

    public static function createDiagnostics(string $pluginName): string
    {
        $config    = (array) json_decode(self::getDiagnosticsConfig($pluginName), true);
        $timestamp = date("Ymd-His");
        $filename  = gethostname() . "-{$pluginName}-diag-{$timestamp}";

        if (http_response_code()) {
            $diagnosticsFile = "/tmp/{$filename}.zip";
        } else {
            $diagnosticsFile = "/boot/logs/{$filename}.zip";
        }
        $diagnosticsFolder     = "/tmp/{$filename}";
        $systemDiagnosticsFile = "{$diagnosticsFolder}/system-diagnostics.zip";

        mkdir($diagnosticsFolder, 0755);

        file_put_contents($diagnosticsFolder . "/plugin-diagnostics.txt", "Plugin: {$pluginName}\n");

        $customFilters = (array_key_exists("filters", $config)) ? $config["filters"] : array();
        if ( ! is_array($customFilters)) {
            throw new \InvalidArgumentException("Invalid filters");
        }

        // Add system diagnostics
        if (array_key_exists("system_diagnostics", $config) ? $config["system_diagnostics"] : false) {
            exec("diagnostics '{$systemDiagnosticsFile}'");
        }

        // Run commands and save output
        if (array_key_exists("commands", $config)) {
            mkdir("{$diagnosticsFolder}/commands", 0755);

            foreach ($config["commands"] as $command) {
                $commandFilters = array_key_exists("filters", $command) ? $command["filters"] : array();

                file_put_contents("{$diagnosticsFolder}/commands/{$command['file']}", shell_exec($command["command"]));
                Utils::sanitizeFile("{$diagnosticsFolder}/commands/{$command['file']}", array_merge($commandFilters, $customFilters));
            }
        }

        // Collect files
        if (array_key_exists("files", $config)) {
            mkdir("{$diagnosticsFolder}/files", 0755);
            foreach ($config["files"] as $fileobj) {
                $fileFilters = array();

                if (is_array($fileobj)) {
                    $fileglob    = $fileobj['file'];
                    $fileFilters = isset($fileobj['filters']) ? $fileobj['filters'] : array();
                    if ( ! is_array($fileFilters)) {
                        throw new \InvalidArgumentException("Invalid filters");
                    }
                } else {
                    $fileglob = $fileobj;
                }

                foreach (glob($fileglob) ?: array() as $file) {
                    $destFile = "{$diagnosticsFolder}/files{$file}";
                    if ( ! is_dir(dirname($destFile))) {
                        mkdir(dirname($destFile), 0755, true);
                    }
                    copy($file, $destFile);
                    Utils::sanitizeFile($destFile, array_merge($fileFilters, $customFilters));
                }
            }
        }

        exec("cd /tmp && zip -qmr " . escapeshellarg($diagnosticsFile) . " " . escapeshellarg("{$filename}/"));

        return $diagnosticsFile;
    }
}

<?php

namespace PluginDiagnostics;

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

class Utils
{
    public static function logmsg(string $message): void
    {
        if ( ! defined(__NAMESPACE__ . "\PLUGIN_NAME")) {
            throw new \RuntimeException("PLUGIN_NAME not defined");
        }

        $timestamp = date('Y/m/d H:i:s');
        $filename  = basename(is_string($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : "");
        file_put_contents("/var/log/" . PLUGIN_NAME . ".log", "{$timestamp} {$filename}: {$message}" . PHP_EOL, FILE_APPEND);
    }

    public static function send_file(string $url, string $file): string
    {
        if (empty($url)) {
            throw new \InvalidArgumentException("URL cannot be empty");
        }

        if ( ! file_exists($file)) {
            throw new \InvalidArgumentException("File does not exist: {$file}");
        }

        $token = self::download_url($url . '?connect');

        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);

        $headers = [
            'Authorization: Bearer ' . $token
        ];

        $curlFile = new \CURLFile($file, "application/zip");
        $body     = [
            'diagFile' => $curlFile,
        ];
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_POSTFIELDS, $body);
        curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($c, CURLOPT_USERAGENT, 'plugin-diagnostics/1.0.0');

        $out = curl_exec($c) ?: false;

        return strval($out);
    }

    public static function download_url(string $url): string
    {
        if (empty($url)) {
            throw new \InvalidArgumentException("URL cannot be empty");
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'plugin-diagnostics/1.0.0');
        $out = curl_exec($ch) ?: false;
        curl_close($ch);
        return strval($out);
    }

    private static function run(string $cmd): void
    {
        exec("timeout -s9 30 {$cmd}");
    }

    /**
     * @param array<string> $customFilters
     */
    public static function sanitizeFile(string $file, array $customFilters = array()): void
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
                    self::run("gzip -cd " . escapeshellarg("{$file}~") . " | sed -r '{$filter}' | gzip > " . escapeshellarg($file));
                    unlink("{$file}~");
                    break;
                default:
                    self::run("sed -ri '{$filter}' " . escapeshellarg($file) . " 2>/dev/null");
            }
        }
    }
}

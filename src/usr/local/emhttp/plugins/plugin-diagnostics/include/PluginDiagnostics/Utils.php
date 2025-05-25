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
    public static function make_option(bool|string $selected, string $value, string $text, string $extra = ""): string
    {
        if (is_string($selected)) {
            $selected = $selected === $value;
        }

        return "<option value='{$value}'" . ($selected ? " selected" : "") . (strlen($extra) ? " {$extra}" : "") . ">{$text}</option>";
    }

    public static function logmsg(string $message): void
    {
        if ( ! defined(__NAMESPACE__ . "\PLUGIN_NAME")) {
            throw new \RuntimeException("PLUGIN_NAME not defined");
        }

        $timestamp = date('Y/m/d H:i:s');
        $filename  = basename(is_string($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : "");
        file_put_contents("/var/log/" . PLUGIN_NAME . ".log", "{$timestamp} {$filename}: {$message}" . PHP_EOL, FILE_APPEND);
    }

    /**
    * @param array<mixed> $args
    */
    public static function run_task(string $functionName, array $args = array()): void
    {
        try {
            $reflectionMethod = new \ReflectionMethod($functionName);
            $reflectionMethod->invokeArgs(null, $args);
        } catch (\Throwable $e) {
            Utils::logmsg("Caught exception in {$functionName} : " . $e->getMessage());
        }
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
     * @param array<mixed> $customFilters
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
            if ( ! is_string($filter) || empty($filter)) {
                throw new \InvalidArgumentException("Invalid filter");
            }

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

    /**
    * @param array<mixed> $content
    */
    private static function send_usage(string $url, array $content): int
    {
        if (empty($url)) {
            throw new \InvalidArgumentException("URL cannot be empty");
        }

        $body = json_encode($content);

        if ( ! $body) {
            throw new \InvalidArgumentException("Failed to encode JSON");
        }

        $token = self::download_url($url . '?connect');

        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];

        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_POSTFIELDS, $body);
        curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($c, CURLOPT_USERAGENT, 'plugin-metrics/1.0.0');

        curl_exec($c);
        if ( ! curl_errno($c)) {
            $info = curl_getinfo($c);
            return $info['http_code'];
        }
        return -1;
    }

    public static function sendUsageData(): void
    {
        $endpoint = "https://plugin-usage.edacerton.win/";

        if ( ! defined(__NAMESPACE__ . "\PLUGIN_NAME")) {
            throw new \RuntimeException("PLUGIN_NAME not defined");
        }

        if ( ! defined(__NAMESPACE__ . "\PLUGIN_ROOT")) {
            throw new \RuntimeException("PLUGIN_ROOT not defined");
        }

        $usage_cfg = parse_ini_file("/boot/config/plugins/" . PLUGIN_NAME . "/usage.cfg", false, INI_SCANNER_RAW) ?: array();
        if ((($usage_cfg['usage_allowed'] ?? "no") != "yes") || (($usage_cfg['modal_displayed'] ?? "no") != "yes")) {
            Utils::logmsg("Usage data not allowed, skipping.");
            return;
        }

        $var     = parse_ini_file('/usr/local/emhttp/state/var.ini');
        $version = parse_ini_file(PLUGIN_ROOT . '/version.ini');

        if ( ! $var || ! $version) {
            Utils::logmsg("Could not retrieve system data, skipping usage data.");
            return;
        }

        $content = array(
            'clientId'       => hash("crc32b", $var['flashGUID']),
            'plugin'         => PLUGIN_NAME,
            'plugin_version' => $version['VERSION'],
            'unraid_version' => $var['version'],
        );

        $attempts = 0;
        $delay    = rand(0, 300);
        do {
            Utils::logmsg("Waiting for {$delay} seconds before sending usage data.");
            sleep($delay);
            $delay += 300;
            $attempts++;

            $result = self::send_usage($endpoint, $content);
            Utils::logmsg("Usage data sent.");
        } while (($result != '200') && ($attempts < 3));

        if ($result != '200') {
            Utils::logmsg("Error occurred while transmitting usage data.");
        }
    }
}

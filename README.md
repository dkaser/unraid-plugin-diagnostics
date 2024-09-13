# unraid-plugin-diagnostics

## Usage

Plugin diagnostics can be invoked either via CLI or WebGUI.

CLI: `plugin-diagnostics pluginFolder`
WebGUI: `http://unraid-server/plugins/plugin-diagnostics/download.php?plugin=pluginFolder`

## Configuration

A diagnostics.json file should be placed in the plugin folder within /usr/local/emhttp/plugins using the following structure. 

Title is required. Other unused sections may be omitted.

```
{
    "title": "Name to use on plugin diagnostics page",
    "commands": [
        {
            "command": "command to run",
            "file": "file-for-output.txt"
        }
    ],
    "files": [
        "/files/to/include"
    ],
    "system_diagnostics": true
}
```

# unraid-plugin-diagnostics

## Usage

Plugin diagnostics can be invoked either via CLI or WebGUI.

CLI: `plugin-diagnostics pluginFolder`
WebGUI: `http://unraid-server/plugins/plugin-diagnostics/download.php?plugin=pluginFolder`

## Configuration

A diagnostics.json file should be placed in the plugin folder within /usr/local/emhttp/plugins using the following structure. 

Title is required. Other unused sections may be omitted.

Optional substitution filters may be included for all output (except for system diagnostics), or for specific files/commands. Filters should use sed syntax.

```
{
    "title": "Name to use on plugin diagnostics page",
    "filters": [
        "s/Cats/Dogs/g",
        "s/lions/Tigers/gI"
    ]
    "commands": [
        {
            "command": "command to run",
            "file": "file-for-output.txt"
        },
        {
            "command": "command to run",
            "file": "file-for-output.txt",
            "filters": [
                "s/eagles/owls/gI",
                "s/sharks/whales/gI"
            ]
        }
    ],
    "files": [
        "/files/to/include",
        {
            "file": "/other/file"
        },
        {
            "file": "/file/with/custom/filters",
            "filters": [
                "s/hyenas/dodos/gI"
            ]
        }
    ],
    "system_diagnostics": true
}
```

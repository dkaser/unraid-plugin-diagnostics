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
    "system_diagnostics": true,
    "upload": "https://diagnostics-upload.developer.net/"
}
```

## Diagnostics Uploads

The `upload` option allows users to upload diagnostic packages to the provided server. When a user uploads diagnostics:

1. Plugin Diagnostics makes a GET request to `{upload}?connect`:
   - The response will be used as the authorization token for the POST request
2. Plugin Diagnostics makes a POST request to `{upload}`:
   - Authorization: `Bearer {response from GET request}`
   - User-Agent: Starts with `plugin-diagnostics`
   - Content-Type: `multipart/form-data`
   - Diagnostic File: `diagFile`
3. The diagnostics server should respond to the POST request with a unique identifier that the user can provide with their support request:
   `{"id": "someValueHere" }`

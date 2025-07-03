<?php

namespace EDACerton\PluginDiagnostics;

use EDACerton\PluginUtils\Translator;

if ( ! defined(__NAMESPACE__ . '\PLUGIN_ROOT') || ! defined(__NAMESPACE__ . '\PLUGIN_NAME')) {
    throw new \RuntimeException("Common file not loaded.");
}

$tr = $tr ?? new Translator(PLUGIN_ROOT);

$path    = ['/usr/local/emhttp/plugins/','/diagnostics.json'];
$plugins = array();

foreach (glob("{$path[0]}*{$path[1]}") ?: array() as $file) {
    $name = str_replace($path, "", $file);

    try {
        $data = (object) json_decode(file_get_contents($file) ?: "{}", false);
        $good = true;

        if ( ! isset($data->title)) {
            $good = false;
        }
        if (preg_match('/[^a-zA-Z0-9 ]/', $data->title) > 0) {
            $good = false;
        }

        if ($good) {
            $plugins[$name] = $data;
        }
    } finally {
    }
}

?>

<script src="/plugins/plugin-diagnostics/assets/sweetalert2.all.min.js"></script>

<table class="unraid t1">
    <thead>
        <tr>
            <td style="width: 25%"><?= $tr->tr("plugin"); ?></td>
            <td style="width: 25%">&nbsp;</td>
            <td style="width: 25%">&nbsp;</td>
            <td><?= $tr->tr("id"); ?></td>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($plugins as $key => $value) { ?>
            <tr>
                <td><?= $value->title; ?></td>
                <td><input type='button' value='<?= $tr->tr("download"); ?>' onclick="window.open('/plugins/plugin-diagnostics/download.php?plugin=<?= $key; ?>','_blank')" /></td>
                <td><input type='button' value='<?= $tr->tr("upload"); ?>' onclick='uploadDiagnostics("<?= $key; ?>", "<?= isset($value->upload) ? htmlspecialchars($value->upload) : ""; ?>")' <?= isset($value->upload) ? "" : "disabled"; ?> /></td>
                <td><div id="status_<?= $key; ?>"></div></td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<ul>
    <li><b><?= $tr->tr("download"); ?></b>: <?= $tr->tr("download_desc"); ?></li>
    <li><b><?= $tr->tr("upload"); ?></b>: <?= $tr->tr("upload_desc"); ?></li>
</ul>

<script>
async function uploadDiagnostics(plugin, url) {
    const promptResult = await Swal.fire({
                title: '<?= $tr->tr("send_diagnostics"); ?>',
                html: `<?= $tr->tr("upload_prompt"); ?> <br><br>${url}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<?= $tr->tr("send_diagnostics"); ?>',
                cancelButtonText: '<?= $tr->tr("cancel"); ?>',
            });

    if (!promptResult.isConfirmed) {
        console.log("User cancelled the upload.");
        return;
    }

    $('div.spinner.fixed').show('fast');

    try {
        var res = await $.post('/plugins/plugin-diagnostics/upload.php', {plugin: plugin});
        const response = JSON.parse(res);

        $(`div[id="status_${plugin}"]`).html(response.id);
        $('div.spinner.fixed').hide('fast');
        await Swal.fire({
            title: '<?= $tr->tr("upload_success"); ?>',
            text: `<?= $tr->tr("diag_success"); ?> ${response.id}`,
            icon: 'success',
            confirmButtonText: '<?= $tr->tr("close"); ?>'
        });
    } catch (error) {
        $('div.spinner.fixed').hide('fast');
        await Swal.fire({
            title: '<?= $tr->tr("upload_error"); ?>',
            text: '<?= $tr->tr("upload_error_msg"); ?>',
            icon: 'error',
            confirmButtonText: '<?= $tr->tr("close"); ?>'
        });
        console.error("Upload error:", error);
    }
}
</script>

<script>

</script>



</div>
<?php

namespace PluginDiagnostics;

$tr = $tr ?? new Translator();

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
                <td><input type='button' value='<?= $tr->tr("upload"); ?>' onclick='uploadDiagnostics("<?= $key; ?>")' <?= isset($value->upload) ? "" : "disabled"; ?> /></td>
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
async function uploadDiagnostics(plugin) {
    $('div.spinner.fixed').show('fast');

    var res = await $.post('/plugins/plugin-diagnostics/upload.php',{plugin: plugin});

    const response = JSON.parse(res);

    if (typeof response == "object") {
        $(`div[id="status_${plugin}"]`).html(response.id);
    } else {
        $(`div[id="status_${plugin}"]`).html(res);
    }
    $('div.spinner.fixed').hide('fast');
}
</script>
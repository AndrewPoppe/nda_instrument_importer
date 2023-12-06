<?php

namespace YaleREDCap\NDAImporter;

/** @var NDAImporter $module */

$module->initializeJavascriptModuleObject();
$project_id       = $module->getProjectId();
$projectStatus    = $module->framework->getProjectStatus($project_id);
$projectWriteable = $module->isProjectWriteable($project_id);
?>

<!doctype html>
<html lang="en">

<head>
    <link rel="stylesheet" type="text/css"
        href="https://cdn.datatables.net/v/dt/dt-1.10.24/b-1.7.0/b-colvis-1.7.0/date-1.0.3/sb-1.0.1/sp-1.2.2/sl-1.3.3/datatables.min.css" />
    <link rel="stylesheet" type="text/css" href="<?= $module->getUrl("src/css/project.css"); ?>">

    <script type="text/javascript"
        src="https://cdn.datatables.net/v/dt/dt-1.10.24/b-1.7.0/b-colvis-1.7.0/date-1.0.3/sb-1.0.1/sp-1.2.2/sl-1.3.3/datatables.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script src="<?= $module->getUrl("src/js/project.js"); ?>" defer></script>
</head>

<body>
    <div class="pagecontainer w-100">
        <div class="infocontainer">
            <h4>
                <?= $module->getModuleName() ?>
                </h3>
                <p>Use this tool to import data dictionaries from the <strong><a title="NIMH Data Archive"
                            href="https://nda.nih.gov/data_dictionary.html" target="_blank"
                            rel="noopener noreferrer">NIMH Data Archive</a></strong> directly into this REDCap project.
                </p>
                <p id="infotext" onclick="(function() {
                    Swal.fire({
                        icon: 'info',
                        iconColor: '#17a2b8',
                        title: '<?= $module->getModuleName() ?>',
                        confirmButtonText: 'Got it!',
                        confirmButtonColor: '#17a2b8',
                        html: `Find data collection instruments in the table below using the provided search/filter tools.
                               <br>Select the instruments you would like to add to this REDCap project using the checkboxes.
                               <br>Multiple instruments can be added by holding <code>CTRL</code> while clicking (<code>CMD</code> on Apple machines).
                               <br><br>Click the <strong>Add selected forms</strong> button once you have made your selections.`
                    })})();">Click here for more information</p>
                <br />
                <?php if ( $projectStatus == 'PROD' && !$projectWriteable ) { ?>
                    <div class="w-75 alert alert-danger" role="alert">
                        <strong>Warning!</strong> This project is in production mode and is not writeable. You will not be
                        able to add forms to this project.
                    </div>
                <?php } elseif ( $projectStatus == 'PROD' && $projectWriteable ) { ?>
                    <div class="w-75 alert alert-warning" role="alert">
                        <strong>Attention:</strong> Because this project is in draft mode, the forms you add will be added
                        as a draft, and any changes will need to be submitted for review on the Online Designer or Data
                        Dictionary pages.
                    </div>
                <?php } ?>
                <hr>
        </div>
        <div id="ndaSearch" class="dataTableParent">
            <table id="ndaSearchTable" class="dataTable w-100">
                <thead>
                    <tr>
                        <th>Add to Project?</th>
                        <th>Short Name</th>
                        <th>Title</th>
                        <th>Sources</th>
                        <th>Categories</th>
                        <th>Data Type</th>
                        <th>Status</th>
                        <th>Submission Status</th>
                        <th>Published Date</th>
                        <th>Modified Date</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
            <button id="<?= $projectWriteable == 'DEV' ? 'addFormsButton' : '' ?>" class="btn btn-large btn-primaryrc"
                disabled>Add selected forms</button>
        </div>
    </div>
    <script>
        const nda_importer = <?= $module->getJavascriptModuleObjectName() ?>;
        nda_importer.converterPath = "<?= $module->getUrl('src/php/converter.php') ?>";
        nda_importer.importDictionaryPath = "<?= $module->getUrl('src/php/importDictionary.php') ?>";
    </script>
</body>
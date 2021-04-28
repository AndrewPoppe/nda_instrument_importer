<?php


$dictionary = json_decode($_POST['dictionary'], true);
$project = $module->getProject();
$project_id = $project->getProjectId();


$filename = tempnam(APP_PATH_TEMP, "Dictionary_");
$success = write_file($dictionary, $filename);

if (!$success) {
    throw_error("Error processing data dictionary.");
}

try {
    $module->importDataDictionary($project_id, $filename);
    exit(json_encode(["success"=>true]));
}
catch (exception $e) {
    throw_error($e);
}



/**
 * Throw an error with the provided array.
 * 
 * @param string $message Describe the error
 * @param int $code Error code for the browser
 * 
 * @return void
 */
function throw_error(string $message, int $code = 500) {
    http_response_code($code);
    exit($message);
}


function write_file(array $data, string $filename) {
    $outstream = fopen($filename, 'w');
    $success = TRUE;
    if ($outstream === FALSE) {
        return FALSE;
    }
    try {
        foreach($data as $row) {
            fputcsv($outstream, $row, ',', '"');
        }
    }
    catch (exception $e) {
        $success = FALSE;
    }
    finally {
        fclose($outstream);
        return $success;
    }
}
<?php


$dictionary = json_decode($_POST['dictionary'], true);
$project = $module->getProject();
$project_id = $project->getProjectId();


$filename = tempnam(APP_PATH_TEMP, "Dictionary_").'.csv';
$success = write_file($dictionary, $filename);

if (!$success) {
    throw_error("Error processing data dictionary.");
}

try {
    importDataDictionary($filename);
    exit(json_encode(["success"=>true]));
}
catch (exception $e) {
    throw_error($e);
}


function importDataDictionary($path) {
    $dictionary_array = \Design::excel_to_array($path);
    
    //Return warnings and errors from file (and fix any correctable errors)
    list ($errors_array, $warnings_array, $dictionary_array) = \MetaData::error_checking($dictionary_array);

    if (count($errors_array) > 0) {
        throw new Exception("Error: supplied dictionary is invalid.".implode(', ', $errors_array));
    }
    
    // Save data dictionary in metadata table
    $sql_errors = saveMetadataArray($dictionary_array);

    // 
    if (count($sql_errors) > 0) {
        throw new Exception("Error importing Data Dictionary: ".$path);
    }
}


// Save metadata to the provided project
function saveMetadataArray($dictionary_array) {
    try {
        // disable for production
        $Proj = new \Project();
        if($Proj->project['status'] > 0)
        {
            throw new Exception("This method is not available for projects in Production status.");
        }

        // Create a data dictionary snapshot of the *current* metadata and 
        // store the file in the edocs table
        \MetaData::createDataDictionarySnapshot();

        // Save metadata array
        $errors = \MetaData::save_metadata($dictionary_array);
    }
    catch (exception $e) {
        throw $e;
    }
    finally {
        return $errors;
    }
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
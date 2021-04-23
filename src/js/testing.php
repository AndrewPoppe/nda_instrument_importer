<?php
$payload = json_decode($_POST['payload']);

// Fields to test csv data against
$matchFields = [
	"ElementName", 
	"DataType", 
	"Required", 
	"ElementDescription", 
	"ValueRange", 
	"Notes"
];

exit(json_encode(array(
    "type" => "text/csv",
    "data" => convert_all_in_one($payload),
    "file" => "test.csv"
)));

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

/**
 * Convert an Array into a CSV string
 * 
 * This function works with a single row of data.
 * By default it will not include a header row. If you want a header, it will 
 * grab the values from the array keys of the $row parameter. 
 *
 * @param array $row The Array to formatted as CSV.
 * @param array $header The array of explicitly defined header values.
 * @param bool $doHeader Whether to write a header row.
 * 
 * @return string The formatted CSV string.
 */
function array_to_csv(array $row, array $header = NULL, bool $doHeader = FALSE) {
    $outstream = fopen("php://temp", 'r+');
    $header = $header ?? array_keys($arr[0]);
    $doHeader && fputcsv($outstream, $header, ',', '"');
    fputcsv($outstream, $row, ',', '"');
    rewind($outstream);
    $csv = fgets($outstream);
    fclose($outstream);
    return $csv;
}
 
/**
 * Convert a CSV String into an associative array with headers as keys.
 *
 * @param string $str The CSV formatted string.
 * @return array The CSV string as Array.
 */
function csv_to_array(string $str) {
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $str);
    rewind($stream);
    $header = fgetcsv($stream);
    $result = array();
    $nfields = count($header);
    while (($row = fgetcsv($stream)) !== FALSE) {
        $assoc_row = array();
        for ($i = 0; $i < $nfields; $i++) {
            $assoc_row[$header[$i]] = $row[$i];
        }
        array_push($result, $assoc_row);
    }
    return $result;
}

function get_header(string $str) {
    $data = str_getcsv($str, "\n");
    return str_getcsv($data[0], ",");
}

function array_duplicates(array $arr) {
    return array_unique(array_diff_assoc($arr,array_unique($arr)));
}


/**
 * Verify input format/fields
 * 
 * @param array $csvArr Parsed array of csv values
 * @param array $fields Array of field names to test against
 * 
 * @return bool Whether or not input is valid
 */
function inputIsValid(array $csvArr, array $fields) {
    foreach ($fields as $field) {
        foreach ($csvArr as $row) {
            if (!in_array($field, array_keys($row))) {
                return false;
            }
        }
    }
    return true;
}


/**
 * Create a single data dictionary.
 * 
 * This does not create a header row so that combining multiple
 * files is easier.
 * 
 * @param array $csvArr Array of CSV values.
 * @param string $form Name of the form
 * @param string $duplicateAction What to do with duplicates:
 *                  ignore: keep duplicates in the data
 *                  remove: remove any field that already exists in the fieldArray
 * 
 * @return array Associative array representing data dictionary
 */
function createDataDictionary(array $csvArr, string $form, string $duplicateAction = "") {
    // remove duplicates if necessary
    static $matchFields = [];
    if ($duplicateAction === "remove") {
        $csvArr = array_filter($csvArr, function ($field) use (&$matchFields) {
            $result = !in_array($field["ElementName"], $fieldArray) && !in_array($field["ElementName"], $matchFields);
            $result && array_push($matchFields, $field["ElementName"]);
            return $result;
        });
    }

    // Create the Data Dictionary
    $result = array_map(function($field) {
        $note = preg_replace('/[\n\r]/', '', $field["Notes"]);

        return array (
            "Notes" => $note
        );
    }, $csvArr);

    // Return result
    return $result;
}



function convert_all_in_one($fileObject) {
    global $matchFields;
    $fieldArray = [];
    $duplicateAction = $fileObject->duplicateAction ?? "";
    $header = get_header($fileObject->fileArray[0]->data);
    $csvString = array_to_csv($header);

    foreach($fileObject->fileArray as $fileData) {
        $csvDat = csv_to_array($fileData->data);

        if (!inputIsValid($csvDat, $matchFields)) {
            throw_error('Error: Input file is not valid: ' . $fileData->formName);
        }

        foreach ($csvDat as $field) {
            array_push($fieldArray, $field["ElementName"]);
        }

        $result = createDataDictionary($csvDat, $fileData->formName, $duplicateAction);
        
        foreach ($result as $row) {
            $csvString .= array_to_csv($row);
        }
    }

    $duplicates = array_duplicates($fieldArray);
    if (count($duplicates) && !$duplicateAction) {
        throw_error('<strong>The following field names were duplicated in your'
        .' file(s)</strong>:<br>' . implode('<br>', $duplicates), 501);
    }
    return $csvString;
}

?>
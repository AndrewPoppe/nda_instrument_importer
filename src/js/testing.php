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

$header = [
	'variable'      => 'Variable / Field Name',
	'form'          => 'Form Name',
	'header'        => 'Section Header',
	'type'          => 'Field Type',
	'label'         => 'Field Label',
	'choices'       => 'Choices, Calculations, OR Slider Labels',
	'note'          => 'Field Note',
	'validation'    => 'Text Validation Type OR Show Slider Number',
	'min'           => 'Text Validation Min',
	'max'           => 'Text Validation Max',
	'id'            => 'Identifier?',
	'branching'     => 'Branching Logic (Show field only if...)',
	'required'      => 'Required Field?',
	'alignment'     => 'Custom Alignment',
	'question_num'  => 'Question Number (surveys only)',
	'matrix_name'   => 'Matrix Group Name',
	'matrix_rank'   => 'Matrix Ranking?',
	'annotation'    => 'Field Annotation',
];

/*$pvr                = parseValueRange("0::3");
$parsed             = parseNote("Positive Diagnosis = 3; Intermediate Diagnosis = 2; Negative Diagnosis = 1; 0 = Inadequate Information", $pvr);
        
exit(json_encode($parsed));
*/

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
 * @param array $header An associative array with header id as key and header title as value
 * @param bool $getHeader Whether to return the header row instead of data.
 * 
 * @return string The formatted CSV string.
 */
/*function array_to_csv(array $data, bool $getHeader = FALSE) {
    $outstream = fopen("php://temp", 'r+');
    $data = $getHeader ? array_values($data) : $data;
    fputcsv($outstream, $data, ',', '"');
    rewind($outstream);
    $csv = fgets($outstream);
    fclose($outstream);
    return $csv;
}*/
function array_to_csv(array $data) {
    $outstream = fopen("php://temp", 'r+');
    fputcsv($outstream, $data, ',', '"');
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


function getRanges(array $arr) {
    return array_map(function($element1) {
        $bounds = explode('::', $element1);
        $bounds = array_map(function($bound) {
            return trim(preg_replace("/NDAR.*/", "", $bound));
        }, $bounds);
        if (count($bounds) === 2 && is_numeric($bounds[0]) && is_numeric($bounds[1])){
            $val1 = intval($bounds[0]);
            $val2 = intval($bounds[1]);
            return range(min($val1, $val2), max($val1, $val2));
        } else {
            return $bounds;
        }
    }, $arr);
}


function reduceRanges(array $arr) {
    return array_reduce($arr, function($res, $item) {
        $item = array_map('strval', $item);
        return array_merge($res, $item);
    }, []);
}


function parseValueRange(string $vr) {
    $arr = explode(';', $vr);
    $ranges = getRanges($arr);
    $reducedRanges = reduceRanges($ranges);
    return array_unique($reducedRanges);
}


function clean($val) {
    return preg_replace('/^[,;\'"\s]*|[,;\'"\s]*$/', '', $val);
}

function reMatch(string $note, string $parsedVrString, string $re) {
    preg_match_all($re, $note, $matches, PREG_SET_ORDER);
    return array_map(function($match) {
        return array(
            "key" => clean($match["key"]),
            "value" => clean($match["val"])
        );
    }, $matches);
}

function delimMatch(string $note, array $parsedVr, string $delimiter) {
    $split = preg_split("/[${delimiter}]/", $note);
    $result = array_filter($split, function($el){
        return strpos($el, '=') !== FALSE;
    });
    $result = array_map(function($whole) use ($parsedVr) {
        $parts = explode('=', $whole);
        $parts = array_map('trim', $parts);
        if (in_array($parts[1], $parsedVr)) {
            $parts = array_reverse($parts);
        }
        if (in_array($parts[0], $parsedVr)) {
            return array(
                "key" => clean($parts[0]),
                "value" => clean($parts[1])
            );
        }
        return NULL;
    }, $result);

    return array_filter($result, function($piece) {
        return !is_null($piece);
    });
}


function chooseMatches(array $matches, int $eqs, array $parsedVr) {
    $pvrl = count($parsedVr);
    if ($eqs === $pvrl) {
        $matches = array_filter($matches, function($match) use ($eqs) {
            return (count($match) === intval($eqs));
        });
        $matches = reset($matches);
        
    } else {
        usort($matches, function ($a, $b) {
            return count($b) - count($a);
        });
        
        $matches = reset($matches);
        
        if (count($matches) > 0) {

            // If we have matches with duplicate keys, merge them
            $matches = array_reduce($matches, function ($acc, $el) {
                $duplicate = FALSE;
                foreach ($acc as $i=>$acc_el) {
                    if ($acc_el["key"] === $el["key"]) {
                        $acc[$i]["value"] .= "; " . $el["value"];
                        $duplicate = TRUE;
                    }
                }
                if (!$duplicate) array_push($acc, $el);
                return $acc;
            }, []);

            // If we have more values without matches, create them
            foreach ($matches as $match) {
                unset($parsedVr[$match["key"]]);
            }
            foreach ($parsedVr as $val) {
                $key = preg_replace('/[^0-9A-Za-z._\-]/', '', $val);
                if ($key !== "") {
                    array_push($matches, array(
                        "key" => $key,
                        "value" => $val
                    ));
                }
            }
        } else {
            foreach ($parsedVr as $i=>$val) {
                $key = (string) (is_numeric($val) ? (int)$val : intval($i)+1);
                array_push($matches, array(
                    "key" => $key,
                    "value" => $val
                ));
            }
        }

    }
    $finalMatches = "";
    $fieldNote = $note;
    if ($matches) {
        $finalMatches = array_map(function($match) {
            return $match["key"] . ", " . $match["value"];
        }, $matches);
        natcasesort($finalMatches);
        $finalMatches = implode(' | ', $finalMatches);
        $fieldNote = array_map(function($match) {
            return $match["key"] . " = " . $match["value"];
        }, $matches);
        natcasesort($fieldNote);
        $fieldNote = implode('; ', $fieldNote);
    }

    return array (
        "matches" => $finalMatches,
        "fieldNote" => $fieldNote
    );
}


function parseNote(string $note, array $parsedVr = []) {
    $parsedVr = array_map(function($el) {
        if ($el === "") return "";
        $result = preg_replace('/[^0-9A-Za-z._\-]/', '', trim($el));
        if ($result === "") $result = "custom".ord(trim($el));
        return $result;
    }, $parsedVr);
    $parsedVrString = array_map(function($el) {
        return addcslashes($el, '\\');
    }, $parsedVr);
    $parsedVrString = implode('|', $parsedVrString);
    
    if ($parsedVrString === "") return "";

    $matchesFore    = reMatch($note, $parsedVrString, "/(?<key>${parsedVrString})\s*=(?<val>(?:(?!(${parsedVrString})\s*=).)*)/");
    $matchesBack    = reMatch($note, $parsedVrString, "/(?<val>(?:(?!\s*=\s*(${parsedVrString})).)*)\s*=\s*(?<key>${parsedVrString})/");
    $matches_sc     = delimMatch($note, $parsedVr, ';');
    $matches_c      = delimMatch($note, $parsedVr, ',');
    $matchs_scc     = delimMatch($note, $parsedVr, ';,');
    $allMatches     = [$matchesFore, $matchesBack, $matches_sc, $matches_c, $matchs_scc];
    $eqs            = substr_count($note, '=');

    $matches = chooseMatches($allMatches, $eqs, $parsedVr);

    return $matches;
}


function parseBounds(string $valueRange, $parsedNote) {
    $bounds = explode('::', $valueRange);
    $bounds = array_map(function($e) {
        return trim(preg_replace('/NDAR.*/', '', $bounds));
    }, $bounds);
    if (!$parsedNote && count($bounds) == 2 && is_numeric($bounds[0]) && is_numeric($bounds[1])) {
        sort($bounds);
        return $bounds;
    } else {
        return [NULL, NULL];
    }
}


function getFieldType(string $parsedNote) {
    return $parsedNote ? "dropdown" : "text";
}


function getTextValidation(string $dataType, string $fieldType) {
    if ($fieldType !== "text") return NULL;
    switch ($dataType) {
        case "Date":
        return "date_mdy";
            break;
        case "Integer":
            return "integer";
            break;
        case "Float":
            return "number";
            break;
        default:
            return NULL;
	}
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
            $result = !in_array($field["ElementName"], $matchFields);
            $result && array_push($matchFields, $field["ElementName"]);
            return $result;
        });
    }

    // Create the Data Dictionary
    $result = array_map(function($field) use ($form) {
        $note               = preg_replace('/[\n\r]/', '', $field["Notes"]);
        $pvr                = parseValueRange($field["ValueRange"]);
        $parsed             = parseNote($note, $pvr);
        $parsedNote         = $parsed["matches"];
        $fieldNote          = $parsed["fieldNote"];
        $bounds             = parseBounds($field["ValueRange"], $parsedNote);
        $field_type         = getFieldType($parsedNote);
        $text_validation    = getTextValidation($field["DataType"], $field_type);
        
        return array (
            "variable"      => $field["ElementName"],
            "form"          => $form,
            "header"        => NULL,
            "type"          => $field_type,
            "label"         => $field["ElementDescription"],
            "choices"       => $parsedNote,
            "note"          => $fieldNote,
            "validation"    => $text_validation,
            "min"           => $bounds[0],
            "max"           => $bounds[1],
            "id"            => NULL,
            "branching"     => NULL,
            "required"      => $field["Required"] === "Required" ? "y" : NULL,
            "alignment"     => NULL,
            "question_num"  => NULL,
            "matrix_name"   => NULL,
            "matrix_rank"   => NULL,
            "annotation"    => $note
        );
    }, $csvArr);

    // Return result
    return $result;
}



function convert_all_in_one($fileObject) {
    global $matchFields, $header;
    $fieldArray = [];
    $duplicateAction = $fileObject->duplicateAction ?? "";
    //$header = get_header($fileObject->fileArray[0]->data);
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
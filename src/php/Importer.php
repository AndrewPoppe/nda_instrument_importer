<?php

namespace YaleREDCap\NDAImporter;

class Importer
{

    private NDAImporter $module;
    private array $dictionary;
    private \ExternalModules\Project $project;
    private $project_id;

    public function __construct(NDAImporter $module, array $dictionary)
    {
        $this->module     = $module;
        $this->dictionary = $dictionary;
        $this->project    = $module->framework->getProject();
        $this->project_id = $this->project->getProjectId();
    }


    private function saveDictionaryCsv()
    {
        $filename = $this->module->framework->createTempFile();

        $success = self::write_file($this->dictionary, $filename);

        return [ 'success' => $success, 'filename' => $filename ];
    }

    function importDataDictionary($project_id, $path, $delimiter = ",")
    {
        if ( !file_exists($path) ) {
            throw new \Exception("File not found for data dictionary import: $path");
        }

        $dictionary_array = \Design::excel_to_array($path, $delimiter);

        // Save data dictionary in metadata table
        $this->saveMetadataCSV($dictionary_array, $project_id);
    }

    public function import()
    {
        $result = $this->saveDictionaryCsv();
        if ( !$result['success'] || empty($result['filename']) || !file_exists($result['filename']) ) {
            return [ "success" => false, "error" => "Error saving dictionary to CSV." ];
        }
        try {
            $this->importDataDictionary($this->project_id, $result['filename']);
            return [ "success" => true ];
        } catch ( \Throwable $e ) {
            return [ "success" => false, "error" => $e->getMessage() ];
        }
    }

    private function saveMetadataCSV($metadata, $pid)
    {
        if ( !is_array($metadata) || empty($metadata) ) {
            throw new \Exception('The metadata specified is not valid.');
        }

        $Proj                                 = $this->project;
        $originalProject                      = $Proj;
        $Proj                                 = \ExternalModules\ExternalModules::getREDCapProjectObject($pid, false);
        list( $errors, $warnings, $dd_array ) = \MetaData::error_checking($metadata);
        $Proj                                 = $originalProject;

        if ( !empty($errors) ) {
            throw new \Exception("The following errors were encountered while trying to save project metadata: " . $this->module->framework->escape(implode("\n", $errors)));
        }

        // Create a data dictionary snapshot, like data_dictionary_upload.php does
        if ( !\MetaData::createDataDictionarySnapshot($pid) ) {
            throw new \Exception("Error calling createDataDictionarySnapshot() for project $pid: " . db_error());
        }
        $errors = \MetaData::save_metadata($dd_array, false, false, $pid);

        if ( count($errors) > 0 ) {
            throw new \Exception("Failed to save metadata due to the following errors: " . $this->module->framework->escape(implode("\n", $errors)));
        }
    }


    private static function write_file(array $data, string $filename)
    {
        $outstream = fopen($filename, 'w');
        $success   = TRUE;
        if ( $outstream === FALSE ) {
            return FALSE;
        }
        try {
            foreach ( $data as $row ) {
                fputcsv($outstream, $row, ',', '"');
            }
        } catch ( \Throwable $e ) {
            $success = FALSE;
        } finally {
            fclose($outstream);
            return $success;
        }
    }
}
?>
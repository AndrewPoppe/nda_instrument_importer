<?php

namespace YaleREDCap\NDAImporter;

use ExternalModules\AbstractExternalModule;
use ExternalModules\Framework;

require_once 'src/php/Converter.php';
require_once 'src/php/Importer.php';

/**
 * @property Framework $framework
 * @see Framework
 */
class NDAImporter extends AbstractExternalModule
{
    public function redcap_module_ajax($action, $payload, $projectId, $record, $instrument, $eventId, $repeatInstance, $surveyHash, $responseId, $surveyQueueHash, $page, $pageFull, $userId, $groupId)
    {
        try {
            if ( $action === 'convert' ) {
                $projectWriteable = $this->isProjectWriteable($projectId);
                if ( !$projectWriteable ) {
                    return [ "success" => false, "error" => "This project is not writeable." ];
                }
                $converter = new Converter($this, $payload);
                return $converter->convert();
            } else if ( $action === 'import' ) {
                $projectWriteable = $this->isProjectWriteable($projectId);
                if ( !$projectWriteable ) {
                    return [ "success" => false, "error" => "This project is not writeable." ];
                }
                $importer = new Importer($this, $payload);
                return $importer->import();
            } else if ( $action === 'projectStatus' ) {
                return $this->framework->getProjectStatus($projectId);
            }
        } catch (\Throwable $e) {
            return [ "success" => false, "error" => $e->getMessage() ];
        }
    }

    public function redcap_module_link_check_display($project_id, $link)
    {
        $user = $this->framework->getUser();
        if ( !$user->isSuperUser() && !$user->hasDesignRights($project_id) ) {
            return false;
        }
        return $link;
    }

    public function isProjectWriteable($project_id)
    {
        $status = $this->framework->getProjectStatus($project_id);
        if ( $status == 'DEV' ) {
            return true;
        }
        $sql    = "SELECT * FROM redcap_projects WHERE project_id = ? AND status = 1 AND draft_mode = 1";
        $result = $this->framework->query($sql, [ $project_id ]);
        return $result->num_rows > 0;
    }
}
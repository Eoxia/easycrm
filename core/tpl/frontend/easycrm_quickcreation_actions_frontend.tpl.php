<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/tpl/frontend/easycrm_quickcreation_actions_frontend.tpl.php
 * \ingroup easycrm
 * \brief   Template page for quick creation action frontend
 */

/**
 * The following vars must be defined :
 * Global     : $conf, $langs, $user
 * Parameters : $action, $subaction
 * Objects    : $project, $task
 * Variable   : $error, $permissionToAddProject
 */

// Protection to avoid direct call of template
if (!$permissionToAddProject) {
    exit;
}

if ($action == 'add_img') {
    $data = json_decode(file_get_contents('php://input'), true);

    $encodedImage = explode(',', $data['img'])[1];
    $decodedImage = base64_decode($encodedImage);
    $uploadDir    = $conf->easycrm->multidir_output[$conf->entity] . '/project/tmp/0/project_photos/';
    if (!dol_is_dir($uploadDir)) {
        dol_mkdir($uploadDir);
    }
    file_put_contents($uploadDir . dol_print_date(dol_now(), 'dayhourlog') . '_img.jpg', $decodedImage);
}

if ($action == 'add') {
    $numberingModules = [
        'project'      => $conf->global->PROJECT_ADDON,
        'project/task' => $conf->global->PROJECT_TASK_ADDON,
    ];

    list ($refProjectMod, $refTaskMod) = saturne_require_objects_mod($numberingModules);

    $project->ref         = $refProjectMod->getNextValue(null, $project);
    $project->title       = GETPOST('title');
    $project->description = GETPOST('description', 'restricthtml');
    $project->opp_percent = GETPOST('opp_percent','int');

    switch ($project->opp_percent) {
        case 20 < 40:
            $project->opp_status = 2;
            break;
        case 40 < 60:
            $project->opp_status = 3;
            break;
        case 60 < 100:
            $project->opp_status = 4;
            break;
        case 100:
            $project->opp_status = 5;
            break;
        default:
            $project->opp_status = 1;
            break;
    }

    $project->opp_amount        = price2num(GETPOST('opp_amount', 'int'));
    $project->date_c            = dol_now();
    $project->date_start        = dol_now();
    $project->statut            = 1;
    $project->usage_opportunity = 1;
    $project->usage_task        = 1;

    $projectID = $project->create($user);
    if ($projectID > 0) {
//        // Category association
//        $categories = GETPOST('categories_project', 'array');
//        if (count($categories) > 0) {
//            $result = $project->setCategories($categories);
//            if ($result < 0) {
//                setEventMessages($project->error, $project->errors, 'errors');
//                $error++;
//            }
//        }

        $pathToProjectImg = $conf->project->multidir_output[$conf->entity] . '/' . $project->ref;
        $pathToTmpImg     = $conf->easycrm->multidir_output[$conf->entity] . '/project/tmp/0/project_photos/';
        $imgList          = dol_dir_list($pathToTmpImg, 'files');
        if (!empty($imgList)) {
            foreach ($imgList as $img) {
                if (!dol_is_dir($pathToProjectImg)) {
                    dol_mkdir($pathToProjectImg);
                }

                $fullPath = $pathToProjectImg . '/' . $img['name'];
                dol_copy($img['fullname'], $fullPath);

                vignette($fullPath, $conf->global->EASYCRM_MEDIA_MAX_WIDTH_MINI, $conf->global->EASYCRM_MEDIA_MAX_HEIGHT_MINI, '_mini');
                vignette($fullPath, $conf->global->EASYCRM_MEDIA_MAX_WIDTH_SMALL, $conf->global->EASYCRM_MEDIA_MAX_HEIGHT_SMALL);
                vignette($fullPath, $conf->global->EASYCRM_MEDIA_MAX_WIDTH_MEDIUM, $conf->global->EASYCRM_MEDIA_MAX_HEIGHT_MEDIUM, '_medium');
                vignette($fullPath, $conf->global->EASYCRM_MEDIA_MAX_WIDTH_LARGE, $conf->global->EASYCRM_MEDIA_MAX_HEIGHT_LARGE, '_large');
                unlink($img['fullname']);
            }
        }

        $project->add_contact($user->id, 'PROJECTLEADER', 'internal');

        $task->fk_project = $projectID;
        $task->ref        = $refTaskMod->getNextValue(null, $task);
        $task->label      = (!empty($conf->global->EASYCRM_TASK_LABEL_VALUE) ? $conf->global->EASYCRM_TASK_LABEL_VALUE : $langs->trans('CommercialFollowUp')) . ' - ' . $project->title;
        $task->date_c     = dol_now();

        $taskID = $task->create($user);
        if ($taskID > 0) {
            $task->add_contact($user->id, 'TASKEXECUTIVE', 'internal');
            $project->array_options['commtask'] = $taskID;
            $project->updateExtraField('commtask');
        } else {
            setEventMessages($task->error, $task->errors, 'errors');
            $error++;
        }
    } else {
        $langs->load('errors');
        setEventMessages($project->error, $project->errors, 'errors');
        $error++;
    }

    if (!$error) {
        setEventMessage($langs->transnoentities('QuickCreationFrontendSuccess', $project->ref));
        header('Location: ' . $_SERVER["PHP_SELF"]);
        exit;
    } else {
        $action = '';
    }
}

if ($subaction == 'unlinkFile') {
    $data = json_decode(file_get_contents('php://input'), true);

    $filePath = $data['filepath'];
    $fileName = $data['filename'];
    $fullPath = $filePath . '/' . $fileName;

    if (is_file($fullPath)) {
        unlink($fullPath);
    }

    $sizesArray = ['mini', 'small', 'medium', 'large'];
    foreach($sizesArray as $size) {
        $thumbName = $filePath . '/thumbs/' . saturne_get_thumb_name($fileName, $size);
        if (is_file($thumbName)) {
            unlink($thumbName);
        }
    }
}

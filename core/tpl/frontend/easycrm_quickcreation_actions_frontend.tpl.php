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
 * Parameters : $action
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
    file_put_contents($uploadDir . generate_random_id(8) . '_img.png', $decodedImage);

    vignette($uploadDir . generate_random_id(8) . '_img.png', $conf->global->EASYCRM_MEDIA_MAX_WIDTH_MINI, $conf->global->EASYCRM_MEDIA_MAX_HEIGHT_MINI, '_mini');
    vignette($uploadDir . generate_random_id(8) . '_img.png', $conf->global->EASYCRM_MEDIA_MAX_WIDTH_SMALL, $conf->global->EASYCRM_MEDIA_MAX_HEIGHT_SMALL);
    vignette($uploadDir . generate_random_id(8) . '_img.png', $conf->global->EASYCRM_MEDIA_MAX_WIDTH_MEDIUM, $conf->global->EASYCRM_MEDIA_MAX_HEIGHT_MEDIUM, '_medium');
    vignette($uploadDir . generate_random_id(8) . '_img.png', $conf->global->EASYCRM_MEDIA_MAX_WIDTH_LARGE, $conf->global->EASYCRM_MEDIA_MAX_HEIGHT_LARGE, '_large');
}

if ($action == 'add') {
    $numberingModules = [
        'project'      => $conf->global->PROJECT_ADDON,
        'project/task' => $conf->global->PROJECT_TASK_ADDON,
    ];

    list ($refProjectMod, $refTaskMod) = saturne_require_objects_mod($numberingModules);

    $project->ref               = $refProjectMod->getNextValue(null, $project);
    $project->title             = GETPOST('title');
    $project->opp_status        = getDolGlobalInt('EASYCRM_PROJECT_OPPORTUNITY_STATUS_VALUE');
    $project->opp_amount        = getDolGlobalInt('EASYCRM_PROJECT_OPPORTUNITY_AMOUNT_VALUE');
    $project->date_c            = dol_now();
    $project->date_start        = dol_now();
    $project->statut            = 1;
    $project->usage_opportunity = 1;
    $project->usage_task        = 1;

    $projectID = $project->create($user);
    if ($projectID > 0) {
        // Category association
        $categories = GETPOST('categories_project', 'array');
        if (count($categories) > 0) {
            $result = $project->setCategories($categories);
            if ($result < 0) {
                setEventMessages($project->error, $project->errors, 'errors');
                $error++;
            }
        }

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
            $project->update($user);
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
        header('Location: ' . $_SERVER["PHP_SELF"]);
        exit;
    } else {
        $action = '';
    }
}

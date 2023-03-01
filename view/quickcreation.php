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
 *  \file       view/quickcreation.php
 *  \ingroup    easycrm
 *  \brief      Page to quick creation project/task
 */

// Load EasyCRM environment
if (file_exists('../easycrm.main.inc.php')) {
    require_once __DIR__ . '/../easycrm.main.inc.php';
} else {
    die('Include of easycrm main fails');
}

// Libraries
if (isModEnabled('project')) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';

    require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
    require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
}
if (isModEnabled('categorie')) {
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
}

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action      = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'quickcretion'; // To manage different context of search
$cancel      = GETPOST('cancel', 'aZ09');
$backtopage  = GETPOST('backtopage', 'alpha');

// Initialize technical objects
if (isModEnabled('project')) {
    $project = new Project($db);
    $task = new Task($db);
}
if (isModEnabled('categorie')) {
    $category = new Categorie($db);
}
if (isModEnabled('societe')) {
    $thirdparty = new Societe($db);
}

// Initialize view objects
$form = new Form($db);
if (isModEnabled('project')) {
    $formproject = new FormProjets($db);
}

$hookmanager->initHooks(['quickcration']); // Note that conf->hooks_modules contains array

$date_start = dol_mktime(0, 0, 0, GETPOST('projectstartmonth', 'int'), GETPOST('projectstartday', 'int'), GETPOST('projectstartyear', 'int'));

// Security check - Protection if external user
$permissiontoread          = $user->rights->easycrm->read;
$permissiontoaddproject    = $user->rights->projet->creer;
$permissiontoaddthirdparty = $user->rights->societe->creer;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

$parameters = [];
$reshook = $hookmanager->executeHooks('doActions', $parameters, $project, $action); // Note that $action and $project may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    $error = 0;

    $backurlforlist = dol_buildpath('/projet/list.php', 1);

    if (empty($backtopage) || ($cancel && empty($id))) {
        if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
            if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
                $backtopage = $backurlforlist;
            } else {
                $backtopage = dol_buildpath('/projet/card.php', 1) . '?id=' . ((!empty($id) && $id > 0) ? $id : '__ID__');
            }
        }
    }

    if ($cancel) {
        if (!empty($backtopageforcancel)) {
            header('Location: ' .$backtopageforcancel);
            exit;
        } elseif (!empty($backtopage)) {
            header('Location: ' .$backtopage);
            exit;
        }
        $action = '';
    }

    if ($action == 'add' && $permissiontoaddproject) {
        if (!GETPOST('title')) {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('ProjectLabel')), null, 'errors');
            $error++;
        }

        if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
            if (GETPOST('opp_amount') != '' && !(GETPOST('opp_status') > 0)) {
                $error++;
                setEventMessages($langs->trans('ErrorOppStatusRequiredIfAmount'), null, 'errors');
            }
        }

        if (!$error) {

            $db->begin();

            $project->ref               = GETPOST('ref');
            $project->title             = GETPOST('title');
            $project->opp_status        = GETPOST('opp_status', 'int');
            $project->opp_amount        = price2num(GETPOST('opp_amount'));
            $project->date_c            = dol_now();
            $project->date_start        = $date_start;
            $project->statut            = 1;
            $project->usage_opportunity = 1;
            $project->usage_task        = 1;

            $result = $project->create($user);
            if (!$error && $result > 0) {
                $project->add_contact($user->id, 'PROJECTLEADER', 'internal');

                $defaultref = '';
                $obj        = empty($conf->global->PROJECT_TASK_ADDON) ? 'mod_task_simple' : $conf->global->PROJECT_TASK_ADDON;

                if (!empty($conf->global->PROJECT_TASK_ADDON) && is_readable(DOL_DOCUMENT_ROOT . '/core/modules/project/task/' . $conf->global->PROJECT_TASK_ADDON . '.php')) {
                    require_once DOL_DOCUMENT_ROOT . '/core/modules/project/task/' . $conf->global->PROJECT_TASK_ADDON . '.php';
                    $modTask = new $obj();
                    $defaultref = $modTask->getNextValue('', null);
                }

                $task->fk_project = $result;
                $task->ref        = $defaultref;
                $task->label      = $langs->trans('CommercialFollowUp') . ' - ' . $project->title;
                $task->date_c     = dol_now();

                $task->create($user);
                $task->add_contact($user->id, 'TASKEXECUTIVE', 'internal');


                // -3 means type not found (PROJECTLEADER renamed, de-activated or deleted), so don't prevent creation if it has been the case
                if ($result == -3) {
                    setEventMessage('ErrorPROJECTLEADERRoleMissingRestoreIt', 'errors');
                    $error++;
                } elseif ($result < 0) {
                    $langs->load('errors');
                    setEventMessages($project->error, $project->errors, 'errors');
                    $error++;
                }
            } else {
                $langs->load('errors');
                setEventMessages($project->error, $project->errors, 'errors');
                $error++;
            }
            if (!$error && !empty($project->id) > 0) {
                // Category association
                $categories = GETPOST('categories', 'array');
                $result = $project->setCategories($categories);
                if ($result < 0) {
                    $langs->load('errors');
                    setEventMessages($project->error, $project->errors, 'errors');
                    $error++;
                }
            }

            if (!$error) {
                $db->commit();

                if (!empty($backtopage)) {
                    $backtopage = preg_replace('/--IDFORBACKTOPAGE--|__ID__/', $project->id, $backtopage); // New method to autoselect project after a New on another form object creation
                    header('Location: ' . $backtopage);
                    exit;
                } else {
                    header('Location:card.php?id=' . $project->id);
                    exit;
                }
            } else {
                $db->rollback();
                unset($_POST['ref']);
                $action = 'create';
            }
        } else {
            $action = 'create';
        }
    }

    if ($action == 'add_thirdparty' && $permissiontoaddthirdparty) {
        if (!GETPOST('name')) {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ThirdPartyName')), null, 'errors');
            $error++;
        }
        if (!empty($thirdparty->email) && !isValidEMail($thirdparty->email)) {
            $langs->load("errors");
            $error++;
            setEventMessages('', $langs->trans("ErrorBadEMail", $thirdparty->email), 'errors');
        }
        if (!$error) {
            $db->begin();

            $thirdparty->name  = GETPOST('name');
            $thirdparty->email = trim(GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL));

            $result = $thirdparty->create($user);
            if (!$error && $result > 0) {
                if ($result < 0) {
                    $langs->load('errors');
                    setEventMessages($thirdparty->error, $thirdparty->errors, 'errors');
                    $error++;
                }
            } else {
                $langs->load('errors');
                setEventMessages($thirdparty->error, $thirdparty->errors, 'errors');
                $error++;
            }
            if (!$error) {
                $db->commit();
                if (!empty($backtopage)) {
                    $backtopage = preg_replace('/--IDFORBACKTOPAGE--|__ID__/', $thirdparty->id, $backtopage); // New method to autoselect project after a New on another form object creation
                    header('Location: ' . $backtopage);
                    exit;
                } else {
                    header('Location:card.php?id=' . $thirdparty->id);
                    exit;
                }
            } else {
                $db->rollback();
                $action = '';
            }
        } else {
            $action = '';
        }
    }
}

/*
 * View
 */

$title    = $langs->trans('QuickProjectCreation');
$help_url = 'FR:Module_EasyCRM';

saturne_header(0, '', $title, $help_url);

if (empty($permissiontoaddproject) && empty($permissiontoaddthirdparty)) {
    accessforbidden($langs->trans('NotEnoughPermissions'), 0);
    exit;
}

// Quick add project/task
if ($permissiontoaddproject) {
    print load_fiche_titre($langs->trans('QuickProjectCreation'), '', 'project');

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="add">';
    if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldcreate">';

    $defaultref = '';
    $modele = empty($conf->global->PROJECT_ADDON) ? 'mod_project_simple' : $conf->global->PROJECT_ADDON;

    // Search template files
    $file = '';
    $classname = '';
    $filefound = 0;
    $dirmodels = array_merge(array('/'), (array)$conf->modules_parts['models']);
    foreach ($dirmodels as $reldir) {
        $file = dol_buildpath($reldir . 'core/modules/project/' . $modele . '.php', 0);
        if (file_exists($file)) {
            $filefound = 1;
            $classname = $modele;
            break;
        }
    }

    if ($filefound) {
        $result = dol_include_once($reldir . 'core/modules/project/' . $modele . '.php');
        $modProject = new $classname();

        $defaultref = $modProject->getNextValue($thirdparty, $object);
    }

    if (is_numeric($defaultref) && $defaultref <= 0) {
        $defaultref = '';
    }

    // Ref
    $suggestedref = (GETPOST('ref') ? GETPOST('ref') : $defaultref);
    print '<tr><td class="titlefieldcreate"><span class="fieldrequired">' . $langs->trans('Ref') . '</span></td><td class><input class="maxwidth150onsmartphone" type="text" name="ref" value="' . dol_escape_htmltag($suggestedref) . '">';
    print ' ' . $form->textwithpicto('', $langs->trans('YouCanCompleteRef', $suggestedref));
    print '</td></tr>';

    // Label
    print '<tr><td><span class="fieldrequired">' . $langs->trans('ProjectLabel') . '</span></td><td><input class="width500 maxwidth150onsmartphone" type="text" name="title" value="' . dol_escape_htmltag(GETPOST('title', 'alphanohtml')) . '" autofocus></td></tr>';

    if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
        // Opportunity status
        print '<tr class="classuseopportunity"><td>' . $langs->trans('OpportunityStatus') . '</td>';
        print '<td class="maxwidthonsmartphone">';
        print $formproject->selectOpportunityStatus('opp_status', GETPOSTISSET('opp_status') ? GETPOST('opp_status') : $project->opp_status, 1, 0, 0, 0, '', 0, 1);
        print '</tr>';

        // Opportunity amount
        print '<tr class="classuseopportunity"><td>' . $langs->trans('OpportunityAmount') . '</td>';
        print '<td><input size="5" type="text" name="opp_amount" value="' . dol_escape_htmltag(GETPOSTISSET('opp_amount') ? GETPOST('opp_amount') : '') . '"></td>';
        print '</tr>';
    }

    // Date start
    print '<tr><td>' . $langs->trans('DateStart') . '</td><td>';
    print $form->selectDate(($date_start ?: ''), 'projectstart');
    print '</td></tr>';

    // Categories
    if (isModEnabled('categorie')) {
        print '<tr><td>' . $langs->trans('Categories') . '</td><td>';
        $cate_arbo = $form->select_all_categories(Categorie::TYPE_PROJECT, '', 'parent', 64, 0, 1);
        print img_picto('', 'category') . $form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx');
        print '</td></tr>';
    }

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel('Create');

    print '</form>';
}

// Quick add thirdparty
if ($permissiontoaddthirdparty) {
    print load_fiche_titre($langs->trans('QuickProjectCreation'), '', 'company');

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="add_thirdparty">';
    if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldcreate">';

    // Name, firstname
    print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
    print '<span id="TypeName" class="fieldrequired">' . $form->editfieldkey('ThirdPartyName', 'name', '', $object, 0) . '</span>';
    print '</td><td' . (empty($conf->global->SOCIETE_USEPREFIX) ? ' colspan="3"' : '') . '>';
    print '<input type="text" class="minwidth300" maxlength="128" name="name" id="name" value="' . dol_escape_htmltag($object->name) . '" autofocus="autofocus">';
    print $form->widgetForTranslation('name', $object, $permissiontoadd, 'string', 'alpahnohtml', 'minwidth300');

    // Email
    print '<tr><td>' . $form->editfieldkey('EMail', 'email', '', $object, 0, 'string', '', empty($conf->global->SOCIETE_EMAIL_MANDATORY) ? '' : $conf->global->SOCIETE_EMAIL_MANDATORY) . '</td>';
    print '<td' . (($conf->browser->layout == 'phone') || empty($conf->mailing->enabled) ? ' colspan="3"' : '') . '>' . img_picto('', 'object_email', 'class="pictofixedwidth"') . ' <input type="text" class="maxwidth200 widthcentpercentminusx" name="email" id="email" value="' . $object->email . '"></td>';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel('Create');

    print '</form>';
}

// End of page
llxFooter();
$db->close();
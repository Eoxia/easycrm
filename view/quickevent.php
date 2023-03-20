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
 *  \file       view/quickevent.php
 *  \ingroup    easycrm
 *  \brief      Page to quick event
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
}
if (isModEnabled('agenda')) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';

    require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
}
if (isModEnabled('categorie')) {
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
}

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['categories']);

// Get parameters
$action      = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'quickeventcretion'; // To manage different context of search
$socid       = GETPOST('socid', 'int');
$fromtype    = GETPOST('fromtype', 'aZ09');
$cancel      = GETPOST('cancel', 'aZ09');
$backtopage  = GETPOST('backtopage', 'alpha');

// Initialize technical objects
if (isModEnabled('agenda')) {
    $actioncomm = new ActionComm($db);
}
if (isModEnabled('project')) {
    $project = new Project($db);
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
if (isModEnabled('agenda')) {
    $formactions = new FormActions($db);
}

$hookmanager->initHooks(['quickeventcreation']); // Note that conf->hooks_modules contains array

// Security check - Protection if external user
$permissiontoread     = $user->rights->easycrm->read;
$permissiontoaddevent = $user->rights->agenda->myactions->create;
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

    if ($fromtype == 'project') {
        $backtopage = dol_buildpath('/projet/card.php', 1) . '?id=' . GETPOST('project_id');
    } else {
        $backtopage = dol_buildpath('/comm/card.php', 1) . '?socid=' . $socid;
    }

    if ($cancel) {
        header('Location: ' . $backtopage);
        exit;
    }

    if ($action == 'add') {
        if (!$error) {
            $db->begin();

            // Check parameters
            if (empty($conf->global->AGENDA_USE_EVENT_TYPE) && !GETPOST('label')) {
                $error++;
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Label')), [], 'errors');
            }

            // Initialisation objet cactioncomm
            if (GETPOSTISSET('actioncode') && !GETPOST('actioncode', 'aZ09')) { // actioncode is '0'
                $error++;
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), [], 'errors');
            } else {
                $actioncomm->type_code = GETPOST('actioncode', 'aZ09');
            }

            $dateStart = dol_mktime(GETPOST('datestarthour', 'int'), GETPOST('datestartmin', 'int'), GETPOST('datestartsec', 'int'), GETPOST('datestartmonth', 'int'), GETPOST('datestartday', 'int'), GETPOST('datestartyear', 'int'), 'tzuser');
            $dateEnd   = dol_mktime(GETPOST('dateendhour', 'int'), GETPOST('dateendmin', 'int'), GETPOST('dateendsec', 'int'), GETPOST('dateendmonth', 'int'), GETPOST('dateendday', 'int'), GETPOST('dateendyear', 'int'), 'tzuser');

            $actioncomm->label        = GETPOST('label');
            $actioncomm->datep        = $dateStart;
            $actioncomm->datef        = $dateEnd;
            $actioncomm->note_private = $langs->transnoentities('CommercialRelaunching');
            $actioncomm->socid        = $socid;
            $actioncomm->userownerid  = $user->id;
            $actioncomm->percentage   = -1;
            if ($fromtype == 'project') {
                $actioncomm->fk_project = GETPOST('project_id');
            }

            $actioncommID = $actioncomm->create($user);
            if (!$error && $actioncommID > 0) {
                // Category association
                $categories = GETPOST('categories', 'array');
                if (count($categories) > 0) {
                    $result = $actioncomm->setCategories($categories);
                    if ($result < 0) {
                        setEventMessages($actioncomm->error, $actioncomm->errors, 'errors');
                        $error++;
                    }
                }
            } else {
                $langs->load('errors');
                setEventMessages($actioncomm->error, $actioncomm->errors, 'errors');
                $error++;
            }
        }

        if (!$error) {
            $db->commit();
            if (!empty($backtopage)) {
                header('Location: ' . $backtopage);
            }
            exit;
        } else {
            $db->rollback();
            $action = '';
        }
    }
}

/*
 * View
 */

$title    = $langs->trans('QuickEvent');
$help_url = 'FR:Module_EasyCRM';

saturne_header(0, '', $title, $help_url);

if (empty($permissiontoaddevent)) {
    accessforbidden($langs->trans('NotEnoughPermissions'), 0);
    exit;
}

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?socid=' . $socid . '&fromtype=' . $fromtype . (GETPOSTISSET('project_id') ? '&project_id=' . GETPOST('project_id') : '') . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="add">';
if ($backtopage) {
    print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
}

// Quick add event
if ($permissiontoaddevent) {
    print load_fiche_titre($langs->trans('QuickEventCreation'), '', 'calendar');

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldcreate">';

    // Type of event
    if ($conf->global->EASYCRM_EVENT_TYPE_CODE_VISIBLE > 0) {
        print '<tr><td class="titlefieldcreate fieldrequired"><label for="actioncode">' . $langs->trans('Type') . '</label></td>';
        $default = (empty($conf->global->AGENDA_USE_EVENT_TYPE_DEFAULT) ? 'AC_RDV' : $conf->global->AGENDA_USE_EVENT_TYPE_DEFAULT);
        print '<td>' . $formactions->select_type_actions(GETPOSTISSET('actioncode') ? GETPOST('actioncode', 'aZ09') : ($actioncomm->type_code ?: $default), 'actioncode', 'systemauto', 0, -1, 0, 1) . '</td>';
        print '</tr>';
    }

    // Label
    if ($conf->global->EASYCRM_EVENT_LABEL_VISIBLE > 0) {
        print '<tr><td' . (empty($conf->global->AGENDA_USE_EVENT_TYPE) ? ' class="titlefieldcreate fieldrequired"' : '') . '><label for="label">' . $langs->trans('Label') . '</label></td>';
        print '<td><input type="text" id="label" name="label" class="maxwidth500 widthcentpercentminusx" maxlength="255" value="' . dol_escape_htmltag((GETPOSTISSET('label') ? GETPOST('label') : '')) . '"></td>';
        print '</tr>';
    }

    // Date start
    if ($conf->global->EASYCRM_EVENT_DATE_START_VISIBLE > 0) {
        print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('DateStart') . '</td>';
        $dateStart = dol_stringtotime(GETPOST('datestart', 'int', 1), 'tzuser');
        print '<td>' . $form->selectDate($dateStart, 'datestart', 1, 1, 1, 'action', 1, 2, 0, 'fulldaystart', '', '', '', 1, '', '', 'tzuserrel') . '</td>';
        print '</tr>';
    }

    // Date end
    if ($conf->global->EASYCRM_EVENT_DATE_END_VISIBLE > 0) {
        print '<tr><td class="titlefieldcreate">' . $langs->trans('DateEnd') . '</td>';
        $dateEnd = dol_stringtotime(GETPOST('dateend', 'int', 1), 'tzuser');
        print '<td>' . $form->selectDate($dateEnd, 'dateend', 1, 1, 1, 'action', 1, 0, 0, 'fulldaystart', '', '', '', 1, '', '', 'tzuserrel') . '</td>';
        print '</tr>';
    }

    // ThirdParty
    if (isModEnabled('societe') && $socid > 0) {
        print '<tr><td class="titlefieldcreate">' . $langs->trans('ActionOnCompany') . '</td>';
        $thirdparty->fetch($socid);
        print '<td>' . $thirdparty->getNomUrl(1) . '</td>';
        print '</tr>';
    }

    // Project
    if (isModEnabled('project') && $fromtype == 'project') {
        print '<tr><td class="titlefieldcreate">' . $langs->trans('Project'). '</td>';
        $project->fetch(GETPOST('project_id'));
        print '<td>' . $project->getNomUrl(1) . '</td>';
        print '</tr>';

        if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
            // Opportunity status
            if ($conf->global->EASYCRM_PROJECT_OPPORTUNITY_STATUS_VISIBLE > 0) {
                print '<tr><td>' . $langs->trans('OpportunityStatus') . '</td>';
                print '<td>' . $formproject->selectOpportunityStatus('opp_status', $project->opp_status, 0, 0, 0, '', 0, 1) . '</td>';
                print '</tr>';
            }
        }
    }

    // Categories
    if (isModEnabled('categorie') && $conf->global->EASYCRM_EVENT_CATEGORIES_VISIBLE > 0) {
        print '<tr><td>' . $langs->trans('Categories') . '</td><td>';
        $cate_arbo = $form->select_all_categories(Categorie::TYPE_ACTIONCOMM, '', 'parent', 64, 0, 1);
        print img_picto('', 'category', 'class="pictofixedwidth"') . $form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx');
        print '</td></tr>';
    }

    print '</table>';

    print dol_get_fiche_end();
}

print $form->buttonsSaveCancel('Create');

print '</form>';

// End of page
llxFooter();
$db->close();
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
if (isModEnabled('societe')) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
}
if (isModEnabled('fckeditor')) {
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
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
    $contact    = new Contact($db);
}

// Initialize view objects
$form = new Form($db);
if (isModEnabled('project')) {
    $formproject = new FormProjets($db);
}
if (isModEnabled('societe')) {
    $formcompany = new FormCompany($db);
}

$hookmanager->initHooks(['quickcration']); // Note that conf->hooks_modules contains array

$date_start = dol_mktime(0, 0, 0, GETPOST('projectstartmonth', 'int'), GETPOST('projectstartday', 'int'), GETPOST('projectstartyear', 'int'));

// Security check - Protection if external user
$permissiontoread          = $user->rights->easycrm->read;
$permissiontoaddproject    = $user->rights->projet->creer;
$permissiontoaddthirdparty = $user->rights->societe->creer;
$permissiontoaddcontact    = $user->rights->societe->contact->creer;
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

    if ($cancel) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action == 'add') {
        // Check thirdparty parameters
        if (!empty($thirdparty->email) && !isValidEMail($thirdparty->email)) {
            setEventMessages($langs->trans('ErrorBadEMail', $thirdparty->email), [], 'errors');
            $error++;
        }

        // Check project parameters
        if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
            if (GETPOST('opp_amount') != '' && !(GETPOST('opp_status') > 0)) {
                setEventMessages($langs->trans('ErrorOppStatusRequiredIfAmount'), [], 'errors');
                $error++;
            }
        }

        if (!$error) {
            $db->begin();

            if (!empty(GETPOST('name'))) {
                $thirdparty->code_client  = -1;
                $thirdparty->client       = GETPOST('client');
                $thirdparty->name         = GETPOST('name');
                $thirdparty->email        = trim(GETPOST('email_thirdparty', 'custom', 0, FILTER_SANITIZE_EMAIL));
                $thirdparty->url          = trim(GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL));
                $thirdparty->note_private = GETPOST('note_private');

                $thirdpartyID = $thirdparty->create($user);
                if ($thirdpartyID > 0) {
                    $backtopage = dol_buildpath('/societe/card.php', 1) . '?id=' . $thirdpartyID;

                    // Category association
                    $categories = GETPOST('categories_customer', 'array');
                    if (count($categories) > 0) {
                        $result = $thirdparty->setCategories($categories, 'customer');
                        if ($result < 0) {
                            setEventMessages($thirdparty->error, $thirdparty->errors, 'errors');
                            $error++;
                        }
                    }
                    if (!empty(GETPOST('lastname', 'alpha'))) {
                        $contact->socid     = !empty($thirdpartyID) ? $thirdpartyID : '';
                        $contact->lastname  = GETPOST('lastname', 'alpha');
                        $contact->firstname = GETPOST('firstname', 'alpha');
                        $contact->poste     = GETPOST('job', 'alpha');
                        $contact->email     = trim(GETPOST('email_contact', 'custom', 0, FILTER_SANITIZE_EMAIL));
                        $contact->phone_pro = GETPOST('phone_pro', 'alpha');

                        $contactID = $contact->create($user);
                        if ($contactID < 0) {
                            setEventMessages($contact->error, $contact->errors, 'errors');
                            $error++;
                        }
                    }
                } else {
                    setEventMessages($thirdparty->error, $thirdparty->errors, 'errors');
                    $error++;
                }
            }

            if (!empty(GETPOST('title'))) {
                $project->socid      = !empty($thirdpartyID) ? $thirdpartyID : '';
                $project->ref        = GETPOST('ref');
                $project->title      = GETPOST('title');
                $project->opp_status = GETPOST('opp_status', 'int');

                switch ($project->opp_status) {
                    case 2:
                        $project->opp_percent = 20;
                        break;
                    case 3:
                        $project->opp_percent = 40;
                        break;
                    case 4:
                        $project->opp_percent = 60;
                        break;
                    case 5:
                        $project->opp_percent = 100;
                        break;
                    default:
                        $project->opp_percent = 0;
                        break;
                }

                $project->opp_amount        = price2num(GETPOST('opp_amount'));
                $project->date_c            = dol_now();
                $project->date_start        = $date_start;
                $project->statut            = 1;
                $project->usage_opportunity = 1;
                $project->usage_task        = 1;

                $projectID = $project->create($user);
                if (!$error && $projectID > 0) {
                    $backtopage = dol_buildpath('/projet/card.php', 1) . '?id=' . $projectID;

                    // Category association
                    $categories = GETPOST('categories_project', 'array');
                    if (count($categories) > 0) {
                        $result = $project->setCategories($categories);
                        if ($result < 0) {
                            setEventMessages($project->error, $project->errors, 'errors');
                            $error++;
                        }
                    }

                    $project->add_contact($user->id, 'PROJECTLEADER', 'internal');

                    $defaultref = '';
                    $obj        = empty($conf->global->PROJECT_TASK_ADDON) ? 'mod_task_simple' : $conf->global->PROJECT_TASK_ADDON;

                    if (!empty($conf->global->PROJECT_TASK_ADDON) && is_readable(DOL_DOCUMENT_ROOT . '/core/modules/project/task/' . $conf->global->PROJECT_TASK_ADDON . '.php')) {
                        require_once DOL_DOCUMENT_ROOT . '/core/modules/project/task/' . $conf->global->PROJECT_TASK_ADDON . '.php';
                        $modTask    = new $obj();
                        $defaultref = $modTask->getNextValue($thirdparty, $task);
                    }

                    $task->fk_project = $projectID;
                    $task->ref        = $defaultref;
                    $task->label      = $langs->trans('CommercialFollowUp') . ' - ' . $project->title;
                    $task->date_c     = dol_now();

                    $taskID = $task->create($user);
                    if ($taskID > 0) {
                        $task->add_contact($user->id, 'TASKEXECUTIVE', 'internal');
                    } else {
                        setEventMessages($task->error, $task->errors, 'errors');
                        $error++;
                    }
                } else {
                    $langs->load('errors');
                    setEventMessages($project->error, $project->errors, 'errors');
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
                unset($_POST['ref']);
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

$title    = $langs->trans('QuickCreation');
$help_url = 'FR:Module_EasyCRM';

saturne_header(0, '', $title, $help_url);

if (empty($permissiontoaddthirdparty) && empty($permissiontoaddcontact) && empty($permissiontoaddproject)) {
    accessforbidden($langs->trans('NotEnoughPermissions'), 0);
    exit;
}

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="add">';
if ($backtopage) {
    print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
}

// Quick add thirdparty
if ($permissiontoaddthirdparty) {
    print load_fiche_titre($langs->trans('QuickThirdPartyCreation'), '', 'company');

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldcreate">';

    // Name, firstname
    if ($conf->global->EASYCRM_THIRDPARTY_NAME_VISIBLE > 0) {
        print '<tr><td class="titlefieldcreate fieldrequired"><label for="name">' . $langs->trans('ThirdPartyName') . '</label></td>';
        print '<td><input type="text" name="name" id="name" class="maxwidth200 widthcentpercentminusx" maxlength="128" value="' . (GETPOSTISSET('name') ? GETPOST('name', 'alpha') : '') . '" autofocus="autofocus"></td>';
        print '</tr>';
    }

    if ($conf->global->EASYCRM_THIRDPARTY_CLIENT_VISIBLE > 0) {
        print '<tr><td class="titlefieldcreate fieldrequired"><label for="name">' . $langs->trans('ProspectCustomer') . '</label></td>';
        print '<td>' . $formcompany->selectProspectCustomerType(GETPOSTISSET('client') ? GETPOST('client') : $conf->global->EASYCRM_THIRDPARTY_CLIENT_VALUE, 'client', 'customerprospect', 'form', 'maxwidth200 widthcentpercentminusx') . '</td>';
    }

    // Email
    if ($conf->global->EASYCRM_THIRDPARTY_EMAIL_VISIBLE > 0) {
        print '<tr><td><label for="email_thirdparty">' . $langs->trans('Email') . '</label></td>';
        print '<td>' . img_picto('', 'object_email', 'class="pictofixedwidth"') . ' <input type="text" name="email_thirdparty" id="email_thirdparty" class="maxwidth200 widthcentpercentminusx" value="' . (GETPOSTISSET('email_thirdparty') ? GETPOST('email_thirdparty', 'alpha') : '') . '"></td>';
        print '</tr>';
    }

    // Web
    if ($conf->global->EASYCRM_THIRDPARTY_WEB_VISIBLE > 0) {
        print '<tr><td><label for="url">' . $langs->trans('Web') . '</label></td>';
        print '<td>' . img_picto('', 'globe', 'class="pictofixedwidth"') . ' <input type="text" name="url" id="url" class="maxwidth200 widthcentpercentminusx" value="' . (GETPOSTISSET('url') ? GETPOST('url', 'alpha') : '') . '"></td>';
        print '</tr>';
    }

    // Private note
    if ($conf->global->EASYCRM_THIRDPARTY_PRIVATE_NOTE_VISIBLE > 0 && isModEnabled('fckeditor')) {
        print '<tr><td><label for="note_private">' . $langs->trans('NotePrivate') . '</label></td>';
        $doleditor = new DolEditor('note_private', (GETPOSTISSET('note_private') ? GETPOST('note_private', 'alpha') : ''), '', 80, 'dolibarr_notes', 'In', 0, false, ((empty($conf->global->FCKEDITOR_ENABLE_NOTE_PRIVATE) || $conf->browser->layout == 'phone') ? 0 : 1), ROWS_3, '90%');
        print '<td>' . $doleditor->Create(1) . '</td>';
        print '</tr>';
    }

    // Categories
    if (isModEnabled('categorie') && $conf->global->EASYCRM_THIRDPARTY_CATEGORIES_VISIBLE > 0 ) {
        print '<tr><td>' . $langs->trans('CustomersProspectsCategoriesShort') . '</td><td>';
        $cate_arbo = $form->select_all_categories(Categorie::TYPE_CUSTOMER, '', 'parent', 64, 0, 1);
        print img_picto('', 'category', 'class="pictofixedwidth"') . $form->multiselectarray('categories_customer', $cate_arbo, GETPOST('categories_customer', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx');
        print '</td></tr>';
    }

    print '</table>';

    print dol_get_fiche_end();
}

// Quick add contact
if ($permissiontoaddcontact) {
    print load_fiche_titre($langs->trans('QuickContactCreation'), '', 'contact');

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldcreate">';

    // Name, firstname
    if ($conf->global->EASYCRM_CONTACT_LASTNAME_VISIBLE > 0) {
        print '<tr><td class="titlefieldcreate fieldrequired"><label for="lastname">' . $langs->trans('Lastname') . ' / ' . $langs->trans('Label') . '</label></td>';
        print '<td><input type="text" name="lastname" id="lastname" class="maxwidth200 widthcentpercentminusx" maxlength="80" value="' . dol_escape_htmltag(GETPOSTISSET('lastname') ? GETPOST('lastname', 'alpha') : '') . '"></td>';
        print '</tr>';
    }

    if ($conf->global->EASYCRM_CONTACT_FIRSTNAME_VISIBLE > 0) {
        print '<tr><td><label for="firstname">' . $langs->trans('Firstname') . '</label></td>';
        print '<td><input type="text" name="firstname" id="firstname" class="maxwidth200 widthcentpercentminusx" maxlength="80" value="' . dol_escape_htmltag(GETPOSTISSET('firstname') ? GETPOST('firstname', 'alpha') : '') . '"></td>';
        print '</tr>';
    }

    // Job position
    if ($conf->global->EASYCRM_CONTACT_JOB_VISIBLE > 0) {
        print '<tr><td><label for="job">' . $langs->trans('PostOrFunction') . '</label></td>';
        print '<td><input type="text" name="job" id="job" class="maxwidth200 widthcentpercentminusx" maxlength="255" value="' . dol_escape_htmltag(GETPOSTISSET('job') ? GETPOST('job') : '') . '"></td>';
        print '</tr>';
    }

    // Phone
    if ($conf->global->EASYCRM_CONTACT_PHONEPRO_VISIBLE > 0) {
        print '<tr><td><label for="phone_pro">' . $langs->trans('PhonePro') . '</label></td>';
        print '<td>' . img_picto('', 'object_phoning', 'class="pictofixedwidth"') . ' <input type="text" name="phone_pro" id="phone_pro" class="maxwidth200 widthcentpercentminusx" value="' . (GETPOSTISSET('phone_pro') ? GETPOST('phone_pro', 'alpha') : '') . '"></td>';
        print '</tr>';
    }

    // Email
    if ($conf->global->EASYCRM_CONTACT_EMAIL_VISIBLE > 0) {
        print '<tr><td><label for="email_contact">' . $langs->trans('Email') . '</label></td>';
        print '<td>' . img_picto('', 'object_email', 'class="pictofixedwidth"') . ' <input type="text" name="email_contact" id="email_contact" class="maxwidth200 widthcentpercentminusx" value="' . (GETPOSTISSET('email_contact') ? GETPOST('email_contact', 'alpha') : '') . '"></td>';
        print '</tr>';
    }

    print '</table>';

    print dol_get_fiche_end();
}

// Quick add project/task
if ($permissiontoaddproject) {
    print load_fiche_titre($langs->trans('QuickProjectCreation'), '', 'project');

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldcreate">';

    $defaultref = '';
    $modele = empty($conf->global->PROJECT_ADDON) ? 'mod_project_simple' : $conf->global->PROJECT_ADDON;

    // Search template files
    $file = '';
    $classname = '';
    $filefound = 0;
    $dirmodels = array_merge(['/'], $conf->modules_parts['models']);
    foreach ($dirmodels as $reldir) {
        $file = dol_buildpath($reldir . 'core/modules/project/' . $modele . '.php');
        if (file_exists($file)) {
            $filefound = 1;
            $classname = $modele;
            break;
        }
    }

    if ($filefound) {
        $result = dol_include_once($reldir . 'core/modules/project/' . $modele . '.php');
        $modProject = new $classname();

        $defaultref = $modProject->getNextValue($thirdparty, $project);
    }

    if (is_numeric($defaultref) && $defaultref <= 0) {
        $defaultref = '';
    }

    // Ref
    $suggestedref = (GETPOST('ref') ? GETPOST('ref') : $defaultref);
    print '<input type="hidden" name="ref" value="' . dol_escape_htmltag($suggestedref) . '">';

    // Label
    if ($conf->global->EASYCRM_PROJECT_LABEL_VISIBLE > 0) {
        print '<tr><td class="titlefieldcreate fieldrequired"><label for="title">' . $langs->trans('ProjectLabel') . '</label></td>';
        print '<td><input type="text" name="title" id="title" class="maxwidth500 widthcentpercentminusx" maxlength="255" value="' . dol_escape_htmltag((GETPOSTISSET('title') ? GETPOST('title') : '')) . '"></td>';
        print '</tr>';
    }

    if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
        // Opportunity status
        if ($conf->global->EASYCRM_PROJECT_OPPORTUNITY_STATUS_VISIBLE > 0) {
            print '<tr><td><label for="opp_status">' . $langs->trans('OpportunityStatus') . '</label></td>';
            print '<td>' . $formproject->selectOpportunityStatus('opp_status', GETPOSTISSET('opp_status') ? GETPOST('opp_status') : $conf->global->EASYCRM_PROJECT_OPPORTUNITY_STATUS_VALUE, 1, 0, 0, 0, '', 0, 1) . '</td>';
            print '</tr>';
        }

        // Opportunity amount
        if ($conf->global->EASYCRM_PROJECT_OPPORTUNITY_AMOUNT_VISIBLE > 0) {
            print '<tr><td><label for="opp_amount">' . $langs->trans('OpportunityAmount') . '</label></td>';
            print '<td><input type="text" name="opp_amount" id="opp_amount" size="5" value="' . dol_escape_htmltag(GETPOSTISSET('opp_amount') ? GETPOST('opp_amount') : $conf->global->EASYCRM_PROJECT_OPPORTUNITY_AMOUNT_VALUE) . '"></td>';
            print '</tr>';
        }
    }

    // Date start
    if ($conf->global->EASYCRM_PROJECT_DATE_START_VISIBLE > 0) {
        print '<tr><td><label for="projectstart">' . $langs->trans('DateStart') . '</label></td>';
        print '<td>' . $form->selectDate(($date_start ?: ''), 'projectstart') . '</td>';
        print '</tr>';
    }

    // Categories
    if (isModEnabled('categorie') && $conf->global->EASYCRM_PROJECT_CATEGORIES_VISIBLE > 0) {
        print '<tr><td>' . $langs->trans('Categories') . '</td><td>';
        $cate_arbo = $form->select_all_categories(Categorie::TYPE_PROJECT, '', 'parent', 64, 0, 1);
        print img_picto('', 'category', 'class="pictofixedwidth"') . $form->multiselectarray('categories_project', $cate_arbo, GETPOST('categories_project', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx');
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
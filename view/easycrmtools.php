<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    view/easycrmtools.php
 * \ingroup easycrm
 * \brief   Tools page of EasyCRM top menu
 */

// Load EasyCRM environment
if (file_exists('../easycrm.main.inc.php')) {
    require_once __DIR__ . '/../easycrm.main.inc.php';
} elseif (file_exists('../../easycrm.main.inc.php')) {
    require_once __DIR__ . '/../../easycrm.main.inc.php';
} else {
    die('Include of easycrm main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

// Load EasyCRM libraries
require_once __DIR__ . '/../lib/easycrm_function.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action = (GETPOSTISSET('action') ? GETPOST('action', 'aZ09') : 'view');

// Initialize technical objects
$facture    = new Facture($db);
$thirdparty = new Societe($db);
$actioncomm = new ActionComm($db);

// Security check - Protection if external user
$permissionToRead = $user->rights->easycrm->adminpage->read;
saturne_check_access($permissionToRead);

/*
 * Actions
 */

if ($action == 'update_object_contact') {
    $factures = saturne_fetch_all_object_type('Facture', '', '', 0, 0,  ['customsql' => 't.fk_statut = ' . Facture::STATUS_VALIDATED]);
    if (is_array($factures) && !empty($factures)) {
        $typeContactID                     = 0;
        $checkObjectContact                = 0;
        $objectContactUpdated              = 0;
        $alreadyCheckObjectContact         = [];
        $societyObjectContactNotDefinedIDs = [];
        $typeContactsID                    = easycrm_fetch_dictionary ('c_type_contact', " AND element = 'facture' AND source = 'external' AND code = 'BILLING'");
        if (is_array($typeContactsID) && !empty($typeContactsID)) {
            $typeContactID = key($typeContactsID);
        }
        foreach ($factures as $facture) {
            $objectContacts = $facture->liste_contact(-1, 'external', 0, 'BILLING');
            if (is_array($objectContacts) && empty($objectContacts)) {
                $notationObjectContacts   = get_notation_object_contacts($facture, 'facture_external_BILLINGS');
                $notationObjectContactKey = key($notationObjectContacts);
                $notationObjectContact    = array_shift($notationObjectContacts);
                if (!empty($notationObjectContact)) {
                    $facture->add_contact($notationObjectContactKey, $typeContactID);
                    setEventMessage($langs->trans('ObjectContactUpdated', $langs->trans('FactureMin'), $facture->ref));
                    $objectContactUpdated++;
                } elseif (!in_array($facture->fk_soc, $alreadyCheckObjectContact)) {
                    $thirdparty->fetch($facture->fk_soc);
                    setEventMessages($langs->trans('SocietyObjectContactNotDefined') . ' ' . $thirdparty->getNomUrl(1), [],'warnings');
                    $checkObjectContact++;
                    $alreadyCheckObjectContact[]         = $facture->fk_soc;
                    $societyObjectContactNotDefinedIDs[] = $thirdparty->id;
                }
            }
        }
        $societyObjectContactNotDefinedIDs = json_encode($societyObjectContactNotDefinedIDs);
        dolibarr_set_const($db, 'EASYCRM_OBJECT_CONTACT_UPDATED', $objectContactUpdated, 'integer', 0, '', $conf->entity);
        dolibarr_set_const($db, 'EASYCRM_ALREADY_CHECK_OBJECT_CONTACT', count($alreadyCheckObjectContact), 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($db, 'EASYCRM_SOCIETY_OBJECT_CONTACT_NOT_DEFINED', $societyObjectContactNotDefinedIDs, 'chaine', 0, '', $conf->entity);
        if ($checkObjectContact == 0) {
            setEventMessage($langs->trans('AllObjectHaveContact', $langs->trans('FactureMins')));
        }
        $user->call_trigger('USER_UPDATE_OBJECT_CONTACT', $user);
    } else {
        setEventMessage($langs->trans('NoObject', $langs->trans('FactureMin')), 'errors');
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/*
 * View
 */

$title   = $langs->trans('Tools');
$helpUrl = 'FR:Module_EasyCRM';

saturne_header(0,'', $title, $helpUrl);

print load_fiche_titre($title, '', 'wrench');

print load_fiche_titre($langs->trans('UpdateObjectContactManagement'), '', '');

print '<form name="update-object-contact-from" id="update-object-contact-from" action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update_object_contact">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('UpdateObjectContact', $langs->trans('FactureMin'));
print '</td><td>';
print $langs->trans('UpdateObjectContactDescription', $conf->global->EASYCRM_OBJECT_CONTACT_UPDATED, $langs->trans('FactureMins'));
$actionComms = $actioncomm->getActions(0, 0,'', " AND code = 'AC_USER_UPDATE_OBJECT_CONTACT'", 'id','DESC', 1);
if (is_array($actionComms) && !empty($actionComms)) {
    print ' : ' . dol_print_date($actionComms[0]->datec, 'dayhour', 'tzuser');
}
print '</td>';

print '<td class="center">';
if ($user->rights->facture->creer) {
    print '<input type="submit" class="button" name="update_object_contact" value="' . $langs->trans('Validate') . '">';
} else {
    print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('PermissionDenied')) . '">' . $langs->trans('Validate') . '</span>';
}
print '</td></tr>';

print '</table>';
print '</form>'; ?>

<div class="wpeo-notice notice-info">
    <div class="notice-content">
        <div class="notice-title"><strong><?php echo $langs->trans('SocietyObjectContactNotDefinedTitle', $conf->global->EASYCRM_ALREADY_CHECK_OBJECT_CONTACT); ?></strong></div>
        <div class="notice-subtitle">
            <?php $societyObjectContactNotDefinedIDs = $conf->global->EASYCRM_SOCIETY_OBJECT_CONTACT_NOT_DEFINED;
            $societyObjectContactNotDefinedIDs = json_decode($societyObjectContactNotDefinedIDs);
            if (is_array($societyObjectContactNotDefinedIDs) && !empty($societyObjectContactNotDefinedIDs)) {
                foreach ($societyObjectContactNotDefinedIDs as $societyObjectContactNotDefinedID) {
                    $thirdparty->fetch($societyObjectContactNotDefinedID);
                    print $langs->trans('SocietyObjectContactNotDefined') . ' ' . $thirdparty->getNomUrl(1) . '<br>';
                }
            } ?>
        </div>
    </div>
</div>

<?php // End of page
llxFooter();
$db->close();

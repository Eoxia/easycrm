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
 * \file    admin/address.php
 * \ingroup easycrm
 * \brief   EasyCRM address config page.
 */

// Load EasyCRM environment
if (file_exists('../easycrm.main.inc.php')) {
    require_once __DIR__ . '/../easycrm.main.inc.php';
} elseif (file_exists('../../easycrm.main.inc.php')) {
    require_once __DIR__ . '/../../easycrm.main.inc.php';
} else {
    die('Include of easycrm main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

require_once __DIR__ . '/../class/address.class.php';
require_once __DIR__ . '/../lib/easycrm.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['admin', 'categories']);

// Get parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

// Initialize technical objects.
$object = new Address($db);

// Security check - Protection if external user
$permissiontoread = $user->rights->easycrm->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

//Set numering modele for address object
if ($action == 'setmod') {
    dolibarr_set_const($db, 'EASYCRM_ADDRESS_ADDON', $value, 'chaine', 0, '', $conf->entity);
}

/*
 * View
 */

$title    = $langs->trans('ModuleSetup', 'EasyCRM');
$help_url = 'FR:Module_EasyCRM';

saturne_header(0,'', $title, $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkback, 'easycrm_color@easycrm');

// Configuration header
$head = easycrm_admin_prepare_head();
print dol_get_fiche_head($head, 'object', $title, -1, 'easycrm_color@easycrm');

require_once __DIR__ . '/../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

print load_fiche_titre($langs->trans('Configs', $langs->trans('Addresses')), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '</tr>';

// Display single/multi address on map
print '<tr class="oddeven"><td>';
print  $langs->trans('DisplayAllAddress');
print '</td><td>';
print $langs->trans('DisplayAllAddressDescription');
print '</td><td class="center">';
print ajax_constantonoff('EASYCRM_DISPLAY_MAIN_ADDRESS');
print '</td></tr>';
print '</table>';

$db->close();
llxFooter();

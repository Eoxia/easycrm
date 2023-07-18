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
 *  \file       view/address.php
 *  \ingroup    easycrm
 *  \brief      Tab of address on generic element
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';

require_once __DIR__ . '/../core/modules/easycrm/address/mod_address_standard.php';
require_once __DIR__ . '/../class/address.class.php';
require_once __DIR__ . '/../../saturne/lib/object.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get object parameters
$objectType  = GETPOST('object_type', 'alpha');
$objectInfos = get_objects_metadata($objectType);

// Get address create parameters
$addressName    = GETPOST('name');
$addressType    = GETPOST('address_type');
$addressCountry = GETPOST('fk_country', 'int');
$addressRegion  = GETPOST('fk_region', 'int');
$addressState   = GETPOST('fk_state', 'int');
$addressTown    = GETPOST('town');
$addressZip     = GETPOST('zip', 'int');
$addressAddress = GETPOST('address');

// Get parameters
$id          = GETPOST('id', 'int');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : $objectType . 'address'; // To manage different context of search
$cancel      = GETPOST('cancel', 'aZ09');
$backtopage  = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$classname     = $objectInfos['class_name'];
$object        = new $classname($db);
$usertmp       = new User($db);
$address       = new Address($db);
$refAddressMod = new $conf->global->EASYCRM_ADDRESS_ADDON($db);


// Initialize view objects
$form        = new Form($db);
$formcompany = new FormCompany($db);

$hookmanager->initHooks([$objectType . 'address', $objectType . 'address', 'easycrmglobal', 'globalcard']); // Note that conf->hooks_modules contains array

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

// Security check - Protection if external user
$permissiontoread   = $user->rights->easycrm->address->read;
$permissiontoadd    = $user->rights->easycrm->address->write;
$permissiontodelete = $user->rights->easycrm->address->delete;
saturne_check_access($permissiontoread);

/*
*  Actions
*/

$parameters = ['id' => $id];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Cancel
	if ($cancel && !empty($backtopage)) {
		header('Location: ' . $backtopage);
		exit;
	}

	// Action to add address
	if ($action == 'add_address' && $permissiontoadd && !$cancel) {
		if (empty($addressName) || empty($addressType) || empty($addressCountry) || empty($addressTown)) {
			setEventMessages($langs->trans('EmptyValue'), [], 'errors');
			header('Location: ' . $_SERVER['PHP_SELF'] .  '?id=' . $id . '&action=create&object_type=' . $objectType . '&name=' . $addressName . '&address_type=' . $addressType . '&fk_country=' . $addressCountry . '&fk_region=' . $addressRegion . '&fk_state=' . $addressState . '&address_type=' . $addressType . '&town=' . $addressTown . '&zip=' . $addressZip . '&address=' . $addressAddress);
			exit;
		} else {
            $address->ref           = $refAddressMod->getNextValue($address);
			$address->name          = $addressName;
			$address->type          = $addressType;
			$address->fk_country    = $addressCountry;
			$address->fk_region     = $addressRegion;
			$address->fk_department = $addressState;
			$address->town          = $addressTown;
			$address->zip           = (int) $addressZip;
			$address->address       = $addressAddress;
			$address->element_type  = $objectType;
			$address->element_id    = $id;

			$result = $address->create($user);

			if ($result > 0) {
				if ($address->status == $address::STATUS_NOT_FOUND) {
					setEventMessages($langs->trans('CouldntFindDataOnOSM'), []);
				} else if ($address->status == $address::STATUS_ACTIVE) {
					setEventMessages($langs->trans('DataSuccessfullyRetrieved'), []);
				}
				setEventMessages($langs->trans('AddressCreated'), []);
			} else {
				setEventMessages($langs->trans('ErrorCreateAddress'), [], 'errors');
			}
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation.
			header('Location: ' . $urltogo);
			$action = '';
		}
	}

	// Action to delete address
	if ($action == 'delete_address' && $permissiontodelete) {
		$addressID = GETPOST('addressID');

		if ($addressID > 0) {
			$address->fetch($addressID);

			$result = $address->delete($user);

			if ($result > 0) {
				setEventMessages($langs->trans('AddressDeleted'), []);
			} else {
				setEventMessages($langs->trans('ErrorDeleteAddress'), [], 'errors');
			}
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation.
			header('Location: ' . $urltogo);
			$action = '';
		}
	}

    if ($action == 'add_favorite') {
        $favoriteAddressId = GETPOST('favorite_id');

        if (isset($object->array_options['options_' . $objectType . 'address']) && dol_strlen($object->array_options['options_' . $objectType . 'address']) > 0) {
            $object->array_options['options_' . $objectType . 'address'] = $object->array_options['options_' . $objectType . 'address'] == $favoriteAddressId ? 0 : $favoriteAddressId;
            $object->update($user);
        }
    }
}

/*
*	View
*/

$title   = $langs->trans('Address') . ' - ' . $langs->trans(ucfirst($objectType));
$helpUrl = 'FR:Module_EasyCRM';

saturne_header(0,'', $title, $helpUrl);

if ($action == 'create' && $id > 0) {
    print load_fiche_titre($langs->trans("NewAddress"), $backtopage, 'fa-map-marker-alt');

    print dol_get_fiche_head();

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&object_type=' . $objectType . '">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add_address">';
	if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
    }

	print '<table class="border centpercent tableforfieldcreate address-table">'."\n";

	// Name -- Nom
	print '<tr><td class="fieldrequired">'.$langs->trans("Name").'</td><td>';
	print '<input class="flat minwidth300 maxwidth300" type="text" size="36" name="name" id="name" value="'.$addressName.'">';
	print '</td></tr>';

	// Type -- Type
	print '<tr><td class="fieldrequired"><label class="" for="type">' . $langs->trans("Type") . '</label></td><td>';
	print saturne_select_dictionary('address_type','c_address_type', 'ref', 'label', $addressType, 0, '', '', 'minwidth300 maxwidth300');
	print '</td></tr>';

	// Country -- Pays
	print '<tr><td class="fieldrequired"><label class="" for="type">' . $langs->trans("Country") . '</label></td><td>';
	print $formcompany->select_country($addressCountry, 'fk_country', '', 0, 'minwidth300 maxwidth300') . ' ' . img_picto('', 'country', 'class="pictofixedwidth"');
	print '</td></tr>';

	// Region -- Region
	print '<tr><td class=""><label class="" for="type">' . $langs->trans("Region") . '</label></td><td>';
	print $formcompany->select_region($addressRegion, 'fk_region') . ' ' . img_picto('', 'state', 'class="pictofixedwidth"');;
	print '</td></tr>';

	// State - Departements
	print '<tr><td class=""><label class="" for="type">' . $langs->trans("State") . '</label></td><td>';
	print $formcompany->select_state($addressState, '', 'fk_state', 'minwidth300 maxwidth300') . ' ' . img_picto('', 'state', 'class="pictofixedwidth"');
	print '</td></tr>';

	// City -- Ville
	print '<tr><td class="fieldrequired">'.$langs->trans("Town").'</td><td>';
	print '<input class="flat minwidth300 maxwidth300" type="text" size="36" name="town" id="town" value="'.$addressTown.'">';
	print '</td></tr>';

	// ZIP -- Code postal
	print '<tr><td class="">'.$langs->trans("Zip").'</td><td>';
	print '<input class="flat minwidth300 maxwidth300" type="number" max="9999999999" name="zip" id="zip" value="'.$addressZip.'">';
	print '</td></tr>';

	// Address -- Adresse
	print '<tr><td class="">'.$langs->trans("Address").'</td><td>';
	print '<input class="flat minwidth300 maxwidth300" type="text" size="36" name="address" id="address" value="'.$addressAddress.'">';
	print '</td></tr>';

	print '</table></br>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel('Create');
} else if ($id > 0 || !empty($ref) && empty($action)) {
	saturne_get_fiche_head($object, 'address', $title);

    $morehtml = '<a href="' . dol_buildpath('/' . $object->element . '/list.php', 1) . '?restore_lastsearch_values=1&object_type=' . $object->element . '">' . $langs->trans('BackToList') . '</a>';
	saturne_banner_tab($object, 'ref', $morehtml, 1, 'ref', 'ref', '', !empty($object->photo));

	$object->fetch_optionals();

	print '<div class="fichecenter">';

	print '<div class="addresses-container">';

	$parameters = ['address' => $address];
	$reshook    = $hookmanager->executeHooks('easycrmAddressType', $parameters, $object); // Note that $action and $object may have been modified by some hooks
	if ($reshook > 0) {
		$addressByType = $hookmanager->resArray;
	} else {
		$addressByType['Address'] = $address->fetchAddresses($object->id, $objectType);
	}

	$alreadyAddedAddress = [];
	if (is_array($addressByType) && !empty($addressByType)) {
		foreach ($addressByType as $addressType => $addresses) {
			require __DIR__ . '/../core/tpl/easycrm_address_table_view.tpl.php';
		}
	} else {
		print load_fiche_titre($langs->trans('Addresses') . ' - ' . $langs->trans('Address'), '', '');

		print '<div class="opacitymedium">' . $langs->trans('NoAddresses') . '</div>';
	}

	print '</div>';

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();

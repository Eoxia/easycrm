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
 * \file    core/tpl/easycrm_address_table_view.tpl.php
 * \ingroup easycrm
 * \brief   Template page for address table.
 */

/**
 * The following vars must be defined:
 * Global     : $conf, $db, $langs, $user,
 * Parameters : $objectType, $id, $backtopage,
 * Objects    : $object, $address
 * Variable   : $addressType, $addresses, $moduleNameLowerCase, $permissiontoadd
 */


print load_fiche_titre($langs->trans('Addresses'), '', '');

print '<table class="border centpercent tableforfield">';

print '<tr class="liste_titre">';
print '<td>' . img_picto('', $objectInfos['picto']) . ' ' . $langs->trans($objectInfos['langs']) . '</td>';
print '<td class="center">' . $langs->trans('Name') . '</td>';
print '<td class="center">' . $langs->trans('Type') . '</td>';
print '<td class="center">' . $langs->trans('Country') . '</td>';
print '<td class="center">' . $langs->trans('Region') . '</td>';
print '<td class="center">' . $langs->trans('State') . '</td>';
print '<td class="center">' . $langs->trans('Town') . '</td>';
print '<td class="center">' . $langs->trans('Zip') . '</td>';
print '<td class="center">' . $langs->trans('Address') . '</td>';
print '<td class="center">' . $langs->trans('SignatureActions') . '</td>';
print '</tr>';

if (is_array($addresses) && !empty($addresses)) {
	foreach ($addresses as $element) {
		$objectTmp = class_exists($objectInfos['className']) ? new $objectInfos['className']($db) : new Project($db);

		// Object type
		print '<tr class="oddeven" data-address-id="' . $element->id . '">';
		print '<td class="oddeven">';
		if (method_exists($objectTmp, 'getNomUrl')) {
			$objectTmp->fetch($element->element_id);
			print $objectTmp->getNomUrl(1);
		} else {
			$nameField = explode(",", $objectInfos['name_field']);
			$nameField = $nameField[count($nameField) - 1];
			$label     = $objectTmp->$nameField ?? $conf->global->MAIN_INFO_SOCIETE_NOM;
			print img_picto('', $objectType) . ' ' . $label;
		}
		print '</td>';

		// Address name
		print '<td class="center">';
		print $element->name;
		print '</td>';

		// Address type
		print '<td class="center">';
		print $langs->transnoentities($element->type);
		print '</td>';

		// Country
		$addressCountry = getCountry($element->fk_country, 'all');
		print '<td class="center">';
		print $addressCountry['label'] ?: $langs->trans('N/A');
		print '</td>';

		// Region
		$addressRegionAndState = getState($element->fk_department, 'all', 0, 1);
		print '<td class="center">';
		print (is_array($addressRegionAndState) && !empty($addressRegionAndState) ? $addressRegionAndState['region'] : $langs->trans('N/A'));
		print '</td>';

		// Department
		print '<td class="center">';
		print (is_array($addressRegionAndState) && !empty($addressRegionAndState) ? $addressRegionAndState['label'] : $langs->trans('N/A'));
		print '</td>';

		print '<td class="center">';
		print $element->town;
		print '</td>';

		print '<td class="center">';
		print $element->zip > 0 ? $element->zip : $langs->trans('N/A');
		print '</td>';

		print '<td class="center">';
		print dol_strlen($element->address) > 0 ? $element->address : $langs->trans('N/A');
		print '</td>';

		// Actions
		print '<td class="center">';
		if ($permissiontodelete) {
			print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '">';
			print '<input type="hidden" name="token" value="' . newToken() . '">';
			print '<input type="hidden" name="action" value="delete_address">';
			print '<input type="hidden" name="addressID" value="' . $element->id . '">';
			print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
			print '<button type="submit" class="wpeo-button button-grey" value="' . $element->id . '">';
			print '<i class="fas fa-trash"></i>';
			print '</button>';
			print '</form>';
		}
		print '</td>';
		print '</tr>';
		$alreadyAddedAddress[$element->element_type][$element->element_id] = $element->element_id;
	}
} else {
	print '<tr><td colspan="10">';
	print '<div class="opacitymedium">' . $langs->trans('NoAddresses') . '</div><br>';
	print '</td></tr>';
}

if ($permissiontoadd) {
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&action=create&object_type=' . $objectType . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="create">';
	if (!empty($backtopage)) {
		print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	}

	print '<tr class="oddeven">';
	print '<td>' . $langs->trans('AddAnAddress') . '</td>';
	print '<td colspan="8"></td>';
	print '<td class="center">';
	print '<button type="submit" class="wpeo-button button-blue"><i class="fas fa-plus"></i></button>';
	print '</td></tr>';
	print '</table>';
	print '</form>';
}

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
* \file    lib/easycrm_function.lib.php
* \ingroup easycrm
* \brief   Library files with common functions for EasyCRM
*/

/**
 * Set notation invoice rec contact
 *
 * @param  CommonObject $object Object
 * @return int                  -1 = error, O = did nothing, 1 = OK
 * @throws Exception
 */
function set_notation_invoice_rec_contact(CommonObject $object): int
{
    $notationInvoiceRecContacts = get_notation_invoice_rec_contacts($object);
    $notationInvoiceRecContact  = array_shift($notationInvoiceRecContacts);
    $object->fetch_optionals();
    $object->array_options['options_notation_invoice_rec_contact'] = ($notationInvoiceRecContact['percentage'] ?: 0) . ' %';
    return $object->updateExtraField('notation_invoice_rec_contact');
}

/**
 * Get notation invoice rec contacts
 *
 * @param  object       $object                     Object
 * @return array        $notationInvoiceRecContacts Multidimensional associative array
 * @throws Exception
 */
function get_notation_invoice_rec_contacts(object $object): array
{
    require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
    require_once __DIR__ . '/../../saturne/lib/object.lib.php';

    $notationInvoiceRecContacts = [];
    $contacts                   = saturne_fetch_all_object_type('Contact', '', '', 0, 0, ['customsql' => 't.fk_soc=' . ($object->fk_soc > 0 ? $object->fk_soc : $object->socid)]);
    if (is_array($contacts) && !empty($contacts)) {
        foreach ($contacts as $contact) {
            $contact->fetchRoles();
            $notationInvoiceRecContacts[$contact->id]['lastname']  = dol_strlen($contact->lastname) > 0 ? 5 : 0;
            $notationInvoiceRecContacts[$contact->id]['firstname'] = dol_strlen($contact->firstname) > 0 ? 5 : 0;
            $notationInvoiceRecContacts[$contact->id]['phone_pro'] = dol_strlen($contact->phone_pro) > 0 ? 5 : 0;
            $notationInvoiceRecContacts[$contact->id]['phone']     = dol_strlen($contact->phone) > 0 ? 5 : 0;
            $notationInvoiceRecContacts[$contact->id]['email']     = dol_strlen($contact->email) > 0 ? 40 : 0;

            $checkRolesArray = in_array('facture', array_column($contact->roles, 'element'));
            $checkRolesArray += in_array('external', array_column($contact->roles, 'source'));
            $checkRolesArray += in_array('BILLING', array_column($contact->roles, 'code'));
            $notationInvoiceRecContacts[$contact->id]['role'] = $checkRolesArray == 3 ? 40 : 0;

            $percentage = 0;
            foreach ($notationInvoiceRecContacts[$contact->id] as $notationInvoiceRecContactsField) {
                $percentage += $notationInvoiceRecContactsField;
            }

            $notationInvoiceRecContacts[$contact->id]['percentage'] = price2num($percentage, 'MT', 1);
        }
        uasort($notationInvoiceRecContacts, 'compareByPercentage');
    }
    return $notationInvoiceRecContacts;
}

/**
 * The function compares two elements using the value of the 'percentage' key
 * It is designed to be used with sort functions such as usort() or uasort()
 *
 * @param  array $first  First element
 * @param  array $second Second element
 *
 * @return int           Returns an integer indicating the comparison relationship between the two elements
 */
function compareByPercentage(array $first, array $second): int
{
    if ($first['percentage'] === $second['percentage']) {
        return 0;
    }
    return ($first['percentage'] > $second['percentage']) ? -1 : 1;
}

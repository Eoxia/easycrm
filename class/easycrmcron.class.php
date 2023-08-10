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
 * \file    class/easycrmcron.class.php
 * \ingroup easycrm
 * \brief   Class file for manage EasycrmCron
 */

/**
 * Class for EasycrmCron
 */
class EasycrmCron
{
    /**
     * @var DoliDB Database handler
     */
    public DoliDB $db;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Update all notation invoice rec contacts (Cronjob)
     *
     * @return int       0 < if KO, > 0 if OK
     * @throws Exception
     */
    public function updateNotationInvoiceRecContacts(): int
    {
        // Load Dolibarr libraries
        require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture-rec.class.php';

        // Load Saturne libraries
        require_once __DIR__ . '/../../saturne/lib/object.lib.php';

        // Load EasyCRM libraries
        require_once __DIR__ . '/../lib/easycrm_function.lib.php';

        $factureRecs = saturne_fetch_all_object_type('FactureRec');

        if (is_array($factureRecs) &&!empty($factureRecs)) {
            foreach ($factureRecs as $factureRec) {
                set_notation_invoice_rec_contact($factureRec);
            }
            $this->output = 'test';
        }
        return 0;
    }
}

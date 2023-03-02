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
 * \file    class/actions_easycrm.class.php
 * \ingroup easycrm
 * \brief   EasyCRM hook overload.
 */

/**
 * Class ActionsEasycrm
 */
class ActionsEasycrm
{
    /**
     * @var DoliDB Database handler.
     */
    public DoliDB $db;

    /**
     * @var string Error code (or message)
     */
    public string $error = '';

    /**
     * @var array Errors
     */
    public array $errors = [];

    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public array $results = [];

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public string $resprints;

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
     *  Overloading the printMainArea function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadatas (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function addMoreBoxStatsCustomer(array $parameters, $object, $action): int
    {
        global $user;

        // Do something only for the current context
        if ($parameters['currentcontext'] == 'thirdpartycomm') {
            if (isModEnabled('project') && $user->rights->project->lire) {
                $project = new Project($this->db);
                $project->fetchAll('', '', 0,  0, []);

                // Box factures
                $tmp = $object->getOutstandingBills('customer', 0);
                $outstandingOpened      = $tmp['opened'];
                $outstandingTotal       = $tmp['total_ht'];
                $outstandingTotalIncTax = $tmp['total_ttc'];

                $text = $langs->trans("OverAllInvoices");
                $link = DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->id;
                $icon = 'bill';
                if ($link) {
                    $boxstat .= '<a href="'.$link.'" class="boxstatsindicator thumbstat nobold nounderline">';
                }
                $boxstat .= '<div class="boxstats" title="'.dol_escape_htmltag($text).'">';
                $boxstat .= '<span class="boxstatstext">'.img_object("", $icon).' <span>'.$text.'</span></span><br>';
                $boxstat .= '<span class="boxstatsindicator">'.price($outstandingTotal, 1, $langs, 1, -1, -1, $conf->currency).'</span>';
                $boxstat .= '</div>';
                if ($link) {
                    $boxstat .= '</a>';
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }
}
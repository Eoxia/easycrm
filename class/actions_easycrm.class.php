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
     *  Overloading the addMoreBoxStatsCustomer function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadatas (context, etc...)
     * @param  CommonObject $object     The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param  string       $action     Current action (if set). Generally create or edit or null
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function addMoreBoxStatsCustomer(array $parameters, CommonObject $object, string $action): int
    {
        global $conf, $langs, $user;
        
        // Do something only for the current context
        if ($parameters['currentcontext'] == 'thirdpartycomm') {
            if (isModEnabled('project') && $user->hasRight('projet', 'lire') && isModEnabled('saturne')) {
                require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
                require_once __DIR__ . '/../../saturne/lib/object.lib.php';

                $projects    = saturne_fetch_all_object_type('Project', '', '', 0, 0, ['customsql' => 't.fk_soc = ' . $object->id]);
                $projectData = [];
                if (is_array($projects) && !empty($projects)) {
                    foreach ($projects as $project) {
                        $projectData['total_opp_amount'] += $project->opp_amount;
                        $projectData['total_opp_weighted_amount'] += $project->opp_amount * $project->opp_percent / 100;
                    }
                }

                // Project box opportunity amount
                $boxTitle = $langs->transnoentities('OpportunityAmount');
                $link = DOL_URL_ROOT . '/projet/list.php?socid=' . $object->id;
                $boxStat = '<a href="' . $link . '" class="boxstatsindicator thumbstat nobold nounderline">';
                $boxStat .= '<div class="boxstats" title="' . dol_escape_htmltag($boxTitle) . '">';
                $boxStat .= '<span class="boxstatstext">' . img_object('', 'project') . ' <span>' . $boxTitle . '</span></span><br>';
                $boxStat .= '<span class="boxstatsindicator">' . price($projectData['total_opp_amount'], 1, $langs, 1, 0, -1, $conf->currency) . '</span>';
                $boxStat .= '</div>';
                $boxStat .= '</a>';

                // Project box opportunity weighted amount
                $boxTitle = $langs->transnoentities('OpportunityWeightedAmount');
                $boxStat .= '<a href="' . $link . '" class="boxstatsindicator thumbstat nobold nounderline">';
                $boxStat .= '<div class="boxstats" title="' . dol_escape_htmltag($boxTitle) . '">';
                $boxStat .= '<span class="boxstatstext">' . img_object('', 'project') . ' <span>' . $boxTitle . '</span></span><br>';
                $boxStat .= '<span class="boxstatsindicator">' . price($projectData['total_opp_weighted_amount'], 1, $langs, 1, 0, -1, $conf->currency) . '</span>';
                $boxStat .= '</div>';
                $boxStat .= '</a>';

                $this->resprints = $boxStat;
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     *  Overloading the addMoreRecentObjects function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadatas (context, etc...)
     * @param  CommonObject $object     The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param  string       $action     Current action (if set). Generally create or edit or null
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function addMoreRecentObjects(array $parameters, CommonObject $object, string $action): int
    {
        global $conf, $db, $langs, $user;

        // Do something only for the current context
        if ($parameters['currentcontext'] == 'thirdpartycomm') {
            if (isModEnabled('project') && $user->hasRight('projet', 'lire') && isModEnabled('saturne')) {
                require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
                require_once __DIR__ . '/../../saturne/lib/object.lib.php';

                $projects = saturne_fetch_all_object_type('Project', 'DESC', 'datec', 0, 0, ['customsql' => 't.fk_soc = ' . $object->id]);
                if (is_array($projects) && !empty($projects)) {
                    $countProjects = 0;
                    $nbProjects    = count($projects);
                    $maxList       = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

                    $out = '<div class="div-table-responsive-no-min">';
                    $out .= '<table class="noborder centpercent lastrecordtable">';

                    $out .= '<tr class="liste_titre">';
                    $out .= '<td colspan="4"><table class="nobordernopadding centpercent"><tr>';
                    $out .= '<td>' . $langs->trans('LastProjects', ($nbProjects <= $maxList ? '' : $maxList)) . '</td>';
                    $out .= '<td class="right"><a class="notasortlink" href="' . DOL_URL_ROOT . '/projet/list.php?socid=' . $object->id . '">' . $langs->trans('AllProjects') . '<span class="badge marginleftonlyshort">' . $nbProjects .'</span></a></td>';
                    $out .= '<td class="right" style="width: 20px;"><a href="' . DOL_URL_ROOT . '/projet/stats/index.php?socid=' . $object->id . '">' . img_picto($langs->trans('Statistics'), 'stats') . '</a></td>';
                    $out .= '</tr></table></td>';
                    $out .= '</tr>';

                    foreach ($projects as $project) {
                        if ($countProjects == $maxList) {
                            break;
                        } else {
                            $countProjects++;
                        }
                        $out .= '<tr class="oddeven">';
                        $out .= '<td class="nowraponall">';
                        $out .= $project->getNomUrl(1);
                        // Preview
                        $filedir = $conf->projet->multidir_output[$project->entity] . '/' . dol_sanitizeFileName($project->ref);
                        $fileList = null;
                        if (!empty($filedir)) {
                            $fileList = dol_dir_list($filedir, 'files', 0, '', '(\.meta|_preview.*.*\.png)$', 'date', SORT_DESC);
                        }
                        if (is_array($fileList) && !empty($fileList)) {
                            // Defined relative dir to DOL_DATA_ROOT
                            $relativedir = '';
                            if ($filedir) {
                                $relativedir = preg_replace('/^' . preg_quote(DOL_DATA_ROOT, '/') . '/', '', $filedir);
                                $relativedir = preg_replace('/^\//', '', $relativedir);
                            }
                            // Get list of files stored into database for same relative directory
                            if ($relativedir) {
                                completeFileArrayWithDatabaseInfo($fileList, $relativedir);
                                if (!empty($sortfield) && !empty($sortorder)) {	// If $sortfield is for example 'position_name', we will sort on the property 'position_name' (that is concat of position+name)
                                    $fileList = dol_sort_array($fileList, $sortfield, $sortorder);
                                }
                            }
                            require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

                            $formfile = new FormFile($db);

                            $relativepath = dol_sanitizeFileName($project->ref) . '/' . dol_sanitizeFileName($project->ref) . '.pdf';
                            $out .= $formfile->showPreview($fileList, $project->element, $relativepath);
                        }
                        $out .= '</td><td class="right" style="width: 80px;">' . dol_print_date($project->datec, 'day') . '</td>';
                        $out .= '<td class="right" style="min-width: 60px;">' . price($project->budget_amount) . '</td>';
                        $out .= '<td class="right" style="min-width: 60px;" class="nowrap">' . $project->LibStatut($project->fk_statut, 5) . '</td></tr>';
                    }

                    $out .= '</table>';
                    $out .= '</div>';

                    $this->resprints = $out;
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     *  Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadatas (context, etc...)
     * @param  CommonObject $object     The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param  string       $action     Current action (if set). Generally create or edit or null
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function addMoreActionsButtons(array $parameters, CommonObject $object, string $action): int
    {
        global $conf, $db, $langs, $user;

        // Do something only for the current context
        if ($parameters['currentcontext'] == 'thirdpartycomm') {
            print dolGetButtonAction('', $langs->trans('QuickEventCreation'), 'default', dol_buildpath('/easycrm/view/quickevent.php', 1) . '?socid='.$object->id . '&action=create&token='.newToken(), '', 1);
        }

        return 0; // or return 1 to replace standard code
    }
}
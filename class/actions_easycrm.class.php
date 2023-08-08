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
        global $langs, $user;

        // Do something only for the current context
        if (in_array($parameters['currentcontext'], ['thirdpartycomm', 'projectcard'])) {
            if (empty(GETPOST('action')) || GETPOST('action') == 'update') {
                if ($parameters['currentcontext'] == 'thirdpartycomm') {
                    $socid = $object->id;
                    $moreparam = '';
                } else {
                    $socid = $object->socid;
                    $moreparam = '&project_id=' . $object->id;
                }
                $url = '?socid=' . $socid . '&fromtype=' . $object->element . $moreparam . '&action=create&token=' . newToken();
                print dolGetButtonAction('', $langs->trans('QuickEventCreation'), 'default', dol_buildpath('/easycrm/view/quickevent.php', 1) . $url, '', $user->rights->agenda->myactions->create);
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printCommonFooter function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadatas (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printCommonFooter(array $parameters): int
    {
        global $conf, $db, $langs;

        // Do something only for the current context
        if (in_array($parameters['currentcontext'], ['thirdpartycomm', 'projectcard'])) {
            if (isModEnabled('agenda')) {
                require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

                $pictopath = dol_buildpath('/easycrm/img/easycrm_color.png', 1);
                $picto     = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoModule');

                $actiomcomm = new ActionComm($db);

                $filter      = ' AND a.id IN (SELECT c.fk_actioncomm FROM '  . MAIN_DB_PREFIX . 'categorie_actioncomm as c WHERE c.fk_categorie = ' . $conf->global->EASYCRM_ACTIONCOMM_COMMERCIAL_RELAUNCH_TAG . ')';
                $actiomcomms = $actiomcomm->getActions(GETPOST('socid'), ($parameters['currentcontext'] != 'thirdpartycomm' ? GETPOST('id') : ''), ($parameters['currentcontext'] != 'thirdpartycomm' ? 'project' : ''), $filter, 'a.datec');
                if (is_array($actiomcomms) && !empty($actiomcomms)) {
                    $nbActiomcomms  = count($actiomcomms);
                    $lastActiomcomm = array_shift($actiomcomms);
                } else {
                    $nbActiomcomms = 0;
                }

                $out = '<tr><td class="titlefield">' . $picto . $langs->trans('CommercialsRelaunching') . '</td>';
                $out .= '<td>' .'<span class="badge badge-info">' . $nbActiomcomms . '</span>';
                if ($nbActiomcomms > 0) {
                    $out .= ' - ' . '<span>' . $langs->trans('LastCommercialReminderDate') . ' : ' . dol_print_date($lastActiomcomm->datec, 'dayhourtext', 'tzuser') . '</span>';
                    $out .= ' ' . $lastActiomcomm->getNomUrl(1);
                }
                $out .= '</td></tr>';

                ?>
                <script>
                    jQuery('.tableforfield').last().append(<?php echo json_encode($out); ?>)
                </script>
                <?php
            }
        }

        // Do something only for the current context
        if ($parameters['currentcontext'] == 'projectcard') {
            if (empty(GETPOST('action')) || GETPOST('action') == 'update') {
                require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
                require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';

                $project = new Project($db);
                $task    = new Task($db);

                $project->fetch(GETPOST('id'));
                $project->fetch_optionals();

                if (!empty($project->array_options['options_commtask'])) {
                    $task->fetch($project->array_options['options_commtask']);
                    $out2 = $task->getNomUrl(1);
                } ?>

                <script>
                    jQuery('.project_extras_commtask').html(<?php echo json_encode($out2); ?>)
                </script>
                <?php
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printFieldListValue function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadatas (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printFieldListValue(array $parameters): int
    {
        global $conf, $db, $langs, $user;

        // Do something only for the current context
        if ($parameters['currentcontext'] == 'projectlist') {
            if (isModEnabled('agenda') && isModEnabled('project') && $user->hasRight('projet', 'lire') && isModEnabled('saturne')) {
                require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
                require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

                $pictopath = dol_buildpath('/easycrm/img/easycrm_color.png', 1);
                $picto     = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoModule');

                $actiomcomm = new ActionComm($db);

                $filter   = ' AND a.id IN (SELECT c.fk_actioncomm FROM '  . MAIN_DB_PREFIX . 'categorie_actioncomm as c WHERE c.fk_categorie = ' . $conf->global->EASYCRM_ACTIONCOMM_COMMERCIAL_RELAUNCH_TAG . ')';
                if (is_object($parameters['obj']) && !empty($parameters['obj'])) {
                    if (!empty($parameters['obj']->id)) {
                        $actiomcomms = $actiomcomm->getActions($parameters['obj']->socid, $parameters['obj']->id, 'project', $filter, 'a.datec');
                        if (is_array($actiomcomms) && !empty($actiomcomms)) {
                            $nbActiomcomms  = count($actiomcomms);
                            $lastActiomcomm = array_shift($actiomcomms);
                        } else {
                            $nbActiomcomms = 0;
                        }

                        $out = $picto . $langs->trans('CommercialsRelaunching');
                        $out .= ' <span class="badge badge-info">' . $nbActiomcomms . '</span>';
                        if ($nbActiomcomms > 0) {
                            $out .= '<br><span>' . dol_print_date($lastActiomcomm->datec, 'dayhourtext', 'tzuser') . '</span>';
                            $out .= ' ' . $lastActiomcomm->getNomUrl(1);
                        }
                        $url = '?socid=' . $parameters['obj']->socid . '&fromtype=project' . '&project_id=' . $parameters['obj']->id . '&action=create&token=' . newToken();
                        if ($user->hasRight('agenda', 'myactions', 'create')) {
                            $out .= '<a href="' . dol_buildpath('/easycrm/view/quickevent.php', 1) . $url . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('QuickEventCreation') . '"></span></a>';
                        }
                    } ?>
                    <script>
                        var outJS = <?php echo json_encode($out); ?>;
                        var commRelauchCell = $('.liste > tbody > tr.oddeven').find('td[data-key="projet.commrelaunch"]').last();
                        commRelauchCell.addClass('minwidth300');
                        commRelauchCell.append(outJS);
                    </script>
                    <?php
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the formConfirm hook
     *
     * @param  array        $parameters Hook metadatas (context, etc...)
     * @param  CommonObject $object
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function formConfirm(array $parameters, CommonObject $object): int
    {
        if ($parameters['currentcontext'] == 'propalcard') {
            if (empty($object->thirdparty->id)) {
                $object->fetch_thirdparty();
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the completeTabsHead function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadatas (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function completeTabsHead(array $parameters): int
    {
        global $hookmanager, $langs;

        if (in_array($parameters['currentcontext'], ['invoicereccard', 'invoicereccontact'])) {
            $nbContact = 0;
            // Enable caching of thirdrparty count Contacts
            require_once DOL_DOCUMENT_ROOT . '/core/lib/memory.lib.php';
            $cacheKey      = 'count_contacts_thirdparty_' . $parameters['object']->id;
            $dataRetrieved = dol_getcache($cacheKey);

            if (!is_null($dataRetrieved)) {
                $nbContact = $dataRetrieved;
            } else {
                $sql  = "SELECT COUNT(p.rowid) as nb";
                $sql .= " FROM " . MAIN_DB_PREFIX . "socpeople as p";
                $sql .= " WHERE p.fk_soc = " . $parameters['object']->socid;
                $resql = $this->db->query($sql);
                if ($resql) {
                    $obj       = $this->db->fetch_object($resql);
                    $nbContact = $obj->nb;
                }

                dol_setcache($cacheKey, $nbContact, 120); // If setting cache fails, this is not a problem, so we do not test result
            }
            $parameters['head'][1][0] = DOL_URL_ROOT . '/custom/easycrm/view/contact.php?id=' . $parameters['object']->id;
            $parameters['head'][1][1] = $langs->trans('ContactsAddresses');
            if ($nbContact > 0) {
                $parameters['head'][1][1] .= '<span class="badge marginleftonlyshort">' . $nbContact . '</span>';
            }
            $parameters['head'][1][2] = 'contact';

            $this->results = $parameters;
        }

        return 0; // or return 1 to replace standard code
    }
}

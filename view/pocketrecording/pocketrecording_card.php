<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    view/pocketrecording/pocketrecording_card.php
 * \ingroup reedcrm
 * \brief   Card of a Pocket recording: summary, action items, transcript and linked objects.
 */

// Load ReedCRM environment
if (file_exists('../../reedcrm.main.inc.php')) {
    require_once __DIR__ . '/../../reedcrm.main.inc.php';
} elseif (file_exists('../../../reedcrm.main.inc.php')) {
    require_once __DIR__ . '/../../../reedcrm.main.inc.php';
} else {
    die('Include of reedcrm main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/parsemd.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

// Load ReedCRM libraries
require_once __DIR__ . '/../../class/pocketrecording.class.php';
require_once __DIR__ . '/../../class/pocketactionitem.class.php';
require_once __DIR__ . '/../../class/pocketapi.class.php';
require_once __DIR__ . '/../../class/pocketsync.class.php';
require_once __DIR__ . '/../../lib/reedcrm_pocketrecording.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['agenda']);

// Get parameters
$id      = GETPOSTINT('id');
$action  = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$show    = GETPOST('show', 'aZ09') ?: 'card';

// Initialize technical objects
$object      = new PocketRecording($db);
$form        = new Form($db);
$contextpage = 'pocketrecordingcard';
$hookmanager->initHooks([$contextpage]);

// Security check
$permissiontoread    = $user->hasRight('reedcrm', 'pocketrecording', 'read');
$permissiontoadd     = $user->hasRight('reedcrm', 'pocketrecording', 'write');
$permissiontodelete  = $user->hasRight('reedcrm', 'pocketrecording', 'delete');

saturne_check_access($permissiontoread);

if ($id > 0 && $object->fetch($id) <= 0) {
    accessforbidden($langs->trans('ErrorRecordNotFound'));
}

/*
 * Actions
 */

if ($action == 'set_status' && $permissiontoadd) {
    $object->status = GETPOSTINT('status');
    if ($object->update($user) > 0) {
        setEventMessage($langs->trans('SavedConfig'));
    } else {
        setEventMessages($object->error, $object->errors, 'errors');
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
    exit;
}

// The thirdparty is set from the banner, as on every Saturne card. The template writes the field
// the banner form names and reloads the object, so the view below already shows the new value.
require_once __DIR__ . '/../../../saturne/core/tpl/actions/banner_actions.tpl.php';

// Pocket writes the summary, the user owns it afterwards: emptying it hands it back to Pocket,
// which fills it again on the next synchronisation
if ($action == 'set_summary' && $permissiontoadd) {
    // restricthtml encodes the ampersands and the quotes of the markdown, which the card escapes
    // again when it prints it: without the decoding, an edit saved twice would store its own source
    $summary = htmlspecialchars_decode(GETPOST('summary', 'restricthtml'), ENT_QUOTES);

    $object->summary        = trim($summary);
    $object->summary_edited = $object->summary !== '' ? 1 : 0;

    if ($object->update($user) > 0) {
        setEventMessage($langs->trans('SavedConfig'));
    } else {
        setEventMessages($object->error, $object->errors, 'errors');
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
    exit;
}

// Attaching from the card: the objects offered are the ones of the thirdparty of the recording
if ($action == 'link_object' && $permissiontoadd) {
    // The selector carries 'linkName:objectId', the empty option of Dolibarr carries -1 and means
    // the form was submitted without a choice: nothing to attach, and nothing to complain about
    list($linkName, $linkedObjectId) = array_pad(explode(':', GETPOST('object_to_link', 'alphanohtml'), 2), 2, '');

    if (!empty($linkName) && (int) $linkedObjectId > 0) {
        if (reedcrm_pocket_link_recording($object, $linkName, (int) $linkedObjectId) > 0) {
            setEventMessage($langs->trans('PocketObjectLinked'));
        } else {
            setEventMessages($langs->trans('PocketRecordingLinkFailed'), [], 'errors');
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
    exit;
}

if ($action == 'unlink_object' && $permissiontoadd) {
    $linkName       = GETPOST('link_name', 'alphanohtml');
    $linkedObjectId = GETPOSTINT('linked_object_id');

    if (!empty($linkName) && $linkedObjectId > 0 && reedcrm_pocket_unlink_recording($object, $linkName, $linkedObjectId) > 0) {
        setEventMessage($langs->trans('PocketObjectUnlinked'));
    } else {
        setEventMessages($langs->trans('PocketRecordingLinkFailed'), [], 'errors');
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
    exit;
}

if ($action == 'refresh_from_pocket' && $permissiontoadd) {
    $pocketSync = new PocketSync($db);
    $recording  = $pocketSync->api->getRecording($object->pocket_id);

    if ($recording === null) {
        setEventMessages($pocketSync->api->error, [], 'errors');
    } else {
        $result = $pocketSync->importRecording(
            $recording['data'] ?? [],
            $user,
            (string) $object->pocket_folder_id,
            (string) $object->pocket_folder_label
        );
        if ($result < 0) {
            setEventMessages($langs->trans('PocketRefreshFailed'), [], 'errors');
        } else {
            setEventMessage($langs->trans('PocketRefreshDone'));
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
    exit;
}

// The signed audio URL expires quickly, it is resolved on demand and never stored
if ($action == 'play_audio' && $permissiontoread) {
    $pocketApi = new PocketApi($db);
    $audioUrl  = $pocketApi->getAudioUrl($object->pocket_id);

    if (empty($audioUrl)) {
        setEventMessages($pocketApi->error ?: $langs->trans('PocketAudioUnavailable'), [], 'errors');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
    } else {
        header('Location: ' . $audioUrl);
    }
    exit;
}

if ($action == 'confirm_delete' && $confirm == 'yes' && $permissiontodelete) {
    if ($object->delete($user) > 0) {
        setEventMessage($langs->trans('RecordDeleted'));
        header('Location: ' . dol_buildpath('/custom/reedcrm/view/pocketrecording/pocketrecording_list.php', 1));
        exit;
    }

    setEventMessages($object->error, $object->errors, 'errors');
}

/*
 * View
 */

$title    = $langs->trans('PocketRecording') . ' - ' . $object->ref;
$help_url = 'FR:Module_ReedCRM';

saturne_header(0, '', $title, $help_url);

$head = pocketrecording_prepare_head($object);
print dol_get_fiche_head($head, $show == 'transcript' ? 'transcript' : 'card', $langs->trans('PocketRecording'), -1, $object->picto);

$moreHtmlRef = '<div class="refidno">';
$moreHtmlRef .= $langs->trans('PocketRecordingDate') . ': ' . dol_print_date($object->recording_date, 'dayhour');
if (!empty($object->duration)) {
    $moreHtmlRef .= ' - ' . $langs->trans('Duration') . ': ' . reedcrm_pocket_format_duration((int) $object->duration);
}
if (!empty($object->pocket_folder_label)) {
    $moreHtmlRef .= '<br>' . $langs->trans('PocketFolder') . ': ' . dol_escape_htmltag($object->pocket_folder_label);
}
$moreHtmlRef .= '</div>';

$linkBack = '<a href="' . dol_buildpath('/custom/reedcrm/view/pocketrecording/pocketrecording_list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';
saturne_banner_tab($object, 'id', $linkBack, 1, 'rowid', 'ref', $moreHtmlRef);

if ($action == 'delete') {
    print $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('DeletePocketRecording'), $langs->trans('ConfirmDeletePocketRecording'), 'confirm_delete', '', 'no', 1);
}

print '<div class="fichecenter">';

if ($show == 'transcript') {
    print '<div class="underbanner clearboth"></div>';
    // The block keeps the original line breaks through white-space: pre-wrap, so the raw text is
    // escaped and printed as is instead of being converted to <br>
    print '<div class="reedcrm-pocket-transcript">';
    print !empty($object->transcript) ? dol_escape_htmltag($object->transcript) : '<span class="opacitymedium">' . $langs->trans('PocketNoTranscript') . '</span>';
    print '</div>';
} else {
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">';

    print '<tr><td class="titlefield">' . $langs->trans('Status') . '</td><td>';
    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="set_status">';
    print $form->selectarray('status', $object->fields['status']['arrayofkeyval'], $object->status, 0, 0, 0, '', 1);
    if ($permissiontoadd) {
        print ' <input type="submit" class="button smallpaddingimp" value="' . $langs->trans('Modify') . '">';
    }
    print '</form>';
    print '</td></tr>';

    print '<tr><td>' . $langs->trans('PocketTags') . '</td><td>' . dol_escape_htmltag($object->pocket_tags) . '</td></tr>';

    // The URL Pocket signs expires within the hour, so the player carries the endpoint that
    // resolves it rather than the URL itself: the native <audio> is injected on the first play
    print '<tr><td>' . $langs->trans('PocketAudio') . '</td><td>';
    print '<div class="reedcrm-pocket-audio" data-url="' . dol_escape_htmltag(dol_buildpath('/custom/reedcrm/ajax/pocket_audio_url.php', 1) . '?id=' . $object->id) . '">';
    print '<span class="reedcrm-pocket-audio-load"><i class="fas fa-play"></i>' . $langs->trans('PocketPlayAudio') . '</span>';
    print '</div>';
    print '</td></tr>';

    print '<tr><td>' . $langs->trans('PocketLastSyncDate') . '</td><td>' . dol_print_date($object->last_sync_date, 'dayhour') . '</td></tr>';

    print '</table>';

    // Summary. Pocket writes markdown, so the edition happens on that source and not on the
    // rendered HTML: the blocks the summary embeds would not survive a round trip through an editor
    print '<br>';
    $summaryTitleButton = '';
    if ($permissiontoadd && $action != 'edit_summary') {
        $summaryTitleButton = dolGetButtonTitle($langs->trans('Modify'), '', 'fa fa-pencil-alt', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit_summary&token=' . newToken());
    }
    print load_fiche_titre($langs->trans('PocketSummary'), $summaryTitleButton, '');
    print '<div class="underbanner clearboth"></div>';

    if ($action == 'edit_summary' && $permissiontoadd) {
        print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="set_summary">';
        print '<textarea class="reedcrm-pocket-summary-edit" name="summary" rows="20">' . dol_escape_htmltag((string) $object->summary, 0, 1) . '</textarea>';
        print '<div class="center">';
        print '<input type="submit" class="button" value="' . $langs->trans('Save') . '">';
        print ' <a class="button button-cancel" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">' . $langs->trans('Cancel') . '</a>';
        print '</div>';
        print '<div class="opacitymedium small">' . $langs->trans('PocketSummaryEditHelp') . '</div>';
        print '</form>';
    } else {
        print '<div class="reedcrm-pocket-summary">';
        print !empty($object->summary) ? reedcrm_pocket_summary_to_html($object->summary) : '<span class="opacitymedium">' . $langs->trans('PocketNoSummary') . '</span>';
        print '</div>';
        if (!empty($object->summary_edited)) {
            print '<div class="opacitymedium small">' . $langs->trans('PocketSummaryEditedHint') . '</div>';
        }
    }

    // Action items. Read from their own rows and not from the recording JSON: the assigned user
    // and the created event belong to Dolibarr and must survive a re-import from Pocket.
    $actionItemStatic = new PocketActionItem($db);
    $actionItems      = $actionItemStatic->fetchAllByRecording($object->id);
    $actionItemUrl    = dol_buildpath('/custom/reedcrm/ajax/pocket_action_item.php', 1);
    $eventStatic      = new ActionComm($db);

    print '<br>';
    print load_fiche_titre($langs->trans('PocketActionItems'), '', '');
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans('Label') . '</td>';
    print '<td class="center">' . $langs->trans('Deadline') . '</td>';
    print '<td class="center">' . $langs->trans('Priority') . '</td>';
    print '<td>' . $langs->trans('PocketAssignedUser') . '</td>';
    print '<td class="center">' . $langs->trans('Event') . '</td>';
    print '</tr>';

    if (is_array($actionItems)) {
        foreach ($actionItems as $actionItem) {
            print '<tr class="oddeven pocket-action-row" data-action-item-id="' . $actionItem->id . '"';
            print ' data-url="' . dol_escape_htmltag($actionItemUrl) . '" data-token="' . newToken() . '">';

            // The wording extracted by Pocket is rarely usable as is, so both the label and its
            // description are edited in place: each field saves itself when it loses the focus
            print '<td class="pocket-action-text">';
            if ($permissiontoadd) {
                print '<input type="text" class="pocket-action-label" value="' . dol_escape_htmltag((string) $actionItem->label) . '" placeholder="' . dol_escape_htmltag($langs->trans('Label')) . '">';
                print '<textarea class="pocket-action-description" rows="2" placeholder="' . dol_escape_htmltag($langs->trans('Description')) . '">' . dol_escape_htmltag((string) $actionItem->description) . '</textarea>';
            } else {
                print dol_escape_htmltag((string) $actionItem->label);
                if (!empty($actionItem->description)) {
                    print '<br><span class="opacitymedium small">' . dol_escape_htmltag($actionItem->description) . '</span>';
                }
            }
            print '</td>';

            print '<td class="center nowraponall">';
            if ($permissiontoadd) {
                print '<input type="date" class="flat pocket-action-due-date" value="' . (!empty($actionItem->due_date) ? dol_print_date($actionItem->due_date, '%Y-%m-%d') : '') . '">';
            } else {
                print !empty($actionItem->due_date) ? dol_print_date($actionItem->due_date, 'day') : '';
            }
            print '</td>';
            // Pocket rates the urgency of each action in its own English wording, which reads as
            // raw data on the card: it is translated, and owned by the user like the rest of the row
            print '<td class="center nowraponall">';
            if ($permissiontoadd) {
                $priorities = [];
                foreach ($actionItem->fields['priority']['arrayofkeyval'] as $priorityKey => $priorityLabel) {
                    $priorities[$priorityKey] = $langs->trans($priorityLabel);
                }
                // A wording Pocket sends and the module does not know stays selectable, so opening
                // the list never silently drops the priority it came with
                $currentPriority = strtolower(trim((string) $actionItem->priority));
                if ($currentPriority !== '' && !isset($priorities[$currentPriority])) {
                    $priorities[$currentPriority] = $currentPriority;
                }
                print $form->selectarray('priority_' . $actionItem->id, $priorities, $currentPriority, 1, 0, 0, '', 0, 0, 0, '', 'pocket-action-priority minwidth75');
            } else {
                print dol_escape_htmltag($actionItem->getPriorityLabel());
            }
            print '</td>';

            print '<td>';
            if ($permissiontoadd) {
                print $form->select_dolusers($actionItem->fk_user_assign, 'fk_user_assign_' . $actionItem->id, 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'pocket-action-assign minwidth150');
            } elseif ($actionItem->fk_user_assign > 0) {
                $assignedUser = new User($db);
                $assignedUser->fetch($actionItem->fk_user_assign);
                print $assignedUser->getNomUrl(1);
            }
            // Pocket also names an assignee, kept as a hint since it is free text, not a Dolibarr user
            if (!empty($actionItem->pocket_assignee)) {
                print '<br><span class="opacitymedium small">' . $langs->trans('PocketAssignee') . ': ' . dol_escape_htmltag($actionItem->pocket_assignee) . '</span>';
            }
            print '</td>';

            print '<td class="center">';
            if ($actionItem->fk_actioncomm > 0 && $eventStatic->fetch($actionItem->fk_actioncomm) > 0) {
                print $eventStatic->getNomUrl(1);
            } elseif ($permissiontoadd && isModEnabled('agenda')) {
                print '<span class="butAction butActionSmall pocket-action-create-event" data-created-label="' . dol_escape_htmltag($langs->trans('Event')) . '">';
                print $langs->trans('PocketCreateEvent');
                print '</span>';
            }
            print '</td>';

            print '</tr>';
        }
    }

    if (empty($actionItems)) {
        print '<tr><td colspan="5" class="opacitymedium center">' . $langs->trans('PocketNoActionItem') . '</td></tr>';
    }

    print '</table>';
    print '</div>';
}

print '</div>';

print dol_get_fiche_end();

/*
 * Action bar
 */

print '<div class="tabsAction">';

if ($permissiontoadd) {
    print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=refresh_from_pocket&token=' . newToken() . '">' . $langs->trans('PocketRefreshFromApi') . '</a>';
}
print '<a class="butAction" target="_blank" rel="noopener" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=play_audio&token=' . newToken() . '">' . $langs->trans('PocketDownloadAudio') . '</a>';
if ($permissiontodelete) {
    print '<a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete&token=' . newToken() . '">' . $langs->trans('Delete') . '</a>';
}

print '</div>';

/*
 * Linked objects
 */

// A recording is attached either from the tab of the business object, or from here: the
// conversation was held with a thirdparty, so what it talks about is one of its objects
if ($show != 'transcript') {
    $object->fetchObjectLinked();

    print load_fiche_titre($langs->trans('PocketLinkedObjects'), '', '');

    if ($permissiontoadd) {
        print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="link_object">';
        print '<div class="reedcrm-pocket-link-form">';

        if ($object->fk_soc <= 0) {
            print '<span class="opacitymedium">' . $langs->trans('PocketLinkNeedsThirdParty') . '</span>';
        } else {
            $linkableThirdPartyObjects = reedcrm_pocket_get_thirdparty_objects($object);

            if (empty($linkableThirdPartyObjects)) {
                print '<span class="opacitymedium">' . $langs->trans('PocketNoObjectToLink') . '</span>';
            } else {
                // One flat list rather than one selector per type: the user looks for the object
                // they talked about, they do not know beforehand whether it is a proposal or a ticket
                $linkChoices = [];
                foreach ($linkableThirdPartyObjects as $linkableObject) {
                    $choice = $linkableObject['type_label'] . ' - ' . $linkableObject['ref'];
                    if (!empty($linkableObject['date'])) {
                        $choice .= ' - ' . dol_print_date($linkableObject['date'], 'day');
                    }
                    if ($linkableObject['amount'] !== null) {
                        $choice .= ' - ' . price($linkableObject['amount'], 0, $langs, 0, -1, -1, $conf->currency);
                    }

                    $linkChoices[$linkableObject['key']] = $choice;
                }

                print '<span>' . $langs->trans('PocketLinkObject') . '</span>';
                print $form->selectarray('object_to_link', $linkChoices, '', 1, 0, 0, '', 0, 0, 0, '', 'minwidth300', 1);
                print '<input type="submit" class="button smallpaddingimp" value="' . $langs->trans('Add') . '">';
            }
        }

        print '</div>';
        print '</form>';
    }

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans('Type') . '</td>';
    print '<td>' . $langs->trans('Ref') . '</td>';
    print '<td class="center">' . $langs->trans('Date') . '</td>';
    print '<td class="right">' . $langs->trans('Amount') . '</td>';
    print '<td class="center"></td>';
    print '</tr>';

    $linkedCount = 0;
    foreach ($object->linkedObjects as $linkedType => $linkedInstances) {
        $linkedMetadata = reedcrm_pocket_get_object_metadata_from_link_name($linkedType);
        $typeLabel      = !empty($linkedMetadata['langs']) ? $langs->trans($linkedMetadata['langs']) : $linkedType;

        foreach ($linkedInstances as $linkedInstance) {
            $linkedData = reedcrm_pocket_get_object_date_and_amount($linkedInstance);

            print '<tr class="oddeven">';
            print '<td class="minwidth100">' . dol_escape_htmltag($typeLabel) . '</td>';
            print '<td>' . $linkedInstance->getNomUrl(1) . '</td>';
            print '<td class="center nowraponall">' . (!empty($linkedData['date']) ? dol_print_date($linkedData['date'], 'day') : '') . '</td>';
            print '<td class="right nowraponall">' . ($linkedData['amount'] !== null ? price($linkedData['amount'], 0, $langs, 1, -1, -1, $conf->currency) : '') . '</td>';
            print '<td class="center">';
            if ($permissiontoadd) {
                print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=unlink_object&link_name=' . urlencode($linkedType) . '&linked_object_id=' . $linkedInstance->id . '&token=' . newToken() . '" title="' . dol_escape_htmltag($langs->trans('PocketUnlinkObject')) . '">';
                print img_picto($langs->trans('PocketUnlinkObject'), 'unlink');
                print '</a>';
            }
            print '</td>';
            print '</tr>';
            $linkedCount++;
        }
    }

    if ($linkedCount == 0) {
        print '<tr><td colspan="5" class="opacitymedium center">' . $langs->trans('PocketNoLinkedObject') . '</td></tr>';
    }

    print '</table>';
    print '</div>';
    print '<div class="opacitymedium small">' . $langs->trans('PocketLinkFromObjectHint') . '</div>';
}

llxFooter();
$db->close();

<?php

// Quick add project/task
if ($permissiontoaddproject) {
    print load_fiche_titre($langs->trans('QuickProjectCreation'), '', 'project');

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldcreate">';

    $defaultref = '';
    $modele = empty($conf->global->PROJECT_ADDON) ? 'mod_project_simple' : $conf->global->PROJECT_ADDON;

    // Search template files
    $file = '';
    $classname = '';
    $filefound = 0;
    $dirmodels = array_merge(['/'], $conf->modules_parts['models']);
    foreach ($dirmodels as $reldir) {
        $file = dol_buildpath($reldir . 'core/modules/project/' . $modele . '.php');
        if (file_exists($file)) {
            $filefound = 1;
            $classname = $modele;
            break;
        }
    }

    if ($filefound) {
        $result = dol_include_once($reldir . 'core/modules/project/' . $modele . '.php');
        $modProject = new $classname();

        $defaultref = $modProject->getNextValue($thirdparty, $project);
    }

    if (is_numeric($defaultref) && $defaultref <= 0) {
        $defaultref = '';
    }

    // Ref
    $suggestedref = (GETPOST('ref') ? GETPOST('ref') : $defaultref);
    print '<input type="hidden" name="ref" value="' . dol_escape_htmltag($suggestedref) . '">';

    // Label
    if ($conf->global->EASYCRM_PROJECT_LABEL_VISIBLE > 0) {
        print '<tr><td class="titlefieldcreate fieldrequired"><label for="title">' . $langs->trans('ProjectLabel') . '</label></td>';
        print '<td><input type="text" name="title" id="title" class="maxwidth500 widthcentpercentminusx" maxlength="255" value="' . dol_escape_htmltag((GETPOSTISSET('title') ? GETPOST('title') : '')) . '"></td>';
        print '</tr>';
    }

    // Description
    if (isModEnabled('fckeditor')) {
        print '<tr><td class="titlefieldcreate">' . $langs->trans('Description') . '</td>';
        print '<td>';
        $dolEditor = new DolEditor('description', GETPOST('description', 'restricthtml'), '', 90, 'dolibarr_details', '', false, true, getDolGlobalString('FCKEDITOR_ENABLE_SOCIETE'), ROWS_3, '90%');
        $dolEditor->Create();
        print '</td></tr>';
    }

    print '<tr class="linked-medias photo gallery-table"><td>' . $langs->trans('Photo') . '</td><td class="linked-medias-list">'; ?>
    <input hidden multiple class="fast-upload" id="fast-upload-photo-default" type="file" name="userfile[]" capture="environment" accept="image/*">
    <label for="fast-upload-photo-default">
        <div class="wpeo-button button-square-50">
            <i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
        </div>
    </label>
    <input type="hidden" class="favorite-photo" id="photo" name="photo" value="<?php echo GETPOST('favorite_photo') ?>"/>
    <div class="wpeo-button button-square-50 open-media-gallery add-media modal-open" value="0">
        <input type="hidden" class="modal-options" data-modal-to-open="media_gallery" data-from-id="<?php echo 0 ?>" data-from-type="project" data-from-subtype="photo" data-from-subdir="project_photos"/>
        <i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>
    </div>
    <?php
    print saturne_show_medias_linked('easycrm', $conf->easycrm->multidir_output[$conf->entity] . '/project/tmp/0/project_photos', 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, 'project/tmp/0/project_photos', $project, '', 0, $permissiontoaddproject);
    print '</td></tr>';

//    // Other attributes.
//    if ($conf->global->EASYCRM_PROJECT_EXTRAFIELDS_VISIBLE > 0) {
//        $object = $project;
//        $extrafields->fetch_name_optionals_label($object->table_element);
//        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';
//        $object = '';
//    }

    // Categories
    if (isModEnabled('categorie') && $conf->global->EASYCRM_PROJECT_CATEGORIES_VISIBLE > 0) {
        print '<tr><td>' . $langs->trans('Categories') . '</td><td>';
        $cate_arbo = $form->select_all_categories(Categorie::TYPE_PROJECT, '', 'parent', 64, 0, 1);
        print img_picto('', 'category', 'class="pictofixedwidth"') . $form->multiselectarray('categories_project', $cate_arbo, GETPOST('categories_project', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx');
        print '</td></tr>';
    }

    print '</table>';

    print dol_get_fiche_end();
}

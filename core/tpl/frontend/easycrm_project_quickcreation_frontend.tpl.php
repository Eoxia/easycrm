<?php

// Quick add project/task
if ( !$permissiontoaddproject) {
    exit;
}

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



// File start
?>
<div class="project-container">
    <div class="page-header">
        <div class="page-title">Opportunités</div>
    </div>

    <div class="page-content">

        <!-- Nom du projet -->
        <label for="firstname">
            <?php echo $langs->trans('ProjectLabel'); ?>
            <input type="text" id="firstname" name="firstname" placeholder="First name" value="<?php echo dol_escape_htmltag((GETPOSTISSET('title') ? GETPOST('title') : '')); ?>" required>
        </label>
        
        <!-- Desctipion du projet -->
        <label for="content">
            <?php echo $langs->trans('Description'); ?>
            <textarea id="content"><?php echo dol_escape_htmltag((GETPOSTISSET('description') ? GETPOST('description', 'restricthtml') : '')); ?></textarea>
        </label>
        
        <!-- Categories -->
        <?php if (isModEnabled('categorie') && $conf->global->EASYCRM_PROJECT_CATEGORIES_VISIBLE > 0) { ?>
            <label for="categories-project">
                <?php echo $langs->trans('Categories'); ?>
                <?php $cate_arbo = $form->select_all_categories(Categorie::TYPE_PROJECT, '', 'parent', 64, 0, 1); ?>
                <?php print img_picto('', 'category', 'class="pictofixedwidth"') . $form->multiselectarray('categories_project', $cate_arbo, GETPOST('categories_project', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx'); ?>
            </label>
        <?php } ?>
        
        <!-- Photos -->
        <input hidden multiple class="fast-upload" id="fast-upload-photo-default" type="file" name="userfile[]" capture="environment" accept="image/*">
        <label class="linked-medias" for="fast-upload-photo-default">
            <div class="linked-medias-list">

                <div class="wpeo-button button-square-50">
                    <i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
                </div>

                <?php print saturne_show_medias_linked('easycrm', $conf->easycrm->multidir_output[$conf->entity] . '/project/tmp/0/project_photos', 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, 'project/tmp/0/project_photos', $project, '', 0, $permissiontoaddproject); ?>
            </div>
        </label>
    </div>

    <div class="page-footer">
        <button type="submit"><?php echo $langs->trans('CreateProject'); ?></button>
    </div>
</div>
<?php
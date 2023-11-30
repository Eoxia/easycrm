<?php

// Quick add project/task
if ( !$permissiontoaddproject) {
    exit;
}

require_once __DIR__ . '/easycrm_media_editor_frontend.tpl.php';

// File start
?>
<div class="project-container">
    <div class="page-header">
        <div class="page-title"><?php echo $langs->trans('Opportunity'); ?></div>
    </div>

    <div class="page-content">
        <!-- Project label -->
        <label for="title">
            <?php echo $langs->trans('ProjectLabel'); ?>
            <input type="text" id="title" name="title" placeholder="<?php echo $langs->trans('ProjectLabel'); ?>" value="<?php echo dol_escape_htmltag((GETPOSTISSET('title') ? GETPOST('title') : '')); ?>" required>
        </label>

        <!-- Description -->
        <?php if ($conf->global->EASYCRM_PROJECT_DESCRIPTION_VISIBLE > 0) : ?>
            <label for="description">
                <?php echo $langs->trans('Description'); ?>
                <textarea id="description"><?php echo dol_escape_htmltag((GETPOSTISSET('description') ? GETPOST('description', 'restricthtml') : '')); ?></textarea>
            </label>
        <?php endif; ?>

        <!-- Categories -->
        <?php if (isModEnabled('categorie') && $conf->global->EASYCRM_PROJECT_CATEGORIES_VISIBLE > 0) : ?>
            <label for="categories-project">
                <?php echo $langs->trans('Categories'); ?>
                <?php $cateArbo = $form->select_all_categories(Categorie::TYPE_PROJECT, '', 'parent', 64, 0, 1); ?>
                <?php print img_picto('', 'category', 'class="pictofixedwidth"') . $form->multiselectarray('categories_project', $cateArbo, GETPOST('categories_project', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx'); ?>
            </label>
        <?php endif; ?>

        <!-- Photos -->
        <input hidden multiple id="upload-image" type="file" name="userfile[]" capture="environment" accept="image/*">
        <label class="linked-medias" for="upload-image">
            <div class="linked-medias-list">
                <div class="wpeo-button button-square-50">
                    <i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
                </div>
                <?php print saturne_show_medias_linked('easycrm', $conf->easycrm->multidir_output[$conf->entity] . '/project/tmp/0/project_photos', 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, 'project/tmp/0/project_photos', $project, '', 0, 1, 0, 0, ''); ?>
            </div>
        </label>
    </div>

    <div class="page-footer">
        <button type="submit"><?php echo $langs->trans('Save'); ?></button>
    </div>
</div>
<?php

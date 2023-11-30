<?php

// Protection to avoid direct call of template
if (!$permissiontoaddproject && empty($conf) || !is_object($conf)) {
    exit;
} ?>

<!-- File start-->
<div class="modal-upload-image" value="0">
    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
    <div class="wpeo-modal modal-upload-image" id="modal-upload-image0">
        <div class="modal-container wpeo-modal-event">
            <!-- Modal-Header-->
            <div class="modal-header">
                <h2 class="modal-title"><?php echo $langs->trans('Image'); ?></h2>
                <div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
            </div>
            <!-- Modal-ADD Image Content-->
            <div class="modal-content" id="#modalContent" style="height: 75%;">
                <div class="canvas-container">
                    <canvas id="canvas" style="border: #0b419b solid 2px"></canvas>
                </div>
            </div>
            <!-- Modal-Footer-->
            <div class="modal-footer">
                <div class="image-rotate-left wpeo-button button-grey button-square-50">
                    <span><i class="fas fa-undo-alt"></i></span>
                </div>
                <div class="image-rotate-right wpeo-button button-grey button-square-50">
                    <span><i class="fas fa-redo-alt"></i></span>
                </div>
                <div class="image-undo wpeo-button button-grey button-square-50">
                    <span><i class="fas fa-undo-alt"></i></span>
                </div>
                <div class="image-erase wpeo-button button-grey button-square-50">
                    <span><i class="fas fa-eraser"></i></span>
                </div>
                <div class="image-validate wpeo-button button-primary button-square-50" value="0">
                    <span><i class="fas fa-check"></i></span>
                </div>
            </div>
        </div>
    </div>
</div>

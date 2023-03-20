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
 * 	\defgroup   easycrm     Module EasyCRM
 *  \brief      EasyCRM module descriptor.
 *
 *  \file       core/modules/modEasyCRM.class.php
 *  \ingroup    easycrm
 *  \brief      Description and activation file for module EasyCRM
 */

require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module EasyCRM
 */
class modEasyCRM extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;
		$this->db = $db;

        if (file_exists(__DIR__ . '/../../../saturne/lib/saturne_functions.lib.php')) {
            require_once __DIR__ . '/../../../saturne/lib/saturne_functions.lib.php';
            saturne_load_langs(['easycrm@easycrm']);
        } else {
            $this->error++;
            $this->errors[] = $langs->trans('activateModuleDependNotSatisfied', 'EasyCRM', 'Saturne');
        }

        // ID for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used module id).
        $this->numero = 436351;

        // Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'easycrm';

        // Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
        // It is used to group modules by family in module setup page
		$this->family = '';

        // Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '';

        // Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        $this->familyinfo = ['Eoxia' => ['position' => '01', 'label' => 'Eoxia']];
        // Module label (no space allowed), used if translation string 'ModulePriseoName' not found (Priseo is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

        // Module description, used if translation string 'ModulePriseoDesc' not found (Priseo is name of module).
        $this->description = $langs->trans('EasyCRMDescription');
        // Used only if file README.md and README-LL.md not found.
        $this->descriptionlong = $langs->trans('EasyCRMDescriptionLong');

        // Author
		$this->editor_name = 'Eoxia';
		$this->editor_url = 'https://www.eoxia.com';

        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0.0';

        // Url to the file with your last numberversion of this module
        //$this->url_last_version = 'http://www.example.com/versionmodule.txt';

        // Key used in llx_const table to save module status enabled/disabled (where EASYCRM is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        // To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'easycrm_color@easycrm';

        // Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = [
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 0,
			// Set this to 1 if module has its own login method file (core/login)
			'login' => 0,
			// Set this to 1 if module has its own substitution function file (core/substitutions)
			'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory (core/menus)
			'menus' => 0,
			// Set this to 1 if module overwrite template dir (core/tpl)
			'tpl' => 0,
			// Set this to 1 if module has its own barcode directory (core/modules/barcode)
			'barcode' => 0,
			// Set this to 1 if module has its own models' directory (core/modules/xxx)
			'models' => 0,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => [],
			// Set this to relative path of js file if module must load a js on all pages
			'js' => [],
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => [
                'thirdpartycomm',
                'projectcard',
                'projectlist',
            ],
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
        ];

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/easycrm/temp","/easycrm/subdir");
		$this->dirs = ['/easycrm/temp'];

		// Config pages. Put here list of php page, stored into easycrm/admin directory, to use to set up module.
		$this->config_page_url = ['setup.php@easycrm'];

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = ['modSaturne', 'modFckeditor'];
		$this->requiredby = []; // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = []; // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = ['easycrm@easycrm'];

		// Prerequisites
		$this->phpmin = [7, 4]; // Minimum version of PHP required by module
		$this->need_dolibarr_version = [15, 0]; // Minimum version of Dolibarr required by module

		// Messages at activation
		$this->warnings_activation = []; // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = []; // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		//$this->automatic_activation = array('FR'=>'EasyCRMWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('EASYCRM_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('EASYCRM_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
        $i = 0;
		$this->const = [
            // CONST CONFIGURATION
            // CONST THIRDPARTY
            $i++ => ['EASYCRM_THIRDPARTY_CLIENT_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_THIRDPARTY_CLIENT_VALUE', 'integer', 2, '', 0, 'current'],
            $i++ => ['EASYCRM_THIRDPARTY_NAME_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_THIRDPARTY_PHONE_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_THIRDPARTY_EMAIL_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_THIRDPARTY_WEB_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_THIRDPARTY_PRIVATE_NOTE_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_THIRDPARTY_CATEGORIES_VISIBLE', 'integer', 1, '', 0, 'current'],

            // CONST CONTACT
            $i++ => ['EASYCRM_CONTACT_LASTNAME_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_CONTACT_FIRSTNAME_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_CONTACT_JOB_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_CONTACT_PHONEPRO_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_CONTACT_EMAIL_VISIBLE', 'integer', 1, '', 0, 'current'],

            // CONST PROJECT
            $i++ => ['EASYCRM_PROJECT_LABEL_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_PROJECT_OPPORTUNITY_STATUS_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_PROJECT_OPPORTUNITY_STATUS_VALUE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_PROJECT_OPPORTUNITY_AMOUNT_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_PROJECT_OPPORTUNITY_AMOUNT_VALUE', 'integer', 3000, '', 0, 'current'],
            $i++ => ['EASYCRM_PROJECT_DATE_START_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_PROJECT_CATEGORIES_VISIBLE', 'integer', 1, '', 0, 'current'],

            // CONST EVENT
            $i++ => ['EASYCRM_EVENT_TYPE_CODE_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_EVENT_LABEL_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_EVENT_DATE_START_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_EVENT_DATE_END_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYCRM_EVENT_CATEGORIES_VISIBLE', 'integer', 1, '', 0, 'current'],

            // CONST MODULE
			$i++ => ['EASYCRM_VERSION','chaine', $this->version, '', 0, 'current'],
			$i++ => ['EASYCRM_DB_VERSION', 'chaine', $this->version, '', 0, 'current'],
            $i   => ['EASYCRM_SHOW_PATCH_NOTE', 'integer', 1, '', 0, 'current'],
        ];

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->easycrm) || !isset($conf->easycrm->enabled)) {
			$conf->easycrm = new stdClass();
			$conf->easycrm->enabled = 0;
		}

		// Array to add new pages in new tabs
        $this->tabs   = [];
        // Example:
        // $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@easycrm:$user->rights->othermodule->read:/easycrm/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
        // $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');

		// Dictionaries
		$this->dictionaries = [];

		// Boxes/Widgets
		$this->boxes = [];

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		$this->cronjobs = [];

		// Permissions provided by this module
		$this->rights = [];
		$r = 0;

        /* EASYCRM PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->trans('LireModule', 'EasyCRM');
        $this->rights[$r][4] = 'lire';
        $this->rights[$r][5] = 1;
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->trans('ReadModule', 'EasyCRM');
        $this->rights[$r][4] = 'read';
        $this->rights[$r][5] = 1;
        $r++;

        /* ADMINPAGE PANEL ACCESS PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('ReadAdminPage', 'EasyCRM');
        $this->rights[$r][4] = 'adminpage';
        $this->rights[$r][5] = 'read';

		// Main menu entries to add
		$this->menu = [];
		$r = 0;

		// Add here entries to declare new menus
        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=easycrm', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'     => 'top', // This is a Top menu entry
            'titre'    => 'EasyCRM',
            'prefix'   => '<i class="fas fa-home pictofixedwidth"></i>',
            'mainmenu' => 'easycrm',
            'leftmenu' => '',
            'url'      => '/easycrm/easycrmindex.php', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'    => 'easycrm@easycrm',
            'position' => 1000 + $r,
            'enabled'  => '$conf->easycrm->enabled', // Define condition to show or hide menu entry. Use '$conf->easycrm->enabled' if entry must be visible if module is enabled.
            'perms'    => '$user->rights->easycrm->lire', // Use 'perms'=>'$user->rights->easycrm->myobject->read' if you want your menu with a permission rules
            'target'   => '',
            'user'     => 0, // 0=Menu for internal users, 1=external users, 2=both
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=easycrm', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'     => 'left', // This is a Top menu entry
            'titre'    => $langs->transnoentities('QuickCreation'),
            'prefix'   => '<i class="fas fa-plus-circle pictofixedwidth"></i>',
            'mainmenu' => 'easycrm',
            'leftmenu' => 'quickcreation',
            'url'      => '/easycrm/view/quickcreation.php', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'    => 'easycrm@easycrm',
            'position' => 1000 + $r,
            'enabled'  => '$conf->easycrm->enabled', // Define condition to show or hide menu entry. Use '$conf->easycrm->enabled' if entry must be visible if module is enabled.
            'perms'    => '$user->rights->easycrm->read', // Use 'perms'=>'$user->rights->easycrm->myobject->read' if you want your menu with a permission rules
            'target'   => '',
            'user'     => 0, // 0=Menu for internal users, 1=external users, 2=both
        ];

		// Exports profiles provided by this module
		// $r = 1;
		/* BEGIN MODULEBUILDER EXPORT MYOBJECT */
		/*
		$langs->load("easycrm@easycrm");
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='MyObjectLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='myobject@easycrm';
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'MyObject'; $keyforclassfile='/easycrm/class/myobject.class.php'; $keyforelement='myobject@easycrm';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		//$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		//$keyforclass = 'MyObjectLine'; $keyforclassfile='/easycrm/class/myobject.class.php'; $keyforelement='myobjectline@easycrm'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='myobject'; $keyforaliasextra='extra'; $keyforelement='myobject@easycrm';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$keyforselect='myobjectline'; $keyforaliasextra='extraline'; $keyforelement='myobjectline@easycrm';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('myobjectline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'...');
		//$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		//$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'myobject as t';
		//$this->export_sql_end[$r]  =' LEFT JOIN '.MAIN_DB_PREFIX.'myobject_line as tl ON tl.fk_myobject = t.rowid';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('myobject').')';
		$r++; */
		/* END MODULEBUILDER EXPORT MYOBJECT */

		// Imports profiles provided by this module
		// $r = 1;
		/* BEGIN MODULEBUILDER IMPORT MYOBJECT */
		/*
		 $langs->load("easycrm@easycrm");
		 $this->export_code[$r]=$this->rights_class.'_'.$r;
		 $this->export_label[$r]='MyObjectLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		 $this->export_icon[$r]='myobject@easycrm';
		 $keyforclass = 'MyObject'; $keyforclassfile='/easycrm/class/myobject.class.php'; $keyforelement='myobject@easycrm';
		 include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		 $keyforselect='myobject'; $keyforaliasextra='extra'; $keyforelement='myobject@easycrm';
		 include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		 //$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		 $this->export_sql_start[$r]='SELECT DISTINCT ';
		 $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'myobject as t';
		 $this->export_sql_end[$r] .=' WHERE 1 = 1';
		 $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('myobject').')';
		 $r++; */
		/* END MODULEBUILDER IMPORT MYOBJECT */
	}

    /**
     *  Function called when module is enabled.
     *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *  It also creates data directories
     *
     * @param  string    $options Options when enabling module ('', 'noboxes')
     * @return int                1 if OK, 0 if KO
     * @throws Exception
     */
	public function init($options = ''): int
    {
		global $conf, $langs;

        if ($this->error > 0) {
            setEventMessages('', $this->errors, 'errors');
            return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
        }

        $sql = [];

        dolibarr_set_const($this->db, 'EASYCRM_VERSION', $this->version, 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($this->db, 'EASYCRM_DB_VERSION', $this->version, 'chaine', 0, '', $conf->entity);

        // Create extrafields during init
        include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
        $extra_fields = new ExtraFields($this->db);

        $extra_fields->addExtraField('commrelaunch', $langs->transnoentities('CommercialsRelaunching'), 'text', 100, 2000, 'projet', 0, 0, '', '', '', '', 2);

		// Permissions
		$this->remove($options);

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = ''): int
    {
		$sql = [];
		return $this->_remove($sql, $options);
	}
}

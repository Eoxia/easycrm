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
 *	\file       view/map.php
 *	\ingroup    map
 *	\brief      Page to show map of object's address
 */

// Load EasyCRM environment
if (file_exists('../easycrm.main.inc.php')) {
	require_once __DIR__ . '/../easycrm.main.inc.php';
} elseif (file_exists('../../easycrm.main.inc.php')) {
	require_once __DIR__ . '/../../easycrm.main.inc.php';
} else {
	die('Include of easycrm main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

require_once __DIR__ . '/../class/address.class.php';
require_once __DIR__ . '/../../saturne/lib/object.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get object parameters
$objectType  = GETPOST('object_type', 'alpha');
$objectInfos = get_objects_metadata($objectType);

// Get map filters parameters
$filterType    = GETPOST('filter_type','aZ');
$filterId      = GETPOST('object_id');
$filterCountry = GETPOST('filter_country');
$filterRegion  = GETPOST('filter_region');
$filterState   = GETPOST('filter_state');
$filterTown    = trim(GETPOST('filter_town', 'alpha'));
$filterCat     = GETPOST("search_category_" . $objectType ."_list", 'array');

// Security check - Protection if external user
$permissiontoread   = $user->rights->easycrm->address->read;
$permissiontoadd    = $user->rights->easycrm->address->write;
$permissiontodelete = $user->rights->easycrm->address->delete;
saturne_check_access($permissiontoread);

// Initialize technical object
$address = new Address($db);
$object  = new $objectInfos['class_name']($db);

// Initialize view objects
$form        = new Form($db);
$formCompany = new FormCompany($db);

$hookmanager->initHooks(['easycrmmap', $objectType . 'map']);

/*
 * Actions
 */

$parameters = [];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		$filterCat     = [];
		$filterId      = 0;
		$filterCountry = 0;
		$filterRegion  = 0;
		$filterState   = 0;
		$filterTown    = '';
		$filterType    = '';
	}
}

/*
 * View
 */

$title   = $langs->trans("Map");
$helpUrl = 'FR:Module_EasyCRM';

saturne_header(0, '', $title, $helpUrl);

/**
 * Build geoJSON datas.
 */

// Filter on address
$IdFilter      = ($filterId > 0 ? 'element_id = "' . $filterId . '" AND ' : '');
$typeFilter    = (dol_strlen($filterType) > 0 ? 'type = "' . $filterType . '" AND ' : '');
$townFilter    = (dol_strlen($filterTown) > 0 ? 'town = "' . $filterTown . '" AND ' : '');
$countryFilter = ($filterCountry > 0 ? 'fk_country = ' . $filterCountry . ' AND ' : '');
$regionFilter  = ($filterRegion > 0 ? 'fk_region = ' . $filterRegion . ' AND ' : '');
$stateFilter   = ($filterState > 0 ? 'fk_department = ' . $filterState . ' AND ' : '');

$allCat = '';
foreach($filterCat as $catId) {
    $allCat .= $catId . ',';
}
$allCat        = rtrim($allCat, ',');
$catFilter     = (dol_strlen($allCat) > 0 ? 'cp.fk_categorie IN (' . $allCat . ') AND ' : '');

$filter        = ['customsql' => $IdFilter . $typeFilter . $townFilter . $countryFilter . $regionFilter . $stateFilter . $catFilter . 'element_type = "'. $objectType .'"'];

$icon          = dol_buildpath('/easycrm/img/dot.png', 1);
$objectList    = [];
$features      = [];
$num           = 0;
$allObjects    = saturne_fetch_all_object_type($objectInfos['class_name']);

if ($conf->global->EASYCRM_DISPLAY_MAIN_ADDRESS) {
	if (is_array($allObjects) && !empty($allObjects)) {
		foreach ($allObjects as $object) {
			$object->fetch_optionals();

			if (!isset($object->array_options['options_' . $objectType . 'address']) || dol_strlen($object->array_options['options_' . $objectType . 'address']) <= 0) {
				continue;
			} else {
				$addressId = $object->array_options['options_' . $objectType . 'address'];
			}

			$address->fetch($addressId);

			if (($filterId > 0 && $filterId != $object->id) || (dol_strlen($filterType) > 0 && $filterType != $address->type) || (dol_strlen($filterTown) > 0 && $filterTown != $address->town) ||
				($filterCountry > 0 && $filterCountry != $address->fk_country) || ($filterRegion > 0 && $filterRegion != $address->fk_region) || ($filterState > 0 && $filterState != $address->fk_department)) {
                continue;
			}

			if ($address->longitude != 0 && $address->latitude != 0) {
				$address->convertCoordinates();
				$num++;
			} else {
				continue;
			}

			$locationID   = $addressId;

			$description  = $object->getNomUrl(1) . '</br>';
			$description .= $langs->trans($address->type) . ' : ' . $address->name;
			$description .= dol_strlen($address->town) > 0 ? '</br>' . $langs->trans('Town') . ' : ' . $address->town : '';
			$color        = randomColor();

			$objectList[$locationID] = !empty($address->fields['color']) ? $address->fields['color'] : '#' . $color;

			// Add geoJSON point
			$features[] = [
				'type' => 'Feature',
				'geometry' => [
					'type' => 'Point',
					'coordinates' => [$address->longitude, $address->latitude],
				],
				'properties' => [
					'desc'    => $description,
					'address' => $locationID,
				],
			];
		}
	}
} else {
	$addresses = $address->fetchAll('', '', 0, 0, $filter, 'AND', !empty($filterCat), $objectType, 't.element_id');
	if (is_array($addresses) && !empty($addresses)) {
		foreach($addresses as $address) {
			if ($address->longitude != 0 && $address->latitude != 0) {
				$address->convertCoordinates();
				$num++;
			} else {
				continue;
			}

			$object->fetch($address->element_id);

			$locationID   = $address->id ?? 0;
			$description  = $object->getNomUrl(1) . '</br>';
			$description .= $langs->trans($address->type) . ' : ' . $address->name;
			$description .= dol_strlen($address->town) > 0 ? '</br>' . $langs->trans('Town') . ' : ' . $address->town : '';
			$color        = randomColor();

			$objectList[$locationID] = !empty($address->fields['color']) ? $address->fields['color'] : '#' . $color;

			// Add geoJSON point
			$features[] = [
				'type' => 'Feature',
				'geometry' => [
					'type' => 'Point',
					'coordinates' => [$address->longitude, $address->latitude],
				],
				'properties' => [
					'desc'    => $description,
					'address' => $locationID,
				],
			];
		}
	}
}

if ($filterId > 0) {
    $object->fetch($filterId);

    saturne_get_fiche_head($object, 'map', $title);

    $morehtml = '<a href="' . dol_buildpath('/' . $object->element . '/list.php', 1) . '?restore_lastsearch_values=1&object_type=' . $object->element . '">' . $langs->trans('BackToList') . '</a>';
    saturne_banner_tab($object, 'ref', $morehtml, 1, 'ref', 'ref', '', !empty($object->photo));
}

print_barre_liste($title, '', $_SERVER["PHP_SELF"], '', '', '', '', '', $num, 'fa-map-marked-alt');

print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '?object_type=' . $objectType . '" name="formfilter">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';

// Filter box
print '<div class="liste_titre liste_titre_bydiv centpercent">';

$selectArray = [];
foreach ($allObjects as $singleObject) {
    $selectArray[$singleObject->id] = $singleObject->ref;
}
// Object ?>
<div class="divsearchfield"> <?php print img_picto('', $objectInfos['picto']) . ' ' . $langs->trans($objectInfos['langs']). ': ';
print $form->selectarray('object_id', $selectArray, $filterId, 1, 0, 0, '', 0, 0, $filterId > 0);

// Type ?>
<div class="divsearchfield"> <?php print $langs->trans('Type'). ': ';
print saturne_select_dictionary('filter_type', 'c_address_type', 'ref', 'label', $filterType, 1);

// Country ?>
<div class="divsearchfield"> <?php print $langs->trans('Country'). ': ';
print $form->select_country($filterCountry, 'filter_country', '', 0, 'maxwidth100') . '</div>';

// Region ?>
<div class="divsearchfield"> <?php print $langs->trans('Region'). ': ';
$formCompany->select_region($filterRegion, 'filter_region') . '</div>';

// Department ?>
<div class="divsearchfield"> <?php print $langs->trans('State'). ': ';
print $formCompany->select_state($filterState, 0, 'filter_state', 'maxwidth100') . '</div>';

// City ?>
<div class="divsearchfield"> <?php print $langs->trans('Town'). ': '; ?>
<input class="flat searchstring maxwidth200" type="text" name="filter_town" value="<?php echo dol_escape_htmltag($filterTown) ?> "> </div>

<?php

//Categories project
if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcategory.class.php';

    if (in_array($objectType, Categorie::$MAP_ID_TO_CODE)) {
        $formCategory = new FormCategory($db);

        print '<div class="divsearchfield">';

        print $langs->trans('ProjectsCategoriesShort') . '</br>' . $formCategory->getFilterBox($objectType, $filterCat) . '</div>';
    }
}

// Morefilter buttons
print '<div class="divsearchfield">';
print $form->showFilterButtons() . '</div> </div> </div>';

print '</form>';

?>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/openlayers/openlayers.github.io@master/en/v6.15.1/css/ol.css" type="text/css">
	<script src="https://cdn.polyfill.io/v2/polyfill.min.js?features=requestAnimationFrame,Element.prototype.classList"></script>
	<script src="https://cdn.jsdelivr.net/gh/openlayers/openlayers.github.io@master/en/v6.15.1/build/ol.js"></script>
	<style>
		.ol-popup {
			position: absolute;
			background-color: white;
			-webkit-filter: drop-shadow(0 1px 4px rgba(0,0,0,0.2));
			filter: drop-shadow(0 1px 4px rgba(0,0,0,0.2));
			padding: 15px;
			border-radius: 10px;
			border: 1px solid #cccccc;
			bottom: 12px;
			left: -50px;
			min-width: 280px;
		}
		.ol-popup:after, .ol-popup:before {
			top: 100%;
			border: solid transparent;
			content: " ";
			height: 0;
			width: 0;
			position: absolute;
			pointer-events: none;
		}
		.ol-popup:after {
			border-top-color: white;
			border-width: 10px;
			left: 48px;
			margin-left: -10px;
		}
		.ol-popup:before {
			border-top-color: #cccccc;
			border-width: 11px;
			left: 48px;
			margin-left: -11px;
		}
		.ol-popup-closer {
			text-decoration: none;
			position: absolute;
			top: 2px;
			right: 8px;
		}
		.ol-popup-closer:after {
			content: "✖";
		}
	</style>

	<div id="display_map" class="display_map"></div>
	<div id="popup" class="ol-popup">
		<a href="#" id="popup-closer" class="ol-popup-closer"></a>
		<div id="popup-content"></div>
	</div>

	<script type="text/javascript">
		/**
		 * Set map height.
		 */
		var _map = $('#display_map');
		var _map_pos = _map.position();
		var h = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
		_map.height(h - _map_pos.top - 20);

		/**
		 * Prospect markers geoJSON.
		 */
		var geojsonMarkers = {
			"type": "FeatureCollection",
			"crs": {
				"type": "name",
				"properties": {
					"name": "EPSG:3857"
				}
			},
			"features": []
		};
		<?php
		$result = $address->injectMapFeatures($features, 500);
		if ($result < 0) {
			setEventMessage($langs->trans('ErrorMapFeatureEncoding'), 'errors');
		}
		?>
		console.log("Map metrics: EPSG:3857");
		console.log("Map features length: " + geojsonMarkers.features.length + " map features loaded.");

		/**
		 * Prospect markers styles.
		 */
		var markerStyles = {};
		$.map(<?php print json_encode($objectList) ?>, function (value, key) {
			if (!(key in markerStyles)) {
				markerStyles[key] = new ol.style.Style({
					image: new ol.style.Icon(/** @type {module:ol/style/Icon~Options} */ ({
						anchor: [0.5, 1],
						color: value,
						crossOrigin: 'anonymous',
						src: '<?php print $icon ?>'
					}))
				});
			}
		});
		var styleFunction = function(feature) {
			return markerStyles[feature.get('address')];
		};

		/**
		 * Prospect markers source.
		 */
		var prospectSource = new ol.source.Vector({
			features: (new ol.format.GeoJSON()).readFeatures(geojsonMarkers)
		});

		/**
		 * Prospect markers layer.
		 */
		var prospectLayer = new ol.layer.Vector({
			source: prospectSource,
			style: styleFunction
		});

		/**
		 * Open Street Map layer.
		 */
		var osmLayer = new ol.layer.Tile({
			source: new ol.source.OSM()
		});

		/**
		 * Elements that make up the popup.
		 */
		var popupContainer = document.getElementById('popup');
		var popupContent = document.getElementById('popup-content');
		var popupCloser = document.getElementById('popup-closer');

		/**
		 * Create an overlay to anchor the popup to the map.
		 */
		var popupOverlay = new ol.Overlay({
			element: popupContainer,
			autoPan: true,
			autoPanAnimation: {
				duration: 250
			}
		});

		/**
		 * Add a click handler to hide the popup.
		 * @return {boolean} Don't follow the href.
		 */
		popupCloser.onclick = function() {
			popupOverlay.setPosition(undefined);
			popupCloser.blur();
			return false;
		};

		/**
		 * View of the map.
		 */
		var mapView = new ol.View({
			projection: 'EPSG:3857'
		});
		if (<?php print $num ?> == 1) {
			var feature = prospectSource.getFeatures()[0];
			var coordinates = feature.getGeometry().getCoordinates();
			mapView.fit([coordinates[0], coordinates[1], coordinates[0], coordinates[1]], {
				padding: [50, 50, 50, 50],
				constrainResolution: false
			})
			mapView.setCenter(coordinates);
			mapView.setZoom(<?php print (!empty($filterTown) ? 14 : 17) ?>);
		} else {
			mapView.setCenter([0, 0]);
			mapView.setZoom(1);
		}

		/**
		 * Create the map.
		 */
		var map = new ol.Map({
			target: 'display_map',
			layers: [osmLayer, prospectLayer],
			overlays: [popupOverlay],
			view: mapView
		});

		/**
		 * Fit map for markers.
		 */
		if (<?php print $num ?> > 1) {
			var extent = limitExtent(prospectSource.getExtent());

			if (mapView.getProjection() == 'EPSG:3857') extent = limitExtent(extent);

			mapView.fit(
				extent, {
					padding: [50, 50, 50, 50],
					constrainResolution: false
				}
			);
		}

		function limitExtent(extent) {
			const max_extent_coords = [-20037508.34, -20048966.1, 20037508.34, 20048966.1];
			for (let i = 0 ; i < 4 ; i++) {
				if (Math.abs(extent[i]) > Math.abs(max_extent_coords[i])) {
					extent[i] = max_extent_coords[i];
				}
			}
			return extent;
		}

		/**
		 * Add a click handler to the map to render the popup.
		 */
		map.on('singleclick', function(evt) {
			var feature = map.forEachFeatureAtPixel(evt.pixel, function (feature) {
				return feature;
			});

			if (feature) {
				var coordinates = feature.getGeometry().getCoordinates();
				popupContent.innerHTML = feature.get('desc');
				popupOverlay.setPosition(coordinates);
			} else {
				popupCloser.click();
			}
		});
	</script>
<?php

llxFooter();
$db->close();

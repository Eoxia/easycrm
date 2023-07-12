<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file        class/address.class.php
 * \ingroup     easycrm
 * \brief       This file is a CRUD class file for Address (Create/Read/Update/Delete)
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

/**
 * Class for Address
 */
class Address extends SaturneObject
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string[] Array of error strings
	 */
	public $errors = [];

	/**
	 * @var string Module name.
	 */
	public string $module = 'easycrm';

	/**
	 * @var string Element type of object.
	 */
	public $element = 'address';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'easycrm_address';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public int $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public int $isextrafieldmanaged = 0;

	/**
	 * @var string String with name of icon for signature. Must be the part after the 'object_' into object_signature.png
	 */
	public string $picto = 'fontawesome_fa-location-dot_fas_#d35968';

	public const STATUS_DELETED  = -1;
	public const STATUS_NOT_FOUND = 0;
	public const STATUS_ACTIVE    = 1;

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public array $fields = [
		'rowid'                => ['type' => 'integer',      'label' => 'TechnicalID',           'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
		'entity'               => ['type' => 'integer',      'label' => 'Entity',                'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 0, 'index' => 1],
		'date_creation'        => ['type' => 'datetime',     'label' => 'DateCreation',          'enabled' => 1, 'position' => 20,  'notnull' => 1, 'visible' => 0],
		'tms'                  => ['type' => 'timestamp',    'label' => 'DateModification',      'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0],
		'import_key'           => ['type' => 'varchar(14)',  'label' => 'ImportId',              'enabled' => 1, 'position' => 40,  'notnull' => 0, 'visible' => 0],
		'status'               => ['type' => 'smallint',     'label' => 'Status',                'enabled' => 1, 'position' => 50,  'notnull' => 1, 'visible' => 0, 'index' => 1, 'default' => 0],
		'name'                 => ['type' => 'varchar(255)', 'label' => 'Name',                  'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 1],
		'type'                 => ['type' => 'varchar(255)', 'label' => 'Type',                  'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 1],
		'fk_country'           => ['type' => 'integer',      'label' => 'Country',               'enabled' => 1, 'position' => 70,  'notnull' => 0, 'visible' => 1],
		'fk_region'            => ['type' => 'integer',      'label' => 'Region',                'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => 1],
		'fk_department'        => ['type' => 'integer',      'label' => 'State',                 'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => 1],
		'town'                 => ['type' => 'varchar(255)', 'label' => 'Town',                  'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => 1],
		'zip'                  => ['type' => 'smallint(5)',  'label' => 'Zip',                   'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => 1],
		'address'              => ['type' => 'varchar(255)', 'label' => 'Address',               'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 1],
		'latitude'             => ['type' => 'double(24,8)', 'label' => 'Latitude',              'enabled' => 1, 'position' => 125, 'notnull' => 1, 'visible' => 1, 'default' => 0],
		'longitude'            => ['type' => 'double(24,8)', 'label' => 'Longitude',             'enabled' => 1, 'position' => 130, 'notnull' => 1, 'visible' => 1, 'default' => 0],
		'element_id'           => ['type' => 'integer',      'label' => 'ElementId',             'enabled' => 1, 'position' => 140, 'notnull' => 1, 'visible' => 1, 'index' => 1],
		'element_type'         => ['type' => 'varchar(255)', 'label' => 'ElementType',           'enabled' => 1, 'position' => 150, 'notnull' => 0, 'visible' => 1],
		'osm_id'               => ['type' => 'varchar(255)', 'label' => 'OpenStreetMapId',       'enabled' => 1, 'position' => 155, 'notnull' => 0, 'visible' => 1],
		'osm_type'             => ['type' => 'varchar(255)', 'label' => 'OpenStreetMapType',     'enabled' => 1, 'position' => 160, 'notnull' => 0, 'visible' => 3],
		'osm_category'         => ['type' => 'varchar(255)', 'label' => 'OpenStreetMapCategory', 'enabled' => 1, 'position' => 165, 'notnull' => 0, 'visible' => 3],
		'fk_user_creat'        => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => 1, 'position' => 170, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid'],
		'fk_user_modif'        => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif',  'picto' => 'user', 'enabled' => 1, 'position' => 180, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
	];

	/**
	 * @var int ID
	 */
	public int $rowid;

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var int|string Creation date
	 */
	public $date_creation;

	/**
	 * @var int|string Timestamp
	 */
	public $tms;

	/**
	 * @var string Import key
	 */
	public $import_key;

	/**
	 * @var int Status
	 */
	public $status;

	/**
	 * @var string Name
	 */
	public $name;

	/**
	 * @var string Type
	 */
	public string $type;
	/**
	 * @var int Fk_country
	 */
	public int $fk_country;

	/**
	 * @var int Fk_region
	 */
	public int $fk_region;

	/**
	 * @var int Fk_department
	 */
	public int $fk_department;

	/**
	 * @var string Town
	 */
	public string $town;

	/**
	 * @var int|null Zip
	 */
	public ?int $zip;

	/**
	 * @var string Address
	 */
	public string $address;

	/**
	 * @var float Latitude
	 */
	public float $latitude;

	/**
	 * @var float Longitude
	 */
	public float $longitude;

	/**
	 * @var int Element id
	 */
	public int $element_id;

	/**
	 * @var string Element type
	 */
	public string $element_type;

	/**
	 * @var int|null OpenStreetMap id
	 */
	public ?int $osm_id = 0;

	/**
	 * @var string|null OpenStreetMap type
	 */
	public ?string $osm_type = '';

	/**
	 * @var string|null OpenStreetMap category
	 */
	public ?string $osm_category = '';

	/**
	 * @var int User ID.
	 */
	public int $fk_user_creat;

	/**
	 * @var int|null User ID.
	 */
	public ?int $fk_user_modif;

	/**
	 * Constructor.
	 *
	 * @param DoliDb $db                  Database handler.
	 * @param string $moduleNameLowerCase Module name.
	 * @param string $objectType          Object element type.
	 */
	public function __construct(DoliDB $db, string $moduleNameLowerCase = 'easycrm', string $objectType = 'address')
	{
		$this->latitude  = $this->latitude ?? 0;
		$this->longitude = $this->longitude ?? 0;
		$this->status    = $this->status ?? 0;

		parent::__construct($db, $moduleNameLowerCase, $objectType);
	}

	/**
	 * Create object into database.
	 *
	 * @param  User $user      User that creates.
	 * @param  bool $notrigger false = launch triggers after, true = disable triggers.
	 * @return int             0 < if KO, ID of created object if OK.
	 */
	public function create(User $user, bool $notrigger = false): int
	{
		$country        = getCountry($this->fk_country);
		$country        = $country != 'Error' ? $country : '';
		$regionAndState = getState($this->fk_department, 'all', 0, 1);
		$region         = is_array($regionAndState) && !empty($regionAndState['region']) ? $regionAndState['region'] : '';
		$state          = is_array($regionAndState) && !empty($regionAndState['label']) ? $regionAndState['label'] : '';
		$parameters     = (dol_strlen($country) > 0 ? $country . ',+' : '') . (dol_strlen($region) > 0 ? $region . ',+' : '') . (dol_strlen($state) > 0 ? $state . ',+' : '') . (dol_strlen($this->town) > 0 ? $this->town . ',+' : '') . (dol_strlen($this->address) > 0 ? $this->address . ',+' : '') . ($this->zip > 0 ? $this->zip : '');
		$parameters     = str_replace(' ', '+', $parameters);

		$context    = stream_context_create(["http" => ["header" => "User-Agent:" . $_SERVER['HTTP_USER_AGENT']]]);
		$response   = file_get_contents('https://nominatim.openstreetmap.org/search?q='. $parameters .'&format=json&polygon=1&addressdetails=1', false, $context);
		$data       = json_decode($response, false);

		if (is_array($data) && !empty($data)) {
			$address = $data[0];

			$this->status       = self::STATUS_ACTIVE;
			$this->latitude     = $address->lat;
			$this->longitude    = $address->lon;
			$this->osm_type     = $address->osm_type ?? '';
			$this->osm_id       = $address->osm_id ?? 0;
			$this->osm_category = $address->osm_category ?? '';
			$this->zip          = $this->zip > 0 ? $this->zip : $address->address->postcode;
		}

		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder         Sort Order
	 * @param  string      $sortfield         Sort field
	 * @param  int         $limit             Limit
	 * @param  int         $offset            Offset
	 * @param  array       $filter            Filter array. Example array('field'=>'value', 'customurl'=>...)
	 * @param  string      $filtermode        Filter mode (AND/OR)
	 * @return int|array                      0 < if KO, array of pages if OK
	 * @throws Exception
	 */
	public function fetchAll(string $sortorder = '', string $sortfield = '', int $limit = 0, int $offset = 0, array $filter = [], string $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = [];

		$sql  = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';

		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= ' WHERE t.entity IN (' . getEntity($this->table_element) . ')';
		} else {
			$sql .= ' WHERE 1 = 1';
		}
		// Manage filter
		$sqlwhere = [];
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key . '=' . $value;
				} elseif (in_array($this->fields[$key]['type'], ['date', 'datetime', 'timestamp'])) {
					$sqlwhere[] = $key . ' = \'' . $this->db->idate($value) . '\'';
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} elseif (strpos($value, '%') === false) {
					$sqlwhere[] = $key . ' IN (' . $this->db->sanitize($this->db->escape($value)) . ')';
				} else {
					$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND (' . implode(' ' . $filtermode . ' ', $sqlwhere) . ')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit, $offset);
		}
		$sql = str_replace(',fk_user_creat,fk_user_modif', '', $sql);

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i   = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Return the status
	 *
	 * @param  int    $status Id status
	 * @param  int    $mode   0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return string         Label of status
	 */
	public function LibStatut(int $status, int $mode = 0): string
	{
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			$this->labelStatus[self::STATUS_DELETED]   = $langs->transnoentities('Inactive');
			$this->labelStatus[self::STATUS_NOT_FOUND] = $langs->transnoentities('NotFound');
			$this->labelStatus[self::STATUS_ACTIVE]    = $langs->transnoentities('Active');

			$this->labelStatusShort[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Inactive');
			$this->labelStatusShort[self::STATUS_NOT_FOUND] = $langs->transnoentitiesnoconv('NotFound');
			$this->labelStatusShort[self::STATUS_ACTIVE]    = $langs->transnoentitiesnoconv('Active');
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', 'status0', $mode);
	}

	/**
	 * Fetch addresses in database with parent ID
	 *
	 * @param  int           $element_id   ID of object linked
	 * @param  string        $element_type Type of object
	 * @param  string        $morefilter   Filter
	 * @return array|integer
	 * @throws Exception
	 */
	public function fetchAddresses(int $element_id, string $element_type, string $morefilter = '1 = 1')
	{
		$filter = ['customsql' => 'element_id=' . $element_id . ' AND ' . $morefilter . ' AND element_type="' . $element_type . '"' . ' AND status >= 0'];
		return $this->fetchAll('', '', 0, 0, $filter);
	}

	/**
	 * Convert longitude and latitude format WGS 84 (EPSG:4326) to Web Mercator (EPSG:3857)
	 *
	 * @return object
	 */
	public function convertCoordinates()
	{
		$convertFactor   = 6378137.0;
		$longitude       = $this->longitude / 180 * pi();
		$latitude        = $this->latitude / 180 * pi();
		$this->longitude = $convertFactor * $longitude;
		$this->latitude  = $convertFactor * log(tan(pi() / 4 + $latitude / 2));

		return $this;
	}

	/**
	 * Inject map features
	 *
	 * @param  array    $features   array of features: id, description, color, longitude, latitude
	 * @param  int      $chunk_size size of chunk
	 * @param  int      $deep
	 * @return array
	 */
	public function injectMapFeatures($features, $chunk_size, $deep = 0): int
	{
		$error = 0;

		if (!empty($features)) {
			$bulkFeatures = array_chunk($features, $chunk_size);
			foreach ($bulkFeatures as $bulk) {
				$encodedBulk = json_encode($bulk);
				if (!empty($encodedBulk)) {
					print "geojsonProspectMarkers.features = $.merge(geojsonProspectMarkers.features, $encodedBulk);\n";
				} else {
					if ($chunk_size > 1) {
						$result = $this->injectMapFeatures($bulk, floor($chunk_size / 2), $deep + 1);
						$result < 0 ? $error++ : '';
					} else {
						ob_start();
						print_r($bulk);
						$content = ob_get_contents();
						ob_clean();
						print "console.error('Map: Error encode json map feature, data:', '" . dol_escape_js($content, 1) . "');\n";
						$error++;
					}
				}
			}
		}

		return $error > 0 ? -1 : 0;
	}
}

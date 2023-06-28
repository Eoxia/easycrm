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
 * \file    class/easycrmdashboard.class.php
 * \ingroup dolismq
 * \brief   Class file for manage EasycrmDashboard.
 */

/**
 * Class for EasycrmDashboard.
 */
class EasycrmDashboard
{
	/**
	 * @var DoliDB Database handler.
	 */
	public DoliDB $db;

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Load dashboard info.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function load_dashboard(): array
	{
		$array['StatusPropal']['graphs'][0] = $this->getDataFromExtrafieldsAndDictionnary('PropalStatusCommRepartition', 'c_status_propal');
		$array['StatusPropal']['graphs'][1] = $this->getDataFromExtrafieldsAndDictionnary('PropalRefusalReasonRepartition', 'c_refusal_reason', 'propal', 'commrefusal');

		return $array;
	}

	/**
	 * Get repartition of a dataset according to extrafields and dictionnary
	 *
	 * @return array     Graph datas (label/color/type/title/data etc..).
	 * @throws Exception
	 */
	public function getDataFromExtrafieldsAndDictionnary($title, $dico, $class = 'propal', $extrafield = 'commstatus'): array
	{
		global $langs;

		require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
		require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

		require_once __DIR__ . '/../../saturne/lib/object.lib.php';

		$extrafields = new ExtraFields($this->db);

		$propals     = saturne_fetch_all_object_type($class);
		$extralabels = $extrafields->fetch_name_optionals_label($class);

		// Graph Title parameters.
		$array['title'] = $langs->transnoentities($title);
		$array['picto'] = 'fontawesome_fa-file-signature_fas_#63ACC9';

		// Graph parameters.
		$array['width']   = 800;
		$array['height']  = 400;
		$array['type']    = 'pie';
		$array['dataset'] = 1;

		$dictionnariesData = saturne_fetch_dictionary($dico);

		$i = 1;
		$array['labels'][0] = ['label' => 'N/A', 'color' => '999999'];

		if (is_array($dictionnariesData) && !empty($dictionnariesData)) {
			foreach ($dictionnariesData as $data) {
				$arrayNbDataByLabel[$i] = 0;
				$array['labels'][$i] = [
					'label' => $langs->transnoentities($data->label),
					'color' => '#' . str_pad(dechex(rand(0x000000, 0xFFFFFF)), 6, 0, STR_PAD_LEFT)
				];
				$i++;
			}

			if (is_array($propals) && !empty($propals)) {
				foreach ($propals as $propal) {
					$propal->fetch_optionals($propal->id, $extralabels[$extrafield]);
					$commStatus = $propal->array_options['options_' . $extrafield];
					$arrayNbDataByLabel[$commStatus]++;
				}
				ksort($arrayNbDataByLabel);
			}
		}

		$array['data'] = $arrayNbDataByLabel;

		return $array;
	}
}

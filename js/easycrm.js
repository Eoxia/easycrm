/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 *
 * Library javascript to enable Browser notifications
 */

/**
 * \file    js/easycrm.js
 * \ingroup easycrm
 * \brief   JavaScript file for module EasyCRM.
 */

'use strict';

if (!window.easycrm) {
  /**
   * Init EasyCRM JS.
   *
   * @memberof EasyCRM_Init
   *
   * @since   1.1.0
   * @version 1.1.0
   *
   * @type {Object}
   */
  window.easycrm = {};

  /**
   * Init scriptsLoaded EasyCRM.
   *
   * @memberof EasyCRM_Init
   *
   * @since   1.1.0
   * @version 1.1.0
   *
   * @type {Boolean}
   */
  window.easycrm.scriptsLoaded = false;
}

if (!window.easycrm.scriptsLoaded) {
  /**
   * EasyCRM init.
   *
   * @memberof EasyCRM_Init
   *
   * @since   1.1.0
   * @version 1.1.0
   *
   * @returns {void}
   */
  window.easycrm.init = function() {
    window.easycrm.load_list_script();
  };

  /**
   * Load all modules' init.
   *
   * @memberof EasyCRM_Init
   *
   * @since   1.1.0
   * @version 1.1.0
   *
   * @returns {void}
   */
  window.easycrm.load_list_script = function() {
    if (!window.easycrm.scriptsLoaded) {
      let key = undefined, slug = undefined;
      for (key in window.easycrm) {
        if (window.easycrm[key].init) {
          window.easycrm[key].init();
        }
        for (slug in window.easycrm[key]) {
          if (window.easycrm[key] && window.easycrm[key][slug] && window.easycrm[key][slug].init) {
            window.easycrm[key][slug].init();
          }
        }
      }
      window.easycrm.scriptsLoaded = true;
    }
  };

  /**
   * Refresh and reload all modules' init.
   *
   * @memberof EasyCRM_Init
   *
   * @since   1.1.0
   * @version 1.1.0
   *
   * @returns {void}
   */
  window.easycrm.refresh = function() {
    let key = undefined;
    let slug = undefined;
    for (key in window.easycrm) {
      if (window.easycrm[key].refresh) {
        window.easycrm[key].refresh();
      }
      for (slug in window.easycrm[key]) {
        if (window.easycrm[key] && window.easycrm[key][slug] && window.easycrm[key][slug].refresh) {
          window.easycrm[key][slug].refresh();
        }
      }
    }
  };
  $(document).ready(window.easycrm.init);
}

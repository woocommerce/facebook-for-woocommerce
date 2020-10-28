<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook;

defined( 'ABSPATH' ) or exit;

/**
 * Helper class with utility methods for handling locales in Facebook.
 *
 * @since 2.2.0-dev.1
 */
class Locales {


	/**
	 * Gets a list of locales supported by Facebook.
	 *
	 * @since 2.2.0-dev.1
	 *
	 * @return string[]
	 */
	public static function get_supported_locales() {

		return [
			'af_ZA',
			'ar_AR',
			'as_IN',
			'az_AZ',
			'be_BY',
			'bg_BG',
			'bn_IN',
			'br_FR',
			'bs_BA',
			'ca_ES',
			'cb_IQ',
			'co_FR',
			'cs_CZ',
			'cx_PH',
			'cy_GB',
			'da_DK',
			'de_DE',
			'el_GR',
			'en_GB',
			'en_US',
			'es_ES',
			'es_LA',
			'et_EE',
			'eu_ES',
			'fa_IR',
			'ff_NG',
			'fi_FI',
			'fo_FO',
			'fr_CA',
			'fr_FR',
			'fy_NL',
			'ga_IE',
			'gl_ES',
			'gn_PY',
			'gu_IN',
			'ha_NG',
			'he_IL',
			'hi_IN',
			'hr_HR',
			'hu_HU',
			'hy_AM',
			'id_ID',
			'is_IS',
			'it_IT',
			'ja_JP',
			'ja_KS',
			'jv_ID',
			'ka_GE',
			'kk_KZ',
			'km_KH',
			'kn_IN',
			'ko_KR',
			'ku_TR',
			'lt_LT',
			'lv_LV',
			'mg_MG',
			'mk_MK',
			'ml_IN',
			'mn_MN',
			'mr_IN',
			'ms_MY',
			'mt_MT',
			'my_MM',
			'nb_NO',
			'ne_NP',
			'nl_BE',
			'nl_NL',
			'nn_NO',
			'or_IN',
			'pa_IN',
			'pl_PL',
			'ps_AF',
			'pt_BR',
			'pt_PT',
			'qz_MM',
			'ro_RO',
			'ru_RU',
			'rw_RW',
			'sc_IT',
			'si_LK',
			'sk_SK',
			'sl_SI',
			'so_SO',
			'sq_AL',
			'sr_RS',
			'sv_SE',
			'sw_KE',
			'sz_PL',
			'ta_IN',
			'te_IN',
			'tg_TJ',
			'th_TH',
			'tl_PH',
			'tr_TR',
			'tz_MA',
			'uk_UA',
			'ur_PK',
			'uz_UZ',
			'vi_VN',
			'zh_CN',
			'zh_HK',
			'zh_TW',
		];
	}


}

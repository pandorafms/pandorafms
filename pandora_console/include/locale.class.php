<?php
  // Locale Class - Working number, date and monetary converter
  // ------------------------------------------------------------
  // Copyright (c) 2008 Evi Vanoost (vanooste@rcbi.rochester.edu)

  // This program is free software; you can redistribute it and/or
  // modify it under the terms of the GNU General Public License
  // as published by the Free Software Foundation for version 2.
  // This program is distributed in the hope that it will be useful,
  // but WITHOUT ANY WARRANTY; without even the implied warranty of
  // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  // GNU General Public License for more details.
  // You should have received a copy of the GNU General Public License
  // along with this program; if not, write to the Free Software
  // Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301,
  // USA.

  // This class should only be used for output, not for the parsing of input nor
  // database storage. This class might output UTF-8 codes that might not be
  // supported by all output formats. Please format these strings accordingly.

class Set_Locale {
	public $localedir = ""; //can be set from outside or you should set it here if it's in an unusual location
	protected $locales = array();
	public $locale = "";

	private $NUMFORMAT;
	private $MONFORMAT;
	private $TIMEFORMAT;
	
	public $lang_codes = array(
		'aa' => 'Afar',
		'ab' => 'Abkhazian',
		'af' => 'Afrikaans',
		'am' => 'Amharic',
		'ar' => 'Arabic',
		'as' => 'Assamese',
		'ay' => 'Aymara',
		'az' => 'Azerbaijani',
		'ba' => 'Bashkir',
		'be' => 'Byelorussian',
		'bg' => 'Bulgarian',
		'bh' => 'Bihari',
		'bi' => 'Bislama',
		'bn' => 'Bengali; Bangla',
		'bo' => 'Tibetan',
		'br' => 'Breton',
		'ca' => 'Catalan',
		'co' => 'Corsican',
		'cs' => 'Czech',
		'cy' => 'Welsh',
		'da' => 'Danish',
		'de' => 'German',
		'dz' => 'Bhutani',
		'el' => 'Greek',
		'en' => 'English',
		'eo' => 'Esperanto',
		'es' => 'Spanish',
		'et' => 'Estonian',
		'eu' => 'Basque',
		'fa' => 'Persian',
		'fi' => 'Finnish',
		'fj' => 'Fiji',
		'fo' => 'Faeroese',
		'fr' => 'French',
		'fy' => 'Frisian',
		'ga' => 'Irish',
		'gd' => 'Scots Gaelic',
		'gl' => 'Galician',
		'gn' => 'Guarani',
		'gu' => 'Gujarati',
		'ha' => 'Hausa',
		'hi' => 'Hindi',
		'hr' => 'Croatian',
		'hu' => 'Hungarian',
		'hy' => 'Armenian',
		'ia' => 'Interlingua',
		'ie' => 'Interlingue',
		'ik' => 'Inupiak',
		'in' => 'Indonesian',
		'is' => 'Icelandic',
		'it' => 'Italian',
		'iw' => 'Hebrew',
		'ja' => 'Japanese',
		'ji' => 'Yiddish',
		'jw' => 'Javanese',
		'ka' => 'Georgian',
		'kk' => 'Kazakh',
		'kl' => 'Greenlandic',
		'km' => 'Cambodian',
		'kn' => 'Kannada',
		'ko' => 'Korean',
		'ks' => 'Kashmiri',
		'ku' => 'Kurdish',
		'ky' => 'Kirghiz',
		'la' => 'Latin',
		'ln' => 'Lingala',
		'lo' => 'Laothian',
		'lt' => 'Lithuanian',
		'lv' => 'Latvian, Lettish',
		'mg' => 'Malagasy',
		'mi' => 'Maori',
		'mk' => 'Macedonian',
		'ml' => 'Malayalam',
		'mn' => 'Mongolian',
		'mo' => 'Moldavian',
		'mr' => 'Marathi',
		'ms' => 'Malay',
		'mt' => 'Maltese',
		'my' => 'Burmese',
		'na' => 'Nauru',
		'ne' => 'Nepali',
		'nl' => 'Dutch',
		'no' => 'Norwegian',
		'oc' => 'Occitan',
		'om' => '(Afan) Oromo',
		'or' => 'Oriya',
		'pa' => 'Punjabi',
		'pl' => 'Polish',
		'ps' => 'Pashto, Pushto',
		'pt' => 'Portuguese',
		'qu' => 'Quechua',
		'rm' => 'Rhaeto-Romance',
		'rn' => 'Kirundi',
		'ro' => 'Romanian',
		'ru' => 'Russian',
		'rw' => 'Kinyarwanda',
		'sa' => 'Sanskrit',
		'sd' => 'Sindhi',
		'sg' => 'Sangro',
		'sh' => 'Serbo-Croatian',
		'si' => 'Singhalese',
		'sk' => 'Slovak',
		'sl' => 'Slovenian',
		'sm' => 'Samoan',
		'sn' => 'Shona',
		'so' => 'Somali',
		'sq' => 'Albanian',
		'sr' => 'Serbian',
		'ss' => 'Siswati',
		'st' => 'Sesotho',
		'su' => 'Sundanese',
		'sv' => 'Swedish',
		'sw' => 'Swahili',
		'ta' => 'Tamil',
		'te' => 'Tegulu',
		'tg' => 'Tajik',
		'th' => 'Thai',
		'ti' => 'Tigrinya',
		'tk' => 'Turkmen',
		'tl' => 'Tagalog',
		'tn' => 'Setswana',
		'to' => 'Tonga',
		'tr' => 'Turkish',
		'ts' => 'Tsonga',
		'tt' => 'Tatar',
		'tw' => 'Twi',
		'uk' => 'Ukrainian',
		'ur' => 'Urdu',
		'uz' => 'Uzbek',
		'vi' => 'Vietnamese',
		'vo' => 'Volapuk',
		'wo' => 'Wolof',
		'xh' => 'Xhosa',
		'yo' => 'Yoruba',
		'zh' => 'Chinese',
		'zu' => 'Zulu'
		);

	public $country_codes = array(
		'AF' => 'AFGHANISTAN',
		'AL' => 'ALBANIA',
		'DZ' => 'ALGERIA',
		'AS' => 'AMERICAN SAMOA',
		'AD' => 'ANDORRA',
		'AO' => 'ANGOLA',
		'AI' => 'ANGUILLA',
		'AQ' => 'ANTARCTICA',
		'AG' => 'ANTIGUA AND BARBUDA',
		'AR' => 'ARGENTINA',
		'AM' => 'ARMENIA',
		'AW' => 'ARUBA',
		'AU' => 'AUSTRALIA',
		'AT' => 'AUSTRIA',
		'AZ' => 'AZERBAIJAN',
		'BS' => 'BAHAMAS',
		'BH' => 'BAHRAIN',
		'BD' => 'BANGLADESH',
		'BB' => 'BARBADOS',
		'BY' => 'BELARUS',
		'BE' => 'BELGIUM',
		'BZ' => 'BELIZE',
		'BJ' => 'BENIN',
		'BM' => 'BERMUDA',
		'BT' => 'BHUTAN',
		'BO' => 'BOLIVIA',
		'BA' => 'BOSNIA AND HERZEGOWINA',
		'BW' => 'BOTSWANA',
		'BV' => 'BOUVET ISLAND',
		'BR' => 'BRAZIL',
		'IO' => 'BRITISH INDIAN OCEAN TERRITORY',
		'BN' => 'BRUNEI DARUSSALAM',
		'BG' => 'BULGARIA',
		'BF' => 'BURKINA FASO',
		'BI' => 'BURUNDI',
		'KH' => 'CAMBODIA',
		'CM' => 'CAMEROON',
		'CA' => 'CANADA',
		'CV' => 'CAPE VERDE',
		'KY' => 'CAYMAN ISLANDS',
		'CF' => 'CENTRAL AFRICAN REPUBLIC',
		'TD' => 'CHAD',
		'CL' => 'CHILE',
		'CN' => 'CHINA',
		'CX' => 'CHRISTMAS ISLAND',
		'CC' => 'COCOS (KEELING) ISLANDS',
		'CO' => 'COLOMBIA',
		'KM' => 'COMOROS',
		'CG' => 'CONGO',
		'CD' => 'CONGO, THE DEMOCRATIC REPUBLIC OF THE',
		'CK' => 'COOK ISLANDS',
		'CR' => 'COSTA RICA',
		'CI' => 'COTE D\'IVOIRE',
		'HR' => 'CROATIA (local name: Hrvatska)',
		'CU' => 'CUBA',
		'CY' => 'CYPRUS',
		'CZ' => 'CZECH REPUBLIC',
		'DK' => 'DENMARK',
		'DJ' => 'DJIBOUTI',
		'DM' => 'DOMINICA',
		'DO' => 'DOMINICAN REPUBLIC',
		'TP' => 'EAST TIMOR',
		'EC' => 'ECUADOR',
		'EG' => 'EGYPT',
		'SV' => 'EL SALVADOR',
		'GQ' => 'EQUATORIAL GUINEA',
		'ER' => 'ERITREA',
		'EE' => 'ESTONIA',
		'ET' => 'ETHIOPIA',
		'FK' => 'FALKLAND ISLANDS (MALVINAS)',
		'FO' => 'FAROE ISLANDS',
		'FJ' => 'FIJI',
		'FI' => 'FINLAND',
		'FR' => 'FRANCE',
		'FX' => 'FRANCE, METROPOLITAN',
		'GF' => 'FRENCH GUIANA',
		'PF' => 'FRENCH POLYNESIA',
		'TF' => 'FRENCH SOUTHERN TERRITORIES',
		'GA' => 'GABON',
		'GM' => 'GAMBIA',
		'GE' => 'GEORGIA',
		'DE' => 'GERMANY',
		'GH' => 'GHANA',
		'GI' => 'GIBRALTAR',
		'GR' => 'GREECE',
		'GL' => 'GREENLAND',
		'GD' => 'GRENADA',
		'GP' => 'GUADELOUPE',
		'GU' => 'GUAM',
		'GT' => 'GUATEMALA',
		'GN' => 'GUINEA',
		'GW' => 'GUINEA-BISSAU',
		'GY' => 'GUYANA',
		'HT' => 'HAITI',
		'HM' => 'HEARD AND MC DONALD ISLANDS',
		'VA' => 'HOLY SEE (VATICAN CITY STATE)',
		'HN' => 'HONDURAS',
		'HK' => 'HONG KONG',
		'HU' => 'HUNGARY',
		'IS' => 'ICELAND',
		'IN' => 'INDIA',
		'ID' => 'INDONESIA',
		'IR' => 'IRAN (ISLAMIC REPUBLIC OF)',
		'IQ' => 'IRAQ',
		'IE' => 'IRELAND',
		'IL' => 'ISRAEL',
		'IT' => 'ITALY',
		'JM' => 'JAMAICA',
		'JP' => 'JAPAN',
		'JO' => 'JORDAN',
		'KZ' => 'KAZAKHSTAN',
		'KE' => 'KENYA',
		'KI' => 'KIRIBATI',
		'KP' => 'KOREA, DEMOCRATIC PEOPLE\'S REPUBLIC OF',
		'KR' => 'KOREA, REPUBLIC OF',
		'KW' => 'KUWAIT',
		'KG' => 'KYRGYZSTAN',
		'LA' => 'LAO PEOPLE\'S DEMOCRATIC REPUBLIC',
		'LV' => 'LATVIA',
		'LB' => 'LEBANON',
		'LS' => 'LESOTHO',
		'LR' => 'LIBERIA',
		'LY' => 'LIBYAN ARAB JAMAHIRIYA',
		'LI' => 'LIECHTENSTEIN',
		'LT' => 'LITHUANIA',
		'LU' => 'LUXEMBOURG',
		'MO' => 'MACAU',
		'MK' => 'MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF',
		'MG' => 'MADAGASCAR',
		'MW' => 'MALAWI',
		'MY' => 'MALAYSIA',
		'MV' => 'MALDIVES',
		'ML' => 'MALI',
		'MT' => 'MALTA',
		'MH' => 'MARSHALL ISLANDS',
		'MQ' => 'MARTINIQUE',
		'MR' => 'MAURITANIA',
		'MU' => 'MAURITIUS',
		'YT' => 'MAYOTTE',
		'MX' => 'MEXICO',
		'FM' => 'MICRONESIA, FEDERATED STATES OF',
		'MD' => 'MOLDOVA, REPUBLIC OF',
		'MC' => 'MONACO',
		'MN' => 'MONGOLIA',
		'MS' => 'MONTSERRAT',
		'MA' => 'MOROCCO',
		'MZ' => 'MOZAMBIQUE',
		'MM' => 'MYANMAR',
		'NA' => 'NAMIBIA',
		'NR' => 'NAURU',
		'NP' => 'NEPAL',
		'NL' => 'NETHERLANDS',
		'AN' => 'NETHERLANDS ANTILLES',
		'NC' => 'NEW CALEDONIA',
		'NZ' => 'NEW ZEALAND',
		'NI' => 'NICARAGUA',
		'NE' => 'NIGER',
		'NG' => 'NIGERIA',
		'NU' => 'NIUE',
		'NF' => 'NORFOLK ISLAND',
		'MP' => 'NORTHERN MARIANA ISLANDS',
		'NO' => 'NORWAY',
		'OM' => 'OMAN',
		'PK' => 'PAKISTAN',
		'PW' => 'PALAU',
		'PS' => 'PALESTINIAN TERRITORY, OCCUPIED',
		'PA' => 'PANAMA',
		'PG' => 'PAPUA NEW GUINEA',
		'PY' => 'PARAGUAY',
		'PE' => 'PERU',
		'PH' => 'PHILIPPINES',
		'PN' => 'PITCAIRN',
		'PL' => 'POLAND',
		'PT' => 'PORTUGAL',
		'PR' => 'PUERTO RICO',
		'QA' => 'QATAR',
		'RE' => 'REUNION',
		'RO' => 'ROMANIA',
		'RU' => 'RUSSIAN FEDERATION',
		'RW' => 'RWANDA',
		'KN' => 'SAINT KITTS AND NEVIS',
		'LC' => 'SAINT LUCIA',
		'VC' => 'SAINT VINCENT AND THE GRENADINES',
		'WS' => 'SAMOA',
		'SM' => 'SAN MARINO',
		'ST' => 'SAO TOME AND PRINCIPE',
		'SA' => 'SAUDI ARABIA',
		'SN' => 'SENEGAL',
		'SC' => 'SEYCHELLES',
		'SL' => 'SIERRA LEONE',
		'SG' => 'SINGAPORE',
		'SK' => 'SLOVAKIA (Slovak Republic)',
		'SI' => 'SLOVENIA',
		'SB' => 'SOLOMON ISLANDS',
		'SO' => 'SOMALIA',
		'ZA' => 'SOUTH AFRICA',
		'GS' => 'SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS',
		'ES' => 'SPAIN',
		'LK' => 'SRI LANKA',
		'SH' => 'ST. HELENA',
		'PM' => 'ST. PIERRE AND MIQUELON',
		'SD' => 'SUDAN',
		'SR' => 'SURINAME',
		'SJ' => 'SVALBARD AND JAN MAYEN ISLANDS',
		'SZ' => 'SWAZILAND',
		'SE' => 'SWEDEN',
		'CH' => 'SWITZERLAND',
		'SY' => 'SYRIAN ARAB REPUBLIC',
		'TW' => 'TAIWAN, PROVINCE OF CHINA',
		'TJ' => 'TAJIKISTAN',
		'TZ' => 'TANZANIA, UNITED REPUBLIC OF',
		'TH' => 'THAILAND',
		'TG' => 'TOGO',
		'TK' => 'TOKELAU',
		'TO' => 'TONGA',
		'TT' => 'TRINIDAD AND TOBAGO',
		'TN' => 'TUNISIA',
		'TR' => 'TURKEY',
		'TM' => 'TURKMENISTAN',
		'TC' => 'TURKS AND CAICOS ISLANDS',
		'TV' => 'TUVALU',
		'UG' => 'UGANDA',
		'UA' => 'UKRAINE',
		'AE' => 'UNITED ARAB EMIRATES',
		'GB' => 'UNITED KINGDOM',
		'US' => 'UNITED STATES',
		'UM' => 'UNITED STATES MINOR OUTLYING ISLANDS',
		'UY' => 'URUGUAY',
		'UZ' => 'UZBEKISTAN',
		'VU' => 'VANUATU',
		'VE' => 'VENEZUELA',
		'VN' => 'VIET NAM',
		'VG' => 'VIRGIN ISLANDS (BRITISH)',
		'VI' => 'VIRGIN ISLANDS (U.S.)',
		'WF' => 'WALLIS AND FUTUNA ISLANDS',
		'EH' => 'WESTERN SAHARA',
		'YE' => 'YEMEN',
		'YU' => 'YUGOSLAVIA',
		'ZM' => 'ZAMBIA',
		'ZW' => 'ZIMBABWE'
		);

	//ISO-4217 currency codes
	public $currency_codes = array (
		'ADP' => 'Andorran Peseta',
		'AED' => 'UAE Dirham',
		'AFA' => 'Afghani',
		'ALL' => 'Lek',
		'AMD' => 'Armenian Dram',
		'ANG' => 'Antillian Guilder',
		'AON' => 'New Kwanza',
		'AOR' => 'Kwanza Reajustado',
		'ARS' => 'Argentine Peso',
		'ATS' => 'Schilling',
		'AUD' => 'Australian Dollar',
		'AWG' => 'Aruban Guilder',
		'AZM' => 'Azerbaijanian Manat',
		'BAM' => 'Convertible Marks',
		'BBD' => 'Barbados Dollar',
		'BDT' => 'Taka',
		'BEF' => 'Belgian Franc',
		'BGL' => 'Lev',
		'BGN' => 'Bulgarian LEV',
		'BHD' => 'Bahraini Dinar',
		'BIF' => 'Burundi Franc',
		'BMD' => 'Bermudian Dollar',
		'BND' => 'Brunei Dollar',
		'BRL' => 'Brazilian Real',
		'BSD' => 'Bahamian Dollar',
		'BTN' => 'Ngultrum',
		'BWP' => 'Pula',
		'BYR' => 'Belarussian Ruble',
		'BZD' => 'Belize Dollar',
		'CAD' => 'Canadian Dollar',
		'CDF' => 'Franc Congolais',
		'CHF' => 'Swiss Franc',
		'CHF' => 'Swiss Franc',
		'CLF' => 'Unidades de fomento',
		'CLP' => 'Chilean Peso',
		'CNY' => 'Yuan Renminbi',
		'COP' => 'Colombian Peso',
		'CRC' => 'Costa Rican Colon',
		'CUP' => 'Cuban Peso',
		'CVE' => 'Cape Verde Escudo',
		'CYP' => 'Cyprus Pound',
		'CZK' => 'Czech Koruna',
		'DEM' => 'Deutsche Mark',
		'DJF' => 'Djibouti Franc',
		'DKK' => 'Danish Krone',
		'DOP' => 'Dominican Peso',
		'DZD' => 'Algerian Dinar',
		'ECS' => 'Sucre',
		'ECV' => 'Unidad de Valor Constante (UVC)',
		'EEK' => 'Kroon',
		'EGP' => 'Egyptian Pound',
		'ERN' => 'Nakfa',
		'ESP' => 'Spanish Peseta',
		'ETB' => 'Ethiopian Birr',
		'EUR' => 'Euro',
		'FIM' => 'Markka',
		'FJD' => 'Fiji Dollar',
		'FKP' => 'Pound',
		'FRF' => 'French Franc',
		'GBP' => 'Pound Sterling',
		'GEL' => 'Lari',
		'GHC' => 'Cedi',
		'GIP' => 'Gibraltar Pound',
		'GMD' => 'Dalasi',
		'GNF' => 'Guinea Franc',
		'GRD' => 'Drachma',
		'GTQ' => 'Quetzal',
		'GWP' => 'Guinea-Bissau Peso',
		'GYD' => 'Guyana Dollar',
		'HKD' => 'Hong Kong Dollar',
		'HNL' => 'Lempira',
		'HRK' => 'Kuna',
		'HTG' => 'Gourde',
		'HUF' => 'Forint',
		'IDR' => 'Rupiah',
		'IDR' => 'Rupiah',
		'IEP' => 'Irish Pound',
		'ILS' => 'New Israeli Sheqel',
		'INR' => 'Indian Rupee',
		'IQD' => 'Iraqi Dinar',
		'IRR' => 'Iranian Rial',
		'ISK' => 'Iceland Krona',
		'ITL' => 'Italian Lira',
		'JMD' => 'Jamaican Dollar',
		'JOD' => 'Jordanian Dinar',
		'JPY' => 'Yen',
		'KES' => 'Kenyan Shilling',
		'KGS' => 'Som',
		'KHR' => 'Riel',
		'KMF' => 'Comoro Franc',
		'KPW' => 'North Korean Won',
		'KRW' => 'Won',
		'KWD' => 'Kuwaiti Dinar',
		'KYD' => 'Cayman Islands Dollar',
		'KZT' => 'Tenge',
		'LAK' => 'Kip',
		'LBP' => 'Lebanese Pound',
		'LKR' => 'Sri Lanka Rupee',
		'LRD' => 'Liberian Dollar',
		'LSL' => 'Loti',
		'LTL' => 'Lithuanian Litas',
		'LUF' => 'Luxembourg Franc',
		'LVL' => 'Latvian Lats',
		'LYD' => 'Libyan Dinar',
		'MAD' => 'Moroccan Dirham',
		'MAD' => 'Moroccan Dirham',
		'MDL' => 'Moldovan Leu',
		'MGF' => 'Malagasy Franc',
		'MKD' => 'Denar',
		'MMK' => 'Kyat',
		'MNT' => 'Tugrik',
		'MOP' => 'Pataca',
		'MRO' => 'Ouguiya',
		'MTL' => 'Maltese Lira',
		'MUR' => 'Mauritius Rupee',
		'MVR' => 'Rufiyaa',
		'MWK' => 'Kwacha',
		'MXN' => 'Mexican Peso',
		'MXV' => 'Mexican Unidad de Inversion (UDI)',
		'MYR' => 'Malaysian Ringgit',
		'MZM' => 'Metical',
		'NAD' => 'Namibia Dollar',
		'NGN' => 'Naira',
		'NIO' => 'Cordoba Oro',
		'NLG' => 'Netherlands Guilder',
		'NOK' => 'Norwegian Krone',
		'NPR' => 'Nepalese Rupee',
		'NZD' => 'New Zealand Dollar',
		'OMR' => 'Rial Omani',
		'PAB' => 'Balboa',
		'PEN' => 'Nuevo Sol',
		'PGK' => 'Kina',
		'PHP' => 'Philippine Peso',
		'PKR' => 'Pakistan Rupee',
		'PLN' => 'Zloty',
		'PTE' => 'Portuguese Escudo',
		'PYG' => 'Guarani',
		'QAR' => 'Qatari Rial',
		'ROL' => 'Leu',
		'RUB' => 'Russian Ruble',
		'RUR' => 'Russian Ruble',
		'RWF' => 'Rwanda Franc',
		'SAR' => 'Saudi Riyal',
		'SBD' => 'Solomon Islands Dollar',
		'SCR' => 'Seychelles Rupee',
		'SDD' => 'Sudanese Dinar',
		'SEK' => 'Swedish Krona',
		'SGD' => 'Singapore Dollar',
		'SHP' => 'St Helena Pound',
		'SIT' => 'Tolar',
		'SKK' => 'Slovak Koruna',
		'SLL' => 'Leone',
		'SOS' => 'Somali Shilling',
		'SRG' => 'Surinam Guilder',
		'STD' => 'Dobra',
		'SVC' => 'El Salvador Colon',
		'SYP' => 'Syrian Pound',
		'SZL' => 'Lilangeni',
		'THB' => 'Baht',
		'TJR' => 'Tajik Ruble (old)',
		'TJS' => 'Somoni',
		'TMM' => 'Manat',
		'TND' => 'Tunisian Dinar',
		'TOP' => 'Pa\'anga',
		'TPE' => 'Timor Escudo',
		'TRL' => 'Turkish Lira',
		'TTD' => 'Trinidad and Tobago Dollar',
		'TWD' => 'New Taiwan Dollar',
		'TZS' => 'Tanzanian Shilling',
		'UAH' => 'Hryvnia',
		'UGX' => 'Uganda Shilling',
		'USD' => 'US Dollar',
		'USN' => 'US Dollar (Next day)',
		'USS' => 'US Dollar (Same day)',
		'UYU' => 'Peso Uruguayo',
		'UZS' => 'Uzbekistan Sum',
		'VEB' => 'Bolivar',
		'VND' => 'Dong',
		'VUV' => 'Vatu',
		'WST' => 'Tala',
		'XAF' => 'CFA Franc BEAC',
		'XAG' => 'Silver',
		'XAU' => 'Gold Bond Markets Units',
		'XBA' => 'European Composite Unit (EURCO)',
		'XBB' => 'European Monetary Unit (E.M.U.-6)',
		'XBC' => 'European Unit of Account 9 (E.U.A.- 9)',
		'XBD' => 'European Unit of Account 17 (E.U.A.- 17)',
		'XCD' => 'East Caribbean Dollar',
		'XDR' => 'SDR',
		'XFO' => 'Gold-Franc',
		'XFU' => 'UIC-Franc',
		'XOF' => 'CFA Franc BCEAO',
		'XPD' => 'Palladium',
		'XPF' => 'CFP Franc',
		'XPT' => 'Platinum',
		'XTS' => 'Currency for testing purposes',
		'XXX' => 'Transactions without currency involved',
		'YER' => 'Yemeni Rial',
		'YUM' => 'New Dinar',
		'ZAL' => '(financial Rand)',
		'ZAR' => 'Rand',
		'ZMK' => 'Kwacha',
		'ZRN' => 'New Zaire',
		'ZWD' => 'Zimbabwe Dollar'
		);

	//When initializing this and the locale specified cannot be found, the
	//system default will be used
	
	//@name = The name of the locale. Must be a locale available on the
	//host system
	//@cache = (Optional) Whether to use cache or not - will generate a
	//file of format Set_Locale_%locale%.cache. Unless otherwise specified,
	//this will be put into $_ENV['TMPDIR']. Use only if bash if the
	//non-cached way is unusably slow or you have to use a pregenerated
	//cache. In case of a pregenerated cache, most likely the date function
	//will be unusable as well.
	//@cacheloc = (Optional) Where to put the cache (if any). Could be
	//useful to specify where to put a pregenerated cache (if locales are 
	//not available on a system) or if you can't put stuff in $_ENV["TMPDIR"]

	function Set_Locale ($name, $cache = false, $cacheloc = false) {
		exec("locale -a",$this->locales);
		
		$this->locale = $name;
	
		//Find out the cache location in case none is given
		if($cacheloc === false) {
			if(!empty($_ENV["TMPDIR"])) {
				$cacheloc = $_ENV["TMPDIR"];
			} elseif (ini_get('upload_tmp_dir') != "") {
				$cacheloc = ini_get('upload_tmp_dir');			
			} elseif (is_dir("/tmp") && is_writable("/tmp")) {
				$cacheloc = "/tmp";
			} else {
				$cacheloc = false;
				$cache = false;
			}
		}
	
		if($cache !== false) {
			$cache_file = $cacheloc."/Set_Locale_".$name.".cache";
			if(file_exists($cache_file)) {
				$cacheobj = file_get_contents($cache_file);
				$glob = unserialize($cacheobj);
				$this->NUMFORMAT = $glob["NUMFORMAT"];
				$this->MONFORMAT = $glob["MONFORMAT"];
				$this->TIMEFORMAT = $glob["TIMEFORMAT"];
				if(!empty($this->NUMFORMAT) && !empty($this->MONFORMAT) && !empty($this->TIMEFORMAT))
					return true;
				//If any of these is empty, we'll have
				//to fill it with something so tthe
				//functions goes on
			}
		}

		if (!in_array($name,$this->locales)) {
			if(in_array(getenv("LANG"),$this->locales)) {
				$backup_locale = getenv("LANG");
			} elseif(in_array("en_US",$this->locales)) {
				$backup_locale = "en_US";
			} elseif(in_array("es_ES",$this->locales)) {
				$backup_locale = "es_ES";
			} else {
				$backup_locale = "POSIX";
			}
			
			if($backup_locale == "" || !in_array($backup_locale,$this->locales)) {
				trigger_error("Bad locale set (".$name.") and no usable system locale",E_USER_ERROR);
			} else {
				trigger_error("Bad locale set (".$name."). Falling back to ".$backup_locale,E_USER_NOTICE);
				return $this->Set_Locale($backup_locale);
			}			
		}

		putenv("LANG=".$name); //Setting variables in the exec line doesn't work in HTTP mode
		exec("locale -c -k LC_NUMERIC",$numeric);
		exec("locale -c -k LC_MONETARY",$monetary);
		exec("locale -c -k LC_TIME",$time);
		
		foreach ($numeric as $value) {
			$value = split('=',$value);
			if(!empty($value[1])) {
				$this->NUMFORMAT[$value[0]] = str_replace('"','',$value[1]);
			}
		}
		
		foreach ($monetary as $value) {
			$value = split('=',$value);
			if(!empty($value[1])) {
				$this->MONFORMAT[$value[0]] = str_replace('"','',$value[1]);
			}
		}

		foreach ($time as $value) {
			$value = split('=',$value);
			if(!empty($value[1])) {
				$this->TIMEFORMAT[$value[0]] = str_replace('"','',$value[1]);
			}
		}

		if($cache !== false) {
			//Apparently we want to cache and we got this far, so
			//cache is empty
			$glob["NUMFORMAT"] = $this->NUMFORMAT;
			$glob["MONFORMAT"] = $this->MONFORMAT;
			$glob["TIMEFORMAT"] = $this->TIMEFORMAT;
			$cacheobj = serialize($glob);
			$fp = fopen($cache_file,'w+');
			fwrite($fp,$cacheobj);
			fclose($fp);
		}

		return true;
	}

	function Get_Locales_Array () {
		return $this->locales;
	}

	function Get_Locales_SelectBox () {
		foreach ($this->locales as $iter_locale) {
			echo '<select name="'.htmlentities ($iter_locale).'">'.$this->Translate_Locale_HTML($iter_locale).'</select>';
		}
	}

	//Input: Language code (en, es, nl) -> should be lower case to
	//distinguish from country code
	//Output: Language name
	function Get_Language_Name ($code) {
		if (array_key_exists (mb_strtolower($code),$this->lang_codes)) {
			return mb_convert_case($this->lang_codes[$code], MB_CASE_TITLE, "UTF-8");
		}
		return false;
	}

	//Input: Country code (BE, NL, US) -> should be upper case to
	//distinguish from language code
	//Output: Country name
	function Get_Country_Name ($code) {
		if (array_key_exists (mb_strtoupper($code),$this->country_codes)) {
			return mb_convert_case($this->country_codes[$code], MB_CASE_TITLE, "UTF-8");
		}
		return false;
	}
	
	//Input: Money code (USD, EUR) -> should be upper case because of
	//international standards
	//Output: Money name
	function Get_Currency_Name ($code) {
		if (array_key_exists (mb_strtoupper($code),$this->currency_codes)) {
			return $this->currency_codes[$code];
		}
		return false;
	}

	function Put_Translated_Language ($array) {
		if(!is_array ($array)) {
			return false;	
		}
		$this->lang_codes = $array;
		return true;
	}

	function Put_Translated_Country ($array) {
                if(!is_array ($array)) {
			return false;
		}
		$this->country_codes = $array;
	}
	
	function Put_Translated_Currency ($array) {
		if(!is_array ($array)) {
			return false;
		}
		$this->currency_codes = $array;
	}

	function Translate_Locale_HTML ($locale) {
		if(is_array($locale)) {
			$locale = array_walk($locale,'Translate_Locale_HTML');
		} else {
			$locale = $this->Translate_Locale ($locale);
			return htmlentities ($locale);
		}
	}

	function Translate_Locale ($locale) {
		if ($locale == "UTF-8" || $locale == "C" || $locale == "POSIX") {
			return $locale; //Pure UTF-8, C or POSIX is not localized to a country
		}
		
		$lang_country = split ("_",$locale);
		$lang = $lang_country[0];
		$country_char = split ("\.",$lang_country[1]);
		$cntr = isset($country_char[0]) ? $country_char[0] : "";
		$char = isset($country_char[1]) ? $country_char[1] : "";
		unset ($lang_country, $country_char);
		
		$lang = $this->Get_Language_Name ($lang);
		$cntr = $this->Get_Country_Name ($cntr);

		if($lang === false)
			$lang = "Unknown";

		if($cntr === false)
			$cntr = "Unknown";
		
		if($char != "") {
			$char = " - ".$char;
		}
		
		return $lang." (".$cntr.$char.")";
	}

	function Translate_Currency ($currency) {
		$currency = $this->Get_Currency_Name (trim($currency));
		if($currency === false)	
			return "Unknown";
		return $currency;
	}

	//This private function comes back with the precision of a specific
	//number. It can parse scientific and should be extended in case other
	//types of numbers give parsing problems
	private function Parse_Number ($value) {
		if(!is_numeric($value))
			return false;
		
		(float) $value = $value; //Convert string from something into float

		$dot_pos = ltrim(strstr($value,'.'),'.'); //Tests if there is a decimal point in the string, returns the rest of the string, false otherwise
		$sci_val = ltrim(stristr($value,'e'),'eE'); //Tests if the string is scientific notated, returns the rest of the string from E, false otherwise
		if($sci_val != "") {
			preg_match("/[0-9]+[e]/i",$dot_pos,$match);
			$dot_pos = strlen($match[0]) - 1;
			if($sci_val < 0) {
				$precision = ($sci_val * -1) + $dot_pos; //The precision is the exponent + number of digits after comma
			} else {
				if($dot_pos > $sci_val) {
					$precision = $dot_pos - $sci_val; //If the exponent is smaller than the number of digits affter comma, substract
				} else {
					$precision = 0;
				}
			}
		} elseif($dot_pos != "") {
			$precision = strlen($dot_pos);
		} else {
			$precision = "0";
		}
		return $precision;
	}
	
	// This function should get values as a non-formatted numeric string
	// This can accept scientific notation and other forms 
	// Example: Correct - 1000.00
	//		  Correct - 0.1e16
	//		  Incorrect - 1,000.00
	// Returns false if string is not numeric
	//
	// @value = Value to be formatted
	// @precision = Optional - Precision (numbers after the comma). The function will
	// automatically figure this out but if you want to force it, set it

	function fmt_number ($value, $precision = false) {
		if($precision === false) {
			$precision = $this->Parse_Number($value);
		}
		if(empty($this->NUMFORMAT['decimal_point'])) {
			$this->NUMFORMAT['decimal_point'] = "";
		}
		if(empty($this->NUMFORMAT['thousands_sep'])) {
			$this->NUMFORMAT['thousands_sep'] = "";
		}
		return number_format($value,$precision,$this->NUMFORMAT['decimal_point'],$this->NUMFORMAT['thousands_sep']);
	}

	// This function should get values as a non formatted numeric string
	// This cannot reliably accept scientific notations and other forms
	// Example: Correct - 1000.00
	//		Incorrect - 1,000.00
	//		Undefined - 1e-15
	// Returns false if string is not numeric 
	//
	// @value = Value to be formatted
	// @out = Optional - If you want to output the currency in SHORT ($),
	// LONG (United States Dollars) or INTERNATIONAL (USD, EUR)
	// @currency = Optional - If you want to output in another currency
	// than the locale setting. Specify a string (eg. "$" or "USD").
	// Specify the ISO string (USD, EUR) if you specify @out to be LONG.
	// @precision = Optional - Precision (numbers after the comma). The function will
	// automatically figure this out but if you want to force it, set it
	function fmt_money ($value, $out = "INTERNATIONAL", $currency = false, $precision = false) {
		//Calculate the precision
		if($precision === false) {
			if ($out == "SHORT") {
				$precision = $this->MONFORMAT['frac_digits']; //Means we're local if we're outputting short
			} else {
				$precision = $this->MONFORMAT['int_frac_digits']; //Otherwise we're international and adhere to it
			}
		}

		//Calculate the currency
		if($currency !== false) {
			if($out == "LONG") {
				$currency = $this->Translate_Currency($currency);
			}
		} elseif($out == "SHORT") {
			$currency = $this->MONFORMAT['currency_symbol'];
		} elseif($out == "LONG") {
			$currency = $this->Translate_Currency($this->MONFORMAT['int_curr_symbol']);
		} else {
			$currency = $this->MONFORMAT['int_curr_symbol'];
		}

		//Find out the negativity sign
		if($value < 0) {
			$signed = $this->MONFORMAT['negative_sign'];
			$value = $value * -1;
		} else {
			$signed = $this->MONFORMAT['positive_sign'];
		}

		$value = number_format($value,$precision,$this->MONFORMAT['mon_decimal_point'],$this->MONFORMAT['mon_thousands_sep']);
	
		if($out == "LONG") {
			//If output is long, then the currency comes after the
			//value.
			return $signed.$value." ".$currency;
		}	
		return trim($currency)." ".$signed.$value; //This output should be universally (ISO) acceptable
	}

	//This function gets a non-formatted or formatted time string and
	//converts it into a locale formatted timestring

	// @utc: Set to 1 if the output should be UTC. Otherwise local timezone
	// will be used. Unless Timezone offset is specified in the timestring
	// (eg. UNIXTIME, ATOM or RFC) the local timezone will be used for input.

	// @out: Specify the OUTPUT, according to locale can be
	//	DATETIME (Date and Time eg. Thu Aug 14 15:31:45 2008) -
	//	some/most systems will have translated output
	//	LONGTIME (Long Time output eg. 03:33:20 PM) - seems to be
	//	always American-style notation but localized 
	//	DATE 	 (Date output eg. 08/14/08)
	//	TIME	 (Time output eg. 15:33:20) - seems to be always
	//	international-style notation but localized
	//	string $format (A format that can be understood by PHP date)
	// These are the safest definitions. Others can be used, but might not
	// be defined across all locales and all systems

	// @type: Specify the INPUT. Can be 
	//	  UNIXTIME for integer unix timeformat (default)
	//	  MYSQL for MySQL format (2000-07-01 00:00)
	//	  ATOM for strings like 2000-07-01T00:00:00+00:00
	//	RFC822 for RFC822 formatted strings
	//	ENGLISH A US English time string understood by strtotime()
	//	If an integer is sent as value, it will default to UNIXTIME


	// @return: Comes back with a (possible UTF-8) string. Can contain
	// UTF-8 characters which might have to be converted to HTML.

	function fmt_time ($value, $type = "UNIXTIME", $out = "DATETIME", $utc = 0) {
		if(!is_int($value) && $type != "UNIXTIME") {
			switch ($type) {
			case "MYSQL":
				$value = preg_split('/[-:\s]+/i',$value);
				$value = mktime($value[3],$value[4],$value[5],$value[1],$value[2],$value[0]);
				break;
			default:	
				$value = strtotime($value);
			}
		} elseif (!is_numeric($value)) {
			trigger_error("Value given to fmt_time is not Unix Timestamp although it is specified");
			return false;
		}

		if($utc == 1) {
			$utc = "-u";
		} else {
			$utc = "";
		}

		//Command line processing is the safest
		switch ($out) {
		case "DATETIME":
			$output = exec('LANGTEMP=\$LANG; LANG='.$this->locale.'; date '.$utc.' -r '.$value.' +"'.$this->TIMEFORMAT['d_t_fmt'].'"; LANG=\$LANGTEMP');
			break;
		case "LONGTIME":
			$output = exec('LANGTEMP=\$LANG; LANG='.$this->locale.'; date '.$utc.' -r '.$value.' +"'.$this->TIMEFORMAT['t_fmt_ampm'].'"; LANG=\$LANGTEMP');
			break;
		case "DATE":
			$output = exec('LANGTEMP=\$LANG; LANG='.$this->locale.'; date '.$utc.' -r '.$value.' +"'.$this->TIMEFORMAT['d_fmt'].'"; LANG=\$LANGTEMP');
			break;
		case "TIME":
			$output = exec('LANGTEMP=\$LANG; LANG='.$this->locale.'; date '.$utc.' -r '.$value.' +"'.$this->TIMEFORMAT['t_fmt'].'"; LANG=\$LANGTEMP');
			break;
		default:
			//This part cannot be safely localized (see
			//warning with setlocale)
			$output = date($out,$value);
		}
		return $output;
	}
}

?>

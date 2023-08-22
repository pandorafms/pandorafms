import sys
from datetime import datetime
import hashlib

####
# Define some global variables
#########################################################################################

# Entity to character mapping. Contains a few tweaks to make it backward compatible with the previous safe_input implementation.
_ENT2CHR = {
    '#x00': chr(0), 
    '#x01': chr(1), 
    '#x02': chr(2), 
    '#x03': chr(3), 
    '#x04': chr(4), 
    '#x05': chr(5), 
    '#x06': chr(6), 
    '#x07': chr(7), 
    '#x08': chr(8), 
    '#x09': chr(9), 
    '#x0a': chr(10), 
    '#x0b': chr(11), 
    '#x0c': chr(12), 
    '#x0d': chr(13), 
    '#x0e': chr(14), 
    '#x0f': chr(15), 
    '#x10': chr(16), 
    '#x11': chr(17), 
    '#x12': chr(18), 
    '#x13': chr(19), 
    '#x14': chr(20), 
    '#x15': chr(21), 
    '#x16': chr(22), 
    '#x17': chr(23), 
    '#x18': chr(24), 
    '#x19': chr(25), 
    '#x1a': chr(26), 
    '#x1b': chr(27), 
    '#x1c': chr(28), 
    '#x1d': chr(29), 
    '#x1e': chr(30), 
    '#x1f': chr(31), 
    '#x20': chr(32), 
    'quot': chr(34), 
    'amp': chr(38), 
    '#039': chr(39), 
    '#40': chr(40), 
    '#41': chr(41), 
    'lt': chr(60), 
    'gt': chr(62), 
    '#92': chr(92), 
    '#x80': chr(128), 
    '#x81': chr(129), 
    '#x82': chr(130), 
    '#x83': chr(131), 
    '#x84': chr(132), 
    '#x85': chr(133), 
    '#x86': chr(134), 
    '#x87': chr(135), 
    '#x88': chr(136), 
    '#x89': chr(137), 
    '#x8a': chr(138), 
    '#x8b': chr(139), 
    '#x8c': chr(140), 
    '#x8d': chr(141), 
    '#x8e': chr(142), 
    '#x8f': chr(143), 
    '#x90': chr(144), 
    '#x91': chr(145), 
    '#x92': chr(146), 
    '#x93': chr(147), 
    '#x94': chr(148), 
    '#x95': chr(149), 
    '#x96': chr(150), 
    '#x97': chr(151), 
    '#x98': chr(152), 
    '#x99': chr(153), 
    '#x9a': chr(154), 
    '#x9b': chr(155), 
    '#x9c': chr(156), 
    '#x9d': chr(157), 
    '#x9e': chr(158), 
    '#x9f': chr(159), 
    '#xa0': chr(160), 
    '#xa1': chr(161), 
    '#xa2': chr(162), 
    '#xa3': chr(163), 
    '#xa4': chr(164), 
    '#xa5': chr(165), 
    '#xa6': chr(166), 
    '#xa7': chr(167), 
    '#xa8': chr(168), 
    '#xa9': chr(169), 
    '#xaa': chr(170), 
    '#xab': chr(171), 
    '#xac': chr(172), 
    '#xad': chr(173), 
    '#xae': chr(174), 
    '#xaf': chr(175), 
    '#xb0': chr(176), 
    '#xb1': chr(177), 
    '#xb2': chr(178), 
    '#xb3': chr(179), 
    '#xb4': chr(180), 
    '#xb5': chr(181), 
    '#xb6': chr(182), 
    '#xb7': chr(183), 
    '#xb8': chr(184), 
    '#xb9': chr(185), 
    '#xba': chr(186), 
    '#xbb': chr(187), 
    '#xbc': chr(188), 
    '#xbd': chr(189), 
    '#xbe': chr(190), 
    'Aacute': chr(193), 
    'Auml': chr(196), 
    'Eacute': chr(201), 
    'Euml': chr(203), 
    'Iacute': chr(205), 
    'Iuml': chr(207), 
    'Ntilde': chr(209), 
    'Oacute': chr(211), 
    'Ouml': chr(214), 
    'Uacute': chr(218), 
    'Uuml': chr(220), 
    'aacute': chr(225), 
    'auml': chr(228), 
    'eacute': chr(233), 
    'euml': chr(235), 
    'iacute': chr(237), 
    'iuml': chr(239), 
    'ntilde': chr(241), 
    'oacute': chr(243), 
    'ouml': chr(246), 
    'uacute': chr(250), 
    'uuml': chr(252), 
    'OElig': chr(338),
    'oelig': chr(339),
    'Scaron': chr(352),
    'scaron': chr(353),
    'Yuml': chr(376),
    'fnof': chr(402),
    'circ': chr(710),
    'tilde': chr(732),
    'Alpha': chr(913),
    'Beta': chr(914),
    'Gamma': chr(915),
    'Delta': chr(916),
    'Epsilon': chr(917),
    'Zeta': chr(918),
    'Eta': chr(919),
    'Theta': chr(920),
    'Iota': chr(921),
    'Kappa': chr(922),
    'Lambda': chr(923),
    'Mu': chr(924),
    'Nu': chr(925),
    'Xi': chr(926),
    'Omicron': chr(927),
    'Pi': chr(928),
    'Rho': chr(929),
    'Sigma': chr(931),
    'Tau': chr(932),
    'Upsilon': chr(933),
    'Phi': chr(934),
    'Chi': chr(935),
    'Psi': chr(936),
    'Omega': chr(937),
    'alpha': chr(945),
    'beta': chr(946),
    'gamma': chr(947),
    'delta': chr(948),
    'epsilon': chr(949),
    'zeta': chr(950),
    'eta': chr(951),
    'theta': chr(952),
    'iota': chr(953),
    'kappa': chr(954),
    'lambda': chr(955),
    'mu': chr(956),
    'nu': chr(957),
    'xi': chr(958),
    'omicron': chr(959),
    'pi': chr(960),
    'rho': chr(961),
    'sigmaf': chr(962),
    'sigma': chr(963),
    'tau': chr(964),
    'upsilon': chr(965),
    'phi': chr(966),
    'chi': chr(967),
    'psi': chr(968),
    'omega': chr(969),
    'thetasym': chr(977),
    'upsih': chr(978),
    'piv': chr(982),
    'ensp': chr(8194),
    'emsp': chr(8195),
    'thinsp': chr(8201),
    'zwnj': chr(8204),
    'zwj': chr(8205),
    'lrm': chr(8206),
    'rlm': chr(8207),
    'ndash': chr(8211),
    'mdash': chr(8212),
    'lsquo': chr(8216),
    'rsquo': chr(8217),
    'sbquo': chr(8218),
    'ldquo': chr(8220),
    'rdquo': chr(8221),
    'bdquo': chr(8222),
    'dagger': chr(8224),
    'Dagger': chr(8225),
    'bull': chr(8226),
    'hellip': chr(8230),
    'permil': chr(8240),
    'prime': chr(8242),
    'Prime': chr(8243),
    'lsaquo': chr(8249),
    'rsaquo': chr(8250),
    'oline': chr(8254),
    'frasl': chr(8260),
    'euro': chr(8364),
    'image': chr(8465),
    'weierp': chr(8472),
    'real': chr(8476),
    'trade': chr(8482),
    'alefsym': chr(8501),
    'larr': chr(8592),
    'uarr': chr(8593),
    'rarr': chr(8594),
    'darr': chr(8595),
    'harr': chr(8596),
    'crarr': chr(8629),
    'lArr': chr(8656),
    'uArr': chr(8657),
    'rArr': chr(8658),
    'dArr': chr(8659),
    'hArr': chr(8660),
    'forall': chr(8704),
    'part': chr(8706),
    'exist': chr(8707),
    'empty': chr(8709),
    'nabla': chr(8711),
    'isin': chr(8712),
    'notin': chr(8713),
    'ni': chr(8715),
    'prod': chr(8719),
    'sum': chr(8721),
    'minus': chr(8722),
    'lowast': chr(8727),
    'radic': chr(8730),
    'prop': chr(8733),
    'infin': chr(8734),
    'ang': chr(8736),
    'and': chr(8743),
    'or': chr(8744),
    'cap': chr(8745),
    'cup': chr(8746),
    'int': chr(8747),
    'there4': chr(8756),
    'sim': chr(8764),
    'cong': chr(8773),
    'asymp': chr(8776),
    'ne': chr(8800),
    'equiv': chr(8801),
    'le': chr(8804),
    'ge': chr(8805),
    'sub': chr(8834),
    'sup': chr(8835),
    'nsub': chr(8836),
    'sube': chr(8838),
    'supe': chr(8839),
    'oplus': chr(8853),
    'otimes': chr(8855),
    'perp': chr(8869),
    'sdot': chr(8901),
    'lceil': chr(8968),
    'rceil': chr(8969),
    'lfloor': chr(8970),
    'rfloor': chr(8971),
    'lang': chr(9001),
    'rang': chr(9002),
    'loz': chr(9674),
    'spades': chr(9824),
    'clubs': chr(9827),
    'hearts': chr(9829),
    'diams': chr(9830),
}

# Construct the character to entity mapping.
_CHR2ENT = {v: "&" + k + ";" for k, v in _ENT2CHR.items()}

####
# Internal: Alias for output.print_debug function
#########################################################################################

def _print_debug(
        var = "",
        print_errors: bool = False
    ):
    """
    Print the variable as a JSON-like representation for debugging purposes.

    Args:
        var (any): The variable to be printed.
        print_errors (bool): A flag indicating whether to print errors during debugging.
    """
    from .output import print_debug
    print_debug(var, print_errors)

####
# Convert the input_string encoded in html entity to clear char string.
#########################################################################################
def safe_input(
        input_string: str = ""
    ) -> str:
    '''
    Convert an input string encoded in HTML entities to a clear character string.

    Args:
        input_string (str): The input string encoded in HTML entities.

    Returns:
        str: The decoded clear character string.
    '''
    if not input_string:
        return ""
    
    return "".join(_CHR2ENT.get(char, char) for char in input_string)

####
# Convert the html entities to input_string encoded to rebuild char string.
#########################################################################################
def safe_output(
        input_string: str = ""
    ) -> str:
    '''
    Convert HTML entities back to their corresponding characters in the input string.

    Args:
        input_string (str): The input string containing HTML entities.

    Returns:
        str: The decoded clear character string.
    '''
    if not input_string:
        return ""
    
    for char, entity in _CHR2ENT.items():
        input_string = input_string.replace(entity, char)
    
    return input_string

####
# Assign to a key in a dict a given value.
#########################################################################################

def set_dict_key_value(
        input_dict: dict = {},
        input_key: str = "",
        input_value = None
    )-> None:
    """
    Assign a given value to a specified key in a dictionary.

    Args:
        input_dict (dict): The dictionary to which the value will be assigned.
        input_key (str): The key in the dictionary to which the value will be assigned.
        input_value (any): The value to be assigned to the specified key.

    Returns:
        None
    """
    key = input_key.strip()

    if len(key) > 0:
        input_dict[key] = input_value

####
# Return the value of a key in a given dict.
#########################################################################################

def get_dict_key_value(
        input_dict: dict = {},
        input_key: str = ""
    )-> None:
    """
    Return the value associated with a given key in a provided dictionary.

    Args:
        input_dict (dict): The dictionary to search for the key-value pair.
        input_key (str): The key to look up in the dictionary.

    Returns:
        The value associated with the specified key, or None if the key is not found.
    """
    key = input_key.strip()

    if key in input_dict:
        return input_dict[key]
    else:
        return None

####
# Return MD5 hash string.
#########################################################################################

def generate_md5(
        input_string: str = ""
    ) -> str:
    """
    Generates an MD5 hash for the given input string.

    Args:
        input_string (str): The string for which the MD5 hash will be generated.

    Returns:
        str: The MD5 hash of the input string as a hexadecimal string.
    """
    try:
        md5_hash = hashlib.md5(input_string.encode()).hexdigest()
    except:
        md5_hash = ""

    return md5_hash

####
# Returns or print current time in date format or utimestamp.
#########################################################################################

def now(
        utimestamp: bool = False,
        print_flag: bool = False
    ) -> str:
    """
    Get the current time in the specified format or as a Unix timestamp.

    Args:
        utimestamp (bool): Set to True to get the Unix timestamp (epoch time).
        print_flag (bool): Set to True to print the time to standard output.

    Returns:
        str: The current time in the desired format or as a Unix timestamp.
    """
    from .output import print_stdout

    today = datetime.today()
    
    if utimestamp:
        time = datetime.timestamp(today)
    else:
        time = today.strftime('%Y/%m/%d %H:%M:%S')

    if print_flag:
        print_stdout(time)

    return time

####
# Translate macros in string from a dict.
#########################################################################################
def translate_macros(
        macro_dic: dict = {},
        data: str = ""
    ) -> str:
    """
    Replace macros in the input string with their corresponding values.

    Args:
        macro_dic (dict): A dictionary containing macro names and their corresponding values.
        data (str): The input string in which macros should be replaced.

    Returns:
        str: The input string with macros replaced by their values.
    """
    for macro_name, macro_value in macro_dic.items():
        data = data.replace(macro_name, macro_value) 

    return data


####
# Parse configuration file line by line based on separator and return dict.
#########################################################################################

def parse_configuration(
        file: str = "/etc/pandora/pandora_server.conf",
        separator: str = " ",
        default_values: dict = {}
    ) -> dict:
    """
    Parse a configuration file and return its data as a dictionary.

    Args:
        file (str): The path to the configuration file. Defaults to "/etc/pandora/pandora_server.conf".
        separator (str, optional): The separator between option and value. Defaults to " ".
        default_values (dict, optional): A dictionary of default values. Defaults to an empty dictionary.

    Returns:
        dict: A dictionary containing all keys and values from the configuration file.
    """
    from .output import print_stderr

    config = {}
    
    try:
        with open (file, "r") as conf:
            lines = conf.read().splitlines()
            for line in lines:
                if line.strip().startswith("#") or len(line.strip()) < 1 :
                    continue
                else:
                    option, value = line.strip().split(separator, maxsplit=1)
                    config[option.strip()] = value.strip()

    except Exception as e:
        print_stderr(f"{type(e).__name__}: {e}")
    
    for option, value in default_values.items():
        if option.strip() not in config:
            config[option.strip()] = value.strip()

    return config

####
# Parse csv file line by line and return list.
#########################################################################################

def parse_csv_file(
        file: str = "",
        separator: str = ';',
        count_parameters: int = 0,
        print_errors: bool = False
    ) -> list:
    """
    Parse a CSV configuration file and return its data in a list.

    Args:
        file (str): The path to the CSV configuration file.
        separator (str, optional): The separator between values in the CSV. Defaults to ";".
        count_parameters (int, optional): The minimum number of parameters each line should have. Defaults to 0.
        print_errors (bool, optional): Set to True to print errors for lines with insufficient parameters. Defaults to False.

    Returns:
        list: A list containing lists of values for each line in the CSV.
    """
    from .output import print_stderr

    csv_arr = []
    
    try:
        with open (file, "r") as csv:
            lines = csv.read().splitlines()
            for line in lines:
                if line.strip().startswith("#") or len(line.strip()) < 1 :
                    continue
                else:
                    value = line.strip().split(separator)
                    if len(value) >= count_parameters:
                        csv_arr.append(value)
                    elif print_errors==True: 
                        print_stderr(f'Csv line: {line} does not match minimun parameter defined: {count_parameters}')

    except Exception as e:
        print_stderr(f"{type(e).__name__}: {e}")

    return csv_arr

####
# Parse given variable to integer.
#########################################################################################

def parse_int(
        var = None
    ) -> int:
    """
    Parse given variable to integer.

    Args:
        var (any): The variable to be parsed as an integer.

    Returns:
        int: The parsed integer value. If parsing fails, returns 0.
    """
    try:
        return int(var)
    except:
        return 0

####
# Parse given variable to float.
#########################################################################################

def parse_float(
        var = None
    ) -> float:
    """
    Parse given variable to float.

    Args:
        var (any): The variable to be parsed as an float.

    Returns:
        float: The parsed float value. If parsing fails, returns 0.
    """
    try:
        return float(var)
    except:
        return 0

####
# Parse given variable to string.
#########################################################################################

def parse_str(
        var = None
    ) -> str:
    """
    Parse given variable to string.

    Args:
        var (any): The variable to be parsed as an string.

    Returns:
        str: The parsed string value. If parsing fails, returns "".
    """
    try:
        return str(var)
    except:
        return ""

####
# Parse given variable to bool.
#########################################################################################

def parse_bool(
        var = None
    ) -> bool:
    """
    Parse given variable to bool.

    Args:
        var (any): The variable to be parsed as an bool.

    Returns:
        bool: The parsed bool value. If parsing fails, returns False.
    """
    try:
        return bool(var)
    except:
        return False
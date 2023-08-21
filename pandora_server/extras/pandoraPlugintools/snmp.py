from easysnmp import Session, TrapSender

####
# Define global variables dict, used in functions as default values.
# Its values can be changed.
#########################################################################################

_GLOBAL_VARIABLES = {
    'hostname'          : '',
    'version'           : 1,
    'community'         : 'public',
    'user'              : '',
    'auth_protocol'     : "",
    'auth_password'     : "",
    'privacy_protocol'  : "",
    'privacy_password'  : "",
    'security_level'    : "noAuthNoPriv",
    'timeout'           : 2,
    'retries'           : 1,
    'remote_port'       : 161,
}

####
# Set a global variable with the specified name and assigns a value to it.
#########################################################################################
def set_global_variable(
        variable_name: str = "", 
        value = None
    )-> None:
    """
    Sets the value of a global variable in the '_GLOBAL_VARIABLES' dictionary.

    Args:
        variable_name (str): Name of the variable to set.
        value (any): Value to assign to the variable.
    
    Returns:
        None
    """
    from .general import set_dict_key_value

    set_dict_key_value(_GLOBAL_VARIABLES, variable_name, value)

####
# Get a global variable with the specified name.
#########################################################################################
def get_global_variable(
        variable_name: str = ""
    )-> None:
    """
    Gets the value of a global variable in the '_GLOBAL_VARIABLES' dictionary.

    Args:
        variable_name (str): Name of the variable to set.

    Returns:
        None
    """
    from .general import get_dict_key_value

    get_dict_key_value(_GLOBAL_VARIABLES, variable_name)

####
# Creates an SNMP session based on the global configuration variables.
#########################################################################################
def create_snmp_session(
        host=None,
        version=None,
        community=None,
        user=None,
        auth_protocol=None,
        auth_password=None,
        privacy_protocol=None,
        privacy_password=None,
        security_level=None,
        timeout=None,
        retries=None,
        remote_port=None
    ):
    """
    Creates an SNMP session based on the provided configuration or global variables.

    Args:
        hostname (str): Hostname or IP address of the SNMP agent.
        version (int): SNMP version (1, 2, or 3).
        community (str): SNMP community string (for version 1 or 2).
        user (str): SNMPv3 username (for version 3).
        auth_protocol (str): SNMPv3 authentication protocol (e.g., 'MD5' or 'SHA').
        auth_password (str): SNMPv3 authentication password.
        privacy_protocol (str): SNMPv3 privacy protocol (e.g., 'AES' or 'DES').
        privacy_password (str): SNMPv3 privacy password.
        security_level (str): SNMPv3 security level ('noAuthNoPriv', 'authNoPriv', 'authPriv').
        timeout (int): SNMP request timeout in seconds.
        retries (int): Number of SNMP request retries.
        remote_port (int): SNMP agent port.

    Returns:
        Session: An SNMP session configured based on the provided or global variables.
    """
    host = _GLOBAL_VARIABLES['hostname']
    version = _GLOBAL_VARIABLES['version']
    community = _GLOBAL_VARIABLES['community']
    user = _GLOBAL_VARIABLES['user']
    auth_protocol = _GLOBAL_VARIABLES['auth_protocol']
    auth_password = _GLOBAL_VARIABLES['auth_password']
    privacy_protocol = _GLOBAL_VARIABLES['privacy_protocol']
    privacy_password = _GLOBAL_VARIABLES['privacy_password']
    security_level = _GLOBAL_VARIABLES['security_level']
    timeout = _GLOBAL_VARIABLES['timeout']
    retries = _GLOBAL_VARIABLES['retries']
    remote_port = _GLOBAL_VARIABLES['remote_port']

    session_kwargs = {
        "hostname": host,
        "version": version,
        "use_numeric": True,
        "timeout": timeout,
        "retries": retries,
        "remote_port": remote_port
    }

    if version == 1 or version == 2:
        session_kwargs["community"] = community
    elif version == 3:
        session_kwargs["security_username"] = user

        if security_level == "authPriv":
            session_kwargs.update({
                "auth_protocol": auth_protocol,
                "auth_password": auth_password,
                "privacy_protocol": privacy_protocol,
                "privacy_password": privacy_password,
                "security_level": "auth_with_privacy"
            })
        elif security_level == "authNoPriv":
            session_kwargs.update({
                "auth_protocol": auth_protocol,
                "auth_password": auth_password,
                "security_level": "auth_without_privacy"
            })
        elif security_level == "noAuthNoPriv":
            session_kwargs["security_level"] = "no_auth_or_privacy"

    return Session(**session_kwargs)


def snmp_get(
        oid: str
    ) -> str:
    """
    Performs an SNMP GET operation to retrieve the value of a specified OID.

    Args:
        oid (str): The OID (Object Identifier) for the SNMP GET operation.

    Returns:
        str: The value retrieved from the specified OID.
    """
    session = create_snmp_session() 
    return session.get(oid)

def snmp_walk(
        oid: str
    ) -> list:
    """
    Performs an SNMP WALK operation to retrieve a list of values from a subtree of the MIB.

    Args:
        oid (str): The OID (Object Identifier) representing the root of the subtree.

    Returns:
        list: A list of values retrieved from the specified subtree.
    """
    session = create_snmp_session()  
    return session.walk(oid)

####
# Sends an SNMP trap to the specified destination IP using the given OID, value, and community.
#########################################################################################
def snmp_trap(
        trap_oid: str,
        trap_value: str,
        destination_ip: str,
        community: str
    ) -> None:
    """
    Sends an SNMP trap to the specified destination IP using the given OID, value, and community.

    Args:
        trap_oid (str): The OID (Object Identifier) for the SNMP trap.
        trap_value (str): The value associated with the trap.
        destination_ip (str): The IP address of the trap's destination.
        community (str): The SNMP community string for authentication.

    Returns:
        None
    """
    trap = TrapSender()

    trap.trap_oid = trap_oid
    trap.trap_value = trap_value
    trap.destination_ip = destination_ip
    trap.community = community

    trap.send_trap()

class SNMPManager:
    def __init__(self):
        self.global_variables = {
            'hostname': '',
            'version': 1,
            'community': 'public',
            'user': '',
            'auth_protocol': "",
            'auth_password': "",
            'privacy_protocol': "",
            'privacy_password': "",
            'security_level': "noAuthNoPriv",
            'timeout': 2,
            'retries': 1,
            'remote_port': 161,
        }
    
    def set_global_variable(self, variable_name, value):
        """
        Sets the value of a global variable in the SNMPManager instance.

        Args:
            variable_name (str): Name of the variable to set.
            value (any): Value to assign to the variable.
        
        Returns:
            None
        """
        self.global_variables[variable_name] = value
    
    def get_global_variable(self, variable_name):
        """
        Gets the value of a global variable from the SNMPManager instance.

        Args:
            variable_name (str): Name of the variable to retrieve.

        Returns:
            any: The value of the specified variable, or None if not found.
        """
        return self.global_variables.get(variable_name)
    
    def create_snmp_session(self):
        """
        Creates an SNMP session based on the global configuration variables in the SNMPManager instance.

        Returns:
            None
        """
        host = _GLOBAL_VARIABLES['hostname']
        version = _GLOBAL_VARIABLES['version']
        community = _GLOBAL_VARIABLES['community']
        user = _GLOBAL_VARIABLES['user']
        auth_protocol = _GLOBAL_VARIABLES['auth_protocol']
        auth_password = _GLOBAL_VARIABLES['auth_password']
        privacy_protocol = _GLOBAL_VARIABLES['privacy_protocol']
        privacy_password = _GLOBAL_VARIABLES['privacy_password']
        security_level = _GLOBAL_VARIABLES['security_level']
        timeout = _GLOBAL_VARIABLES['timeout']
        retries = _GLOBAL_VARIABLES['retries']
        remote_port = _GLOBAL_VARIABLES['remote_port']

        session_kwargs = {
            "hostname": host,
            "version": version,
            "use_numeric": True,
            "timeout": timeout,
            "retries": retries,
            "remote_port": remote_port
        }

        if version == 1 or version == 2:
            session_kwargs["community"] = community
        elif version == 3:
            session_kwargs["security_username"] = user

            if security_level == "authPriv":
                session_kwargs.update({
                    "auth_protocol": auth_protocol,
                    "auth_password": auth_password,
                    "privacy_protocol": privacy_protocol,
                    "privacy_password": privacy_password,
                    "security_level": "auth_with_privacy"
                })
            elif security_level == "authNoPriv":
                session_kwargs.update({
                    "auth_protocol": auth_protocol,
                    "auth_password": auth_password,
                    "security_level": "auth_without_privacy"
                })
            elif security_level == "noAuthNoPriv":
                session_kwargs["security_level"] = "no_auth_or_privacy"
        
        self.session = Session(**session_kwargs)
    
    def snmp_get(self, oid):
        """
        Performs an SNMP GET operation to retrieve the value of a specified OID.

        Args:
            oid (str): The OID (Object Identifier) for the SNMP GET operation.

        Returns:
            str: The value retrieved from the specified OID.
        """
        return self.session.get(oid)
    
    def snmp_walk(self, oid):
        """
        Performs an SNMP WALK operation to retrieve a list of values from a subtree of the MIB.

        Args:
            oid (str): The OID (Object Identifier) representing the root of the subtree.

        Returns:
            list: A list of values retrieved from the specified subtree.
        """
        return self.session.walk(oid)
    
    def snmp_trap(self, trap_oid, trap_value, destination_ip, community):
        """
        Sends an SNMP trap to the specified destination IP using the given OID, value, and community.

        Args:
            trap_oid (str): The OID (Object Identifier) for the SNMP trap.
            trap_value (str): The value associated with the trap.
            destination_ip (str): The IP address of the trap's destination.
            community (str): The SNMP community string for authentication.

        Returns:
            None
        """
        trap = TrapSender()
        trap.trap_oid = trap_oid
        trap.trap_value = trap_value
        trap.destination_ip = destination_ip
        trap.community = community
        trap.send_trap()
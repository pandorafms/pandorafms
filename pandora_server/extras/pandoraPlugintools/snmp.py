from easysnmp import Session
from pysnmp.hlapi import *

####
# Define global variables dict, used in functions as default values.
# Its values can be changed.
#########################################################################################

_GLOBAL_VARIABLES = {
    'hostname'          : "",
    'version'           : 1,
    'community'         : "public",
    'user'              : "",
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
# A class that represents an SNMP target, providing methods for setting up SNMP configuration and performing SNMP operations like GET and WALK.
#########################################################################################
class SNMPTarget:
    """
    A class that represents an SNMP target, providing methods for setting up SNMP configuration
    and performing SNMP operations like GET and WALK.
    """
    def __init__(
        self,
        host: str = _GLOBAL_VARIABLES['hostname'],
        version: int = _GLOBAL_VARIABLES['version'],
        community: str = _GLOBAL_VARIABLES['community'],
        user: str = _GLOBAL_VARIABLES['user'],
        auth_protocol: str = _GLOBAL_VARIABLES['auth_protocol'],
        auth_password: str = _GLOBAL_VARIABLES['auth_password'],
        privacy_protocol: str = _GLOBAL_VARIABLES['privacy_protocol'],
        privacy_password: str = _GLOBAL_VARIABLES['privacy_password'],
        security_level: str = _GLOBAL_VARIABLES['security_level'],
        timeout: int = _GLOBAL_VARIABLES['timeout'],
        retries: int = _GLOBAL_VARIABLES['retries'],
        remote_port: int = _GLOBAL_VARIABLES['remote_port']):

        self.session = create_snmp_session(
        host,
        version,
        community,
        user,
        auth_protocol,
        auth_password,
        privacy_protocol,
        privacy_password,
        security_level,
        timeout,
        retries,
        remote_port
        )
    
    ####
    # Performs an SNMP GET operation to retrieve the value of a specified OID.
    #########################################################################################
    def snmp_get(self, oid):
        """
        Performs an SNMP GET operation to retrieve the value of a specified OID.

        Args:
            oid (str): The OID (Object Identifier) for the SNMP GET operation.

        Returns:
            str: The value retrieved from the specified OID.
        """
        return self.session.get(oid).value
    
    ####
    # Performs an SNMP WALK operation to retrieve a list of values from a subtree of the MIB.
    #########################################################################################
    def snmp_walk(self, oid):
        """
        Performs an SNMP WALK operation to retrieve a list of values from a subtree of the MIB.

        Args:
            oid (str): The OID (Object Identifier) representing the root of the subtree.

        Returns:
            list: A list of values retrieved from the specified subtree.
        """

        oid_items = self.session.walk(oid)
        
        oid_value_dict = {}  # Initialize an empty dictionary
        
        for item in oid_items:
            oid_with_index = f"{item.oid}.{item.oid_index}"
            oid_value_dict[oid_with_index] = item.value
        
        return oid_value_dict
   
####
# Creates an SNMP session based on the global configuration variables.
#########################################################################################
def create_snmp_session(
        host: str = _GLOBAL_VARIABLES['hostname'],
        version: int = _GLOBAL_VARIABLES['version'],
        community: str = _GLOBAL_VARIABLES['community'],
        user: str = _GLOBAL_VARIABLES['user'],
        auth_protocol: str = _GLOBAL_VARIABLES['auth_protocol'],
        auth_password: str = _GLOBAL_VARIABLES['auth_password'],
        privacy_protocol: str = _GLOBAL_VARIABLES['privacy_protocol'],
        privacy_password: str = _GLOBAL_VARIABLES['privacy_password'],
        security_level: str = _GLOBAL_VARIABLES['security_level'],
        timeout: int = _GLOBAL_VARIABLES['timeout'],
        retries: int = _GLOBAL_VARIABLES['retries'],
        remote_port: int = _GLOBAL_VARIABLES['remote_port']
    ) -> Session:
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

####
# Performs an SNMP GET operation to retrieve the value of a specified OID.
#########################################################################################
def snmp_get(
        oid: str,
        host: str = _GLOBAL_VARIABLES['hostname'],
        version: int = _GLOBAL_VARIABLES['version'],
        community: str = _GLOBAL_VARIABLES['community'],
        user: str = _GLOBAL_VARIABLES['user'],
        auth_protocol: str = _GLOBAL_VARIABLES['auth_protocol'],
        auth_password: str = _GLOBAL_VARIABLES['auth_password'],
        privacy_protocol: str = _GLOBAL_VARIABLES['privacy_protocol'],
        privacy_password: str = _GLOBAL_VARIABLES['privacy_password'],
        security_level: str = _GLOBAL_VARIABLES['security_level'],
        timeout: int = _GLOBAL_VARIABLES['timeout'],
        retries: int = _GLOBAL_VARIABLES['retries'],
        remote_port: int = _GLOBAL_VARIABLES['remote_port']
    ) -> str:
    """
    Performs an SNMP GET operation to retrieve the value of a specified OID.

    Args:
        oid (str): The OID (Object Identifier) for the SNMP GET operation.

    Returns:
        str: The value retrieved from the specified OID.
    """
    session = create_snmp_session(host,version,community,user,auth_protocol,auth_password,privacy_protocol,privacy_password,security_level,timeout,retries,remote_port) 
    return session.get(oid).value

####
# Performs an SNMP WALK operation to retrieve a list of values from a subtree of the MIB.
#########################################################################################
def snmp_walk(
        oid: str,
        host: str = _GLOBAL_VARIABLES['hostname'],
        version: int = _GLOBAL_VARIABLES['version'],
        community: str = _GLOBAL_VARIABLES['community'],
        user: str = _GLOBAL_VARIABLES['user'],
        auth_protocol: str = _GLOBAL_VARIABLES['auth_protocol'],
        auth_password: str = _GLOBAL_VARIABLES['auth_password'],
        privacy_protocol: str = _GLOBAL_VARIABLES['privacy_protocol'],
        privacy_password: str = _GLOBAL_VARIABLES['privacy_password'],
        security_level: str = _GLOBAL_VARIABLES['security_level'],
        timeout: int = _GLOBAL_VARIABLES['timeout'],
        retries: int = _GLOBAL_VARIABLES['retries'],
        remote_port: int = _GLOBAL_VARIABLES['remote_port']
    ) -> dict:
    """
    Performs an SNMP WALK operation to retrieve a list of values from a subtree of the MIB.

    Args:
        oid (str): The OID (Object Identifier) representing the root of the subtree.

    Returns:
        list: A list of values retrieved from the specified subtree.
    """
    session = create_snmp_session(host,version,community,user,auth_protocol,auth_password,privacy_protocol,privacy_password,security_level,timeout,retries,remote_port)  
    oid_items = session.walk(oid)
    
    oid_value_dict = {}  
    
    for item in oid_items:
        oid_with_index = f"{item.oid}.{item.oid_index}"
        oid_value_dict[oid_with_index] = item.value
    
    return oid_value_dict

####
# Sends an SNMP trap to the specified destination IP using the given OID, value, and community.
#########################################################################################
def snmp_trap(
        trap_oid: str, 
        trap_value: str, 
        destination_ip: str, 
        community: str) -> None:
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
    trap_object = ObjectIdentity(trap_oid)
    trap_value = OctetString(trap_value)

    errorIndication, errorStatus, errorIndex, varBinds = next(
        sendNotification(
            SnmpEngine(),
            CommunityData(community),
            UdpTransportTarget((destination_ip, 162)),
            ContextData(),
            'trap',
            NotificationType(
                ObjectIdentity('SNMPv2-MIB', 'coldStart')
            ).addVarBinds(
                (trap_object, trap_value)
            )
        )
    )

    if errorIndication:
        print('Error:', errorIndication)
    elif errorStatus:
        print(
            '%s at %s' %
            (
                errorStatus.prettyPrint(),
                errorIndex and varBinds[int(errorIndex) - 1][0] or '?'
            )
        )
    else:
        print('SNMP trap sent successfully.')
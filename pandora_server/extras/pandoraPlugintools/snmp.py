from easysnmp import Session, TrapSender

####
# Define global variables dict, used in functions as default values.
# Its values can be changed.
#########################################################################################

_GLOBAL_VARIABLES = {
    'hostname'          : '',
    'version'           : 3,
    'community'         : 'public',
    'user'              : '',
    'auth_protocol'     : "",
    'auth_password'     : "",
    'privacy_protocol'  : "",
    'privacy_password'  : "",
    'security_level'    : "authPriv",
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
def create_snmp_session():
    """
    Creates an SNMP session based on the global configuration variables.

    Returns:
        Session: An SNMP session configured based on the global variables.
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


####
# Performs an SNMP GET operation to retrieve the value of a specified OID.
#########################################################################################
def snmp_get(
        session: Session,
        oid: str
    ) -> str:
    """
    Performs an SNMP GET operation to retrieve the value of a specified OID.

    Args:
        session (Session): The SNMP session to use for the operation.
        oid (str): The OID (Object Identifier) for the SNMP GET operation.

    Returns:
        str: The value retrieved from the specified OID.
    """
    return session.get(oid)

####
# Performs an SNMP WALK operation to retrieve a list of values from a subtree of the MIB.
#########################################################################################
def snmp_walk(
        session: Session,
        oid: str
    ) -> list:
    """
    Performs an SNMP WALK operation to retrieve a list of values from a subtree of the MIB.

    Args:
        session (Session): The SNMP session to use for the operation.
        oid (str): The OID (Object Identifier) representing the root of the subtree.

    Returns:
        list: A list of values retrieved from the specified subtree.
    """
    return session.walk(oid)

####
# Sends an SNMP trap to the specified destination IP using the given OID, value, and community.
#########################################################################################
def send_trap(
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
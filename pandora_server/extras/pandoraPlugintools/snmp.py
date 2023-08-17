from easysnmp import Session

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

def create_snmp_session():
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

def snmp_get(session, oid):
    return session.get(oid)

def snmp_walk(session, oid):
    return session.walk(oid)

from requests_ntlm import HttpNtlmAuth
from requests.auth import HTTPBasicAuth
from requests.auth import HTTPDigestAuth
from requests.sessions import Session

####
# Auth URL session
#########################################################################################

def auth_call(
        session = None,
        authtype: str = "basic",
        user: str = "",
        passw: str = ""
    ):
    """
    Authentication for url request. Requires request.sessions.Session() object.

    Args:
    - session (object): request Session() object.
    - authtype (str): 'ntlm', 'basic' or 'digest'.
    - user (str): auth user.
    - passw (str): auth password.
    """
    if session is not None:
        if authtype == 'ntlm':
            session.auth = HttpNtlmAuth(user, passw)
        elif authtype == 'basic':
            session.auth = HTTPBasicAuth(user, passw)
        elif authtype == 'digest':
            session.auth = HTTPDigestAuth(user, passw)

####
# Call URL and return output
#########################################################################################

def call_url(
        url: str = "",
        authtype: str = "basic",
        user: str = "",
        passw: str = "",
        timeout: int = 1
    ) -> str:
    """
    Call URL. Uses request module to get url contents.

    Args:
    - url (str): URL
    - authtype (str): ntlm', 'basic', 'digest'. Optional.
    - user (str): auth user. Optional.
    - passw (str): auth password. Optional.
    - timeout (int): session timeout seconds. Optional.

    Returns:
    - str: call output
    """
    # using with so we make sure the session is closed even when exceptions are encountered
    with Session() as session:
        if authtype != None:
            auth_call(session, authtype, user, passw)
        
        output = ""

        try:
            output = session.get(url, timeout=timeout, verify=False)
        except ValueError:
            output = "Error: URL format not valid (example http://myserver/page.php)"
        except Exception as e:
            output = f"{type(e).__name__}:\t{str(e)}"
        
        return output

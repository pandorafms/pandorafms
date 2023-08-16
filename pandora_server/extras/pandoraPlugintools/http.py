import urllib3
import warnings
from requests.sessions import Session
from requests_ntlm import HttpNtlmAuth
from requests.auth import HTTPBasicAuth
from requests.auth import HTTPDigestAuth

####
# Internal: Alias for output.print_debug function
#########################################################################################

def _print_debug(
        var = "",
        print_errors: bool = False
    ):
    """
    Prints any list, dict, string, float or integer as a json
    """
    from .output import print_debug
    print_debug(var, print_errors)

####
# Internal: Auth URL session
#########################################################################################

def _auth_call(
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
        timeout: int = 1,
        verify: bool = True,
        print_errors: bool = False
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
    from .output import print_stderr

    if url == "":
        if print_errors:
            print_stderr("Error: URL not provided")
        return None
    else:
        # using with so we make sure the session is closed even when exceptions are encountered
        with Session() as session:
            if authtype is not None:
                _auth_call(session, authtype, user, passw)
            
            output = ""

            try:
                with warnings.catch_warnings():
                    warnings.filterwarnings("ignore", category=urllib3.exceptions.InsecureRequestWarning)
                    response = session.get(url, timeout=timeout, verify=verify)
                    response.raise_for_status()  # Raise an exception for non-2xx responses
                    return response.content
            except requests.exceptions.Timeout:
                if print_errors:
                    print_stderr("Error: Request timed out")
            except requests.exceptions.RequestException as e:
                if print_errors:
                    print_stderr(f"RequestException:\t{e}")
            except ValueError:
                if print_errors:
                    print_stderr("Error: URL format not valid (example http://myserver/page.php)")

            return None

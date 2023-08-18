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
    Print the provided variable as JSON, supporting various data types.

    Args:
        var (any): The variable to be printed as JSON.
        print_errors (bool): Set to True to print errors encountered during printing.
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
    )-> None:
    """
    Perform authentication for URL requests using various authentication types.

    Args:
        session (object, optional): The request Session() object. Defaults to None.
        authtype (str, optional): The authentication type. Supported values: 'ntlm', 'basic', or 'digest'. Defaults to 'basic'.
        user (str, optional): The authentication user. Defaults to an empty string.
        passw (str, optional): The authentication password. Defaults to an empty string.

    Returns:
        None
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
    Call a URL and return its contents.

    Args:
        url (str): The URL to call.
        authtype (str, optional): The authentication type. Supported values: 'ntlm', 'basic', 'digest'. Defaults to 'basic'.
        user (str, optional): The authentication user. Defaults to an empty string.
        passw (str, optional): The authentication password. Defaults to an empty string.
        timeout (int, optional): The session timeout in seconds. Defaults to 1.
        print_errors (bool, optional): Set to True to print errors encountered during the call. Defaults to False.

    Returns:
        str: The output from the URL call.
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

try:
    from Crypto.Cipher import AES
    from Crypto.Util.Padding import pad, unpad
except ImportError as e:
    import sys
    from .output import print_stderr
    print_stderr("ModuleNotFoundError: No module named 'pycryptodome'")
    sys.exit(1)

import hashlib
import base64
import hmac
from binascii import unhexlify

####
# Define encription internal global variables.
#########################################################################################

_PASSWORD = "default_salt"

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
# Internal use only: Get AES cipher
#########################################################################################
def _get_cipher(
        password: str = _PASSWORD
    ) -> AES:
    '''
    Internal use only: Get AES cipher for encryption and decryption.

    Args:
        password (str): The password used to derive the encryption key.

    Returns:
        AES: An AES cipher instance for encryption and decryption.
    '''
    key = b''
    msg = password.encode('utf-8')
    hash_obj = hmac.new(key, msg, hashlib.sha256)
    hash_result = hash_obj.digest()
    hash_base64 = base64.b64encode(hash_result)[:16].decode()
    
    iv = b'0000000000000000'
    
    return AES.new(hash_base64.encode(), AES.MODE_CBC, iv)

####
# Return encrypted string
#########################################################################################
def encrypt_AES(
        str_to_encrypt: str = "",
        password: str = _PASSWORD
    ) -> str:
    '''
    Encrypt a string using AES encryption.

    Args:
        str_to_encrypt (str): The string to be encrypted.
        password (str): The password used to derive the encryption key.

    Returns:
        str: The encrypted string in base64 encoding.
    '''
    cipher = _get_cipher(password)
    
    try:
        msg_padded = pad(str_to_encrypt.encode(), AES.block_size, style='pkcs7')
        cipher_text = cipher.encrypt(msg_padded)
        b64str = base64.b64encode(cipher_text).decode()
    except:
        b64str = ''
    
    return b64str

####
# Return decrypted string
#########################################################################################
def decrypt_AES(
        str_to_decrypt: str = "",
        password: str = _PASSWORD
    ) -> str:
    '''
    Decrypt an encrypted string using AES decryption.

    Args:
        str_to_decrypt (str): The encrypted string to be decrypted.
        password (str): The password used to derive the encryption key.

    Returns:
        str: The decrypted string.
    '''
    cipher = _get_cipher(password)
    
    try:
        decrypted_str = unpad(cipher.decrypt(base64.b64decode(str_to_decrypt)), AES.block_size, style='pkcs7').decode().strip()
    except:
        decrypted_str = ''

    return decrypted_str
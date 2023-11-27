import sys
import os
import json

####
# Internal: Alias for output.print_debug function
#########################################################################################

def _print_debug(
        var = "",
        print_errors: bool = False
    ):
    """
    Prints any list, dict, string, float or integer as a json

    Args:
        var (any, optional): Variable to be printed. Defaults to "".
        print_errors (bool, optional): Flag to indicate whether to print errors. Defaults to False.
    """
    print_debug(var, print_errors)

####
# Prints message in stdout
#########################################################################################

def print_stdout(
        message: str = ""
    )-> None:
    """
    Prints message in stdout

    Args:
        message (str, optional): Message to be printed. Defaults to "".

    Returns:
        None
    """
    print(message)

####
# Prints message in stderr
#########################################################################################

def print_stderr(
        message: str = ""
    )-> None:
    """
    Prints message in stderr

    Args:
        message (str, optional): Message to be printed. Defaults to "".

    Returns:
        None
    """
    print(message, file=sys.stderr)

####
# Prints dictionary in formatted json string.
#########################################################################################

def print_debug(
        var = "",
        print_errors: bool = False
    )-> None:
    """
    Prints any list, dict, string, float or integer as a json

    Args:
        var: Variable to be printed.
        print_errors (bool, optional): Whether to print errors. Defaults to False.

    Returns:
        None
    """
    try:
        debug_json = json.dumps(var, indent=4)
        print_stdout(debug_json)
    except json.JSONDecodeError as e:
        if print_errors:
            print_stderr(f"debug_dict: Failed to dump. Error: {e}")
    except Exception as e:
        if print_errors:
            print_stderr(f"debug_dict: Unexpected error: {e}")

####
# Add new line to log file
#########################################################################################
def logger(
        log_file: str = "",
        message: str = "",
        log_level: str = "",
        add_date: bool = True,
        print_errors: bool = False
    ) -> bool:
    '''
    Add new line to log file

    Args:
        log_file (str): Path to the log file.
        message (str): Message to be added to the log.
        log_level (str): Log level, if applicable. Defaults to an empty string.
        add_date (bool): Whether to add the current date and time to the log entry. Defaults to True.
        print_errors (bool): Whether to print errors. Defaults to False.

    Returns:
        bool: True if the log entry was successfully added, False otherwise.
    '''
    from .general import now

    try:
        if not os.path.exists(log_file):
            with open(log_file, 'w') as file:
                pass  # Creates an empty file
        elif not os.access(log_file, os.W_OK):
            if print_errors:
                print_stderr(f"Log file '{log_file}' is not writable.")
            return False

        with open(log_file, 'a') as file:
            final_message = ""

            if add_date:
                final_message += now() + " "
            if log_level != "":
                final_message += "[" + log_level + "] "

            final_message += message + "\n"

            file.write(final_message)

            return True
    
    except Exception as e:
        if print_errors:
            print_stderr(f"An error occurred while appending to the log: {e}")
        return False
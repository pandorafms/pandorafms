import sys
import os
import json

####
# Prints message in stdout
#########################################################################################

def print_stdout(
        message: str = ""
    ):
    """
    Prints message in stdout
    """
    print(message)

####
# Prints message in stderr
#########################################################################################

def print_stderr(
        message: str = ""
    ):
    """
    Prints message in stderr
    """
    print(message, file=sys.stderr)

####
# Prints dictionary in formatted json string.
#########################################################################################

def print_debug(
        var = "",
        print_errors: bool = False
    ):
    """
    Prints any list, dict, string, float or integer as a json
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
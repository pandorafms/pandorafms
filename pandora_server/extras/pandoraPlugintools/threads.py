import sys
from queue import Queue
from threading import Thread
from multiprocessing import Pool, Manager

####
# Define multi-processing internal global variables.
#########################################################################################

_MANAGER = Manager()
_SHARED_DICT = _MANAGER.dict()
_SHARED_DICT_LOCK = _MANAGER.Lock()

####
# Internal: Alias for output.print_debug function
#########################################################################################

def _print_debug(
        var = "",
        print_errors: bool = False
    ):
    """
    Prints the provided variable in a JSON-like format.
    
    Args:
        var: The variable (list, dict, string, float, integer) to be printed.
        print_errors (bool): If True, prints any errors that occur during formatting.
    """
    from .output import print_debug
    print_debug(var, print_errors)

####
# Internal use only: Run a given function in a thread
#########################################################################################
def _single_thread(
        q = None,
        function: callable = None,
        errors: list = []
    ):
    """
    Internal use only: Runs a given function in a thread.

    Args:
        q: A queue from which to get parameters for the function.
        function (callable): The function to be executed in the thread.
        errors (list): A list to store any errors encountered during execution.
    """
    params=q.get()
    q.task_done()
    try:
        function(params)
    except Exception as e:
        errors.append("Error while runing single thread: "+str(e))

####
# Run a given function for given items list in a given number of threads
#########################################################################################
def run_threads(
        max_threads: int = 1,
        function: callable = None,
        items: list = [],
        print_errors: bool = False
    ) -> bool:
    """
    Run a given function for a list of items in multiple threads.

    Args:
        max_threads (int): Maximum number of threads to use.
        function (callable): The function to be executed in each thread.
        items (list): List of items to process.
        print_errors (bool): Whether to print errors encountered during execution.

    Returns:
        bool: True if all threads executed successfully, False otherwise.
    """
    from .output import print_stderr

    # Assign threads
    threads = max_threads

    if threads > len(items):
        threads = len(items)

    if threads < 1:
        threads = 1

    # Distribute items per thread
    items_per_thread = []
    thread = 0
    for item in items:
        if not 0 <= thread < len(items_per_thread):
            items_per_thread.append([])
        
        items_per_thread[thread].append(item)

        thread += 1
        if thread >= threads:
            thread=0

    # Run threads
    try:
        q=Queue()
        for n_thread in range(threads) :
            q.put(items_per_thread[n_thread])

        run_threads = []
        errors = []

        for n_thread in range(threads):
            t = Thread(target=_single_thread, args=(q, function, errors))
            t.daemon=True
            t.start()
            run_threads.append(t)

        for t in run_threads:
            t.join()

        q.join()

        if print_errors:
            for error in errors:
                print_stderr(str(error))

        if len(errors) > 0:
            return False
        else:
            return True

    except Exception as e:
        if print_errors:
            print_stderr("Error while running threads: "+str(e))
        return False

####
# Set a given value to a key in the internal shared dict.
# Used by all parallel processes.
#########################################################################################
def set_shared_dict_value(
        key: str = None,
        value = None
    )-> None:
    """
    Set a value for a key in the internal shared dictionary.
    This function is used by all parallel processes.

    Args:
        key (str): The key in the shared dictionary.
        value: The value to be assigned to the key.
    """
    global _SHARED_DICT

    if key is not None:
        with _SHARED_DICT_LOCK:
            _SHARED_DICT[key] = value

####
# Add a given value to a key in the internal shared dict.
# Used by all parallel processes.
#########################################################################################
def add_shared_dict_value(
        key: str = None,
        value = None
    )-> None:
    """
    Add a value to a key in the internal shared dictionary.
    This function is used by all parallel processes.

    Args:
        key (str): The key in the shared dictionary.
        value: The value to be added to the key.
    """
    global _SHARED_DICT

    if key is not None:
        with _SHARED_DICT_LOCK:
            if key in _SHARED_DICT:
                _SHARED_DICT[key] += value
            else:
                set_shared_dict_value(key, value)

####
# Get the value of a key in the internal shared dict.
# Used by all parallel processes.
#########################################################################################
def get_shared_dict_value(
        key: str = None
    ):
    """
    Get the value of a key in the internal shared dictionary.
    This function is used by all parallel processes.

    Args:
        key (str): The key in the shared dictionary.

    Returns:
        The value associated with the key, or None if the key does not exist.
    """
    global _SHARED_DICT

    with _SHARED_DICT_LOCK:
        if key in _SHARED_DICT and key is not None:
            return _SHARED_DICT[key]
        else:
            return None

####
# Run a given function for given items list in a given number of processes
# Given function receives each item as first parameter
#########################################################################################
def run_processes(
        max_processes: int = 1,
        function: callable = None,
        items: list = [],
        print_errors: bool = False
    ) -> bool:
    """
    Run a given function for given items list in a given number of processes

    Args:
        max_processes (int): The maximum number of processes to run in parallel.
        function (callable): The function to be executed for each item.
        items (list): List of items to be processed.
        print_errors (bool): Whether to print errors.

    Returns:
        bool: True if all processes completed successfully, False otherwise.
    """
    from .output import print_stderr

    # Assign processes
    processes = max_processes

    if processes > len(items):
        processes = len(items)

    if processes < 1:
        processes = 1

    # Run processes
    with Pool(processes) as pool:
        try:
            pool.map(function, items)
            result = True
        except Exception as error:
            if print_errors:
                print_stderr(str(error))
            result = False

    return result
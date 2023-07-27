import sys
from queue import Queue
from threading import Thread

####
# Internal use only: Run a given function in a thread
#########################################################################################
def _single_thread(
        q = None,
        function: callable = None,
        errors: list = []
    ):
    """
    Internal use only: Run a given function in a thread
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
        items: list = []
    ) -> bool:
    """
    Run a given function for given items list in a given number of threads
    """

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

        for error in errors:
            print(error,file=sys.stderr)

        if len(errors) > 0:
            return False
        else:
            return True

    except Exception as e:
        print("Error while running threads: "+str(e)+"\n",file=sys.stderr)
        return False
from Queue import Queue
from threading import Thread

# Pool of threads consuming tasks from a queue"""
class QueueManager:
    global queue

    # Constructor
    def __init__(self, num_threads):
        self.queue = Queue(num_threads)
        for _ in range(num_threads):
            Worker(self.queue)

            # Add a task to the pool

    def add_task(self, func, *args, **kargs):
        self.queue.put((func, args, kargs))

        # Get the size of the pool

    def get_queue_size(self):
        return self.queue.qsize()

        # Wait for threads in the pool

    def wait_completion(self):
        self.queue.join()

        # clear the queue

    def clearQueue(self):
        with self.tasks.mutex:
            self.tasks.clear()


# The worker class
class Worker:
    def __init__(self, tasks):
        self.tasks = tasks
        self.daemon = True
        child = Thread(target=self.run)
        try:
            child.start()
        except Exception, e:
            print e

    def run(self):
        while 1:
            func, args, kargs = self.tasks.get()
            try:
                func(*args, **kargs)
            except Exception, e:
                print e
            self.tasks.task_done()
            ##############################################################
            # End this class
            ##############################################################

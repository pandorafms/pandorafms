#!/usr/bin/env python
from unittest import *
from console.include.common_functions_60 import *
from console.include.common_classes_60 import *
from sauceclient import SauceClient
import testtools
from os import environ, getenv
import subprocess, time, sys


class ArticaTestSuite(TestSuite):
	def __init__(self,*args,**kwargs):
		super(ArticaTestSuite,self).__init__(*args,**kwargs)
		
	def run(self,*args,**kwargs):
		#print "Running "+str(self.countTestCases())+" tests."
		#print "Tests are: "+str(self._tests)
		super(ArticaTestSuite,self).run(*args,**kwargs)

class ArticaTestLoader(TestLoader):
	def __init__(self,*args,**kwargs):
		self.suiteClass = ArticaTestSuite
		super(ArticaTestLoader, self).__init__(*args,**kwargs)
	

def split_suite_into_chunks(n, suite):
    import math

    # Keep n to a reasonable number of threads
    if n < 0:
        n = 1
    if n > 8:
        n = 8
    # Compute n such that the number of threads does not exceed the value passed to the function
    n = math.ceil(suite.countTestCases() / n)
    s = []
    i = 0
    s_tmp = ArticaTestSuite()
    for case in suite:
        if i < n:
            s_tmp.addTest(case)
            i += 1
        if i == n:
            s.append([s_tmp, None])
            i = 0
            s_tmp = ArticaTestSuite()
    if (i > 0):
        s.append([s_tmp, None])
    return s

class TracingStreamResult(testtools.StreamResult):
	failures = []
	success = []
	skipped = []
	errors = []
	
	def status(self, test_status, test_id, *args, **kwargs):
		if test_status=='inprogress':
			print "Running test "+str(test_id)

		elif test_status=='xfail' or test_status=='fail' or test_status=='exists':
			print "Test "+str(test_id)+" has failed"
			self.failures.append(test_id)

		elif test_status=='uxsuccess' or test_status=='success':
			print "Test "+str(test_id)+" has succeeded"
			self.success.append(test_id)

		elif test_status=='exists':
			print "Test "+str(test_id)+" has failed (already existed)"
			self.errors.append(test_id)

		elif test_status=='skip':
			print "Test "+str(test_id)+" was skipped"
			self.skipped.append('test_id')

is_enterprise = '1' == getenv('ENTERPRISE', False)
num_threads = 0

if is_enterprise:
        num_threads = 2
else:
        num_threads = 3

a = ArticaTestLoader()

#Network server tests
tests = a.discover(start_dir='console',pattern='*.py')

print str(tests.countTestCases())+" tests found"
print "Using "+str(num_threads)+" threads"

concurrent_suite = testtools.ConcurrentStreamTestSuite(lambda: (split_suite_into_chunks(num_threads, tests)))
result = TracingStreamResult()

try:
	result.startTestRun()
finally:
	concurrent_suite.run(result)

print "SUMMARY"
print "======="
print "Tests failed: %s" % result.failures
print "Tests succeeded: %s" % result.success
print "Tests skipped: %s" % result.skipped
print "Tests with errors: %s" % result.errors
print "======="

if (len(result.failures)+len(result.errors)) != 0:
	sys.exit(1)
else:
	sys.exit(0)

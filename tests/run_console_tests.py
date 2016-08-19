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
	

""" Splits a Test Suite so that no more than 'n' threads will execute the tests """
def split_suite_into_chunks(num_threads, suite):
    # Compute num_threads such that the number of threads does not exceed the value passed to the function
    # Keep num_threads to a reasonable number of threads
    if num_threads < 0: num_threads = 1
    if num_threads > 8: num_threads = 8
    num_tests = suite.countTestCases()
    s = []
    s_tmp = ArticaTestSuite()
    n = round(num_tests / num_threads)
    for case in suite:
        if n <= 0 and s_tmp.countTestCases() > 0:
            s.append([s_tmp, None])
            num_threads -= 1
            num_tests -= s_tmp.countTestCases()
            s_tmp = ArticaTestSuite()
            n = round(num_tests / num_threads)
        s_tmp.addTest(case)
        n -= 1
    if s_tmp.countTestCases() > 0:
        if s_tmp.countTestCases() > 0: s.append([s_tmp, None])
        num_tests -= s_tmp.countTestCases()
    if num_tests != 0: print("Error: num_tests should be 0 but is %s!" % num_tests)
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
if is_enterprise:
        num_threads = 2
else:
        num_threads = 3
a = ArticaTestLoader()

#Network server tests
tests = a.discover(start_dir='console',pattern='*.py')

print str(tests.countTestCases())+" tests found"

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

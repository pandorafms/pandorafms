#!/usr/bin/env python
from unittest import *
from console.include.common_functions_60 import *
from console.include.common_classes_60 import *
from sauceclient import SauceClient
import testtools
from os import environ, getenv
import subprocess, time, sys

def get_test_file(test_list):
	#return [test[0].split(' ')[0].split('.')[0].split('<')[1] for test in test_list]
	return [test[0].test_name for test in test_list]

""" Splits a Test Suite so that no more than 'n' threads will execute the tests """
def split_suite_into_chunks(num_threads, suite):
    # Compute num_threads such that the number of threads does not exceed the value passed to the function
    # Keep num_threads to a reasonable number of threads
    if num_threads < 0: num_threads = 1
    if num_threads > 8: num_threads = 8
    num_tests = suite.countTestCases()
    s = []
    s_tmp = TestSuite()
    n = round(num_tests / num_threads)
    for case in suite:
        if n <= 0 and s_tmp.countTestCases() > 0:
            s.append([s_tmp, None])
            num_threads -= 1
            num_tests -= s_tmp.countTestCases()
            s_tmp = TestSuite()
            n = round(num_tests / num_threads)
        s_tmp.addTest(case)
        n -= 1
    if s_tmp.countTestCases() > 0:
        if s_tmp.countTestCases() > 0: s.append([s_tmp, None])
        num_tests -= s_tmp.countTestCases()
    if num_tests != 0: print("Error: num_tests should be 0 but is %s!" % num_tests)
    return s

def add_test_case_to_suite(suite, tc_name):
    # Creates a Test Suite with each Test Case added n times
    n = 1
    for i in range(0, n):
        suite.addTest(tc_name)

def get_suite():
    suite = TestSuite()
    add_test_case_to_suite(suite, My_login_test('tc_login'))
    add_test_case_to_suite(suite, My_login_test('tc_logout'))
    return suite

class TracingStreamResult(testtools.StreamResult):
	failures = []
	success = []
	skipped = []
	errors = []
	
	def status(self, test_status, test_id, *args, **kwargs):
		if test_status=='inprogress':
			print "Running "+str(test_id)

		elif test_status=='xfail' or test_status=='fail' or test_status=='exists':
			self.failures.append(test_id)

		elif test_status=='uxsuccess' or test_status=='success':
			self.success.append(test_id)

		elif test_status=='exists':
			self.errors.append(test_id)

		elif test_status=='skip':
			self.skipped.append('test_id')

#Run Enterprise tests
is_enterprise = '1' == getenv('ENTERPRISE', False)

a = TestLoader()

if is_enterprise:
	tests = a.discover(start_dir='console',pattern='*.py')
else:
	tests = a.discover(start_dir='console',pattern='PAN*.py')
if is_enterprise:
	num_threads = 2
else:
	num_threads = 3
suite = tests
concurrent_suite = testtools.ConcurrentStreamTestSuite(lambda: (split_suite_into_chunks(num_threads, suite)))
result = TracingStreamResult()
result.startTestRun()
concurrent_suite.run(result)

#Update Saouce Labs jobs
sauce_client = SauceClient(environ["SAUCE_USERNAME"], environ["SAUCE_ACCESS_KEY"])
c = result
for test_id in c.failures+c.skipped+c.errors:
	try:
		sauce_client.jobs.update_job(test.sauce_labs_job_id, passed=False,tags=[environ["TRAVIS_BRANCH"],test_id],build_num=environ["TRAVIS_JOB_NUMBER"],name=str(environ["TRAVIS_COMMIT"])+"_"+str(test_id.split('.')[1]))
	except:
		print "Could not annotate Sauce Labs job #%s" % str(test_id)
		next

for test_id in c.success:
	try:
		sauce_client.jobs.update_job(test.sauce_labs_job_id, passed=True,tags=[environ["TRAVIS_BRANCH"],test_id],build_num=environ["TRAVIS_JOB_NUMBER"],name=str(environ["TRAVIS_COMMIT"])+"_"+str(test_id.split('.')[1]))
	except:
                print "Could not annotate Sauce Labs job #%s" % str(test_id)
                next
	


print "Tests failed: %s" % c.failures
print "Tests succeeded: %s" % c.success
print "Tests skipped: %s" % c.skipped
print "Tests with errors: %s" % c.errors

if (len(c.failures)+len(c.errors)+len(c.skipped)) != 0:
	sys.exit(1)

else:
	sys.exit(0)

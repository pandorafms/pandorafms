#!/usr/bin/env python
from unittest import *
from console.include.common_functions_60 import *
from console.include.common_classes_60 import *
from sauceclient import SauceClient
from os import environ
import subprocess, time, sys

def get_test_file(test_list):
	#return [test[0].split(' ')[0].split('.')[0].split('<')[1] for test in test_list]
	return [test[0].test_name for test in test_list]

a = TestLoader()
tests = a.discover(start_dir='console',pattern='PAN*.py')
c = ArticaTestResult()
tests.run(c)

#Update Saouce Labs jobs
sauce_client = SauceClient(environ["SAUCE_USERNAME"], environ["SAUCE_ACCESS_KEY"])
for test,error_msg in c.failures+c.skipped+c.errors:
	try:
		sauce_client.jobs.update_job(test.sauce_labs_job_id, passed=False,tags=[environ["TRAVIS_BRANCH"],test.id()],build_num=environ["TRAVIS_JOB_NUMBER"],name=str(environ["TRAVIS_COMMIT"])+"_"+str(test.id().split('.')[1]))
	except:
		print "Could not annotate Sauce Labs job #%s" % str(test.sauce_labs_job_id)
		next

for test,error_msg in c.success:
	try:
		sauce_client.jobs.update_job(test.sauce_labs_job_id, passed=True,tags=[environ["TRAVIS_BRANCH"],test.id()],build_num=environ["TRAVIS_JOB_NUMBER"],name=str(environ["TRAVIS_COMMIT"])+"_"+str(test.id().split('.')[1]))
	except:
                print "Could not annotate Sauce Labs job #%s" % str(test.sauce_labs_job_id)
                next
	


print "Tests failed: %s" % c.failures
print "Tests succeeded: %s" % c.success
print "Tests skipped: %s" % c.skipped
print "Tests with errors: %s" % c.errors

if (len(c.failures)+len(c.errors)+len(c.skipped)) != 0:
	raise Exception(Build failed)



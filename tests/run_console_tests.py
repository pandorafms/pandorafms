#!/usr/bin/env python
from unittest import *
from console.include.common_functions_60 import *
from console.include.common_classes_60 import *
import subprocess, os, time

def get_test_file(test_list):
	#return [test[0].split(' ')[0].split('.')[0].split('<')[1] for test in test_list]
	return [test[0].test_name for test in test_list]

a = TestLoader()
tests = a.discover(start_dir='console',pattern='PAN*.py')
c = ArticaTestResult()
tests.run(c)

print "Tests failed: %s" % c.failures
print "Tests succeeded: %s" % c.success
print "Tests skipped: %s" % c.skipped
print "Tests with errors: %s" % c.errors

if (len(c.failures)+len(c.errors)+len(c.skipped)) != 0:
	exit(1)

else:
	exit(0)

#!/usr/bin/env python
from unittest2 import *
from console import *
from console.include.common_functions_60 import *
from console.include.common_classes_60 import *
from os import environ
import subprocess, time, sys
from console.PAN3 import *


suite = TestSuite()
for i in range(0,10):
	suite.addTest(PAN3('test_pan3'))

result = ArticaTestResult()
suite.run(result)

print "Tests failed: %s" % result.failures
print "Tests succeeded: %s" % result.success
print "Tests skipped: %s" % result.skipped
print "Tests with errors: %s" % result.errors

if (len(result.failures)+len(result.errors)+len(result.skipped)) != 0:
		sys.exit(1)

else:
		sys.exit(0)


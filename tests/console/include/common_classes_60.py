# -*- coding: utf-8 -*-
from unittest import TestResult, TestCase
from common_functions_60 import *
from datetime import datetime
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException


class ArticaTestResult(TestResult):
	success = []
	def addSuccess(self, test):
		self.success.append((test,u'Success'))
		TestResult.addSuccess(self, test)



class PandoraWebDriverTestCase(TestCase):
	test_name = u'' #Name of the test.
	test_description = u'' #Description of the test
	time_started = None
	time_elapsed = None #Total time of the test
	tickets_associated = []

	def setUp(self):
		self.time_started = datetime.now()
		self.driver = webdriver.Firefox()
		self.driver.implicitly_wait(30)
		self.base_url = "http://localhost/"
		self.verificationErrors = []
		self.accept_next_alert = True
		super(PandoraWebDriverTestCase, self).setUp()

	def is_element_present(self, how, what):
		try: self.driver.find_element(by=how, value=what)
		except NoSuchElementException, e: return False
		return True

	def is_alert_present(self):
		try: self.driver.switch_to_alert()
		except NoAlertPresentException, e: return False
		return True

	def close_alert_and_get_its_text(self):
		try:
			alert = self.driver.switch_to_alert()
			alert_text = alert.text
			if self.accept_next_alert:
				alert.accept()
			else:
				alert.dismiss()
			return alert_text
		finally: self.accept_next_alert = True

	def tearDown(self):
		tack = datetime.now()
		diff = tack - self.time_started
		self.time_elapsed = diff.seconds
		self.driver.quit()
		self.assertEqual([], self.verificationErrors)
		super(PandoraWebDriverTestCase, self).tearDown()


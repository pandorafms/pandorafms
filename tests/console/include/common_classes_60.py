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
from sauceclient import SauceClient
from os import environ


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
	sauce_username = environ["SAUCE_USERNAME"]
	sauce_access_key = environ["SAUCE_ACCESS_KEY"]
	sauce_client = None

	desired_cap = {
		'tunnel-identifier': environ["TRAVIS_JOB_NUMBER"],
		'platform': "Windows 10",
		'browserName': "firefox",
		'version': "46",
	}

	sauce_custom_tags = [environ["TRAVIS_BRANCH"],self.id()]

	def setUp(self):
		self.time_started = datetime.now()
		#Start VM in Sauce Labs
		self.driver = webdriver.Remote(command_executor='http://'+self.sauce_username+':'+self.sauce_access_key+'@ondemand.saucelabs.com:80/wd/hub',desired_capabilities=self.desired_cap)

		self.sauce_client = SauceClient(self.sauce_username, self.sauce_access_key)
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
		#Update Sauce Labs job
		is_test_successful = self.verificationErrors == []
		sauce_client.jobs.update_job(driver.session_id, passed=is_test_successful,tags=self.sauce_custom_tags,build_num=environ["TRAVIS_JOB_NUMBER"],name=str(self.id())+"_"+str(environ["TRAVIS_BRANCH"])+"_"+str(environ["TRAVIS_JOB_NUMBER"]))

		self.assertEqual([], self.verificationErrors)
		super(PandoraWebDriverTestCase, self).tearDown()


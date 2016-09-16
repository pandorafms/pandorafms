# -*- coding: utf-8 -*-
from unittest import TestResult, TestCase
from common_functions_60 import *
from datetime import datetime
from pyvirtualdisplay import Display
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
#from sauceclient import SauceClient
from os import environ
import os


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
	#sauce_username = environ["SAUCE_USERNAME"]
	#sauce_access_key = environ["SAUCE_ACCESS_KEY"]
	#sauce_client = None
	#sauce_labs_job_id = None

	#desired_cap = {
	#	'tunnel-identifier': environ["TRAVIS_JOB_NUMBER"],
	#	'platform': "Windows 10",
	#	'browserName': "firefox",
	#	'version': "46",
	#}

	@classmethod
	def setUpClass(cls):
		cls.is_development = os.getenv('DEVELOPMENT', False)
		cls.is_enterprise = os.getenv('ENTERPRISE', False)
		if cls.is_development != False:
			cls.driver = webdriver.Firefox()
			cls.base_url = os.getenv('DEVELOPMENT_URL')
		else:
			#Start VM in Sauce Labs
			#cls.driver = webdriver.Remote(command_executor='http://'+cls.sauce_username+':'+cls.sauce_access_key+'@ondemand.saucelabs.com:80/wd/hub',desired_capabilities=cls.desired_cap)
			#cls.sauce_labs_job_id = cls.driver.session_id
                        display = Display(visible=0, size=(800, 600))
                        display.start()
			cls.driver = webdriver.Firefox()
			cls.base_url = "http://127.0.0.1/"
		
	@classmethod
	def tearDownClass(cls):
		if cls.is_development == False:
                    display.stop()
		cls.driver.quit()

	def setUp(self):
		self.time_started = datetime.now()
		self.driver.implicitly_wait(30)
		self.verificationErrors = []
		self.accept_next_alert = True
		#self.is_development = self.is_development
		#TODO Print test name
		print "Starting test"
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

		self.assertEqual([], self.verificationErrors)
		super(PandoraWebDriverTestCase, self).tearDown()


	def login(self,user="admin",passwd="pandora",pandora_url=None):
		print u"Logging in"

		driver = self.driver

		if pandora_url is None:
			pandora_url = self.base_url

		driver.get(pandora_url+"/pandora_console/index.php")
		driver.find_element_by_id("nick").clear()
		driver.find_element_by_id("nick").send_keys(user)
		driver.find_element_by_id("pass").clear()
		driver.find_element_by_id("pass").send_keys(passwd)
		driver.find_element_by_id("submit-login_button").click()

	def logout(self,pandora_url=None):
		print u"Logging out"

		driver = self.driver

		if pandora_url is None:
			pandora_url = self.base_url

		if pandora_url[-1] != '/':
			driver.find_element_by_xpath('//div[@id="container"]//a[@href="'+pandora_url+'/pandora_console/index.php?bye=bye"]').click()
		else:
			driver.find_element_by_xpath('//div[@id="container"]//a[@href="'+pandora_url+'pandora_console/index.php?bye=bye"]').click()

		driver.get(pandora_url+"/pandora_console/index.php")
		refresh_N_times_until_find_element(driver,2,"nick")


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
import os


class ArticaTestResult(TestResult):
	success = []
	def addSuccess(self, test):
		self.success.append((test,u'Success'))
		TestResult.addSuccess(self, test)

class PandoraWebDriverTestCase(TestCase):
	test_name = u'' #Name of the test.
	test_description = u'' #Description of the test
	tickets_associated = []
	sauce_username = environ["SAUCE_USERNAME"]
	sauce_access_key = environ["SAUCE_ACCESS_KEY"]
	sauce_client = None
	sauce_labs_job_id = None
	
	currentResult = None # holds last result object passed to run method

	desired_cap = {
		'tunnel-identifier': environ["TRAVIS_JOB_NUMBER"],
		'platform': "Windows 10",
		'browserName': "firefox",
		'version': "46",
	}
	
	def run(self,result=None,*args,**kwargs):
		self.currentResult = result # remember result for use in tearDown
		unittest.TestCase.run(self, result,*args,**kwargs) # call superclass run method

	@classmethod
	def setUpClass(cls):
		cls.is_development = os.getenv('DEVELOPMENT', False)
		cls.is_enterprise = os.getenv('ENTERPRISE', False)
		
	def setUp(self):
		if self.is_development != False:
			self.driver = webdriver.Firefox()
			self.base_url = os.getenv('DEVELOPMENT_URL')
		else:
			#Start VM in Sauce Labs
			self.driver = webdriver.Remote(command_executor='http://'+self.sauce_username+':'+self.sauce_access_key+'@ondemand.saucelabs.com:80/wd/hub',desired_capabilities=self.desired_cap)
			self.sauce_labs_job_id = self.driver.session_id
			self.base_url = "http://127.0.0.1/"
		self.driver.implicitly_wait(30)
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
		self.driver.quit()
		ok = self.currentResult.wasSuccessful()
		errors = self.currentResult.errors
		failures = self.currentResult.failures
		skipped = self.currentResult.skipped

		if not self.is_development:	
			sauce_client = SauceClient(environ["SAUCE_USERNAME"], environ["SAUCE_ACCESS_KEY"])
			sauce_client.jobs.update_job(self.sauce_labs_job_id, passed=ok==True,tags=[environ["TRAVIS_BRANCH"],self.id()],build_num=environ["TRAVIS_JOB_NUMBER"],name=str(environ["TRAVIS_COMMIT"])+"_"+str(self.id().split('.')[1])+"_"+str(self.id().split('.')[2]))
		
		#self.assertEqual([], self.verificationErrors) #TODO Review if this line is actually needed
		super(PandoraWebDriverTestCase, self).tearDown()


	def login(self,user="admin",passwd="pandora",pandora_url=None):

		driver = self.driver

		if pandora_url is None:
			pandora_url = self.base_url

		driver.get(pandora_url+"/pandora_console/index.php")
		driver.find_element_by_id("nick").clear()
		driver.find_element_by_id("nick").send_keys(user)
		driver.find_element_by_id("pass").clear()
		driver.find_element_by_id("pass").send_keys(passwd)
		
		#Hack
		driver.add_cookie({'name': 'clippy', 'value': 'deleted'})
		driver.add_cookie({'name': 'clippy_is_annoying', 'value': '1'})
		driver.find_element_by_id("submit-login_button").click()

	"""
	def test_ZZZZZZZZZZZ(self):
		#The hackiest way to end the driver in the LAST test (all credits to python unittest library for sorting tests alphabetically! :D)
		self.driver.quit()
	"""

	def logout(self,pandora_url=None):

		driver = self.driver

		if pandora_url is None:
			pandora_url = self.base_url

		if pandora_url[-1] != '/':
			driver.find_element_by_xpath('//div[@id="container"]//a[@href="'+pandora_url+'/pandora_console/index.php?bye=bye"]').click()
		else:
			driver.find_element_by_xpath('//div[@id="container"]//a[@href="'+pandora_url+'pandora_console/index.php?bye=bye"]').click()

		driver.get(pandora_url+"/pandora_console/index.php")
		refresh_N_times_until_find_element(driver,2,"nick")



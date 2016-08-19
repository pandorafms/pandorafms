# -*- coding: utf-8 -*-
import sys, os
sys.path.append(os.path.dirname(os.path.realpath(__file__)) + "/../include")
from common_classes_60 import PandoraWebDriverTestCase
from common_functions_60 import login, logout, click_menu_element, detect_and_pass_all_wizards, activate_home_screen
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.remote.webelement import WebElement

import unittest, time, re	

class Users(PandoraWebDriverTestCase):

	test_name = u'Users'
	test_description = u'Users tests'
	tickets_associated = []

	def test_A_home_screen(self):
	
		u"""
		Modify home screen, and check that change is correct. Return this change
		"""

		driver = self.driver
		self.login()
		detect_and_pass_all_wizards(driver)
		
		activate_home_screen(driver,"Event list")
		
		self.logout()
		self.login()
	
		element = driver.find_element_by_xpath('//a[contains(.,"Event control filter")]')
		self.assertIsInstance(element,WebElement)

		#Return this change		
		
		activate_home_screen(driver,"Default")
			
		
if __name__ == "__main__":
	unittest.main()


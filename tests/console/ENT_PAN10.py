"""
# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element, detect_and_pass_all_wizards, logout
from include.module_functions import create_module
from include.agent_functions import create_agent_group
from include.policy_functions import *
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.remote.webelement import WebElement
import unittest, time, re

class PAN10(PandoraWebDriverTestCase):

	test_name = u'PAN_10'
	test_description = u'Policy tests'
	tickets_associated = []

	def test_1_create_policy(self):

		driver = self.driver
		login(driver)
		detect_and_pass_all_wizards(driver)

		create_policy(driver,"policy_PAN_10","Applications",description="Policy for test PAN_10")

		search_policy(driver,"policy_PAN_10",go_to_policy=False)

		time.sleep(6)

		try:
			element = driver.find_element_by_xpath('//a[contains(.,"policy_PAN_10")]')
			self.assertIsInstance(element,WebElement)

		except AssertionError as e:
			self.verificationErrors.append(str(e))

		except NoSuchElementException as e:
			self.verificationErrors.append(str(e))

	def test_2_add_module_policy(self):

		driver = self.driver
		login(driver)
		detect_and_pass_all_wizards(driver)

		add_module_policy(driver,"policy_PAN_10","network_server",driver,module_name="PAN10",component_group="Network Management",network_component="Host Alive")

		search_policy(driver,"policy_PAN_10")

		driver.find_element_by_xpath('//*[@id="menu_tab"]/ul/li[2]/a/img').click()
		
		time.sleep(6)

		try:
			element = driver.find_element_by_xpath('//a[contains(.,"PAN10")]')
			self.assertIsInstance(element,WebElement)

		except AssertionError as e:
			self.verificationErrors.append(str(e))

		except NoSuchElementException as e:
			self.verificationErrors.append(str(e))

if __name__ == "__main__":
	unittest.main()
"""

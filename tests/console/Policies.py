# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, detect_and_pass_all_wizards, gen_random_string
from include.policy_functions import *
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.remote.webelement import WebElement
import unittest, time, re

class Policies(PandoraWebDriverTestCase):

	test_name = u'Policies'
	test_description = u'Policy tests'
	tickets_associated = []

	policy_name = gen_random_string(6)
	network_server_module_name = gen_random_string(6)

	def test_1_create_policy(self):

		driver = self.driver
		self.login()

		create_policy(driver,self.policy_name,"Applications",description="Policy for test")

		search_policy(driver,self.policy_name,go_to_policy=False)

		element = driver.find_element_by_xpath('//a[contains(.,"'+self.policy_name+'")]')
		self.assertIsInstance(element,WebElement)

	def test_2_add_network_server_module_to_policy(self):

		driver = self.driver
		detect_and_pass_all_wizards(driver)

		add_module_policy(driver,self.policy_name,"network_server",driver,module_name=self.network_server_module_name,component_group="Network Management",network_component="Host Alive")

		element = driver.find_element_by_xpath('//td[contains(.,"uccessfully")]')
		self.assertIsInstance(element,WebElement)


if __name__ == "__main__":
	unittest.main()

# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element, detect_and_pass_all_wizards
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
import unittest, time, re

class PAN1(PandoraWebDriverTestCase):
	test_name = u'PAN_1'
	test_description = u'Tests that an Administrator user can access the Setup'
	tickets_associated = []
	
	def test_pan1(self):
		driver = self.driver
		self.login()
		detect_and_pass_all_wizards(driver)
		click_menu_element(driver,"General Setup")
		self.assertEqual("IP list with API access", driver.find_element_by_id("table2-15-0").text)
	
if __name__ == "__main__":
	unittest.main()


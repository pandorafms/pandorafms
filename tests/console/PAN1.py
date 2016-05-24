# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login
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
		login(driver,"admin","pandora",self.base_url)
		element = driver.find_element_by_css_selector("#subSetup > li.sub_subMenu > a > div.submenu_text.submenu2_text_middle")
		driver.execute_script("arguments[0].click();", element)
		self.assertEqual("IP list with API access", driver.find_element_by_id("table2-15-0").text)
	
if __name__ == "__main__":
	unittest.main()


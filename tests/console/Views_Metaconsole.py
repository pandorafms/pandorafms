# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, is_element_present, click_menu_element, detect_and_pass_all_wizards, logout, gen_random_string, is_enterprise
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.remote.webelement import WebElement

import unittest2, time, re
import logging

class viewsMetaconsole(PandoraWebDriverTestCase):

	test_name = u'test menu in metaconsole'
	tickets_associated = []

	@is_enterprise
	def test_views_metaconsole(self):

		u"""
		This test do login in metaconsole and check one by one that all views appear.
		"""

		"""

		logging.basicConfig(filename="ViewsMetaconsole.log", level=logging.INFO, filemode='w')

		driver = self.driver
		self.login()
		detect_and_pass_all_wizards(driver)
		
		click_menu_element(driver,"Tree view")
		time.sleep(2)
		self.assertEqual("Show Options" in driver.page_source,True)
		
		click_menu_element(driver,"Tactical view")
		time.sleep(2)
		self.assertEqual("Report of events (1 hours)" in driver.page_source,True)
		
		click_menu_element(driver,"Group view")
		time.sleep(2)
		self.assertEqual("Summary by status" in driver.page_source,True)
		
		click_menu_element(driver,"Alerts view")
		time.sleep(2)
		self.assertEqual("Show Options" in driver.page_source,True)
		
		click_menu_element(driver,"Monitors view")
		time.sleep(2)
		self.assertEqual("Show Options" in driver.page_source,True)
		
		click_menu_element(driver,"Wizard")
		time.sleep(2)

		click_menu_element(driver,"Events")
		time.sleep(2)
		self.assertEqual("Show Options" in driver.page_source,True)
				
		click_menu_element(driver,"Create new report")
		time.sleep(2)
		self.assertEqual("Main data" in driver.page_source,True)
				
		click_menu_element(driver,"Reports")
		time.sleep(2)
		self.assertEqual("Show Options" in driver.page_source,True)
				
		click_menu_element(driver,"Report templates")
		time.sleep(2)
		self.assertEqual("Template name" in driver.page_source,True)
				
		click_menu_element(driver,"Templates wizard")
		time.sleep(2)
		self.assertEqual("Create template report wizard" in driver.page_source,True)
				
		click_menu_element(driver,"Services")
		time.sleep(2)
		self.assertEqual("Filter" in driver.page_source,True)
				
		click_menu_element(driver,"Network map")
		time.sleep(2)
		self.assertEqual("Show Options" in driver.page_source,True)
				
		click_menu_element(driver,"Visual Console")
		time.sleep(2)
		self.assertEqual("Map name" in driver.page_source,True)
		
		click_menu_element(driver,"Live view")
		time.sleep(2)
		self.assertEqual("Draw live filter" in driver.page_source,True)
				
		click_menu_element(driver,"Live view")
		time.sleep(2)
		self.assertEqual("Draw live filter" in driver.page_source,True)	
				
		click_menu_element(driver,"Filters")
		time.sleep(2)
						
		click_menu_element(driver,"Synchronising")
		time.sleep(2)
		self.assertEqual("Synchronizing Users" in driver.page_source,True)			
				
		click_menu_element(driver,"User management")
		time.sleep(2)
		self.assertEqual("Show Options" in driver.page_source,True)

		click_menu_element(driver,"Agent management")
		time.sleep(2)
		self.assertEqual("Source Server" in driver.page_source,True)

		click_menu_element(driver,"Module management")
		time.sleep(2)
		self.assertEqual("Name" in driver.page_source,True)

		click_menu_element(driver,"Alert management")
		time.sleep(2)
		self.assertEqual("Show Options" in driver.page_source,True)
		
		click_menu_element(driver,"Event alerts")
		time.sleep(2)
		self.assertEqual("Show Options" in driver.page_source,True)
		
		click_menu_element(driver,"Component management")
		time.sleep(2)
		self.assertEqual("Show Options" in driver.page_source,True)

		click_menu_element(driver,"Policy management")
		time.sleep(2)
		self.assertEqual("Show Options" in driver.page_source,True)
						
		click_menu_element(driver,"Cron jobs")
		time.sleep(2)
		self.assertEqual("ADD NEW JOB" in driver.page_source,True)
		
		logging.info("test_views_appear_metaconsole is correct")
		
if __name__ == "__main__":
	unittest2.main()

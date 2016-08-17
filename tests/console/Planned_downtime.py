# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element, detect_and_pass_all_wizards, logout, is_enterprise, gen_random_string
from include.planned_downtime_functions import *
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.remote.webelement import WebElement
import unittest, time, re


class PAN13(PandoraWebDriverTestCase):

	test_name = u'Planned_downtime'
	test_description = u'Planed downtime test'
	tickets_associated = []

	@is_enterprise
	def test_A_create_planned_downtime_Quiet(self):

		u"""
		Create and search planned downtime quiet
		"""
		driver = self.driver
		self.login()
		detect_and_pass_all_wizards(driver)

		planned_name = gen_random_string(6)

		create_planned_downtime(driver,planned_name,"Applications","Quiet",description=planned_name)
		
		time.sleep(10)
		
		search_planned_downtime(driver,planned_name)	

		element = driver.find_element_by_xpath('//a[contains(.,"Running")]')
                self.assertIsInstance(element,WebElement)
	
	@is_enterprise
	def test_B_create_planned_downtime_disabled_agents(self):
		
                u"""
                Create and search planned downtime disabled agents
                """
		driver = self.driver

		planned_name = gen_random_string(6)
		
		create_planned_downtime(driver,planned_name,"Applications","Disabled Agents",description=planned_name)
		
		time.sleep(10)
		
		search_planned_downtime(driver,planned_name)	
	
		element = driver.find_element_by_xpath('//a[contains(.,"Running")]')
                self.assertIsInstance(element,WebElement)
	   
	@is_enterprise
	def test_C_create_planned_downtime_disabled_only_alerts(self):
		
		u"""
		Create and search planned downtime disabled only alerts
		"""
		driver = self.driver
		
		planned_name = gen_random_string(6)

		create_planned_downtime(driver,planned_name,"Applications","Disabled only Alerts",description=planned_name)
		
		time.sleep(10)
		
		search_planned_downtime(driver,planned_name)	

 		element = driver.find_element_by_xpath('//a[contains(.,"Running")]')
                self.assertIsInstance(element,WebElement)


if __name__ == "__main__":
	unittest.main()

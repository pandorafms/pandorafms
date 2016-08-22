# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element, detect_and_pass_all_wizards, logout, gen_random_string, refresh_N_times_until_find_element
from include.planned_downtime_functions import *
from include.alert_functions import *
from include.module_functions import *
from include.agent_functions import *
from include.event_functions import *
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.remote.webelement import WebElement
import unittest, time, re, datetime


class Creation(PandoraWebDriverTestCase):

	test_name = u'Planned downtime creation'
	test_description = u'Planed downtime creation test'
	tickets_associated = []

	quiet_name = gen_random_string(6)
	disabled_agents_name = gen_random_string(6)
	disabled_only_alerts_name = gen_random_string(6)
	

	def test_A_create_planned_downtime_Quiet(self):

		u"""
		Create and search planned downtime quiet
		"""
		driver = self.driver
		self.login()
		detect_and_pass_all_wizards(driver)

		planned_name = self.quiet_name

		create_planned_downtime(driver,planned_name,"Applications","Quiet","Once",description=planned_name)
	
		search_planned_downtime(driver,planned_name)	

		element = driver.find_element_by_xpath('//tr[@id="table3-0"]/td[contains(.,"'+planned_name+'")]')
		self.assertIsInstance(element,WebElement)

	def test_B_create_planned_downtime_disabled_agents(self):
		
                u"""
                Create and search planned downtime disabled agents
                """
		driver = self.driver
		self.login()

		planned_name = self.disabled_agents_name
		
		create_planned_downtime(driver,planned_name,"Applications","Disabled Agents","Once",description=planned_name)
		
		search_planned_downtime(driver,planned_name)	
	
		element = driver.find_element_by_xpath('//tr[@id="table3-0"]/td[contains(.,"'+planned_name+'")]')
                self.assertIsInstance(element,WebElement)
	   
	def test_C_create_planned_downtime_disabled_only_alerts(self):
		
		u"""
		Create and search planned downtime disabled only alerts
		"""
		driver = self.driver
		self.login()
		
		planned_name = self.disabled_only_alerts_name

		create_planned_downtime(driver,planned_name,"Applications","Disabled only Alerts","Once",description=planned_name)
		
		search_planned_downtime(driver,planned_name)	

		element = driver.find_element_by_xpath('//tr[@id="table3-0"]/td[contains(.,"'+planned_name+'")]')
                self.assertIsInstance(element,WebElement)

        def test_D_delete_planned_downtime(self):

	
		driver=self.driver
		self.login()

		downtime_list = [self.disabled_only_alerts_name,self.disabled_agents_name,self.quiet_name]

		for planned_name in downtime_list:
			delete_planned_downtime(driver,planned_name)
			element = driver.find_element_by_xpath('//td[contains(.,"Success")]')
			self.assertIsInstance(element,WebElement)

		

if __name__ == "__main__":
	unittest.main()

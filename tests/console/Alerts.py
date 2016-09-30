# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element, refresh_N_times_until_find_element, detect_and_pass_all_wizards, is_element_present, logout
from include.alert_functions import *
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import StaleElementReferenceException, NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.remote.webelement import WebElement
import unittest2, time, re

class Alerts (PandoraWebDriverTestCase):

        test_name = u'Alerts tests'
        tickets_associated = []

        def test_A_create_new_email_action(self):
		
		u"""
		Create a new alert action using eMail command and check that create ok
		"""
			
		action_name = gen_random_string(6)

		driver = self.driver
		self.login()
		detect_and_pass_all_wizards(driver)

		create_new_action_to_alert(driver,action_name,"Applications","eMail",field1="prueba@prueba.com",field2="Test",field3="This is a test")
			
		element = driver.find_element_by_xpath('//td[contains(.,"Successfully created")]')
		self.assertIsInstance(element,WebElement)

		click_menu_element(driver,"Actions")

		element = driver.find_element_by_xpath('//a[contains(.,"'+action_name+'")]')
		self.assertIsInstance(element,WebElement)


	def test_B_create_new_action_command(self):
		
		u"""
		Create a new command and then crreate a new action with this command. Check results
		"""
						
		action_name = gen_random_string(6)
		command_name = gen_random_string(6)
		
		driver = self.driver
		
		list_values = ["_agent_","_agent_status","_agentdescription_"]
		list_description=["agent name","status of agent","agent description"]
	
		create_new_command_to_alert(driver,command_name,"_agent_",list_field_description=list_description,list_field_values=list_values,description="command by test_B of Alerts")

		element = driver.find_element_by_xpath('//td[contains(.,"Successfully created")]')		
		self.assertIsInstance(element,WebElement)

		create_new_action_to_alert(driver,action_name,"Applications",command_name,field1="prueba@prueba.com",field2="Test",field3="This is a action with test B ")

		element = driver.find_element_by_xpath('//td[contains(.,"Successfully created")]')
		self.assertIsInstance(element,WebElement)

		click_menu_element(driver,"Actions")

		element = driver.find_element_by_xpath('//a[contains(.,"'+action_name+'")]')
		self.assertIsInstance(element,WebElement)	

		driver.find_element_by_xpath('//a[contains(.,"'+action_name+'")]').click()

if __name__ == "__main__":
	unittest2.main()


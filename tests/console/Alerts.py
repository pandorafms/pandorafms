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

	def test_C_create_new_template(self):
		
		u"""
		Create a new template (Unknown Status) and check that changes are applied
		"""
		
		template_name = gen_random_string(6)

		driver = self.driver

		field_list = ["_agent_","_agentdescription_","_data_","_alert_description_"]		

		days_list=["wednesday","saturday"]

		create_new_template_to_alert(driver,template_name,"Applications","Mail to Admin","Unknown status",list_days=days_list,description="Template with test C",field_list=field_list)

		element = driver.find_element_by_xpath('//td[contains(.,"Successfully")]')
		self.assertIsInstance(element,WebElement)

		time.sleep(3)		

		click_menu_element(driver,"Templates")

		element = driver.find_element_by_xpath('//a[contains(.,"'+template_name+'")]')
		self.assertIsInstance(element,WebElement)

	def test_D_edit_template_created(self):

		u"""
		Create a new template and edit template created, verify the changes
		"""

		template_name = gen_random_string(6)

		driver = self.driver

		self.login()

		detect_and_pass_all_wizards(driver)

		field_list = ["_agentcustomid_","_address_","_module_","_modulecustomid_"]

		days_list=["monday","wednesday","saturday"]

		create_new_template_to_alert(driver,template_name,"Databases","Mail to Admin","Critical",list_days=days_list,description="Template with test C",field_list=field_list)

		element = driver.find_element_by_xpath('//td[contains(.,"Successfully")]')
		self.assertIsInstance(element,WebElement)

		time.sleep(3)

		click_menu_element(driver,"Templates")

		element = driver.find_element_by_xpath('//a[contains(.,"'+template_name+'")]')
		self.assertIsInstance(element,WebElement)
		
		new_field_list = ["_agent_","_agentdescription_","_data_","_alert_description_"]
		
		edit_template_to_alert(driver,template_name,new_action="Create a ticket in Integria IMS",new_field_list=new_field_list)	
		
		element = driver.find_element_by_xpath('//td[contains(.,"Successfully")]')
		self.assertIsInstance(element,WebElement)

		click_menu_element(driver,"Templates")	
		
		driver.find_element_by_xpath('//a[contains(.,"'+template_name+'")]').click()

		driver.find_element_by_id("submit-next").click()
		
		self.assertEqual("Create a ticket in Integria IMS" in driver.page_source,True)

		driver.find_element_by_id("submit-next").click()

		self.assertEqual("_agentdescription_" in driver.page_source,True)

if __name__ == "__main__":
	unittest2.main()


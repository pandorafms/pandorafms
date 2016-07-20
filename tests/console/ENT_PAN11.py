# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element, detect_and_pass_all_wizards, logout
from include.module_functions import create_module
from include.agent_functions import create_agent_group
from include.policy_functions import *
from include.collection_functions import *
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.remote.webelement import WebElement
import unittest, time, re


class PAN11(PandoraWebDriverTestCase):

	test_name = u'PAN_11'
	test_description = u'Collection tests'
	tickets_associated = []

	def test_A_create_collection(self):

		driver = self.driver
		login(driver)
		detect_and_pass_all_wizards(driver)

		create_collection(driver,"collectionPAN11","PAN11",group="All",description="Collection with PAN11")

		search_collection(driver,"collectionPAN11",go_to_collection=False)

		time.sleep(6)

		try:
				element = driver.find_element_by_xpath('//a[contains(.,"collectionPAN11")]')
				self.assertIsInstance(element,WebElement)

		except AssertionError as e:
				self.verificationErrors.append(str(e))

		except NoSuchElementException as e:
				self.verificationErrors.append(str(e))

	def test_B_edit_collection(self):
	
		driver = self.driver
		login(driver)
		detect_and_pass_all_wizards(driver)
		
		edit_collection(driver,"collectionPAN11",new_name="New_collectionPAN11",group="Applications",description="Edit collectionPAN11")
		
		search_collection(driver,"New_collectionPAN11",go_to_collection=False)

		time.sleep(6)

		try:
			element = driver.find_element_by_xpath('//a[contains(.,"New_collectionPAN11")]')
			self.assertIsInstance(element,WebElement)

		except AssertionError as e:
			self.verificationErrors.append(str(e))

		except NoSuchElementException as e:
			self.verificationErrors.append(str(e))
	
	def test_C_create_text_collection(self):
	
		driver = self.driver
		login(driver)
		detect_and_pass_all_wizards(driver)
	
		create_text_in_collection(driver,"New_collectionPAN11","file_collectionPAN11",text_file="test file")
		time.sleep(6)
				
		try:
			element = driver.find_element_by_xpath('//a[contains(.,"file_collectionPAN11")]')
			self.assertIsInstance(element,WebElement)

		except AssertionError as e:
			self.verificationErrors.append(str(e))

		except NoSuchElementException as e:
			self.verificationErrors.append(str(e))	

	def test_D_directory_collection(self):	
	
		driver = self.driver
		login(driver)
		detect_and_pass_all_wizards(driver)	
	
		create_directory_in_collection(driver,"New_collectionPAN11","directory_collectionPAN11")
		
		time.sleep(6)
		
		try:
			element = driver.find_element_by_xpath('//a[contains(.,"directory_collectionPAN11")]')
			self.assertIsInstance(element,WebElement)

		except AssertionError as e:
			self.verificationErrors.append(str(e))

		except NoSuchElementException as e:
			self.verificationErrors.append(str(e))

	def test_E_delete_collection(self):
	
		driver = self.driver
		login(driver)
		detect_and_pass_all_wizards(driver)
		
		delete_collection(driver,"New_collectionPAN11")
	
		time.sleep(6)		
	
		try:
			#Check that New_collectionPAN11 is delete
			element = driver.find_elements_by_xpath('//a[contains(.,"New_collectionPAN11")]')
			self.assertEqual(element,[])
			
		except AssertionError as e:
			self.verificationErrors.append(str(e))
if __name__ == "__main__":
	unittest.main()

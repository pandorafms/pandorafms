# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import click_menu_element, detect_and_pass_all_wizards, gen_random_string, is_enterprise, enterprise_class
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

@enterprise_class
class Collections(PandoraWebDriverTestCase):

	test_name = u'Collections'
	test_description = u'Collection tests'
	tickets_associated = []

	collection_name = gen_random_string(6)
	new_collection_name = gen_random_string(6)

	def test_A_create_collection(self):

		driver = self.driver
		self.login()
		detect_and_pass_all_wizards(driver)

		create_collection(driver,self.collection_name,"PAN11",group="All",description="Collection with PAN11")

		search_collection(driver,self.collection_name,go_to_collection=False)

		element = driver.find_element_by_xpath('//a[contains(.,self.collection_name)]')
		self.assertIsInstance(element,WebElement)

	def test_B_edit_collection(self):
	
		driver = self.driver
		self.login()
		
		edit_collection(driver,self.collection_name,new_name=self.new_collection_name,group="Applications",description="Edit collectionPAN11")
		
		search_collection(driver,self.new_collection_name,go_to_collection=False)

		element = driver.find_element_by_xpath('//a[contains(.,self.new_collection_name)]')
		self.assertIsInstance(element,WebElement)

	def test_C_create_text_collection(self):
	
		driver = self.driver
		self.login()
	
		create_text_in_collection(driver,self.new_collection_name,"file_collectionPAN11",text_file="test file")
				
		element = driver.find_element_by_xpath('//a[contains(.,"file_collectionPAN11")]')
		self.assertIsInstance(element,WebElement)

	def test_D_directory_collection(self):	
	
		driver = self.driver
		self.login()
	
		create_directory_in_collection(driver,self.new_collection_name,"directory_collectionPAN11")
		
		element = driver.find_element_by_xpath('//a[contains(.,"directory_collectionPAN11")]')
		self.assertIsInstance(element,WebElement)

	def test_E_delete_collection(self):
	
		driver = self.driver
		self.login()
		
		delete_collection(driver,self.new_collection_name)
	
		#Check that New_collectionPAN11 is delete
		element = driver.find_elements_by_xpath('//a[contains(.,"'+self.new_collection_name+'")]')
		self.assertEqual(element,[])

if __name__ == "__main__":
	unittest.main()

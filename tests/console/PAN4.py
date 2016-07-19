# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element, refresh_N_times_until_find_element, detect_and_pass_all_wizards, is_element_present, logout
from selenium import webdriver
from include.reports_functions import delete_report, create_report
from include.user_functions import create_user
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import StaleElementReferenceException
import unittest, time, re
 
 
class PAN4(PandoraWebDriverTestCase):

	test_name = u'PAN_4'
	test_description = u'Creates a user with Chief Operator permissions over the Applications group. Then creates two reports: one in the Applications group and other in the Servers group. Then, it checks that the given user can only see the Application report'
	tickets_associated = []

	def test_pan4(self):
		driver = self.driver
		login(driver,user="admin",passwd="pandora",pandora_url=self.base_url)
		detect_and_pass_all_wizards(driver)
		
		#Creates a user with Chief Operator - Applications profile
		profile_list = []
		profile_list.append(("Chief Operator","Applications",[]))
		create_user(driver,'PAN_4','PAN_4',email='pan_4@pandorafms.com',profile_list=profile_list)
	
		#Creates report
		create_report(driver,"PAN_4_Applications","Applications")
		create_report(driver,"PAN_4_Servers","Servers")
	
		#Logout
		logout(driver,self.base_url)
	
		#Login
		login(driver,user='PAN_4',passwd='PAN_4',pandora_url=self.base_url)
		detect_and_pass_all_wizards(driver)
	
		#Check that the report is visible
		click_menu_element(driver,"Custom reporting")
		driver.find_element_by_id('text-search').clear()
		driver.find_element_by_id('text-search').send_keys("PAN_4_Applications")
		driver.find_element_by_id('submit-search_submit').click()
		self.assertEqual(is_element_present(driver, By.ID, 'report_list-0'),True)
	
	
		#Check that the report is not visible
		click_menu_element(driver,"Custom reporting")
		driver.find_element_by_id('text-search').clear()
		driver.find_element_by_id('text-search').send_keys("PAN_4_Servers")
		driver.find_element_by_id('submit-search_submit').click()
	
		try:
			element = driver.find_element_by_xpath('//a[contains(.,"No data found.")]')
			self.assertIsInstance(element,WebElement)

		except AssertionError as e:
			self.verificationErrors.append(str(e))

		except NoSuchElementException as e:
			self.verificationErrors.append(str(e))

	
		#Delete reports
		logout(driver,self.base_url)
		login(driver,user="admin",passwd="pandora",pandora_url=self.base_url)
	
		delete_report(driver,"PAN_4_Servers")
		delete_report(driver,"PAN_4_Applications")


if __name__ == "__main__":
	unittest.main()


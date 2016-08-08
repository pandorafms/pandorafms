# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element, detect_and_pass_all_wizards, logout
from include.agent_functions import create_agent, search_agent, create_agent_group
from include.user_functions import create_user, create_user_profile
from include.module_functions import create_network_server_module
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.remote.webelement import WebElement
import unittest, time, re

class PAN9(PandoraWebDriverTestCase):

	test_name = u'PAN_9'
	test_description = u'ACL Propagation test: Creates one group "A" with ACL propagation, then a group "B" son of "A" with no ACL propagation, and finally group "C". The test asserts if a user with privileges to "A" can see the agent of "B" but no agents of "C". '
	tickets_associated = []

	def test_pan9(self):

		driver = self.driver
		self.login()
		detect_and_pass_all_wizards(driver)
		
		create_agent_group(driver,"PAN9_A",propagate_acl=True,description="Group A, with propagate ACL, son of ALL")	
		create_agent_group(driver,"PAN9_B",parent_group="PAN9_A",description="Group B, son of A")		
		create_agent_group(driver,"PAN9_C",parent_group="PAN9_B",description="Group C, son of B")	
		
		create_agent(driver,"PAN9_agent_B",description="Agent in group B",group="PAN9_B")
		
		create_agent(driver,"PAN9_agent_C",description="Agent in group C",group="PAN9_C")
		
		l=[("Chief Operator","PAN9_A",[])]
		
		create_user(driver,"PAN9_user","pandora",profile_list=l)
		
		self.logout()
		
		self.login(user="PAN9_user")
		
		detect_and_pass_all_wizards(driver)
		
		search_agent(driver,"PAN9_agent_B",go_to_agent=False)
		
		time.sleep(6)
		
		try:
			element = driver.find_element_by_xpath('//a[contains(.,"PAN9_agent_B")]')
			self.assertIsInstance(element,WebElement)

		except AssertionError as e:
			self.verificationErrors.append(str(e))

		except NoSuchElementException as e:
			self.verificationErrors.append(str(e))

		search_agent(driver,"PAN9_agent",go_to_agent=False)
		
		time.sleep(6)

		try:
			#self.assertEqual(False,u"PAN9_agent_C" in driver.page_source)
			element = driver.find_elements_by_xpath('//a[contains(.,"PAN9_agent_C")]')
			self.assertEqual(element,[])
		except AssertionError as e:
			self.verificationErrors.append(str(e))

if __name__ == "__main__":
	unittest.main()

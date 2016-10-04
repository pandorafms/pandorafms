# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element, refresh_N_times_until_find_element, detect_and_pass_all_wizards
from include.agent_functions import create_agent
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import StaleElementReferenceException
from include.module_functions import *

import unittest2, time, re

class Network_server_module(PandoraWebDriverTestCase):

	test_name = u'Modules'
	test_description = u'Module tests'
	tickets_associated = []

	def test_create_ICMP_module(self):

		u"""
		Creates a simple ICMP check against localhost and checks the result is 1
		"""

		driver = self.driver
		self.login()
		detect_and_pass_all_wizards(driver)

		create_agent(driver,"PAN3_agent",ip="127.0.0.1")
		
		create_module("network_server",driver,agent_name="PAN3_agent",module_name="PAN3_module",component_group="Network Management",network_component="Host Alive",ip="127.0.0.1")

		driver.find_element_by_xpath('//*[@id="menu_tab"]//a[contains(@href,"ver_agente")]').click()

		max_retries = 3
		i = 1
		element_text = ""

		while (i <= max_retries): # Temporary workaround to weird StaleElementReferenceException exceptions due Javascript altering the DOM
			try:
				element_text = refresh_N_times_until_find_element(driver,5,"table1-1-7",how=By.ID).text
				self.assertEqual("1", element_text.lstrip().rstrip()) # The lstrip.rstrip is done because if not, this error is raised: "'1' != u'1 '"
				break
			except StaleElementReferenceException as e_stale:
				i = i+1
				if i > max_retries:
                                        self.verificationErrors.append(str(e_stale))
					break
                                else:
                                        next
			except AssertionError as e:
				self.verificationErrors.append(str(e))
				break

if __name__ == "__main__":
	unittest2.main()

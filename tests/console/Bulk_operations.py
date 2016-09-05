# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element, detect_and_pass_all_wizards, gen_random_string
from include.agent_functions import create_agent, delete_agent
from include.api_functions import *
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException

import unittest, time, re

class Bulk_operations(PandoraWebDriverTestCase):
	
	test_name = u'Bulk_operation'
	test_description = u'Creation two agents and delete this agents using bulk operation'
	tickets_associated = []

	def test_A_delete_agent_bulk_operations(self):

		u"""
		Creation two agents and delete this agents using bulk operation'
		Ticket Associated = 3831
		"""

		agent_name_1 = gen_random_string(6)
                agent_name_2 = gen_random_string(6)

		driver = self.driver
		self.login()
		detect_and_pass_all_wizards(driver)
		
		activate_api(driver,"1234")

		params = [agent_name_1,"127.0.0.1","0","4","0","300","2","pandorafms","2","0","0","pruebas"]
                create_agent_api(driver,params,user="admin",pwd="pandora")

		params = [agent_name_2,"127.0.0.1","0","4","0","300","2","pandorafms","2","0","0","pruebas"]
                create_agent_api(driver,params,user="admin",pwd="pandora")		
		
		lista = driver.current_url.split('/')

                url = lista[0]+'//'+lista[2]+'/pandora_console'

                driver.get(url)	
		
		delete_agent(driver,[agent_name_1,agent_name_2])
			
		self.assertRegexpMatches(self.close_alert_and_get_its_text(), r"^Are you sure[\s\S]$")
		self.assertEqual(self.driver.find_element_by_xpath('//div[@id="main"]//td[contains(.,"Successfully deleted (2)")]').text,"Successfully deleted (2)")
		
if __name__ == "__main__":
        unittest.main()

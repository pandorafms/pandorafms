# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element, detect_and_pass_all_wizards, gen_random_string
from include.agent_functions import search_agent,create_agent, delete_agent
from include.api_functions import *
from include.module_functions import search_module
from include.bulk_operations import *
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.remote.webelement import WebElement

import unittest, time, re

class Bulk_operations(PandoraWebDriverTestCase):
	
	test_name = u'Bulk_operation'
	test_description = u'Bulk operation tests'
	tickets_associated = []

	def test_A_delete_agent_bulk_operations(self):

		u"""
		Creation two agents and delete this agents using bulk operation
		Ticket Associated = 3831
		"""

		agent_name_1 = gen_random_string(6)
                agent_name_2 = gen_random_string(6)

		driver = self.driver
		self.login()
		detect_and_pass_all_wizards
		
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

	def test_B_edit_agents_group_bulk_operations(self):

		u"""
		Create two agents and edit group with bulk operation                
		"""
		
		agent_name_1 = gen_random_string(6)
		agent_name_2 = gen_random_string(6)

		driver = self.driver

		activate_api(driver,"1234")

		params = [agent_name_1,"127.0.0.1","0","4","0","300","2","pandorafms","2","0","0","pruebas"]
		create_agent_api(driver,params,user="admin",pwd="pandora")

		params = [agent_name_2,"127.0.0.1","0","4","0","300","2","pandorafms","2","0","0","pruebas"]
		create_agent_api(driver,params,user="admin",pwd="pandora")

		lista = driver.current_url.split('/')

		url = lista[0]+'//'+lista[2]+'/pandora_console'

		driver.get(url)

		agent_names_list = [agent_name_1,agent_name_2]
						
		edit_agents_in_bulk(driver,agent_names_list,new_group="Web")

		self.assertRegexpMatches(self.close_alert_and_get_its_text(), r"^Are you sure[\s\S]$")
		self.assertEqual(self.driver.find_element_by_xpath('//div[@id="main"]//td[contains(.,"Agents updated successfully(2)")]').text,"Agents updated successfully(2)")

	def test_C_edit_agent_description_bulk_operation(self):

		u"""
		Create two agents and edit description with bulk operation
		"""
		
                agent_name_1 = gen_random_string(6)
                agent_name_2 = gen_random_string(6)

                driver = self.driver

		activate_api(driver,"1234")

                params = [agent_name_1,"127.0.0.1","0","4","0","300","2","pandorafms","2","0","0","pruebas"]
                create_agent_api(driver,params,user="admin",pwd="pandora")

                params = [agent_name_2,"127.0.0.1","0","4","0","300","2","pandorafms","2","0","0","pruebas"]
                create_agent_api(driver,params,user="admin",pwd="pandora")

                lista = driver.current_url.split('/')

                url = lista[0]+'//'+lista[2]+'/pandora_console'

                driver.get(url)

                agent_names_list = [agent_name_1,agent_name_2]

                edit_agents_in_bulk(driver,agent_names_list,new_description="test C edit description bulk operation")
		self.assertRegexpMatches(self.close_alert_and_get_its_text(), r"^Are you sure[\s\S]$")
                self.assertEqual(self.driver.find_element_by_xpath('//div[@id="main"]//td[contains(.,"Agents updated successfully(2)")]').text,"Agents updated successfully(2)")


		search_agent(driver,agent_name_1,go_to_agent=True)

		self.assertEqual("test C edit description bulk operation" in driver.page_source,True)

	def test_D_delete_modules_in_bulk(self):
		
		u"""
		Create two agents with two modules and delete this modules through bulk operation	
		"""

		agent_name_1 = gen_random_string(6)
		agent_name_2 = gen_random_string(6)

		module_name_1 = gen_random_string(6)
                
		driver = self.driver

		activate_api(driver,"1234")

		params = [agent_name_1,"127.0.0.1","0","4","0","300","2","pandorafms","2","0","0","pruebas"]
		create_agent_api(driver,params,user="admin",pwd="pandora")

		params = [agent_name_2,"127.0.0.1","0","4","0","300","2","pandorafms","2","0","0","pruebas"]
		create_agent_api(driver,params,user="admin",pwd="pandora")
	
		params = [agent_name_1,module_name_1,"0","6","1","0","0","0","0","0","0","0","0","129.99.40.1","0","0","180","0","0","0","0","Host_Alive"]
		add_network_module_to_agent_api(driver,params,user="admin",pwd="pandora",apipwd="1234")

		params = [agent_name_2,module_name_1,"0","6","1","0","0","0","0","0","0","0","0","129.99.40.1","0","0","180","0","0","0","0","Host_Alive"]
		add_network_module_to_agent_api(driver,params,user="admin",pwd="pandora",apipwd="1234")

		lista = driver.current_url.split('/')

		url = lista[0]+'//'+lista[2]+'/pandora_console'

		driver.get(url)

		agent_name_list = [agent_name_1,agent_name_2]

		module_name_list = [module_name_1]

		delete_modules_in_bulk(driver,agent_name_list,module_name_list)	
		
		self.assertRegexpMatches(self.close_alert_and_get_its_text(), r"^Are you sure[\s\S]$")

		search_module(driver,agent_name_1,module_name_1)

		element = driver.find_elements_by_xpath('//a[contains(.,"No available data to show")]')

		self.assertEqual(element,[])


	def test_E_edit_module_group_in_bulk(self):

		u"""
		Create two agents and one module in this agents. With bulk operation, change module group that this module
		"""

		agent_name_1 = gen_random_string(6)
		agent_name_2 = gen_random_string(6)

		module_name_1 = gen_random_string(6)

		driver = self.driver

		activate_api(driver,"1234")

		params = [agent_name_1,"127.0.0.1","0","4","0","300","2","pandorafms","2","0","0","pruebas"]
		create_agent_api(driver,params,user="admin",pwd="pandora")

		params = [agent_name_2,"127.0.0.1","0","4","0","300","2","pandorafms","2","0","0","pruebas"]
		create_agent_api(driver,params,user="admin",pwd="pandora")

		params = [agent_name_1,module_name_1,"0","6","1","0","0","0","0","0","0","0","0","129.99.40.1","0","0","180","0","0","0","0","Host_Alive"]
		add_network_module_to_agent_api(driver,params,user="admin",pwd="pandora",apipwd="1234")

		params = [agent_name_2,module_name_1,"0","6","1","0","0","0","0","0","0","0","0","129.99.40.1","0","0","180","0","0","0","0","Host_Alive"]
		add_network_module_to_agent_api(driver,params,user="admin",pwd="pandora",apipwd="1234")

		lista = driver.current_url.split('/')

		url = lista[0]+'//'+lista[2]+'/pandora_console'

		driver.get(url)

		agent_name_list = [agent_name_1,agent_name_2]

		module_name_list = [module_name_1]

		edit_modules_in_bulk(driver,module_name_list,agent_name_list,new_module_group="Users")
	
		self.assertRegexpMatches(self.close_alert_and_get_its_text(), r"^Are you sure[\s\S]$")

		search_module(driver,agent_name_1,module_name_1,go_to_module=True)

		self.assertEqual("Users" in driver.page_source,True)


	def test_F_edit_module_umbral_in_bulk(self):

		u"""
		Create two agents and one module in this agents. With bulk operation, change module umbral with bulk operation
		"""

		agent_name_1 = gen_random_string(6)
		agent_name_2 = gen_random_string(6)

		module_name_1 = gen_random_string(6)

		driver = self.driver
		
		activate_api(driver,"1234")

		params = [agent_name_1,"127.0.0.1","0","4","0","300","2","pandorafms","2","0","0","pruebas"]
		create_agent_api(driver,params,user="admin",pwd="pandora")

		params = [agent_name_2,"127.0.0.1","0","4","0","300","2","pandorafms","2","0","0","pruebas"]
		create_agent_api(driver,params,user="admin",pwd="pandora")

		params = [agent_name_1,module_name_1,"0","6","1","0","0","0","0","0","0","0","0","129.99.40.1","0","0","180","0","0","0","0","Host_Alive"]
		add_network_module_to_agent_api(driver,params,user="admin",pwd="pandora",apipwd="1234")

		params = [agent_name_2,module_name_1,"0","6","1","0","0","0","0","0","0","0","0","129.99.40.1","0","0","180","0","0","0","0","Host_Alive"]
		add_network_module_to_agent_api(driver,params,user="admin",pwd="pandora",apipwd="1234")

		lista = driver.current_url.split('/')

		url = lista[0]+'//'+lista[2]+'/pandora_console'

		driver.get(url)

		agent_name_list = [agent_name_1,agent_name_2]

		module_name_list = [module_name_1]

		edit_modules_in_bulk(driver,module_name_list,agent_name_list,new_min="1",new_max="2")

		self.assertRegexpMatches(self.close_alert_and_get_its_text(), r"^Are you sure[\s\S]$")

<<<<<<< HEAD
=======
		search_module (driver,agent_name_1,module_name_1)


		



>>>>>>> 7d1de5c... Add changes in Bulk_operations.py and extend search_module function
if __name__ == "__main__":
        unittest.main()

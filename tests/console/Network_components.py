# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element, detect_and_pass_all_wizards, gen_random_string
from include.agent_functions import search_agent,create_agent,delete_agent
from include.api_functions import *
from include.module_functions import search_module
from include.network_components_functions import *
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.remote.webelement import WebElement

import unittest2, time, re


class Network_components(PandoraWebDriverTestCase):


	test_name = u'Planned_downtime'
	test_description = u'Planed downtime test'
	tickets_associated = []

	def test_A_create_network_component(self):

		u"""
		Create and search new network component module
		"""
		
		driver = self.driver
		self.login()
		detect_and_pass_all_wizards(driver)
		
		agent_name = gen_random_string(6)
		network_component_name = gen_random_string(6)

		activate_api(driver,"1234")

		params = [agent_name,"127.0.0.1","0","4","0","300","2","pandorafms","2","0","0","pruebas"]
		create_agent_api(driver,params,user="admin",pwd="pandora")

		lista = driver.current_url.split('/')

		url = lista[0]+'//'+lista[2]+'/pandora_console'

		driver.get(url)
			
		create_network_component(driver,network_component_name,"Remote TCP network agent, boolean data","Network Management","Application",min_warning=10,max_critical=100,description="New network component by test")

		search_agent(driver,agent_name,go_to_agent=True)

		driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Manage"]').click()
		driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Modules"]').click()

		Select(driver.find_element_by_id("moduletype")).select_by_visible_text("Create a new network server module")

		driver.find_element_by_xpath('//*[@id="main"]/form/table/tbody/tr/td[5]/input').click()

		driver.find_element_by_xpath('//a[contains(.,"Advanced options")]').click()

		Select(driver.find_element_by_id("network_component_group")).select_by_visible_text("Network Management")

		time.sleep(3)
			
		Select(driver.find_element_by_id("network_component")).select_by_visible_text(network_component_name)

		driver.find_element_by_id("submit-crtbutton").click()

		search_module (driver,agent_name,network_component_name,go_to_module=False)

		self.assertEqual(network_component_name in driver.page_source,True)


	def test_B_create_plugin_component(self):

		u"""
		Create and search new plug-in component
		"""

		driver = self.driver

		agent_name = gen_random_string(6)
		plugin_component_name = gen_random_string(6)

		activate_api(driver,"1234")

		params = [agent_name,"127.0.0.1","0","4","0","300","2","pandorafms","2","0","0","pruebas"]
		create_agent_api(driver,params,user="admin",pwd="pandora")

		lista = driver.current_url.split('/')
		
		url = lista[0]+'//'+lista[2]+'/pandora_console'
		
		driver.get(url)
		
		create_plugin_component(driver,plugin_component_name,"Generic numeric","Network Management","Application",max_warning=50,max_critical=100,description="New plugin component")
		
		search_agent(driver,agent_name,go_to_agent=True)

		driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Manage"]').click()
		driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Modules"]').click()

		Select(driver.find_element_by_id("moduletype")).select_by_visible_text("Create a new plug-in server module")
					
		driver.find_element_by_xpath('//*[@id="main"]/form/table/tbody/tr/td[5]/input').click()

		driver.find_element_by_xpath('//a[contains(.,"Advanced options")]').click()

		Select(driver.find_element_by_id("network_component_group")).select_by_visible_text("Network Management")

		time.sleep(3)
		
		Select(driver.find_element_by_id("network_component")).select_by_visible_text(plugin_component_name)
		
		driver.find_element_by_id("submit-crtbutton").click()
		
		search_module (driver,agent_name,plugin_component_name,go_to_module=False)
		
		self.assertEqual(plugin_component_name in driver.page_source,True)


if __name__ == "__main__":
	unittest2.main()



	

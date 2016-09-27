# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, detect_and_pass_all_wizards, gen_random_string, is_enterprise
from include.policy_functions import *
from include.agent_functions import *
from include.collection_functions import *
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.remote.webelement import WebElement
import unittest2, time, re

class Policies(PandoraWebDriverTestCase):

	test_name = u'Policies'
	test_description = u'Policy tests'
	tickets_associated = []

	policy_name = gen_random_string(6)
	network_server_module_name = gen_random_string(6)


	@is_enterprise
	def test_A_create_policy(self):
		
		u"""
		Create a policy and verify that it is created
		"""
		driver = self.driver
		self.login()
		detect_and_pass_all_wizards(driver)

		create_policy(driver,self.policy_name,"Applications",description="Policy for test")

		search_policy(driver,self.policy_name,go_to_policy=False)

		element = driver.find_element_by_xpath('//a[contains(.,"'+self.policy_name+'")]')
		self.assertIsInstance(element,WebElement)

	
	@is_enterprise
	def test_B_add_network_server_module_to_policy(self):
		
		u"""
		Add network server module to previous policy 
		"""	
		driver = self.driver

		add_module_policy(driver,self.policy_name,"network_server",driver,module_name=self.network_server_module_name,component_group="Network Management",network_component="Host Alive")

		element = driver.find_element_by_xpath('//td[contains(.,"uccessfully")]')
		self.assertIsInstance(element,WebElement)

	
	@is_enterprise
	def test_C_add_collection_to_policy(self):

		u"""
		Create policy, create collection and add collection to policy
		"""
		
		policy_name = gen_random_string(6)
		collection_name = gen_random_string(6)


		driver = self.driver

		create_policy(driver,policy_name,"Applications",description="Policy for test")

		create_collection(driver,collection_name,collection_name,group="All",description="Collection for test")

		add_collection_to_policy(driver,policy_name,collection_name)

		element = driver.find_element_by_xpath('//td[contains(.,"Correct: add the collection in the policy")]')
		self.assertIsInstance(element,WebElement)



	@is_enterprise
	def test_D_Apply_policy_to_agents(self):

		u"""
		Create two agent, create a policy, create two modules in policy and apply policy in new agents, check that modules is created in agents
		"""

		policy_name = gen_random_string(6)
		agent_name_1 = gen_random_string(6)
		agent_name_2 = gen_random_string(6)
		module_name_1 = gen_random_string(6)
		module_name_2 = gen_random_string(6)

		driver = self.driver

		create_agent(driver,agent_name_1,description="First agent by test")
		create_agent(driver,agent_name_2,description="Second agent 2 by test")
				
		create_policy(driver,policy_name,"Applications",description="This is policy by test")
		
		add_module_policy(driver,policy_name,"network_server",driver,module_name=module_name_1,component_group="Network Management",network_component="Host Alive")
		
		add_module_policy(driver,policy_name,"network_server",driver,module_name=module_name_2,component_group="Network Management",network_component="Host Latency")
		
		list_agent = (agent_name_1,agent_name_2)
		apply_policy_to_agent(driver,policy_name,list_agent)
		
		search_agent(driver,agent_name_1,go_to_agent=True)		
		driver.find_element_by_xpath('//*[@id="menu_tab"]/ul/li[1]/a/img').click()
		
		driver.find_element_by_xpath('//*[@id="menu_tab"]/ul/li[3]/a/img').click()
		
		module = driver.find_element_by_xpath('//td[contains(.,"'+module_name_1+'")]')
		self.assertIsInstance(module,WebElement)	
		
		module = driver.find_element_by_xpath('//td[contains(.,"'+module_name_2+'")]')
		self.assertIsInstance(module,WebElement)
	
		search_agent(driver,agent_name_2,go_to_agent=True)		
		driver.find_element_by_xpath('//*[@id="menu_tab"]/ul//img[@data-title="Manage"]').click()
		
		driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Modules"]').click()
	
                module = driver.find_element_by_xpath('//td[contains(.,"'+module_name_1+'")]')
                self.assertIsInstance(module,WebElement)

                module = driver.find_element_by_xpath('//td[contains(.,"'+module_name_2+'")]')
                self.assertIsInstance(module,WebElement)


if __name__ == "__main__":
	unittest2.main()

# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element, detect_and_pass_all_wizards, is_enterprise, gen_random_string
from include.agent_functions import *
from include.module_functions import *
from include.service_functions import *
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.remote.webelement import WebElement

import unittest, time, re

class Service(PandoraWebDriverTestCase):

	test_name = u'Service tests'
	test_description = u''
	tickets_associated = []

	agent_name = gen_random_string(6)
	
	module_critical_1_name = gen_random_string(6)
	module_critical_2_name = gen_random_string(6)
	module_critical_3_name = gen_random_string(6)
	module_normal_1_name = gen_random_string(6)
	module_normal_2_name = gen_random_string(6)
	module_warning_1_name = gen_random_string(6)

	
	@is_enterprise	
	def test_A_simple_service(self):

		u"""
		Add 3 modules in Simple service, two critical and one in warning, force service and check that service is warning.
		"""

		service_name = gen_random_string(6)

		driver = self.driver
		self.login()
		detect_and_pass_all_wizards(driver)

		create_agent(driver,self.agent_name,ip="127.0.0.1",group="Applications")

		# creamos 3 modulos uno que este router ping (127.0.0.3) y otro ping printer (127.0.0.1) y Apache server -> Host latency min_warning 0.01

		create_network_server_module(driver,self.agent_name,module_name = self.module_critical_1_name,component_group="Network Management",network_component="Host Alive",ip="129.99.40.1")

		create_network_server_module(driver,self.agent_name,module_name =  self.module_critical_2_name,component_group="Network Management",network_component="Host Alive",ip="129.99.40.1")
		
		create_network_server_module(driver,self.agent_name,module_name = self.module_critical_3_name,component_group="Network Management",network_component="Host Alive",ip="129.99.40.1")
		
		create_network_server_module(driver,self.agent_name,module_name = self.module_normal_1_name,component_group="Network Management",network_component="Host Alive",ip="127.0.0.1")
			
		create_network_server_module(driver,self.agent_name,module_name = self.module_normal_2_name,component_group="Network Management",network_component="Host Alive",ip="127.0.0.1")

		create_network_server_module(driver,self.agent_name,module_name = self.module_warning_1_name,component_group="Network Management",network_component="Host Latency",ip="127.0.0.1",min_warning="-10")

		#Creamos servicio en modo simple 
		
		create_service(driver,service_name,"Applications",self.agent_name,description=service_name,mode="Simple") 

		# añadimos los 3 modulos al servicio un router y el warning router critico printer no critico y apache critico
		
		add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_critical_1_name,description=self.module_critical_1_name,is_critical=True)
		add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_critical_2_name,description=self.module_critical_2_name)
		add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_warning_1_name,description=self.module_warning_1_name,is_critical=True)
		# Forzamos el servicio y comprobamos que el estado es warning
		
		force_service(driver,service_name)
		
		search_service(driver,service_name,go_to_service=False)

		element = driver.find_element_by_xpath('//td/img[@data-title="Warning"]')
		self.assertIsInstance(element,WebElement)

	@is_enterprise
        def test_B_simple_service(self):

                u"""
                Add 3 modules in Simple service, two normal and one in critical, force service and check that service is critical.
                """

                service_name = gen_random_string(6)

                driver = self.driver

                #Creamos servicio en modo simple

                create_service(driver,service_name,"Applications",self.agent_name,description=service_name,mode="Simple")

                # añadimos los 3 modulos al servicio un router y el warning router critico printer no critico y apache critico

                add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_critical_1_name,description=self.module_critical_1_name,is_critical=True)
                add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_critical_2_name,description=self.module_critical_2_name)
                add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_critical_3_name,description=self.module_critical_3_name,is_critical=True)

                # Forzamos el servicio y comprobamos que el estado es warning

                force_service(driver,service_name)

                search_service(driver,service_name,go_to_service=False)

                element = driver.find_element_by_xpath('//td/img[@data-title="Critical"]')
                self.assertIsInstance(element,WebElement)


        @is_enterprise
        def test_C_simple_service(self):

                u"""
                Add 3 modules in Simple service, two normal and one in critical, force service and check that service is critical.
                """
		
                service_name = gen_random_string(6)

                driver = self.driver
	
                #Creamos servicio en modo simple

                create_service(driver,service_name,"Applications",self.agent_name,description=service_name,mode="Simple")

                # añadimos los 3 modulos al servicio un router y el warning router critico printer no critico y apache critico

                add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_normal_1_name,description=self.module_normal_1_name,is_critical=True)
                add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_critical_2_name,description=self.module_critical_2_name)
                add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_normal_1_name,description=self.module_normal_1_name,is_critical=True)

                # Forzamos el servicio y comprobamos que el estado es warning

                force_service(driver,service_name)

                search_service(driver,service_name,go_to_service=False)

                element = driver.find_element_by_xpath('//td/img[@data-title="Ok"]')
                self.assertIsInstance(element,WebElement)

if __name__ == "__main__":
        unittest.main()

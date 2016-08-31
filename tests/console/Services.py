# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element, detect_and_pass_all_wizards, is_enterprise, gen_random_string
from include.agent_functions import *
from include.module_functions import *
from include.service_functions import *
from include.api_functions import *
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.remote.webelement import WebElement

import unittest, time, re

class SimpleService(PandoraWebDriverTestCase):

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
	
		activate_api(driver,"1234")
	
		params = [self.agent_name,"127.0.0.1","0","4","0","300","2","pandorafms","2","0","0","pruebas"]
                create_agent_api(driver,params,user="admin",pwd="pandora")

		# creamos 3 modulos uno que este router ping (127.0.0.3) y otro ping printer (127.0.0.1) y Apache server -> Host latency min_warning 0.01

		time.sleep(3)

		params = [self.agent_name,self.module_critical_1_name,"0","6","1","0","0","0","0","0","0","0","0","129.99.40.1","0","0","180","0","0","0","0","Host_Alive"]
		add_network_module_to_agent_api(driver,params,user="admin",pwd="pandora",apipwd="1234")

		time.sleep(3)

		params = [self.agent_name,self.module_critical_2_name,"0","6","1","0","0","0","0","0","0","0","0","129.99.40.1","0","0","180","0","0","0","0","Host_Alive"]
		add_network_module_to_agent_api(driver,params,user="admin",pwd="pandora",apipwd="1234")

		time.sleep(3)

		params = [self.agent_name,self.module_critical_3_name,"0","6","1","0","0","0","0","0","0","0","0","129.99.40.1","0","0","180","0","0","0","0","Host_Alive"]
		add_network_module_to_agent_api(driver,params,user="admin",pwd="pandora",apipwd="1234")	
		
		time.sleep(3)

		params = [self.agent_name,self.module_normal_1_name,"0","6","1","0","0","0","0","0","0","0","0","127.0.0.1","0","0","180","0","0","0","0","Host_Alive"]
		add_network_module_to_agent_api(driver,params,user="admin",pwd="pandora",apipwd="1234")
		
		time.sleep(3)

		params = [self.agent_name,self.module_normal_2_name,"0","6","1","0","0","0","0","0","0","0","0","127.0.0.1","0","0","180","0","0","0","0","Host_Alive"]
		add_network_module_to_agent_api(driver,params,user="admin",pwd="pandora",apipwd="1234")
	
		time.sleep(3)

                params = [self.agent_name,self.module_warning_1_name,"0","7","1","-10","9999","0","0","0","0","0","0","127.0.0.1","0","0","180","0","0","0","0","Host_Latency"]
                add_network_module_to_agent_api(driver,params,user="admin",pwd="pandora",apipwd="1234")

		lista = driver.current_url.split('/')

		url = lista[0]+'//'+lista[2]+'/pandora_console'
		
		driver.get(url)
	
		#Creamos servicio en modo simple 
		
		create_service(driver,service_name,"Applications",self.agent_name,description=service_name,mode="simple") 

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

		create_service(driver,service_name,"Applications",self.agent_name,description=service_name,mode="simple")

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

		create_service(driver,service_name,"Applications",self.agent_name,description=service_name,mode="simple")

		# añadimos los 3 modulos al servicio un router y el warning router critico printer no critico y apache critico

		add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_normal_1_name,description=self.module_normal_1_name,is_critical=True)
		add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_critical_2_name,description=self.module_critical_2_name)
		add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_normal_1_name,description=self.module_normal_1_name,is_critical=True)

		# Forzamos el servicio y comprobamos que el estado es warning

		force_service(driver,service_name)

		search_service(driver,service_name,go_to_service=False)

		element = driver.find_element_by_xpath('//td/img[@data-title="Ok"]')
		self.assertIsInstance(element,WebElement)

	
class ManualService(PandoraWebDriverTestCase):

        test_name = u'Auto service tests'
        test_description = u'Test for auto service type'
        tickets_associated = []

        agent_name = gen_random_string(6)

        module_ok_1_name = gen_random_string(6)
	module_warning_1_name = gen_random_string(6)
	module_critical_1_name = gen_random_string(6)

	def test_A_manual_service_ok(self):

		u"""
		Creamos agente y modulos necesarios, creamos un servicio de tipo manual para añadirle el modulo OK y al dar valor menor de 0.5 tener el servicio en Ok
                """

		service_name = gen_random_string(6)

                driver = self.driver
		self.login()
		detect_and_pass_all_wizards(driver)

		activate_api(driver,"1234")

		#Creamos agentes y modulos warning, critical y ok que usaremos en los 3 test de servicios tipo manual
		params = [self.agent_name,"127.0.0.1","0","4","0","300","2","pandorafms","2","0","0","pruebas"]
                create_agent_api(driver,params,user="admin",pwd="pandora")
		
		params = [self.agent_name,self.module_critical_1_name,"0","6","1","0","0","0","0","0","0","0","0","129.99.40.1","0","0","180","0","0","0","0","Host_Alive"]
                add_network_module_to_agent_api(driver,params,user="admin",pwd="pandora",apipwd="1234")

		params = [self.agent_name,self.module_warning_1_name,"0","7","1","-10","9999","0","0","0","0","0","0","127.0.0.1","0","0","180","0","0","0","0","Host_Latency"]
                add_network_module_to_agent_api(driver,params,user="admin",pwd="pandora",apipwd="1234")	

		params = [self.agent_name,self.module_ok_1_name,"0","6","1","0","0","0","0","0","0","0","0","127.0.0.1","0","0","180","0","0","0","0","Host_Alive"]
                add_network_module_to_agent_api(driver,params,user="admin",pwd="pandora",apipwd="1234")

		lista = driver.current_url.split('/')

                url = lista[0]+'//'+lista[2]+'/pandora_console'

                driver.get(url)

		#Creamos el servicio añadiendo modulo en warnining y el servicio será OK			
		create_service(driver,service_name,"Applications",self.agent_name,description=service_name,mode="manual",critical="1",warning="0.5")
		add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_ok_1_name,description=self.module_ok_1_name,ok_weight="0.2")
		add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_warning_1_name,description=self.module_warning_1_name,warning_weight="0.2")
		
		force_service(driver,service_name)

                search_service(driver,service_name,go_to_service=False)

                element = driver.find_element_by_xpath('//td/img[@data-title="Ok"]')
                self.assertIsInstance(element,WebElement)


	def test_B_auto_service_critical(self):

		u"""
                Utilizando el agente y modulos necesarios, creamos un servicio de tipo auto para añadirle el modulo warning y Ok y al dar valor mayor de 1 tendremos el servicio en critical
                """

		service_name = gen_random_string(6)

		driver = self.driver

		#Creamos el servicio añadiendo el modulo ok y warning y el servicio será critical
		create_service(driver,service_name,"Applications",self.agent_name,description=service_name,mode="manual",critical="1",warning="0.5")
		add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_ok_1_name,description=self.module_ok_1_name,ok_weight="0.5")
		add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_critical_1_name,description=self.module_critical_1_name,critical_weight="0.5")
		

		force_service(driver,service_name)

		search_service(driver,service_name,go_to_service=False)

		element = driver.find_element_by_xpath('//td/img[@data-title="Critical"]')
		self.assertIsInstance(element,WebElement)		


	def test_C_auto_service_warning(self):

		u"""
                Utilizando el agente y modulos necesarios, creamos un servicio de tipo auto para añadirle el modulo warning y añadimos un critical y warning weight para que de un valor entre 0.5 y 1 y así obtener un servicio tipo auto en warning
                """

		service_name = gen_random_string(6)

                driver = self.driver

		#Creamos el servicio que será de tipo warning
		create_service(driver,service_name,"Applications",self.agent_name,description=service_name,mode="manual",critical="1",warning="0.5")

		add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_warning_1_name,description=self.module_warning_1_name,warning_weight="0.3")
		add_elements_to_service(driver,service_name,"Module",agent_name=self.agent_name,module=self.module_critical_1_name,description=self.module_critical_1_name,critical_weight="0.3")

		force_service(driver,service_name)

		search_service(driver,service_name,go_to_service=False)

		element = driver.find_element_by_xpath('//td/img[@data-title="Warning"]')
		self.assertIsInstance(element,WebElement)

	
if __name__ == "__main__":
	unittest.main()

# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element, detect_and_pass_all_wizards, logout, gen_random_string
from include.planned_downtime_functions import *
from include.alert_functions import *
from include.module_functions import *
from include.agent_functions import *
from include.event_functions import *
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.remote.webelement import WebElement
import unittest, time, re, datetime


class PAN13(PandoraWebDriverTestCase):

	test_name = u'Planned_downtime'
	test_description = u'Planed downtime test'
	tickets_associated = []

	def test_A_create_planned_downtime_Quiet(self):

		u"""
		Create and search planned downtime quiet
		"""
		driver = self.driver
		self.login()
		detect_and_pass_all_wizards(driver)

		planned_name = gen_random_string(6)

		create_planned_downtime(driver,planned_name,"Applications","Quiet","Once",description=planned_name)
		
		time.sleep(10)
		
		search_planned_downtime(driver,planned_name)	

		element = driver.find_element_by_xpath('//img[@data-title="Running"]')
                self.assertIsInstance(element,WebElement)
	
	def test_B_create_planned_downtime_disabled_agents(self):
		
                u"""
                Create and search planned downtime disabled agents
                """
		driver = self.driver

		planned_name = gen_random_string(6)
		
		create_planned_downtime(driver,planned_name,"Applications","Disabled Agents","Once",description=planned_name)
		
		time.sleep(10)
		
		search_planned_downtime(driver,planned_name)	
	
		element = driver.find_element_by_xpath('//img[@data-title="Running"]')
                self.assertIsInstance(element,WebElement)
	   
	def test_C_create_planned_downtime_disabled_only_alerts(self):
		
		u"""
		Create and search planned downtime disabled only alerts
		"""
		driver = self.driver
		
		planned_name = gen_random_string(6)

		create_planned_downtime(driver,planned_name,"Applications","Disabled only Alerts","Once",description=planned_name)
		
		time.sleep(10)
		
		search_planned_downtime(driver,planned_name)	

 		element = driver.find_element_by_xpath('//img[@data-title="Running"]')
                self.assertIsInstance(element,WebElement)

        def test_D_delete_planned_downtime(self):

	
		driver=self.driver

		planned_name = gen_random_string(6)

                create_planned_downtime(driver,planned_name,"Applications","Quiet","Once",description=planned_name)

		delete_planned_downtime(driver,planned_name)

	def test_E_quiet_functionality(self):

		driver=self.driver
		
		planned_name = gen_random_string(6)
		agent_name_A = gen_random_string(6)
		agent_name_B = gen_random_string(6)

		module_name_A_A = gen_random_string(6)
		module_name_A_B = gen_random_string(6)

		module_name_B_A = gen_random_string(6)
		module_name_B_B = gen_random_string(6)

		create_agent(driver,agent_name_A,ip="127.0.0.1",group="Applications")
		create_agent(driver,agent_name_B,ip="127.0.0.1",group="Applications")

		create_module('network_server',driver,agent_name=agent_name_A,module_name=module_name_A_A,component_group='Network Management',network_component='Host Alive')
		create_module('network_server',driver,agent_name=agent_name_A,module_name=module_name_A_B,component_group='Network Management',network_component='Host Latency')

		create_module('network_server',driver,agent_name=agent_name_B,module_name=module_name_B_A,component_group='Network Management',network_component='Host Alive')
		create_module('network_server',driver,agent_name=agent_name_B,module_name=module_name_B_B,component_group='Network Management',network_component='Host Alive')

                assign_alert_template_to_module(driver,agent_name_A,module_name_A_A,'Critical condition')
                assign_alert_template_to_module(driver,agent_name_A,module_name_A_B,'Critical condition')

                assign_alert_template_to_module(driver,agent_name_B,module_name_B_A,'Critical condition')
                assign_alert_template_to_module(driver,agent_name_B,module_name_B_B,'Critical condition')

		#Little hack to allow the planned downtime to be edited
		fifteen_seconds_later = datetime.datetime.now() + datetime.timedelta(seconds=15)
		fifteen_seconds_later_to_pandora = str(fifteen_seconds_later.hour)+":"+str(fifteen_seconds_later.minute)+":"+str(fifteen_seconds_later.second)

		create_planned_downtime(driver,planned_name,"Applications","Quiet","Once",hour_from=fifteen_seconds_later_to_pandora,description=planned_name,agent_module_list=[(agent_name_A,[module_name_A_A]),(agent_name_B,[module_name_B_A])])

		#We wait 10 seconds to the planned downtime to start. Since we do not specify a date, default dates are from now to one hour in the future.
		time.sleep(40)

		#Is the planned downtime running?
		search_planned_downtime(driver,planned_name)
                element = driver.find_element_by_xpath('//img[@data-title="Running"]')
                self.assertIsInstance(element,WebElement)

		force_alert_of_module(driver,agent_name_A,module_name_A_A,'Critical condition')
		force_alert_of_module(driver,agent_name_A,module_name_A_B,'Critical condition')
		force_alert_of_module(driver,agent_name_B,module_name_B_A,'Critical condition')
		force_alert_of_module(driver,agent_name_B,module_name_B_B,'Critical condition')

		search_events(driver,agent_name=agent_name_A,module_name=module_name_A_A)

		event_who_should_not_be_present = driver.find_elements_by_xpath('//tbody/tr[td[3][contains(.,"Alert fired")] and td[4][contains(.,"'+agent_name_A+'")]]')

		self.assertEqual(event_who_should_not_be_present,[])

		search_events(driver,agent_name=agent_name_A,module_name=module_name_A_B)

		event_who_should_be_present = driver.find_elements_by_xpath('//tbody/tr[td[3][contains(.,"Alert fired")] and td[4][contains(.,"'+agent_name_A+'")]]')
		
		self.assertNotEqual(event_who_should_be_present,[])

		search_events(driver,agent_name=agent_name_B,module_name=module_name_B_A)

		event_who_should_not_be_present_b = driver.find_elements_by_xpath('//tbody/tr[td[3][contains(.,"Alert fired")] and td[4][contains(.,"'+agent_name_B+'")]]')

		self.assertEqual(event_who_should_not_be_present_b,[])

		search_events(driver,agent_name=agent_name_B,module_name=module_name_B_B)

		event_who_should_be_present_b = driver.find_elements_by_xpath('//tbody/tr[td[3][contains(.,"Alert fired")] and td[4][contains(.,"'+agent_name_B+'")]]')

		self.assertNotEqual(event_who_should_be_present_b,[])

if __name__ == "__main__":
	unittest.main()

# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, is_element_present, click_menu_element, detect_and_pass_all_wizards, logout, gen_random_string, is_enterprise
from include.agent_functions import create_agent, search_agent, create_agent_group
from include.user_functions import create_user, create_user_profile
from include.module_functions import create_module
from include.reports_functions import create_report
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.remote.webelement import WebElement
import unittest2, time, re

class ACLPropagation(PandoraWebDriverTestCase):

	test_name = u'ACL propagation'
	tickets_associated = []


	def test_ACL_propagation(self):

		u"""
		ACL Propagation test: Creates one group "A" with ACL propagation, then a group "B" son of "A" with no ACL propagation, and finally group "C".
		The test asserts if a user with privileges to "A" can see the agent of "B" but no agents of "C".
		"""
        	group_name_A = gen_random_string(6)
        	group_name_B = gen_random_string(6)
        	group_name_C = gen_random_string(6)
        	agent_name_A = gen_random_string(6)
        	agent_name_B = gen_random_string(6)
	        user_name = gen_random_string(6)
		
		driver = self.driver
		self.login()
		detect_and_pass_all_wizards(driver)
		
		create_agent_group(driver,group_name_A,propagate_acl=True,description="Group A, with propagate ACL, son of ALL")	
		create_agent_group(driver,group_name_B,parent_group=group_name_A,description="Group B, son of A")		
		create_agent_group(driver,group_name_C,parent_group=group_name_B,description="Group C, son of B")	
		
		create_agent(driver,agent_name_A,description="Agent in group B",group=group_name_B)

		create_agent(driver,agent_name_B,description="Agent in group C",group=group_name_C)

		l=[("Chief Operator",group_name_A,[])]
		
		create_user(driver,user_name,"pandora",profile_list=l)

		self.logout()
		
		self.login(user=user_name)
		
		detect_and_pass_all_wizards(driver)
	
                #Is the agent listed in the agent list?
		search_agent(driver,agent_name_A,go_to_agent=False)
		element = driver.find_element_by_xpath('//a[contains(.,"'+agent_name_A+'")]')
		self.assertIsInstance(element,WebElement)

		#Is the agent accesible for the user?
		search_agent(driver,agent_name_A,go_to_agent=True)
		element = driver.find_element_by_xpath('//*[@id="agent_contact_main"]/thead/tr/th')
		self.assertIsInstance(element,WebElement)

		#Is the agent invisible to the user? (It should be invisible)
		search_agent(driver,agent_name_B,go_to_agent=False)
		element = driver.find_elements_by_xpath('//a[contains(.,"'+agent_name_B+'")]')
		self.assertEqual(element,[])

class ACLReports(PandoraWebDriverTestCase):

        test_name = u'ACL reports'
        tickets_associated = []

 	def test_ACL_reports(self):

                u"""
		Creates a user with Chief Operator permissions over the Applications group. 
		Then creates two reports: one in the Applications group and other in the Servers group. Then, it checks that the given user can only see the Application report
		"""

		user_name = gen_random_string(6)
		report_name_A = agent_name = gen_random_string(6)
		report_name_B = agent_name = gen_random_string(6)		

		driver = self.driver
                self.login()

                #Creates a user with Chief Operator - Applications profile
                profile_list = []
                profile_list.append(("Chief Operator","Applications",[]))
                create_user(driver,user_name,user_name,email=user_name+'@pandorafms.com',profile_list=profile_list)

                #Creates report
                create_report(driver,report_name_A,"Applications")
                create_report(driver,report_name_B,"Servers")

                #Logout
                self.logout()

                #Login
                self.login(user=user_name,passwd=user_name)

                #Check that the report is visible
                click_menu_element(driver,"Custom reports")
                driver.find_element_by_id('text-search').clear()
                driver.find_element_by_id('text-search').send_keys(report_name_A)
                driver.find_element_by_id('submit-search_submit').click()
                self.assertEqual(is_element_present(driver, By.ID, 'report_list-0'),True)


                #Check that the report is not visible
                click_menu_element(driver,"Custom reports")
                driver.find_element_by_id('text-search').clear()
                driver.find_element_by_id('text-search').send_keys(report_name_B)
                driver.find_element_by_id('submit-search_submit').click()

                time.sleep(6)

                
                element = driver.find_element_by_xpath('//td[contains(.,"No data found.")]')
                self.assertIsInstance(element,WebElement)

class ACLTags(PandoraWebDriverTestCase):

        test_name = u'ACL tag test'
        tickets_associated = []
        
	def test_ACL_tag(self):

		u"""Create agent and two modules, one without tag and with tag, create a user with tag and check this user can view module with tag and user can´t view module without tag"""
		
                #agent_name = gen_random_string(6)
                #module_name_A = gen_random_string(6)
                #module_name_B = gen_random_string(6)
		#user_name = gen_random_string(6)

		#driver = self.driver
		#self.login()
		#detect_and_pass_all_wizards(driver)
		
		#create_agent(driver,agent_name,group="Applications",ip="192.168.50.50")
		
		#We create a module without a tag
			
		#create_module("network_server",driver,agent_name=agent_name,module_name=module_name_A,component_group="Network Management",network_component="Host Alive",ip="192.168.50.50")
		
		#We now create a modulo with tag "critical"
		
		#create_module("network_server",driver,agent_name=agent_name,module_name=module_name_B,component_group="Network Management",network_component="Host Alive",ip="192.168.50.50",tag_name="critical")

		
		#l = [("Operator (Read)","All",["critical"])]

		#create_user(driver,user_name,"pandora",profile_list=l) 
		
		#self.logout()
		
		#self.login(user=user_name)
		
		#detect_and_pass_all_wizards(driver)
		
		#search_agent(driver,agent_name)

		#time.sleep(6)

		
		#The user should be able to see the module with Tag
		#module = driver.find_element_by_xpath('//td[contains(.,"'+module_name_B+'")]')
		#self.assertIsInstance(module,WebElement)		

		#The user should NOT be able to see the module without tag
		#modules = driver.find_elements_by_xpath('//td[contains(.,"'+module_name_A+'")]')
		#self.assertEqual(modules,[])
 
if __name__ == "__main__":
	unittest2.main()

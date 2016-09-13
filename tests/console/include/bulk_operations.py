
# -*- coding: utf-8 -*-
from selenium import selenium
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from common_functions_60 import *

import random, time
import string

def delete_agents_in_bulk(driver,agent_names_list):

        click_menu_element(driver,"Agent operations")
        driver.find_element_by_id("option").click()
        Select(driver.find_element_by_id("option")).select_by_visible_text("Delete agents in bulk")

        for agent_name in agent_names_list:
                Select(driver.find_element_by_id("id_agents")).select_by_visible_text(agent_name)

        driver.find_element_by_id("submit-go").click()


def edit_agents_in_bulk(driver,agent_names_list,new_group=None,new_description=None):

	click_menu_element(driver,"Agent operations")
	driver.find_element_by_id("option").click()
	Select(driver.find_element_by_id("option")).select_by_visible_text("Edit agents in bulk")

	for agent_name in agent_names_list:
		Select(driver.find_element_by_id("id_agents")).select_by_visible_text(agent_name)

	time.sleep(3)

	if new_group != None:
		
		Select(driver.find_element_by_id("group")).select_by_visible_text(new_group)

	if new_description != None:
		
		driver.find_element_by_id("text-description").clear()
		driver.find_element_by_id("text-description").send_keys(new_description)

	driver.find_element_by_id("submit-updbutton").click()


def delete_modules_in_bulk(driver,agent_name_list,module_name_list,select_agent_first=None):

	#If select_agent_first is None, select the modules first
	
	#To erase all modules with this names, add "Any" in agent_name_list
	#To erase all modules of the agent put "Any" in module_name_list

	click_menu_element(driver,"Module operations")
	
	driver.find_element_by_id("option").click()
	Select(driver.find_element_by_id("option")).select_by_visible_text("Delete modules in bulk")
	
	if select_agent_first != None:
		
		driver.find_element_by_id("radiobtn0002").click()
		
		for agent_name in agent_name_list:
			Select(driver.find_element_by_id("id_agents")).select_by_visible_text(agent_name)

		time.sleep(3)

		
		for module_name in module_name_list:
			Select(driver.find_element_by_id("module")).select_by_visible_text(module_name)

	else:

		#driver.find_element_by_id("module_type").click()
		Select(driver.find_element_by_id("module_type")).select_by_visible_text("All")

		time.sleep(3)

		for module_name in module_name_list:
			Select(driver.find_element_by_id("module_name")).select_by_visible_text(module_name)
		
		time.sleep(3)
		
		for agent_name in agent_name_list:
			Select(driver.find_element_by_id("agents")).select_by_visible_text(agent_name)


	driver.find_element_by_id("submit-go").click()



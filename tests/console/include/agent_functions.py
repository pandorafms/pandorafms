# -*- coding: utf-8 -*-
from selenium import selenium
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from common_functions_60 import *

import random, time
import string

def delete_agent (driver,agent_names_list):

	click_menu_element(driver,"Agent operations")
	driver.find_element_by_id("option").click()
	Select(driver.find_element_by_id("option")).select_by_visible_text("Bulk agent delete")

	for agent_name in agent_names_list:
		Select(driver.find_element_by_id("id_agents")).select_by_visible_text(agent_name)

	driver.find_element_by_id("submit-go").click()

def search_agent(driver,agent_name,go_to_agent=True):

	click_menu_element(driver,"Agent detail")
	driver.find_element_by_id("text-search").click()
	driver.find_element_by_id("text-search").clear()
	driver.find_element_by_id("text-search").send_keys(agent_name)
	driver.find_element_by_id("submit-srcbutton").click()
	# If go_to_agent is true this function enters the agent view

	if go_to_agent == True:
		driver.find_element_by_css_selector("b").click()

def create_agent(driver,agent_name,ip=None,description=None,group=None,os_id=None):

	click_menu_element(driver,"Agent detail")
	driver.find_element_by_id("submit-crt").click()
	driver.find_element_by_id("text-agente").send_keys(agent_name)

	if ip != None:
		driver.find_element_by_id("text-direccion").clear()
		driver.find_element_by_id("text-direccion").send_keys(ip)

	if description != None:
		driver.find_element_by_id("text-comentarios").clear()
		driver.find_element_by_id("text-comentarios").send_keys(description)

	if group != None:
		driver.find_element_by_xpath('//option[contains(.,"'+group+'")]').click()

	if os_id !=None:
		Select(driver.find_element_by_id("id_os")).select_by_visible_text(os_id)

	driver.find_element_by_id("submit-crtbutton").click()


def create_agent_group(driver,group_name,parent_group="All",alerts=True,propagate_acl=False,description=None):
	
	# parent_group by defect is All.
	# Alerts by default is activate.
	
	click_menu_element(driver,"Manage agent groups")
	driver.find_element_by_id("submit-crt").click()
	
	driver.find_element_by_id("text-name").send_keys(group_name)
	
	if parent_group != "All":
	
		driver.find_element_by_xpath('//option[contains(.,"'+parent_group+'")]').click()
	
	if alerts == False:
	
		driver.find_element_by_id("checkbox-alerts_enabled").click()
		
	if propagate_acl == True:
	
		driver.find_element_by_id("checkbox-propagate").click()
		
	if description!= None:
	
		driver.find_element_by_id("text-description").send_keys(description)
	
	driver.find_element_by_id("submit-crtbutton").click()

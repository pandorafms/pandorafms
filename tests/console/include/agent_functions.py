# -*- coding: utf-8 -*-
from selenium import selenium
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from common_functions_60 import *

import random, time
import string

def delete_agent (driver,agent_names_list):

	click_menu_element(driver,"Agents operations")
	driver.find_element_by_id("option").click()
	Select(driver.find_element_by_id("option")).select_by_visible_text("Bulk agent delete")

	for agent_name in agent_names_list:
		Select(driver.find_element_by_id("id_agents")).select_by_visible_text(agent_name)

	driver.find_element_by_id("submit-go").click()

def search_agent(driver,agent_name):

	click_menu_element(driver,"Agent detail")
	driver.find_element_by_id("text-search").click()
	driver.find_element_by_id("text-search").clear()
	driver.find_element_by_id("text-search").send_keys(agent_name)
	driver.find_element_by_id("submit-srcbutton").click()
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
		Select(driver.find_element_by_id("grupo")).select_by_visible_text(group)

	if os_id !=None:
		Select(driver.find_element_by_id("id_os")).select_by_visible_text(os_id)

	driver.find_element_by_id("submit-crtbutton").click()


# -*- coding: utf-8 -*-
from selenium import selenium
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from agent_functions import search_agent
from common_functions_60 import *

import random, time
import string


def create_network_component(driver,name,type_component,group,module_group,min_warning=None,max_warning=None,min_critical=None,max_critical=None,str_warning=None,str_critical=None,description=None):

	# type_component is for example -> Remote ICMP network agent (latency) or Remote TCP network agent, numeric data	

	click_menu_element(driver,"Network components")

	driver.find_element_by_id("id_component_type").click()
	Select(driver.find_element_by_id("id_component_type")).select_by_visible_text("Create a new network component")
	
	driver.find_element_by_id("submit-crt").click()

	driver.find_element_by_id("text-name").click()
	driver.find_element_by_id("text-name").clear()
	driver.find_element_by_id("text-name").send_keys(name)

	driver.find_element_by_id("type").click()
	Select(driver.find_element_by_id("type")).select_by_visible_text(type_component)

	driver.find_element_by_id("id_group").click()
	Select(driver.find_element_by_id("id_group")).select_by_visible_text(group)
	
	driver.find_element_by_id("id_module_group").click()
	Select(driver.find_element_by_id("id_module_group")).select_by_visible_text(module_group)

	if min_warning != None:

		driver.find_element_by_id("text-min_warning").click()
		driver.find_element_by_id("text-min_warning").clear()
		driver.find_element_by_id("text-min_warning").send_keys(min_warning)

	if max_warning != None:
		
		driver.find_element_by_id("text-max_warning").click()
		driver.find_element_by_id("text-max_warning").clear()
		driver.find_element_by_id("text-max_warning").send_keys(max_warning)

	if min_critical != None:

		driver.find_element_by_id("text-min_critical").click()
		driver.find_element_by_id("text-min_critical").clear()
		driver.find_element_by_id("text-min_critical").send_keys(min_critical)

	if max_critical != None:

		driver.find_element_by_id("text-max_critical").click()
		driver.find_element_by_id("text-max_critical").clear()
		driver.find_element_by_id("text-max_critical").send_keys(max_critical)

	if str_warning != None:
		
		driver.find_element_by_id("text-str_warning").click()
		driver.find_element_by_id("text-str_warning").clear()
		driver.find_element_by_id("text-str_warning").send_keys(str_warning)

	if str_critical != None:
		
		driver.find_element_by_id("text-str_critical").click()
		driver.find_element_by_id("text-str_critical").clear()
		driver.find_element_by_id("text-str_critical").send_keys(str_critical)
	
	if description != None:

		driver.find_element_by_id("textarea_description").click()
		driver.find_element_by_id("textarea_description").clear()
		driver.find_element_by_id("textarea_description").send_keys(description)

	driver.find_element_by_id("submit-crt").click()

def create_plugin_component(driver,name,type_component,group,module_group,min_warning=None,max_warning=None,min_critical=None,max_critical=None,str_warning=None,str_critical=None,description=None):

	# type_component is for example -> Generic boolean or Generic numeric incremental (absolute)

	click_menu_element(driver,"Network components")
	
	driver.find_element_by_xpath('//*[@id="id_component_type"]/option[2]').click()
	
	driver.find_element_by_id("submit-crt").click()
	
	driver.find_element_by_id("text-name").click()
	driver.find_element_by_id("text-name").clear()
	driver.find_element_by_id("text-name").send_keys(name)

	driver.find_element_by_id("type").click()
	Select(driver.find_element_by_id("type")).select_by_visible_text(type_component)

	driver.find_element_by_id("id_group").click()
	Select(driver.find_element_by_id("id_group")).select_by_visible_text(group)

	driver.find_element_by_id("id_module_group").click()
	Select(driver.find_element_by_id("id_module_group")).select_by_visible_text(module_group)

	if min_warning != None:

		driver.find_element_by_id("text-min_warning").click()
		driver.find_element_by_id("text-min_warning").clear()
		driver.find_element_by_id("text-min_warning").send_keys(min_warning)

	if max_warning != None:

		driver.find_element_by_id("text-max_warning").click()
		driver.find_element_by_id("text-max_warning").clear()
		driver.find_element_by_id("text-max_warning").send_keys(max_warning)

	if min_critical != None:

		driver.find_element_by_id("text-min_critical").click()
		driver.find_element_by_id("text-min_critical").clear()
		driver.find_element_by_id("text-min_critical").send_keys(min_critical)
		

	if max_critical != None:

		driver.find_element_by_id("text-max_critical").click()
		driver.find_element_by_id("text-max_critical").clear()
		driver.find_element_by_id("text-max_critical").send_keys(max_critical)

	# str_warning and str_critical for Generic string type:
	if str_warning != None:

		driver.find_element_by_id("text-str_warning").click()
		driver.find_element_by_id("text-str_warning").clear()
		driver.find_element_by_id("text-str_warning").send_keys(str_warning)

	if str_critical != None:

		driver.find_element_by_id("text-str_critical").click()
		driver.find_element_by_id("text-str_critical").clear()
		driver.find_element_by_id("text-str_critical").send_keys(str_critical)
	
	if description != None:

		driver.find_element_by_id("textarea_description").click()
		driver.find_element_by_id("textarea_description").clear()
		driver.find_element_by_id("textarea_description").send_keys(description)

	driver.find_element_by_id("submit-crt").click()	


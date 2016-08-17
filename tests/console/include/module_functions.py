# -*- coding: utf-8 -*-
from selenium import selenium
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from agent_functions import search_agent
from common_functions_60 import *

import random, time
import string


def create_module(module_type,*args,**kwargs):
	if module_type=='network_server':
		create_network_server_module(*args,**kwargs)
	elif module_type=='data_server':
		create_data_server_module(*args,**kwargs)
		
def create_network_server_module(driver,agent_name=None,module_name=None,component_group=None,type_of_module=None,network_component=None,min_warning=None,max_warning=None,min_critical=None,max_critical=None,ip=None,tag_name=None,description=None):

	# component_group -> Example: Remote ICMP network agent (latency)
	# network_component -> Example: Host Alive
	
	#The type_of_module variable is optional, but required if component_group and network_component variables are specified
	
	if agent_name != None:
		search_agent(driver,agent_name)
		driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Manage"]').click()
		driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Modules"]').click()
	
	Select(driver.find_element_by_id("moduletype")).select_by_visible_text("Create a new network server module")
	driver.find_element_by_xpath('//*[@id="main"]/form/table/tbody/tr/td[5]/input').click()
	
	driver.find_element_by_xpath('//a[contains(.,"Advanced options")]').click()
	
	if min_warning != None:
	
		driver.find_element_by_id("text-min_warning").clear()
		driver.find_element_by_id("text-min_warning").send_keys(min_warning)
	
	if max_warning != None:
	
		driver.find_element_by_id("text-max_warning").clear()
		driver.find_element_by_id("text-max_warning").send_keys(min_warning)
		
	if min_critical != None:
	
		driver.find_element_by_id("text-min_critical").clear()
		driver.find_element_by_id("text-min_critical").send_keys(min_critical)
	
	if max_critical != None:
	
		driver.find_element_by_id("text-max_critical").clear()
		driver.find_element_by_id("text-max_critical").send_keys(max_critical)
		
	if ip != None:
		
		driver.find_element_by_id("text-ip_target").clear()
		driver.find_element_by_id("text-ip_target").send_keys(ip)		
	
	if component_group!= None and network_component!= None:
		Select(driver.find_element_by_id("network_component_group")).select_by_visible_text(component_group)
		Select(driver.find_element_by_id("network_component")).select_by_visible_text(network_component)
	
	else:
		driver.find_element_by_id("text-name").clear()
		driver.find_element_by_id("text-name").send_keys(module_name)
		Select(driver.find_element_by_id("id_module_type")).select_by_visible_text(type_of_module)	
	
	if module_name != None:
		time.sleep(3)
		driver.find_element_by_id("text-name").clear()
                driver.find_element_by_id("text-name").send_keys(module_name)
		
        if tag_name != None:

                Select(driver.find_element_by_id("id_tag_available")).select_by_visible_text(tag_name)
                driver.find_element_by_xpath('//*[@id="right"]').click()

        if description != None:

                driver.find_element_by_id("textarea_description").clear()
                driver.find_element_by_id("textarea_description").send_keys(description)

	driver.find_element_by_id("submit-crtbutton").click()


def create_data_server_module(driver,module_name,agent_name=None,type_of_module=None,min_warning=None,max_warning=None,min_critical=None,max_critical=None,tag_name=None,description=None):

	# type_of_module -> Example: Generic numeric
	
	# The type_of_module variable is Generic numeric by default

	if agent_name != None:
		search_agent(driver,agent_name)
		driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Manage"]').click()
		driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Modules"]').click()

	Select(driver.find_element_by_id("moduletype")).select_by_visible_text("Create a new data server module")
	driver.find_element_by_xpath('//*[@id="create_module_type"]/table/tbody/tr/td[5]/input').click()
	
	driver.find_element_by_xpath('//a[contains(.,"Advanced options")]').click()
	
	driver.find_element_by_id("text-name").clear()
	driver.find_element_by_id("text-name").send_keys(module_name)
	
	if type_of_module != None:
	
		Select(driver.find_element_by_id("id_module_type")).select_by_visible_text(type_of_module)	
		
	if min_warning != None:
	
		driver.find_element_by_id("text-min_warning").clear()
		driver.find_element_by_id("text-min_warning").send_keys(min_warning)
	
	if max_warning != None:
	
		driver.find_element_by_id("text-max_warning").clear()
		driver.find_element_by_id("text-max_warning").send_keys(min_warning)
		
	if min_critical != None:
	
		driver.find_element_by_id("text-min_critical").clear()
		driver.find_element_by_id("text-min_critical").send_keys(min_critical)
	
	if max_critical != None:
	
		driver.find_element_by_id("text-max_critical").clear()
		driver.find_element_by_id("text-max_critical").send_keys(max_critical)
		
	if tag_name != None:
		
		Select(driver.find_element_by_id("id_tag_available")).select_by_visible_text(tag_name)
		driver.find_element_by_xpath('//*[@id="right"]').click()
		
	if description != None:
	
		driver.find_element_by_id("textarea_description").clear()
		driver.find_element_by_id("textarea_description").send_keys(description)

	driver.find_element_by_id("submit-crtbutton").click()
		
def delete_module (driver,agent_name,module_name):

	search_agent(driver,agent_name)
	driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Manage"]').click()
	driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Modules"]').click()
	
	driver.find_element_by_id("text-search_string").clear()
	driver.find_element_by_id("text-search_string").send_keys(module_name)
		
	driver.find_element_by_id("submit-filter").click()
	driver.find_element_by_id("checkbox-id_delete").click()
	driver.find_element_by_xpath('//*[@id="table2-1-9"]/a/img').click()

	alert = driver.switch_to_alert()
	alert.accept()
	
def search_module (driver,agent_name,module_name):
	
	search_agent(driver,agent_name)
	driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Manage"]').click()
	driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Modules"]').click()
	
	driver.find_element_by_id("text-search_string").clear()
	driver.find_element_by_id("text-search_string").send_keys(module_name)
	
	driver.find_element_by_id("submit-filter").click()


	

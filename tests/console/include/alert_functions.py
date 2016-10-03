# -*- coding: utf-8 -*-
from selenium import selenium
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from module_functions import search_module
from common_functions_60 import *

import random, time
import string


def assign_alert_template_to_module (driver,agent_name,module_name,template_name):

        search_module(driver,agent_name,module_name)
        driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Alerts"]').click()
        Select(driver.find_element_by_id("id_agent_module")).select_by_visible_text(module_name)
        Select(driver.find_element_by_id("template")).select_by_visible_text(template_name)
        driver.find_element_by_id("submit-add").click()

def force_alert_of_module(driver,agent_name,module_name,template_name):

        click_menu_element(driver,"Agent detail")
        driver.find_element_by_id("text-search").clear()
        driver.find_element_by_id("text-search").send_keys(agent_name)
        driver.find_element_by_id("submit-srcbutton").click()
        driver.find_element_by_css_selector("b").click()
        driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Alerts"]').click()
        driver.find_element_by_xpath('//tr[td[3][contains(.,"'+module_name+'")] and td[4][contains(.,"'+template_name+'")]]/td[2]/a').click()

        time.sleep(10)


def create_new_action_to_alert(driver,action_name,action_group,command,threshold=None,field1=None,field2=None,field3=None):

	click_menu_element(driver,"Actions")
	driver.find_element_by_id("submit-create").click()

	driver.find_element_by_id("text-name").clear()
	driver.find_element_by_id("text-name").send_keys(action_name)

	driver.find_element_by_xpath('//option[contains(.,"'+action_group+'")]').click()

	driver.find_element_by_xpath('//option[contains(.,"'+command+'")]').click()

	if threshold != None:

		driver.find_element_by_id("text-action_threshold").clear()
		driver.find_element_by_id("text-action_threshold").send_keys(threshold)

	if command == "eMail" and field1 != None and field2 != None and field3 != None:

		driver.find_element_by_id("textarea_field1_value").clear()
		driver.find_element_by_id("textarea_field1_value").send_keys(field1)

		driver.find_element_by_id("textarea_field2_value").clear()
		driver.find_element_by_id("textarea_field2_value").send_keys(field2)

		driver.find_element_by_id("textarea_field3_value").clear()
		driver.find_element_by_id("textarea_field3_value").send_keys(field3)

	if command == "Syslog" and field1 != None and field2 != None:

		driver.find_element_by_id("textarea_field1_value").clear()
		driver.find_element_by_id("textarea_field1_value").send_keys(field1)
	
		driver.find_element_by_id("textarea_field2_value").clear()
		driver.find_element_by_id("textarea_field2_value").send_keys(field2)
	
	driver.find_element_by_id("submit-create").click()


def create_new_command_to_alert(driver,command_name,command,list_field_description=None,list_field_values=None,description=None):

	click_menu_element(driver,"Commands")
	driver.find_element_by_id("submit-create").click()

	time.sleep(3)

	driver.find_element_by_id("text-name").clear()	
	driver.find_element_by_xpath('//*[@id="text-name"]').send_keys(command_name)
	
	driver.find_element_by_id("textarea_command").clear()
	driver.find_element_by_id("textarea_command").send_keys(command)
	
	if description != None:
		
		driver.find_element_by_id("textarea_description").clear()
		driver.find_element_by_id("textarea_description").send_keys(description)	

	if list_field_description != None:

		i=1
		for field_description in list_field_description:
			driver.find_element_by_id("text-field"+str(i)+"_description").clear()
			driver.find_element_by_id("text-field"+str(i)+"_description").send_keys(field_description)
			i=i+1
	
	if list_field_values != None:

		i=1
		for field_value in list_field_values:
			driver.find_element_by_id("text-field"+str(i)+"_values").clear()
			driver.find_element_by_id("text-field"+str(i)+"_values").send_keys(field_value)
			i=i+1
		
	driver.find_element_by_id("submit-create").click()

def create_new_template_to_alert(driver,template_name,group,action_name,condition_type,list_days=None,time_from=None,time_to=None,description=None,alert_recovery=False,field_list=None):

	#If list_days is None, we select all days. Example list_days = ["monsday","friday"]
	#alert_recovery is disabled by defect. Choose True for pyt in enabled status


	click_menu_element(driver,"Templates")	
	driver.find_element_by_id("submit-create").click()

	time.sleep(3)

	driver.find_element_by_id("text-name").clear()
	driver.find_element_by_id('text-name').send_keys(template_name)

	driver.find_element_by_xpath('//option[contains(.,"'+group+'")]').click()

	if description != None:
		driver.find_element_by_id("textarea_description").clear()
		driver.find_element_by_id('textarea_description').send_keys(description)

	
	driver.find_element_by_id("submit-next").click()

	time.sleep(3)
	
	if list_days != None:

		driver.find_element_by_id("checkbox-monday").click()
		driver.find_element_by_id("checkbox-tuesday").click()
		driver.find_element_by_id("checkbox-wednesday").click()
		driver.find_element_by_id("checkbox-thursday").click()
		driver.find_element_by_id("checkbox-friday").click()
		driver.find_element_by_id("checkbox-saturday").click()
		driver.find_element_by_id("checkbox-sunday").click()
	
		for day in list_days:
	
			driver.find_element_by_id("checkbox-"+day).click()

	if time_from != None:
		
		driver.find_element_by_id("text-time_from").clear()
		driver.find_element_by_id("text-time_from").send_keys(time_from)

	if time_to != None:
		
		driver.find_element_by_id("text-time_to").clear()		
		driver.find_element_by_id("text-time_to").send_keys(time_to)

	driver.find_element_by_xpath('//option[contains(.,"'+action_name+'")]').click()

	driver.find_element_by_xpath('//option[contains(.,"'+condition_type+'")]').click()

	driver.find_element_by_id("submit-next").click()

	time.sleep(3)

	if alert_recovery == True:

		driver.find_element_by_xpath('//option[contains(.,"Enabled")]').click()

	if field_list != None:
		
		i=1
		for field in field_list:
			
			driver.find_element_by_id("textarea_field"+str(i)).clear()
			driver.find_element_by_id("textarea_field"+str(i)).send_keys(field)
			i=i+1
	
	driver.find_element_by_id("submit-finish").click()

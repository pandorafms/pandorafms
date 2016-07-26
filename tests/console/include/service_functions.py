# -*- coding: utf-8 -*-
from selenium import selenium
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from common_functions_60 import *

from time import sleep

import random, re
import string


def create_service(driver,name,group,agent,description=None,mode="Auto",critical=None,warning=None):

	# Mode by defect is "Auto". Mode can be "Auto", "Simple" or "Manual". 
	# If mode = "manual" insert critial and warning values
	
	click_menu_element(driver,"Services")
	
	# We check if we have any service or not because the menu change
	
	if ("A service is a way to group your IT resources based on their functionalities." in driver.page_source) == True:
		driver.find_element_by_xpath('//*[@id="main"]/div[2]/div[2]/form/input').click()
	
	else:
		driver.find_element_by_id("submit-crt").click()		
	
	driver.find_element_by_id("text-name").clear()
	driver.find_element_by_id("text-name").send_keys(name)	

	driver.find_element_by_xpath('//option[contains(.,"'+group+'")]').click()

        driver.find_element_by_xpath('//*[@id="text-agent_target"]').clear()
        driver.find_element_by_xpath('//*[@id="text-agent_target"]').send_keys(agent)

        sleep(6)

        driver.find_element_by_xpath('//a[contains(.,"'+agent+'")]').click()

	
	if description != None:
		
		driver.find_element_by_id("text-description").clear()
		driver.find_element_by_id("text-description").send_keys(description)
	
	if mode == "simple":
		driver.find_element_by_id("radiobtn0003").click()
		
	if mode == "manual":
		driver.find_element_by_id("radiobtn0001").click()
		
		driver.find_element_by_id("text-critical").clear()
		driver.find_element_by_id("text-critical").send_keys(critical)
		
		driver.find_element_by_id("text-warning").clear()
		driver.find_element_by_id("text-warning").send_keys(warning)
	
	driver.find_element_by_id("submit-crt").click()
		

def search_service(driver,name,group="All",status="Any",mode="Any",go_to_service=True):

	# If go_to_service = False this function can not enter in service

	click_menu_element(driver,"Services")
	
	driver.find_element_by_xpath('//a[contains(.,"Filter")]').click()
	driver.find_element_by_id("text-free_search").clear()
	driver.find_element_by_id("text-free_search").send_keys(name)
	
	if group != None:
		driver.find_element_by_xpath('//option[contains(.,"'+group+'")]').click()
	
	if status != "Any":
		driver.find_element_by_xpath('//option[contains(.,"'+status+'")]').click()
	
	if mode != "Any":
		driver.find_element_by_xpath('//option[contains(.,"'+mode+'")]').click()
	
	driver.find_element_by_id("submit-search").click()

	if go_to_service == True:
		driver.find_element_by_xpath('//a[contains(.,"'+name+'")]').click()
	

def delete_service(driver,name):

	search_service(driver,name,go_to_service=False)	
	driver.find_element_by_xpath('//*[@id="table3-0-10"]/a[3]/img').click()
	alert = driver.switch_to_alert() 
	alert.accept()


def edit_service(driver,name,new_name=None,new_group=None,new_description=None,new_mode=None,critical=None,warning=None):

	# If choose new_mode = manual, insert critical and warning variables.
	
	search_service(driver,name,go_to_service=False)	
	driver.find_element_by_xpath('//*[@id="table3-0-10"]/a[1]/img').click()
	
	if new_name != None:
		driver.find_element_by_id("text-name").clear()
		driver.find_element_by_id("text-name").send_keys(new_name)
		
	if new_group != None:	
		driver.find_element_by_xpath('//option[contains(.,"'+new_group+'")]').click()
		
	if new_description != None:
		driver.find_element_by_id("text-description").clear()
		driver.find_element_by_id("text-description").send_keys(new_description)
	
	if new_mode == "simple":
		driver.find_element_by_id("radiobtn0003").click()
		
	if new_mode == "manual":
		driver.find_element_by_id("radiobtn0001").click()
		
		driver.find_element_by_id("text-critical").clear()
		driver.find_element_by_id("text-critical").send_keys(critical)
		
		driver.find_element_by_id("text-warning").clear()
		driver.find_element_by_id("text-warning").send_keys(warning)
	
	if new_mode == "auto":		
		driver.find_element_by_id("radiobtn0002").click()
		
	diver.find_elemet_by_id("submit-crt").click()

# -*- coding: utf-8 -*-
from selenium import selenium
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from common_functions_60 import *

import random, time
import string

def search_events(driver,agent_name=None,module_name=None):

	click_menu_element(driver,"View events")
	driver.find_element_by_xpath('//a[contains(.,"Event control filter")]').click()
	driver.find_element_by_xpath('//a[contains(.,"Advanced options")]').click()
	
	if agent_name != None:
		driver.find_element_by_id("text-text_agent").clear()
		driver.find_element_by_id("text-text_agent").send_keys(agent_name)
		time.sleep(3)
		driver.find_element_by_xpath('//a[@class="ui-corner-all"][contains(.,"'+agent_name+'")]').click() # In this line you click the drop-down box search
		
	if module_name != None:
		driver.find_element_by_id("text-module_search").clear()
		driver.find_element_by_id("text-module_search").send_keys(module_name)
		time.sleep(3)
		driver.find_element_by_xpath('//a[@class="ui-corner-all"][contains(.,"'+module_name+'")]').click() # In this line you click the drop-down box search
	
	driver.find_element_by_id("submit-update").click()

# -*- coding: utf-8 -*-
from selenium import selenium
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from common_functions_60 import *

import random, time
import string

def create_report(driver,nombre,group_name,description=None):
	click_menu_element(driver,"Custom reports")
	driver.find_element_by_id("submit-create").click()
	driver.find_element_by_id("text-name").clear()
	driver.find_element_by_id("text-name").send_keys(nombre)
	if group_name == "All":
		Select(driver.find_element_by_id("id_group")).select_by_visible_text(group_name)
	else:
		#TODO This will not work when choosing a group within a group within another group
		Select(driver.find_element_by_id("id_group")).select_by_visible_text("    "+group_name)

	if description != None:
		driver.find_element_by_id("textarea_description").clear()
		driver.find_element_by_id("textarea_description").send_keys(description)

	driver.find_element_by_id("submit-add").click()

def delete_report(driver,report_name):
	click_menu_element(driver,"Custom reports")
	driver.find_element_by_id('text-search').clear()
	driver.find_element_by_id('text-search').send_keys(report_name)
	driver.find_element_by_id('submit-search_submit').click()
	driver.find_element_by_id('image-delete2').click()
	alert = driver.switch_to_alert()
	alert.accept()




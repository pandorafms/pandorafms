# -*- coding: utf-8 -*-
from selenium import selenium
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from common_functions_60 import *

import random, time
import string


def create_collection(driver,name,short_name,group="All",description=None):

	click_menu_element(driver,"Collections")
	driver.find_element_by_id("submit-crt").click()
	
	driver.find_element_by_id("text-name").clear()
	driver.find_element_by_id("text-name").send_keys(name)
	
	driver.find_element_by_id("text-short_name").clear()
	driver.find_element_by_id("text-short_name").send_keys(name)
	
	if group != "All":
		driver.find_element_by_xpath('//option[contains(.,"'+group+'")]').click()
		
	if description != None:
		driver.find_element_by_id("textarea_description").clear()
		driver.find_element_by_id("textarea_description").send_keys(description)
		
	driver.find_element_by_id("submit-submit").click()
	
	
def search_collection(driver,name,go_to_collection=True):

	click_menu_element(driver,"Collections")
	driver.find_element_by_xpath('//*[@id="main"]/form[1]/table/tbody/tr/td[2]/input').clear()
	driver.find_element_by_xpath('//*[@id="main"]/form[1]/table/tbody/tr/td[2]/input').send_keys(name)
	driver.find_element_by_xpath('//*[@id="main"]/form[1]/table/tbody/tr/td[3]/input').click()
	
	if go_to_collection == True:
		
		driver.find_element_by_xpath('//a[contains(.,"'+name+'")]').click()

def delete_collection(driver,name):
	search_collection(driver,name,go_to_collection=False)
	driver.find_element_by_xpath('//*[@id="agent_list"]/tbody/tr[2]/td[5]/a[1]/img').click()
	alert = driver.switch_to_alert() 
	alert.accept()

def edit_collection(driver,name,new_name=None,group=None,description=None):
	search_collection(driver,name)
	
	if new_name != None:
		driver.find_element_by_id("text-name").clear()
		driver.find_element_by_id("text-name").send_keys(name)
	
	if group != None:
		driver.find_element_by_xpath('//option[contains(.,"'+group+'")]').click()
	
	if description != None:
		driver.find_element_by_id("textarea_description").clear()
		driver.find_element_by_id("textarea_description").send_keys(description)
		
	driver.find_element_by_id("submit-submit").click()


def create_text_in_collection(driver,collection_name,file_name,text_file=None):

	#text_file is a content of file.
	
	search_collection(driver,collection_name)
	driver.find_element_by_xpath('//*[@id="menu_tab"]/ul/li[2]/a/img').click()
	driver.find_element_by_xpath('//*[@id="main"]/div[2]/a[2]/img').click()	
	driver.find_element_by_id("text-name_file").clear()
	driver.find_element_by_id("text-name_file").send_keys(file_name)	
	driver.find_element_by_id("submit-create").click()
	
	if text_file != None:
		driver.find_element_by_xpath('//*[@id="main"]/form/table/tbody/tr[2]/td/textarea').clear()
		driver.find_element_by_xpath('//*[@id="main"]/form/table/tbody/tr[2]/td/textarea').send_keys(text_file)
	
	driver.find_element_by_xpath('//*[@id="main"]/form/table/tbody/tr[3]/td/input[3]').click()	
	

def create_directory_in_collection(driver,collection_name,directory_name):

	search_collection(driver,collection_name)
	driver.find_element_by_xpath('//*[@id="menu_tab"]/ul/li[2]/a/img').click()
	driver.find_element_by_xpath('//*[@id="main"]/div[2]/a[1]/img').click()
	driver.find_element_by_id('text-dirname').clear()
	driver.find_element_by_id('text-dirname').send_keys(directory_name)
	driver.find_element_by_id('submit-crt').click()

def recreate_collection(driver,collection_name):
	
	search_collection(driver,collection_name)
	driver.find_element_by_xpath('//*[@id="button-recreate_file"]').click()
	alert = driver.switch_to_alert() 
	alert.accept()



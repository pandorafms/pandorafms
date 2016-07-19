# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import click_menu_element, detect_and_pass_all_wizards
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
import unittest, time, re

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

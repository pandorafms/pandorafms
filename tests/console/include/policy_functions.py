# -*- coding: utf-8 -*-
from common_classes_60 import PandoraWebDriverTestCase
from common_functions_60 import login, click_menu_element, detect_and_pass_all_wizards, logout
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
import unittest, time, re


def create_policy(driver,policy_name,group,description=None):

	click_menu_element(driver,"Manage policies")
	driver.find_element_by_id("submit-crt").click()
	driver.find_element_by_id("text-name").send_keys(profile_name)
	driver.find_element_by_xpath('//option[contains(.,"'+group+'")]').click()

	if description!= None:
		driver.find_element_by_id("textarea_description").send_keys(profile_name)
	
	driver.find_element_by_id("submit-crt").click()

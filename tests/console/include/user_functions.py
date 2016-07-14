# -*- coding: utf-8 -*-
from selenium import selenium
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from common_functions_60 import *

import random, time
import string

def add_user_profile(driver,user_name,profile,group,tags=[]):
        click_menu_element(driver,"Users management")
        driver.find_element_by_css_selector("b").click()
        driver.find_element_by_id("text-filter_search").clear()
        driver.find_element_by_id("text-filter_search").send_keys(user_name)
        driver.find_element_by_id("submit-search").click()
        driver.find_element_by_xpath('//*[@id="table3-0-6"]/a[2]').click()
        Select(driver.find_element_by_id("assign_profile")).select_by_visible_text(profile)


        if group == "All":
                Select(driver.find_element_by_id("assign_group")).select_by_visible_text(group)
        else:
                #TODO This will not work when choosing a group within a group within another group
                Select(driver.find_element_by_id("assign_group")).select_by_visible_text("    "+group)

	for tag in tags:
		Select(driver.find_element_by_id("assign_tags")).select_by_visible_text(tag)
		
	#If we do not provide tags, we NEED to leave "Any" selected, otherwise we need to deselect it.
	if tags != []:
		Select(driver.find_element_by_id("assign_tags")).deselect_by_visible_text("Any")
		
        driver.find_element_by_xpath('//*[@name="add"]').click()

		
		
def create_user(driver,user_name,userpwd,email=None,profile_list=None,is_admin=False):

        u"""
        Profile list es una LISTA de TUPLAS:
                                l = [("Chief Operator","All",[]),("Read Operator","Servers",["tag1","tag2"])]
        """

        click_menu_element(driver,"Users management")
        driver.find_element_by_id("submit-crt").click()
        driver.find_element_by_name("id_user").clear()
        driver.find_element_by_name("id_user").send_keys(user_name)
        driver.find_element_by_name("password_new").clear()
        driver.find_element_by_name("password_new").send_keys(userpwd)
        driver.find_element_by_name("password_confirm").clear()
        driver.find_element_by_name("password_confirm").send_keys(userpwd)
        driver.find_element_by_name("email").clear()

        if is_admin == True:
                driver.find_element_by_id('radiobtn0001').click()

        if is_admin == False:
                driver.find_element_by_id('radiobtn0002').click()

        if email != None:
                driver.find_element_by_name("email").clear()
                driver.find_element_by_name("email").send_keys(email)
        driver.find_element_by_id("submit-crtbutton").click()

        if profile_list != None:
                for profile_name,group_name,tag_list in profile_list:
                        add_user_profile(driver,user_name,profile_name,group_name,tags=tag_list)


def search_user(driver,user_name):
	click_menu_element(driver,"Users management")
	driver.find_element_by_css_selector("b").click()
	driver.find_element_by_id('text-filter_search').clear()
	driver.find_element_by_id("text-filter_search").send_keys(user_name)
	driver.find_element_by_id("submit-search").click()

		
def activate_home_screen(driver,mode):

	click_menu_element(driver,"Edit my user")
	Select(driver.find_element_by_id("section")).select_by_visible_text(mode)
	driver.find_element_by_id("submit-uptbutton").click()


def create_user_profile(driver,profile_name,bit_list=[]):

	#bit_list can be the profile name or bit.	
	
	click_menu_element(driver,"Profile management")
	driver.find_element_by_id("submit-crt").click()

	driver.find_element_by_id("text-name").send_keys(profile_name)
	
	for profile in bit_list:
		
		if profile == "View incidents" or profile == "IR":
			
			driver.find_element_by_xpath('//*[@id="checkbox-incident_view"]').click()
			
		if profile == "Edit incidents" or profile == "IW":
			
			driver.find_element_by_xpath('//*[@id="checkbox-incident_edit"]').click()
			
		if profile == "Manage incidents" or profile == "IM":

			driver.find_element_by_xpath('//*[@id="checkbox-incident_management"]').click()
			
		if profile == "View agents" or profile == "AR":
		
			driver.find_element_by_xpath('//*[@id="checkbox-agent_view"]').click()

		if profile == "Edit agents" or profile == "AW":
		
			driver.find_element_by_xpath('//*[@id="checkbox-agent_edit"]').click()

		if profile == "Disable agents" or profile == "AD":
		
			driver.find_element_by_xpath('//*[@id="checkbox-agent_disable"]').click()

		if profile == "Edit alerts" or profile == "LW":
		
			driver.find_element_by_xpath('//*[@id="checkbox-alert_edit"]').click()

		if profile == "Manage alerts" or profile == "LM":
		
			driver.find_element_by_xpath('//*[@id="checkbox-alert_management"]').click()

		if profile == "Manage users" or profile == "UM":
			
			driver.find_element_by_xpath('//*[@id="checkbox-user_management"]').click()

		if profile == "Manage database" or profile == "DM":
		
			driver.find_element_by_xpath('//*[@id="checkbox-db_management"]').click()
	
		if profile == "View events" or profile == "ER":
		
			driver.find_element_by_xpath('//*[@id="checkbox-event_view"]').click()

		if profile == "Edit events" or profile == "EW":
		
			driver.find_element_by_xpath('//*[@id="checkbox-event_edit"]').click()		

		if profile == "Manage events" or profile == "EM":
		
			driver.find_element_by_xpath('//*[@id="checkbox-event_management"]').click()		

		if profile == "View reports" or profile == "RR":
		
			driver.find_element_by_xpath('//*[@id="checkbox-report_view"]').click()		

		if profile == "Edit reports" or profile == "RW":
		
			driver.find_element_by_xpath('//*[@id="checkbox-report_edit"]').click()		
		
		if profile == "Manage reports" or profile == "RM":
		
			driver.find_element_by_xpath('//*[@id="checkbox-report_management"]').click()		

		if profile == "View network maps" or profile == "MR":
			
			driver.find_element_by_xpath('//*[@id="checkbox-map_view"]').click()		
			
		if profile == "Edit network maps" or profile == "MW":
			
			driver.find_element_by_xpath('//*[@id="checkbox-map_edit"]').click()		

		if profile == "Manage network maps" or profile == "MM":
		
			driver.find_element_by_xpath('//*[@id="checkbox-map_management"]').click()		
			
		if profile == "View visual console" or profile == "VR":
		
			driver.find_element_by_xpath('//*[@id="checkbox-vconsole_view"]').click()		
			
		if profile == "Edit visual console" or profile == "VW":
		
			driver.find_element_by_xpath('//*[@id="checkbox-vconsole_edit"]').click()		
			
		if profile == "Manage visual console" or profile == "VM":
		
			driver.find_element_by_xpath('//*[@id="checkbox-vconsole_management"]').click()		
			
		if profile == "Pandora management" or profile == "PM":
			
			driver.find_element_by_xpath('//*[@id="checkbox-pandora_management"]').click()		
			
	driver.find_element_by_id("submit-crt").click()
			

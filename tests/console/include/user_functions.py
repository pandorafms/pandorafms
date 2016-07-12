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

        #driver.find_element_by_id("image-add2").click()
		
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


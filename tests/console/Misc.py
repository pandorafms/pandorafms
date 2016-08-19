# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element, refresh_N_times_until_find_element, detect_and_pass_all_wizards, is_element_present, logout
from include.reports_functions import create_report, delete_report
from include.user_functions import create_user
from include.agent_functions import create_agent
from include.module_functions import create_module
from include.event_functions import *
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import StaleElementReferenceException, NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
from selenium.webdriver.remote.webelement import WebElement
import unittest, time, re

class Miscellaneous (PandoraWebDriverTestCase):

	test_name = u'Miscellaneous'
	tickets_associated = []

	def test_japanese_characters(self):
		
		u"""
		Creates an agent and a module with japanese characters and test if the event list show the characters properly
		"""
		
		driver = self.driver
		self.login()
		detect_and_pass_all_wizards(driver)

		create_agent(driver,u"次のライセンスに基づいていま")

		#Create module
		create_module("network_server",driver,agent_name=u"次のライセンスに基づいていま",module_name=u"管理者ガイド",component_group="Network Management",network_component="Host Alive",ip="192.168.50.50")
		
		#Create alert
		driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Alerts"]').click()
		Select(driver.find_element_by_id("id_agent_module")).select_by_visible_text(u"管理者ガイド")
		Select(driver.find_element_by_id("template")).select_by_visible_text("Critical condition")
		Select(driver.find_element_by_id("action_select")).select_by_visible_text("Default action")
		driver.find_element_by_id("submit-add").click()

		#Force alert
		click_menu_element(driver,"Agent detail")
		driver.find_element_by_id("text-search").clear()
		driver.find_element_by_id("text-search").send_keys(u"次のライセンスに基づいていま")
		driver.find_element_by_id("submit-srcbutton").click()
		driver.find_element_by_css_selector("b").click()
		driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Alerts"]').click()
		driver.find_element_by_xpath('//tr[@id="table2-0"]/td/a/img[@data-title="Force"]').click()
		time.sleep(10)

		#Search events of our agent
		
		search_events(driver,agent_name=u"次のライセンスに基づいていま",module_name=u"管理者ガイド")

		#Check that there are japanese characters present on the event
		element = driver.find_element_by_xpath(u'//a[contains(.,"Alert fired (Critical condition) assigned to (管理者ガイド)")]')
		self.assertIsInstance(element,WebElement)

if __name__ == "__main__":
	unittest.main()


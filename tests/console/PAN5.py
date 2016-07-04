# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element, refresh_N_times_until_find_element, detect_and_pass_all_wizards, create_user, is_element_present, create_report, logout, delete_report
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import StaleElementReferenceException
import unittest, time, re

class PAN5(PandoraWebDriverTestCase):

        test_name = u'PAN_5'
        test_description = u'Creates an agent and a module with japanese characters and test if the event list show the characters properly'
        tickets_associated = []

        def test_pan5(self):
		driver = self.driver
		login(driver)
		detect_and_pass_all_wizards(driver)

		click_menu_element(driver,"Agent detail")
		driver.find_element_by_id("submit-crt").click()
		driver.find_element_by_id("text-agente").click()
		driver.find_element_by_id("text-agente").clear()
		driver.find_element_by_id("text-agente").send_keys(u"次のライセンスに基づいていま")
		driver.find_element_by_id("submit-crtbutton").click()
		driver.find_element_by_css_selector("li.nomn.tab_godmode > a > img.forced_title").click()
		
		Select(driver.find_element_by_id("moduletype")).select_by_visible_text("Create a new network server module")
		driver.find_element_by_name("updbutton").click()
		
		Select(driver.find_element_by_id("network_component_group")).select_by_visible_text("Network Management")
		Select(driver.find_element_by_id("network_component")).select_by_visible_text("Host Alive")
		driver.find_element_by_id("text-name").clear()
		driver.find_element_by_id("text-name").send_keys(u"管理者ガイド")
		driver.find_element_by_id("submit-crtbutton").click()
		
		#TODO Improve xpath expression
		driver.find_element_by_xpath('//*[@id="menu_tab"]/ul/li[4]/a/img').click()
		Select(driver.find_element_by_id("id_agent_module")).select_by_visible_text(u"管理者ガイド")
		Select(driver.find_element_by_id("template")).select_by_visible_text("Critical condition")
		Select(driver.find_element_by_id("action_select")).select_by_visible_text("Default action")
		driver.find_element_by_id("submit-add").click()
		
		click_menu_element(driver,"Agent detail")
		driver.find_element_by_id("text-search").clear()
		driver.find_element_by_id("text-search").send_keys(u"次のライセンスに基づいていま")
		driver.find_element_by_id("submit-srcbutton").click()
		driver.find_element_by_css_selector("b").click()
		driver.find_element_by_xpath('//*[@id="menu_tab"]/ul/li[3]/a/img').click()
		driver.find_element_by_xpath('//*[@id="table2-0-2"]/a/img').click()
	
		click_menu_element(driver,"View events")

		driver.find_element_by_xpath('//a[contains(.,"Event control filter")]').click()

		driver.find_element_by_xpath('//a[contains(.,"Advanced options")]').click()

		driver.find_element_by_id("text-text_agent").clear()
		driver.find_element_by_id("text-text_agent").send_keys(u"次のライセンスに基づいていま")
	
		
		driver.find_element_by_id("text-module_search").clear()
		driver.find_element_by_id("text-module_search").send_keys(u"管理者ガイド")
		driver.find_element_by_id("submit-update").click()

		try:
			self.assertEqual(True,u"Alert fired (Critical condition) assigned to (管理者ガイド)" in driver.page_source)
		except AssertionError as e:
			self.verificationErrors.append(str(e))	

				
				
if __name__ == "__main__":
	unittest.main()

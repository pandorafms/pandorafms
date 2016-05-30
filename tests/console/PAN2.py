# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login, click_menu_element
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
import unittest, time, re

class PAN2(PandoraWebDriverTestCase):
	test_name = u'PAN_2'
	test_description = u'Creation two agents and delete this agents using bulk operation'
	tickets_associated = []
	
	def test_pan2(self):
		driver = self.driver
		login(driver,"admin","pandora",self.base_url)
		click_menu_element(driver,"Agent detail")
		driver.find_element_by_id("submit-crt").click()
		driver.find_element_by_id("text-agente").click()
		driver.find_element_by_id("text-agente").clear()
		driver.find_element_by_id("text-agente").send_keys("prueba masivas 1")
		driver.find_element_by_id("submit-crtbutton").click()
		driver.find_element_by_css_selector("b").click()
		click_menu_element(driver,"Agent detail")
		driver.find_element_by_id("submit-crt").click()
		driver.find_element_by_id("text-agente").click()
		driver.find_element_by_id("text-agente").clear()
		driver.find_element_by_id("text-agente").send_keys("prueba masivas 2")
		driver.find_element_by_id("submit-crtbutton").click()
		driver.find_element_by_css_selector("b").click()
		driver.find_element_by_css_selector("b").click()
		click_menu_element(driver,"Agents operations")
		driver.find_element_by_id("option").click()
		Select(driver.find_element_by_id("option")).select_by_visible_text("Bulk agent delete")
		Select(driver.find_element_by_id("id_agents")).select_by_visible_text("prueba masivas 1")
		Select(driver.find_element_by_id("id_agents")).select_by_visible_text("prueba masivas 2")
		driver.find_element_by_id("submit-go").click()
		try:
			self.assertRegexpMatches(self.close_alert_and_get_its_text(), r"^Are you sure[\s\S]$")
			self.assertEqual(self.driver.find_element_by_xpath('//div[@id="main"]//td[contains(.,"Successfully deleted (2)")]').text,"Successfully deleted (2)")
		except AssertionError as e:
			self.verificationErrors.append(str(e))
	
if __name__ == "__main__":
	unittest.main()


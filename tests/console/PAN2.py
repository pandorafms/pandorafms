# -*- coding: utf-8 -*-
from include.common_classes_60 import PandoraWebDriverTestCase
from include.common_functions_60 import login
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
		element = driver.find_element_by_xpath("//ul[@id='subViews']/li[4]/a/div")
		driver.execute_script("arguments[0].click();", element)
		driver.find_element_by_id("submit-crt").click()
		driver.find_element_by_id("text-agente").click()
		driver.find_element_by_id("text-agente").clear()
		driver.find_element_by_id("text-agente").send_keys("prueba masivas 1")
		driver.find_element_by_id("submit-crtbutton").click()
		driver.find_element_by_css_selector("b").click()
		element = driver.find_element_by_xpath("//ul[@id='subViews']/li[4]/a/div")
		driver.execute_script("arguments[0].click();", element)
		driver.find_element_by_id("submit-crt").click()
		driver.find_element_by_id("text-agente").click()
		driver.find_element_by_id("text-agente").clear()
		driver.find_element_by_id("text-agente").send_keys("prueba masivas 2")
		driver.find_element_by_id("submit-crtbutton").click()
		driver.find_element_by_css_selector("b").click()
		driver.find_element_by_css_selector("b").click()
		element = driver.find_element_by_css_selector('#subBulk_operations > li.sub_subMenu > a > div.submenu_text.submenu2_text_middle')
		driver.execute_script("arguments[0].click();", element)
		driver.find_element_by_id("option").click()
		Select(driver.find_element_by_id("option")).select_by_visible_text("Bulk agent delete")
		Select(driver.find_element_by_id("id_agents")).select_by_visible_text("prueba masivas 1")
		Select(driver.find_element_by_id("id_agents")).select_by_visible_text("prueba masivas 2")
		driver.find_element_by_id("submit-go").click()
		self.assertRegexpMatches(self.close_alert_and_get_its_text(), r"^Are you sure[\s\S]$")
		self.assertTrue("Successfully deleted (2)" in driver.page_source)
	
if __name__ == "__main__":
	unittest.main()


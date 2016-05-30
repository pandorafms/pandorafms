# -*- coding: utf-8 -*-
from selenium import selenium
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

def login(driver,user="admin",passwd="pandora",pandora_url="http://127.0.0.1/"):
	driver.get(pandora_url+"/pandora_console/index.php")
	driver.find_element_by_id("nick").clear()
	driver.find_element_by_id("nick").send_keys(user)
	driver.find_element_by_id("pass").clear()
	driver.find_element_by_id("pass").send_keys(passwd)
	driver.find_element_by_id("submit-login_button").click()

def get_menu_element(driver,menu_item_text):
	return driver.find_element_by_xpath('//div[@class="menu"]//a[contains(.,"'+menu_item_text+'")]')

def click_menu_element(driver,menu_item_text):
	return driver.execute_script("arguments[0].click();", get_menu_element(driver,menu_item_text))

def refresh_N_times_until_find_element(driver,n,element_text,how=By.ID,refresh_time=5):
	from selenium.common.exceptions import TimeoutException

	i = 1
	while (1<=n):
		try:
			element = WebDriverWait(driver, refresh_time).until(EC.presence_of_element_located((how, element_text)))
			return element
		except:
			driver.get(driver.current_url)
			i = i+1

	raise TimeoutException("Element %s not found" % (element_text))


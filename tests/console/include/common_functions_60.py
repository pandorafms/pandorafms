# -*- coding: utf-8 -*-
from selenium import selenium
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

import random
import string

def gen_random_string(size,preffix=None):
	random_string =  ''.join(random.SystemRandom().choice(string.ascii_uppercase+string.digits) for _ in range(size))
	if preffix:
		return preffix+random_string
	else:
		return random_string


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

def refresh_N_times_until_find_element(driver,n,element_text,how=By.ID,refresh_time=10):
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


def create_user(driver,userid,userpwd,email=None):
	click_menu_element(driver,"Users management")
	driver.find_element_by_id("submit-crt").click()
	driver.find_element_by_name("id_user").clear()
	driver.find_element_by_name("id_user").send_keys(userid)
	driver.find_element_by_name("password_new").clear()
	driver.find_element_by_name("password_new").send_keys(userpwd)
	driver.find_element_by_name("password_confirm").clear()
	driver.find_element_by_name("password_confirm").send_keys(userpwd)
	driver.find_element_by_name("email").clear()
	if email != None:
		driver.find_element_by_name("email").send_keys(email)
		driver.find_element_by_id("submit-crtbutton").click()
	

def is_element_present(driver, how, what):
	from selenium.common.exceptions import NoSuchElementException
	try: driver.find_element(by=how, value=what)
	except NoSuchElementException: return False
    return True

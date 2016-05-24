# -*- coding: utf-8 -*-
from selenium import selenium

def login(driver,user="admin",passwd="pandora",pandora_url="http://127.0.0.1/"):
	driver.get(pandora_url+"/pandora_console/index.php")
	driver.find_element_by_id("nick").clear()
	driver.find_element_by_id("nick").send_keys(user)
	driver.find_element_by_id("pass").clear()
	driver.find_element_by_id("pass").send_keys(passwd)
	driver.find_element_by_id("submit-login_button").click()

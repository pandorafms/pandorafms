# -*- coding: utf-8 -*-
from selenium import selenium
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
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

def logout(driver,url):
	if url[-1] != '/':
		driver.find_element_by_xpath('//div[@id="container"]//a[@href="'+url+'/pandora_console/index.php?bye=bye"]').click()
	else:
		driver.find_element_by_xpath('//div[@id="container"]//a[@href="'+url+'pandora_console/index.php?bye=bye"]').click()

	driver.get(url+"/pandora_console/index.php")
	refresh_N_times_until_find_element(driver,2,"nick")

def create_report(driver,nombre,group_name):
	click_menu_element(driver,"Custom reporting")
	driver.find_element_by_id("submit-create").click()
	driver.find_element_by_id("text-name").clear()
	driver.find_element_by_id("text-name").send_keys(nombre)
	if group_name == "All":
		Select(driver.find_element_by_id("id_group")).select_by_visible_text(group_name)
	else:
		#TODO This will not work when choosing a group within a group within another group
		Select(driver.find_element_by_id("id_group")).select_by_visible_text("    "+group_name)
	driver.find_element_by_id("submit-add").click()

def add_user_profile(driver,user_name,profile,group):
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
	driver.find_element_by_xpath('//*[@name="add"]').click()


def create_user(driver,user_name,userpwd,email=None,profile_list=None):
	u"""
	Profile list es una LISTA de TUPLAS:
		l = [("Chief Operator","All"),("Read Operator","Servers")]
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
	if email != None:
		driver.find_element_by_name("email").clear()
		driver.find_element_by_name("email").send_keys(email)
	driver.find_element_by_id("submit-crtbutton").click()
	
	if profile_list != None:
		for profile_name,group_name in profile_list:
			add_user_profile(driver,user_name,profile_name,group_name)

def search_user(driver,user_name):
	click_menu_element(driver,"Users management")
	driver.find_element_by_css_selector("b").click()
	driver.find_element_by_id('text-filter_search').clear()
	driver.find_element_by_id("text-filter_search").send_keys(user_name)
	driver.find_element_by_id("submit-search").click()

def is_element_present(driver, how, what):
	from selenium.common.exceptions import NoSuchElementException
	try: driver.find_element(by=how, value=what)
	except NoSuchElementException: return False
	return True

def detect_and_pass_pandorin(driver):
	if is_element_present(driver,By.NAME,'clippy_is_annoying'):
		driver.find_element_by_id('checkbox-clippy_is_annoying').click()
		driver.find_element_by_class_name('introjs-skipbutton').click()
		alert = driver.switch_to_alert()
		alert.accept()

def detect_and_pass_initial_wizard(driver):
	#We need to distinguish between the REQUIRED wizard
	if is_element_present(driver,By.ID,'login_id_dialog'):
		driver.find_element_by_id('text-email').clear()
		driver.find_element_by_id('text-email').send_keys("test@pandora.com")
		driver.find_element_by_id('submit-id_dialog_button').click()


def detect_and_pass_newsletter_wizard(driver):
	if is_element_present(driver,By.ID,'login_accept_register'):
		driver.find_element_by_id('submit-finish_dialog_button').click()
		driver.find_element_by_id('submit-yes_registration').click()


def detect_and_pass_all_wizards(driver):
	detect_and_pass_pandorin(driver)
	detect_and_pass_initial_wizard(driver)
	detect_and_pass_newsletter_wizard(driver)


def delete_agent (driver,agent_names_list):
 
	click_menu_element(driver,"Agents operations")
	driver.find_element_by_id("option").click()
	Select(driver.find_element_by_id("option")).select_by_visible_text("Bulk agent delete")

	for agent_name in agent_names_list:
		Select(driver.find_element_by_id("id_agents")).select_by_visible_text(agent_name)

	driver.find_element_by_id("submit-go").click()



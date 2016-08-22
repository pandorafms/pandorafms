# -*- coding: utf-8 -*-
from selenium import selenium
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC

import random, time
import string
import unittest

def is_enterprise(func):
        u"""
        This decorator is intended to be used for Enterprise tests only
        """
        def inner(*args,**kwargs):
                is_enterprise = 0
                try:
                        is_enterprise = args[0].is_enterprise == '1'
                except:
                        pass

                if is_enterprise:
                        return func(*args,**kwargs)
		else:
			raise unittest.SkipTest("Skipping test")
        return inner


def gen_random_string(size,preffix=None):
	random_string =  ''.join(random.SystemRandom().choice(string.ascii_uppercase+string.digits) for _ in range(size))
	if preffix:
		return preffix+random_string
	else:
		return random_string


def login(driver,user="admin",passwd="pandora",pandora_url=None):
	print u"Logging in"
	try:
		print driver.base_url
	except Exception as e:
		print "se casco"
		print e
	print u"yep"
	if pandora_url is None:
		pandora_url = driver.base_url

	print "Pandora url is "+pandora_url

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
	print u"Logging out"
	if url[-1] != '/':
		driver.find_element_by_xpath('//div[@id="container"]//a[@href="'+url+'/pandora_console/index.php?bye=bye"]').click()
	else:
		driver.find_element_by_xpath('//div[@id="container"]//a[@href="'+url+'pandora_console/index.php?bye=bye"]').click()

	driver.get(url+"/pandora_console/index.php")
	refresh_N_times_until_find_element(driver,2,"nick")


#Pass Wizards

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
	driver.implicitly_wait(2) #Optimisation workaround for skipping wizards quickly
	detect_and_pass_pandorin(driver)
	detect_and_pass_initial_wizard(driver)
	detect_and_pass_newsletter_wizard(driver)
	driver.implicitly_wait(30)

def activate_home_screen(driver,mode):
 
	click_menu_element(driver,"Edit my user")
	Select(driver.find_element_by_id("section")).select_by_visible_text(mode)
	driver.find_element_by_id("submit-uptbutton").click()
	
def is_element_present(driver, how, what):
	from selenium.common.exceptions import NoSuchElementException
	try:
		driver.implicitly_wait(5)
		driver.find_element(by=how, value=what)
	except NoSuchElementException:
		driver.implicitly_wait(5)
		return False

	driver.implicitly_wait(30)
	return True


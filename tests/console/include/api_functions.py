
# -*- coding: utf-8 -*-
from common_classes_60 import PandoraWebDriverTestCase
from common_functions_60 import login, click_menu_element, detect_and_pass_all_wizards, logout
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
from selenium.common.exceptions import NoAlertPresentException
import unittest, time, re


def activate_api(driver,api_pwd):

	click_menu_element(driver,"General Setup")
	
	driver.find_element_by_id("textarea_list_ACL_IPs_for_API").clear()
	driver.find_element_by_id("textarea_list_ACL_IPs_for_API").send_keys("*")

        driver.find_element_by_id("password-api_password").clear()
        driver.find_element_by_id("password-api_password").send_keys(api_pwd)

	driver.find_element_by_id("submit-update_button").click()


def create_agent_api(driver,params,user="admin",pwd="pandora",apipwd="1234"):

	lista = driver.current_url.split('/')
	base_url = lista[0]+'//'+lista[2]+'/'

	url = base_url+"pandora_console/include/api.php?op=set&op2=new_agent&other={0}|{1}|{2}|{3}|{4}|{5}|{6}|{7}|{8}|{9}|{10}|hola&other_mode=url_encode_separator_|".format(params[0],params[1],params[2],params[3],params[4],params[5],params[6],params[7],params[8],params[9],params[10],params[11])+"&user="+user+"&pass="+pwd+"&apipass="+apipwd
	
	driver.get(url)

def add_network_module_to_agent_api(driver,params,user="admin",pwd="pandora",apipwd="1234"):

	#params[3] = id_module_type, 6 para Host Alive, 7 para Host Latency

	lista = driver.current_url.split('/')
	base_url = lista[0]+'//'+lista[2]+'/'
	
	url = base_url+"pandora_console/include/api.php?op=set&op2=create_network_module&id={0}&other={1}|{2}|{3}|{4}|{5}|{6}|{7}|{8}|{9}|{10}|{11}|{12}|{13}|{14}|{15}|{16}|{17}|{18}|{19}|{20}|{21}|latency&other_mode=url_encode_separator_|".format(params[0],params[1],params[2],params[3],params[4],params[5],params[6],params[7],params[8],params[9],params[10],params[11],params[12],params[13],params[14],params[15],params[16],params[17],params[18],params[19],params[20],params[21])+"&apipass="+apipwd+"&user="+user+"&pass="+pwd

	driver.get(url)

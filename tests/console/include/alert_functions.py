# -*- coding: utf-8 -*-
from selenium import selenium
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from module_functions import search_module
from common_functions_60 import *

import random, time
import string


def assign_alert_template_to_module (driver,agent_name,module_name,template_name):

        search_module(driver,agent_name,module_name)
        driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Alerts"]').click()
        Select(driver.find_element_by_id("id_agent_module")).select_by_visible_text(module_name)
        Select(driver.find_element_by_id("template")).select_by_visible_text(template_name)
        driver.find_element_by_id("submit-add").click()

def force_alert_of_module(driver,agent_name,module_name,template_name):

        click_menu_element(driver,"Agent detail")
        driver.find_element_by_id("text-search").clear()
        driver.find_element_by_id("text-search").send_keys(agent_name)
        driver.find_element_by_id("submit-srcbutton").click()
        driver.find_element_by_css_selector("b").click()
        driver.find_element_by_xpath('//ul[@class="mn"]/li/a/img[@data-title="Alerts"]').click()
        driver.find_element_by_xpath('//tr[td[3][contains(.,"'+module_name+'")] and td[4][contains(.,"'+template_name+'")]]/td[2]/a').click()

        time.sleep(10)






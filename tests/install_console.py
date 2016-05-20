#!/usr/bin/env python
# Script to install the Pandora FMS Console.
import os
from pyvirtualdisplay import Display
from selenium import webdriver

# Are we running headless?
if ('DISPLAY' not in os.environ):
    display = Display(visible=0, size=(800, 600))
    display.start()

# Go to the installation page.
browser = webdriver.Firefox()
browser.implicitly_wait(5)
browser.get('http://localhost/pandora_console/install.php')
assert("Pandora FMS - Installation Wizard" in browser.title)

# Accept the license agreement.
browser.find_element_by_xpath("//*[@id='step11']/img").click()
browser.find_element_by_xpath("//*[@id='install_box']/form/div/input").click()

# Fill-in the configuration form.
browser.find_element_by_xpath("//*[@id='step3']/img").click()
browser.find_element_by_name("pass").send_keys("pandora")
browser.find_element_by_id("step4").click()

# Complete the installation.
browser.implicitly_wait(300) # The installation is going to take a long time.
browser.find_element_by_xpath("//*[@id='step5']/img").click()
browser.implicitly_wait(5)
assert("Installation complete" in browser.page_source)

# Clean-up
browser.quit()
if ('DISPLAY' not in os.environ):
    display.stop()

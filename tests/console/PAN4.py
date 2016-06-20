
def test_pan4(self):
	driver = self.driver
	login(driver,"admin","pandora",self.base_url)
	detect_and_pass_all_wizards(driver)

	#Creates a user with Chief Operator - Applications profile
	profile_list = []
	profile_list.append(("Chief Operator","Applications"))
	create_user(driver,'PAN_4','PAN_4',email='pan_4@pandorafms.com',profile_list=profile_list)

	#Creates report
	create_report(driver,"PAN_4_Applications","Applications")
	create_report(driver,"PAN_4_Servers","Servers")

	#Logout
	logout(driver,'http://127.0.0.1:85')

	#Login
	login(driver,user='PAN_4',passwd='PAN_4',self.base_url)
	detect_and_pass_all_wizards(driver)

	#Check that the report is visible
	click_menu_element(driver,"Custom reporting")
	driver.find_element_by_id('text-search').clear()
	driver.find_element_by_id('text-search').send_keys("PAN_4_Applications")
	driver.find_element_by_id('submit-search_submit').click()
	self.assertEqual(is_element_present(driver, By.ID, 'report_list-0'),True)


	#Check that the report is not visible
	click_menu_element(driver,"Custom reporting")
	driver.find_element_by_id('text-search').clear()
	driver.find_element_by_id('text-search').send_keys("PAN_4_Servers")
	driver.find_element_by_id('submit-search_submit').click()
	
	self.assertEqual("No data found." in driver.page_source,True)
	
	#Delete reports
	logout(driver,self.base_url)
	login(driver,self.base_url)

	delete_report(driver,"PAN_4_Servers")
	delete_report(driver,"PAN_4_Applications")


if __name__ == "__main__":
        unittest.main()

def create_new_dashboard(driver,name,group):

	click_menu_element(driver,"Main dashboard")
	driver.find_element_by_xpath('//*[@id="menu_tab"]/ul/li[4]/a/img').click()
	driver.find_element_by_xpath('//*[@id="table2-0-1"]/a[2]/img').click()
	driver.find_element_by_id("text-name").send_keys(name)
	Select(driver.find_element_by_id("group")).select_by_visible_text(group)
	driver.find_element_by_id('submit-add-btn').click()

def delete_dashboard(driver,name):

	click_menu_element(driver,"Main dashboard")
	driver.find_element_by_xpath('//*[@id="menu_tab"]/ul/li[4]/a/img').click()
	Select(driver.find_element_by_id("id_dashboard")).select_by_visible_text(name)
	driver.find_element_by_xpath('//*[@id="menu_tab"]/ul/li[4]/a/img').click()
	driver.find_element_by_xpath('//*[@id="table2-0-1"]/a[1]/img').click()


def edit_dashboard(driver,name,new_number_cell=None,new_group=None,new_name=None):

	click_menu_element(driver,"Main dashboard")
	driver.find_element_by_xpath('//*[@id="menu_tab"]/ul/li[4]/a/img').click()
	Select(driver.find_element_by_id("id_dashboard")).select_by_visible_text(name)

	if new_number_cell != None:
		driver.find_element_by_xpath('//*[@id="menu_tab"]/ul/li[4]/a/img').click()
		driver.find_element_by_id("text-number_cells").clear()
		driver.find_element_by_id("text-number_cells").send_keys(new_number_cell)
		driver.find_element_by_id('button-update_cells').click()
		time.sleep(3)

	if new_group != None:
		driver.find_element_by_xpath('//*[@id="menu_tab"]/ul/li[4]/a/img').click()
		Select(driver.find_element_by_id("group_dashboard")).select_by_visible_text(new_group)
		driver.find_element_by_id('button-update_group_dashboard').click()
		time.sleep(3)

	if new_name != None:
		driver.find_element_by_xpath('//*[@id="menu_tab"]/ul/li[4]/a/img').click()
		driver.find_element_by_id("text-name_dashboard").clear()
		driver.find_element_by_id("text-name_dashboard").send_keys(new_name)
		driver.find_element_by_id('button-update_name_dashboard').click()


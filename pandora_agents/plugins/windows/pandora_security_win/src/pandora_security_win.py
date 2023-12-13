import wmi, sys, winreg, os, subprocess, json, re
from datetime import datetime, timedelta


## Define modules
modules=[]

def print_module(module, print_flag=None):
    """Returns module in XML format. Accepts only {dict}.\n
    - Only works with one module at a time: otherwise iteration is needed.
    - Module "value" field accepts str type or [list] for datalists.
    - Use print_flag to show modules' XML in STDOUT.
    """
    data = dict(module)
    module_xml = ("<module>\n"
                  "\t<name><![CDATA[" + str(data["name"]) + "]]></name>\n"
                  "\t<type>" + str(data["type"]) + "</type>\n"
                  )
    
    if type(data["type"]) is not str and "string" not in data["type"]: #### Strip spaces if module not generic_data_string
        data["value"] = data["value"].strip()
    if isinstance(data["value"], list): # Checks if value is a list
        module_xml += "\t<datalist>\n"
        for value in data["value"]:
            if type(value) is dict and "value" in value:
                module_xml += "\t<data>\n"
                module_xml += "\t\t<value><![CDATA[" + str(value["value"]) + "]]></value>\n"
                if "timestamp" in value:
                    module_xml += "\t\t<timestamp><![CDATA[" + str(value["timestamp"]) + "]]></timestamp>\n"
            module_xml += "\t</data>\n"
        module_xml += "\t</datalist>\n"
    else:
        module_xml += "\t<data><![CDATA[" + str(data["value"]) + "]]></data>\n"
    if "desc" in data:
        module_xml += "\t<description><![CDATA[" + str(data["desc"]) + "]]></description>\n"
    if "unit" in data:
        module_xml += "\t<unit><![CDATA[" + str(data["unit"]) + "]]></unit>\n"
    if "interval" in data:
        module_xml += "\t<module_interval><![CDATA[" + str(data["interval"]) + "]]></module_interval>\n"
    if "tags" in data:
        module_xml += "\t<tags>" + str(data["tags"]) + "</tags>\n"
    if "module_group" in data:
        module_xml += "\t<module_group>" + str(data["module_group"]) + "</module_group>\n"
    if "module_parent" in data:
        module_xml += "\t<module_parent>" + str(data["module_parent"]) + "</module_parent>\n"
    if "min_warning" in data:
        module_xml += "\t<min_warning><![CDATA[" + str(data["min_warning"]) + "]]></min_warning>\n"
    if "min_warning_forced" in data:
        module_xml += "\t<min_warning_forced><![CDATA[" + str(data["min_warning_forced"]) + "]]></min_warning_forced>\n"
    if "max_warning" in data:
        module_xml += "\t<max_warning><![CDATA[" + str(data["max_warning"]) + "]]></max_warning>\n"
    if "max_warning_forced" in data:
        module_xml += "\t<max_warning_forced><![CDATA[" + str(data["max_warning_forced"]) + "]]></max_warning_forced>\n"
    if "min_critical" in data:
        module_xml += "\t<min_critical><![CDATA[" + str(data["min_critical"]) + "]]></min_critical>\n"
    if "min_critical_forced" in data:
        module_xml += "\t<min_critical_forced><![CDATA[" + str(data["min_critical_forced"]) + "]]></min_critical_forced>\n"
    if "max_critical" in data:
        module_xml += "\t<max_critical><![CDATA[" + str(data["max_critical"]) + "]]></max_critical>\n"
    if "max_critical_forced" in data:
        module_xml += "\t<max_critical_forced><![CDATA[" + str(data["max_critical_forced"]) + "]]></max_critical_forced>\n"
    if "str_warning" in data:
        module_xml += "\t<str_warning><![CDATA[" + str(data["str_warning"]) + "]]></str_warning>\n"
    if "str_warning_forced" in data:
        module_xml += "\t<str_warning_forced><![CDATA[" + str(data["str_warning_forced"]) + "]]></str_warning_forced>\n"
    if "str_critical" in data:
        module_xml += "\t<str_critical><![CDATA[" + str(data["str_critical"]) + "]]></str_critical>\n"
    if "str_critical_forced" in data:
        module_xml += "\t<str_critical_forced><![CDATA[" + str(data["str_critical_forced"]) + "]]></str_critical_forced>\n"
    if "critical_inverse" in data:
        module_xml += "\t<critical_inverse><![CDATA[" + str(data["critical_inverse"]) + "]]></critical_inverse>\n"
    if "warning_inverse" in data:
        module_xml += "\t<warning_inverse><![CDATA[" + str(data["warning_inverse"]) + "]]></warning_inverse>\n"
    if "max" in data:
        module_xml += "\t<max><![CDATA[" + str(data["max"]) + "]]></max>\n"
    if "min" in data:
        module_xml += "\t<min><![CDATA[" + str(data["min"]) + "]]></min>\n"
    if "post_process" in data:
        module_xml += "\t<post_process><![CDATA[" + str(data["post_process"]) + "]]></post_process>\n"
    if "disabled" in data:
        module_xml += "\t<disabled><![CDATA[" + str(data["disabled"]) + "]]></disabled>\n"
    if "min_ff_event" in data:
        module_xml += "\t<min_ff_event><![CDATA[" + str(data["min_ff_event"]) + "]]></min_ff_event>\n"
    if "status" in data:
        module_xml += "\t<status><![CDATA[" + str(data["status"]) + "]]></status>\n"
    if "timestamp" in data:
        module_xml += "\t<timestamp><![CDATA[" + str(data["timestamp"]) + "]]></timestamp>\n"
    if "custom_id" in data:
        module_xml += "\t<custom_id><![CDATA[" + str(data["custom_id"]) + "]]></custom_id>\n"
    if "critical_instructions" in data:
        module_xml += "\t<critical_instructions><![CDATA[" + str(data["critical_instructions"]) + "]]></critical_instructions>\n"
    if "warning_instructions" in data:
        module_xml += "\t<warning_instructions><![CDATA[" + str(data["warning_instructions"]) + "]]></warning_instructions>\n"
    if "unknown_instructions" in data:
        module_xml += "\t<unknown_instructions><![CDATA[" + str(data["unknown_instructions"]) + "]]></unknown_instructions>\n"
    if "quiet" in data:
        module_xml += "\t<quiet><![CDATA[" + str(data["quiet"]) + "]]></quiet>\n"
    if "module_ff_interval" in data:
        module_xml += "\t<module_ff_interval><![CDATA[" + str(data["module_ff_interval"]) + "]]></module_ff_interval>\n"
    if "crontab" in data:
        module_xml += "\t<crontab><![CDATA[" + str(data["crontab"]) + "]]></crontab>\n"
    if "min_ff_event_normal" in data:
        module_xml += "\t<min_ff_event_normal><![CDATA[" + str(data["min_ff_event_normal"]) + "]]></min_ff_event_normal>\n"
    if "min_ff_event_warning" in data:
        module_xml += "\t<min_ff_event_warning><![CDATA[" + str(data["min_ff_event_warning"]) + "]]></min_ff_event_warning>\n"
    if "min_ff_event_critical" in data:
        module_xml += "\t<min_ff_event_critical><![CDATA[" + str(data["min_ff_event_critical"]) + "]]></min_ff_event_critical>\n"
    if "ff_type" in data:
        module_xml += "\t<ff_type><![CDATA[" + str(data["ff_type"]) + "]]></ff_type>\n"
    if "ff_timeout" in data:
        module_xml += "\t<ff_timeout><![CDATA[" + str(data["ff_timeout"]) + "]]></ff_timeout>\n"
    if "each_ff" in data:
        module_xml += "\t<each_ff><![CDATA[" + str(data["each_ff"]) + "]]></each_ff>\n"
    if "module_parent_unlink" in data:
        module_xml += "\t<module_parent_unlink><![CDATA[" + str(data["parent_unlink"]) + "]]></module_parent_unlink>\n"
    if "global_alerts" in data:
        for alert in data["alert"]:
            module_xml += "\t<alert_template><![CDATA[" + alert + "]]></alert_template>\n"
    module_xml += "</module>\n"

    if print_flag:
        print (module_xml)

    return (module_xml)

def check_antivirus_status():
    try:
        wmi_obj = wmi.WMI(namespace="root/SecurityCenter2")
        antivirus_products = wmi_obj.query("SELECT * FROM AntivirusProduct")

        for product in antivirus_products:
            display_name = product.displayName
            product_state = product.productState
            product_state_hex = hex(product_state)
            last_update = product.timestamp
            atv_status = int(product_state_hex[3:5])
            atv_uptodate = int(product_state_hex[5:7])
            atv_status = 1 if atv_status in [10, 11] else 0
            atv_uptodate = 1 if atv_uptodate in [00,] else 0

            #print(f"{display_name}, product_state: {product_state}, product_state_hex: {product_state_hex}, last_update: {last_update}, status: {atv_status}, uptodate: {atv_uptodate}")
            modules.append({
                "name" : f"{display_name} Antivirus status",
                "type" : "generic_proc",
                "value": atv_status,
                "module_group": "security",
                "desc" : f"{display_name} state: {product_state}, last update: {last_update}",
            })
            modules.append({
                "name" : f"{display_name} Antivirus up to date",
                "type" : "generic_proc",
                "value": atv_uptodate,
                "module_group": "security",
                "desc" : f"{display_name} state: {product_state}, last update: {last_update}",
            })           

    except Exception as e:
        print(f"Error check antivirus: {e}", file=sys.stderr)

def is_lock_screen_enabled():
    try:
        # Open the registry key
        key_path = r"SOFTWARE\Microsoft\Windows\CurrentVersion\Policies\System"
        with winreg.OpenKey(winreg.HKEY_LOCAL_MACHINE, key_path) as key:
            # Query the value of the DisableLockScreen key
            value_name = "DisableLockScreen"
            value, _ = winreg.QueryValueEx(key, value_name)

            # Check if the lock screen is enabled (0 means enabled)
            status = value == 0
            if status == False: return status 
        
    except FileNotFoundError:
        # If the registry key or value is not found, consider it as enabled
        status = True
    except Exception as e:
        print(f"Error check lockscreen: {e}", file=sys.stderr)
        status = False

    try:
         # Define the registry key for the lock screen settings
        reg_key_path = r"SOFTWARE\Policies\Microsoft\Windows\Personalization"
        reg_key = winreg.OpenKey(winreg.HKEY_LOCAL_MACHINE, reg_key_path)

        # Query the "NoLockScreen" DWORD value
        value_name = "NoLockScreen"
        value, _ = winreg.QueryValueEx(reg_key, value_name)

        # Check if the "NoLockScreen" value is 0 (enabled)
        status = value == 0
        if status == False: return status 
    
    except FileNotFoundError:
        # If the registry key or value is not found, consider it as enabled
        status = True
    except Exception as e:
        print(f"Error check lockscreen: {e}", file=sys.stderr)
        status = False
    
    return status

def check_locksreen_enables():
    status = is_lock_screen_enabled()
    value = 1 if status == True else 0
    
    modules.append({
                "name" : "Lockscreen status",
                "type" : "generic_proc",
                "value": value,
                "module_group": "security",
                "desc" : f"Check lockscreen enable",
            })

def convert_to_human_readable_date(timestamp_str):
    try:
        # Parse the timestamp string without the time zone
        timestamp = datetime.strptime(timestamp_str, '%Y%m%d%H%M%S')

        # Convert to a human-readable format
        human_readable_date = timestamp.strftime('%Y-%m-%d %H:%M:%S %z')

        return human_readable_date.strip()
    except Exception as e:
        print(f"Error converting date: {e}", file=sys.stderr)
        return None

def check_time_difference(timestamp, timedays=10):
    try:
        # Convert the timestamp string to a datetime object
        given_timestamp = datetime.strptime(timestamp, '%Y-%m-%d %H:%M:%S')

        # Get the current time
        current_time = datetime.now()

        #Calculate the time difference
        time_difference = current_time - given_timestamp

        # Check if the time difference is greater than one hour
        if time_difference < timedelta(days=timedays):
            return "1"
        else:
            return "0"
    except Exception as e:
        print(f"Error check time difference: {e}", file=sys.stderr)
        return 0



def get_windows_update_info(limit=5):
    try:
        # Connect to the Win32_ReliabilityRecords class in the root/cimv2 namespace
        wmi_conn = wmi.WMI()

        # Query the Win32_ReliabilityRecords class for Windows Update information
        query = "SELECT * FROM Win32_ReliabilityRecords WHERE sourcename = 'Microsoft-Windows-WindowsUpdateClient'"
        result = wmi_conn.query(query)

        # Extract relevant information and format output
        update_info = [
            {
                "date": convert_to_human_readable_date(record.timegenerated.split('.')[0]),
                "update": record.message
            }
            for record in result[:limit]
        ]
        
        last_update_date=update_info[0]['date']
        value=check_time_difference(last_update_date)
        
        modules.append({
                "name" : "Microsoft Update system status",
                "type" : "generic_proc",
                "value": value,
                "module_group": "security",
                "desc" : f"Check if system was updated in the last 10 days. last update: {last_update_date}",
            })
        return True
    except Exception as e:
        print(f"Error windows update check: {e}", file=sys.stderr)
        return False

def is_firewall_enabled():
    try:
        # Run PowerShell command to check if the Windows Firewall is enabled
        result = subprocess.run(
            ['powershell', 'Get-NetFirewallProfile |Select-Object profile, enabled | ConvertTo-Json'],
            capture_output=True,
            text=True
        )

        result_json= json.loads(result.stdout)
        for profile in result_json:
            modules.append({
                    "name" : f"Firewall profile: {profile['Profile']} status",
                    "type" : "generic_proc",
                    "value": profile["Enabled"],
                    "module_group": "security",
                    "desc" : f"Check if firewall profile {profile['Profile']} is enabled",
                })
        return True
    except Exception as e:
        print(f"Error firewall check: {e}", file=sys.stderr)
        return False

def check_password_enforcement():
    enforce_pass = 1
    counter = 0
    try:
        # Connect to the WMI service
        wmi_service = wmi.WMI()

        # Query for user accounts
        users = wmi_service.Win32_UserAccount()

        # Check if each user enforces password
        for user in users:
            # username = user.Name
            # password_required = user.PasswordRequired
            if user.PasswordRequired == False : 
                enforce_pass = 0
                counter += 1 
            #print(f"User: {username}, Password Required: {password_required}")

        modules.append({
                "name" : "All users enforced password",
                "type" : "generic_proc",
                "value": enforce_pass,
                "module_group": "security",
                "desc" : f"Check if all users has enforced password, not secure users = {counter}",
            })
    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        print("Failed to check password enforcement for users.",  file=sys.stderr)


def check_login_audit_policy():
    try:
        # Run the auditpol command to check the audit policy for Logon/Logoff
        cmd_command = "auditpol /get /subcategory:Logon"
        result = subprocess.run(cmd_command, shell=True, capture_output=True, text=True, check=True)
        last_line = result.stdout.strip().split('\n')[-1]
        cleaned_line = re.sub(' +', ' ', last_line)
        
        # Interpret the result
        if "Success and Failure" in result.stdout:
            result = 1
        elif "Aciertos y errores" in result.stdout:
            result = 1
        elif "No Auditing" in result.stdout:
            result = 0
        elif "Sin auditoría" in result.stdout:
            result = 0
        else:
            print("Unable to determine audit policy for Logon/Logoff events.", file=sys.stderr)
            result = 0
        modules.append({
                "name" : "Check logon event audited",
                "type" : "generic_proc",
                "value": result,
                "module_group": "security",
                "desc" : f"Check if the logon events audit log is enables, status:{cleaned_line}",
            })

    except subprocess.CalledProcessError as e:
        print(f"Error: {e}")
        print("Failed to check audit policy using auditpol command.", file=sys.stderr)
        return


if __name__ == "__main__":
    check_antivirus_status()
    check_locksreen_enables()
    get_windows_update_info()
    is_firewall_enabled()
    check_password_enforcement()
    check_login_audit_policy()

    for module in modules:
        print_module(module, True)


# Windows Defender status values:
# 0: No action needed
# 266240: Antivirus is up to date
# 266256: Antivirus is out of date
# 266304: Antivirus is not monitoring
# 393216 (0x60000): No action needed.
# 393232 (0x60010): Antivirus is up to date.
# 393240 (0x60018): Antivirus is out of date.
# 393216 (0x60030): Antivirus is not monitoring.
# 397312 (0x61000): Antivirus is disabled.

# AVG Internet Security 2012 (from antivirusproduct WMI)
# 262144 (040000) = disabled and up to date
# 266240 (041000) = enabled and up to date
# AVG Internet Security 2012 (from firewallproduct WMI)
# 266256 (041010) = firewall enabled - (last two blocks not relevant it seems for firewall)
# 262160 (040010) = firewall disabled - (last two blocks not relevant it seems for firewall)

# Windows Defender
# 393472 (060100) = disabled and up to date
# 397584 (061110) = enabled and out of date
# 397568 (061100) = enabled and up to date
# Microsoft Security Essentials
# 397312 (061000) = enabled and up to date
# 393216 (060000) = disabled and up to date

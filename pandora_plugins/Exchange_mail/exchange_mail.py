#!/usr/bin/env python
# -*- coding: utf-8 -*-

"""
EXCHANGE MAIL PLUGIN

Author: Alejandro Sanchez Carrion
Copyright: Copyright 2024, PandoraFMS
Maintainer: Operations Department
Status: Production
Version: 1.0
"""

from exchangelib import Credentials,Configuration, Account, DELEGATE, OAUTH2, IMPERSONATION,Message, Mailbox
from exchangelib import OAuth2Credentials
from exchangelib.version import Version, EXCHANGE_O365
from exchangelib.protocol import BaseProtocol, NoVerifyHTTPAdapter

BaseProtocol.HTTP_ADAPTER_CLS = NoVerifyHTTPAdapter

import urllib3
urllib3.disable_warnings()

import pandoraPlugintools as ppt

import argparse,sys,re,json,os,traceback
from datetime import datetime,timedelta,timezone



__author__ = "Alejandro Sánchez Carrion"
__copyright__ = "Copyright 2022, PandoraFMS"
__maintainer__ = "Operations department"
__status__ = "Production"
__version__= '1.0'

info = f"""
Pandora FMS Exchange Mail
Version = 1.0
Description = This plugin can search for matches in your mail and find the number of matches, as well as list them.

Manual execution

./exchange_mail \
--auth <oauth> \
--server <server> \
--smtp_address <smtp_address> \
--client_id <client_id> \
--tenant_id <tenant_id> \
--secret <secret> \
[--user <user>] \
[--password <password>] \
[--subject <subject>] \
[--sender <sender>] \
[--date_start <date_start>] \
[--date_end <date_end>] \
[--mail_list <mail_list>] \
[--module_prefix <module_prefix>] \
[--agent_prefix <agent_prefix>] \
[--group <group>] \
[--interval <interval>] \
[--temporal <temporal>] \
[--data_dir <data_dir>] \
[--transfer_mode <transfer_mode>] \
[--tentacle_client <tentacle_client>] \
[--tentacle_opts <tentacle_opts>] \
[--tentacle_port <tentacle_port>] \
[--tentacle_address <tentacle_address>] \
[--log_file <log_file>]


there are three parameters with which to filter the mails

subject
email
date

You can use only one and filter from that or use the following combinations:

subject 
subject + sender
subject + sender + date
"""

parser = argparse.ArgumentParser(description= info, formatter_class=argparse.RawTextHelpFormatter)

parser.add_argument('--server'                , help="Server name"                                    , default = "outlook.office365.com"                     , type=str)
parser.add_argument('--smtp_address'          , help="SMTP address"                                   , required = True                                       , type=str)

parser.add_argument('--user'                  , help="User name"                                      , default=""                                            , type=str)
parser.add_argument('--password'              , help="Password"                                       , default=""                                            , type=str)

parser.add_argument('--client_id'             , help="Client_id"                                      , default=""                                            , type=str)         
parser.add_argument('--tenant_id'             , help="Tenant_id"                                      , default=""                                            , type=str)        
parser.add_argument('--secret'                , help="Secret"                                         , default=""                                            , type=str)                                           


parser.add_argument('--subject'               , help="Select match in subjects"                       , default=None                                            , type=str)
parser.add_argument('--sender'                , help="Select coincidences from email"                 , default=None                                            , type=str)
parser.add_argument('--date_start'            , help="Search for matches from a certain date,Each date must be separated by a hyphen and in quotation marks, with the following format: 'year-month-day-hour-minute'. example: '2021-1-12-0-0'", default=None, type=str)
parser.add_argument('--date_end'              , help="Search for matches from a certain date,Each date must be separated by a hyphen and in quotation marks, with the following format: 'year-month-day-hour-minute'. example: '2021-6-12-0-0'", default=None, type=str)
parser.add_argument('--mail_list'             , help='List mail coincidences'                              , default=0                                          ,type=int    )

parser.add_argument('--module_prefix'         , help='Prefix for the modules. Example : meraki.'           , default=""                                         , type=str   )
parser.add_argument('--agent_prefix'          , help='Prefix for the agents. Example : meraki.'            , default=""                                         , type=str   )

parser.add_argument('--group'                 , help='PandoraFMS destination group (default exchange)'     , default=''                                         , type=str   )
parser.add_argument('--interval'              , help='Agent monitoring interval'                           , default=300                                        , type=int   )
parser.add_argument('--temporal'              , help='PandoraFMS temporal dir'                             , default='/tmp'                                     , type=str   )
parser.add_argument('--data_dir'              , help='PandoraFMS data dir '                                , default='/var/spool/pandora/data_in/'              , type=str   )
parser.add_argument('--transfer_mode'         , help='Data transfer mode, local or tentacle'               , default="tentacle"                                 , type=str   )
parser.add_argument('--tentacle_client'       , help='Tentacle client path, by default tentacle_client'    , default="tentacle_client"                          , type=str   )
parser.add_argument('--tentacle_opts'         , help='Additional tentacle options'                         , default=""                                         , type=str   )
parser.add_argument('--tentacle_port'         , help='Tentacle port'                                       , default=41121                                      , type=int   )
parser.add_argument('--tentacle_address'      , help='Tentacle adress'                                     , default="127.0.0.1"                                , type=str   )

parser.add_argument('--log_file'              , help='Log file path'                                       , default='/tmp/exchangemail_logfile.txt'            , type=str   )

parser.add_argument('--auth', choices=['basic', 'oauth'], help='Auth type', required=True)

args = parser.parse_args()

###############
## VARIABLES ##
###############

server            = args.server
smtp_address      = args.smtp_address

user              = args.user
password          = args.password

client_id         = args.client_id
tenant_id         = args.tenant_id
secret            = args.secret

subject           = args.subject
sender            = args.sender
date_start        = args.date_start
date_end          = args.date_end
mail_list         = args.mail_list

module_prefix     = args.module_prefix
agent_prefix      = args.agent_prefix

temporal          = args.temporal
group             = args.group
interval          = args.interval
data_dir          = args.data_dir
transfer_mode     = args.transfer_mode

tentacle_address  = args.tentacle_address
tentacle_port     = args.tentacle_port
tentacle_client   = args.tentacle_client
tentacle_opts     = args.tentacle_opts

log_file          = args.log_file

###############
## FUNCTIONS ##
###############

def Oauth_session(credentials):
    """
    Creates an OAuth session with an Exchange server using the provided credentials.

    Args:
        credentials (Credentials): Credentials object containing information for OAuth authentication.

    Returns:
        Account: An Account object representing the OAuth session with the Exchange server.
    """
    try:
        config = Configuration(server=server,credentials=credentials, auth_type=OAUTH2,version=Version(build=EXCHANGE_O365),)
        account = Account(
            smtp_address, 
            credentials=credentials, 
            config=config, 
            autodiscover=False, 
            access_type=IMPERSONATION)
        return account
    except Exception as e:
        print(0)
        write_to_log(f"{type(e).__name__}: {e}", log_file)
        sys.exit()

def basic_session(credentials):
    """
    Creates a basic session with an Exchange server using the provided credentials.

    Args:
        credentials (Credentials): Credentials object containing information for authentication.

    Returns:
        Account: An Account object representing the basic session with the Exchange server.
    """
    try:
        config = Configuration(server=server, credentials=credentials)

        account = Account(
            primary_smtp_address=args.smtp_address,
            autodiscover=False, 
            config=config,
            access_type=DELEGATE
        )
        return account
    except Exception as e:
        print(0)
        write_to_log(f"{type(e).__name__}: {e}", log_file)
        sys.exit()

def create_module(name, module_type, description, value, unit=""):
    """
    Creates a generic module based on a template.

    Args:
        module_prefix (str): The prefix for the module name.
        name_suffix (str): The suffix to be appended to the module name.
        module_type (str): The type of the module.
        description (str): The description of the module.
        value: The value of the module.
        unit (str, optional): The unit of measurement for the module. Defaults to "".

    Returns:
        dict: A dictionary representing the generic module.
    """
    return {
        "name": f'{module_prefix}{name}',
        "type": module_type,
        "desc": description,
        "value": value,
        "unit": unit
    }

def create_agent(count,list_mail = None):

    """
    Creates an agent with specified parameters and transfers it to a target address.

    Args:
        count (int): Number of mails matching the filter used in the run.
        list_mail (str, optional): List of mails matching the filter used in the run. Default is None.

    Returns:
        None
    """

    modules = []

    modules.append(create_module(f"{module_prefix}.Coincidences_count", "generic_data", "Number of mails matching the filter used in the run", count))

    if list_mail is not None :

        modules.append(create_module(f"{module_prefix}.Coincidences_list", "generic_data_string", "List of mails matching the filter used in the run", list_mail))

    agent = {
                "agent_name"        : ppt.generate_md5(agent_prefix + smtp_address),
                "agent_alias"       : agent_prefix + smtp_address,
                "parent_agent_name" : "",
                "description"       : "",
                "version"           : "",
                "os_name"           : "",
                "os_version"        : "",
                "timestamp"         : now(),
                "address"           : server,
                "group"             : group,
                "interval"          : interval,
                "agent_mode"        : "1"
            }

    xml_content = ppt.print_agent(agent, modules)
    xml_file = ppt.write_xml(xml_content, agent["agent_name"])
    ppt.transfer_xml(
        xml_file,
        transfer_mode=transfer_mode,
        tentacle_ip=tentacle_address,
        tentacle_port=tentacle_port
    ) 
    write_to_log("Agent: " + agent_prefix + smtp_address + " getting mail data.", log_file)

def parse_result(list_email,sep="")-> list: 

    """
    Parses a list of email elements and converts them into a list of dictionaries.

    Args:
        list_email (list): List of email elements to be parsed.
        sep (str): Separator to join elements into a string. Default is an empty string.

    Returns:
        list: A list of dictionaries, where each dictionary has a single key "value" containing the joined string.
    """
        
    result=[]

    for line in list_email:
        str_line=sep.join(str(elem) for elem in line)
        str_dict={"value":str_line}
        result.append(str_dict)

    return result

def now(
        utimestamp: bool = False
    ):
    """
    Get the current time in the specified format or as a Unix timestamp.

    Args:
        utimestamp (bool): Set to True to get the Unix timestamp (epoch time).
        print_flag (bool): Set to True to print the time to standard output.

    Returns:
        str: The current time in the desired format or as a Unix timestamp.
    """

    today = datetime.today()
    
    if utimestamp:
        time = datetime.timestamp(today)
    else:
        time = today.strftime('%Y/%m/%d %H:%M:%S')

    return time

def write_to_log(variable_content, log_file_path):
    """
    Writes the content of a variable to a log file with timestamp.

    Args:
        variable_content: Content of the variable to be logged.
        log_file_path (str): Path to the log file.
    """
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    log_entry = f"{timestamp} - {variable_content}\n"

    try:
        with open(log_file_path, 'a') as log_file:
            log_file.write(log_entry)
    except IOError as e:
        print(f"Error writing to log file: {e}")

if args.auth == 'basic':
    credentials = Credentials(username=user, password=password)
    account = basic_session(credentials)
elif args.auth == 'oauth':
    credentials = OAuth2Credentials(client_id=client_id, client_secret=secret, tenant_id=tenant_id)
    account = Oauth_session(credentials)
else:
    print(0)
    write_to_log(f"{type(e).__name__}: {e}", log_file)
    sys.exit()

try:
    ## Only one parameter
    if subject and sender==None and date_start==None and date_end == None:
        filtered_items = account.inbox.filter(subject__contains=args.subject)
    if subject==None and sender and date_start==None and date_end == None:
        filtered_items = account.inbox.filter(sender__icontains=sender)
    if subject==None and sender==None and date_start and date_end :

        date_start=date_start.split("-")
        date_end=date_end.split("-")
        filtered_items = account.inbox.filter(datetime_received__range=(datetime(int(date_start[0].strip()), int(date_start[1].strip()), int(date_start[2].strip()), int(date_start[3].strip()), int(date_start[4].strip())).replace(tzinfo=timezone.utc),datetime(int(date_end[0].strip()), int(date_end[1].strip()), int(date_end[2].strip()), int(date_end[3].strip()), int(date_end[4].strip())).replace(tzinfo=timezone.utc)))

    ## Subject + sender
    if subject and sender and date_start==None and date_end==None :
        filtered_items = account.inbox.filter(sender__icontains=sender,subject__contains=subject)

    ## All parameters
    if subject and sender and date_start and date_end :
        date_start=date_start.split("-")
        date_end=date_end.split("-")
        filtered_items = account.inbox.filter(datetime_received__range=(datetime(int(date_start[0].strip()), int(date_start[1].strip()), int(date_start[2].strip()), int(date_start[3].strip()), int(date_start[4].strip())).replace(tzinfo=timezone.utc),datetime(int(date_end[0].strip()), int(date_end[1].strip()), int(date_end[2].strip()), int(date_end[3].strip()), int(date_end[4].strip())).replace(tzinfo=timezone.utc)),sender__icontains=args.sender,subject__contains=args.subject)

    # List Number email coincidences
    list_mail=[]
    # Count number messages coincidences
    count=0

    for item in filtered_items:

        count=count+1

        if mail_list != 0:
            list_mail.append("("+str(item.datetime_received) + ") - "+str(item.subject)+" - "+str(item.sender))

        #print(item.subject, item.sender, item.datetime_received)

    if mail_list!= 0:    
        list_mail = parse_result(list_mail)
        create_agent(count,list_mail)
    else:
        create_agent(count)
except Exception as e:
    print(0)
    write_to_log(f"{type(e).__name__}: {e}", log_file)
    sys.exit()

print(1)

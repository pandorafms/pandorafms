#!/usr/bin/env python
# -*- coding: utf-8 -*-

import requests, argparse, sys, os, signal
from slack_sdk import WebClient
from slack_sdk.errors import SlackApiError
from datetime import datetime
from re import search
from base64 import b64decode



### Variables and arg parser ###
parser = argparse.ArgumentParser(description='Slack BOT APP conector')
parser.add_argument('-d', '--data', help='Data in coma separate keypairs. Ex: test=5,house=2', required=True)
parser.add_argument('-t', '--token', help='BOT Token', required=True)
parser.add_argument('-c', '--channel', help='Slack channel id/name', required=True)
parser.add_argument('-e', '--emoji', help='Slack emoji for tittle, default: :red_circle:', default=':red_circle:')
parser.add_argument('-T', '--tittle', help='Alert tittle, default: PandoraFMS alert', default='PandoraFMS alert')
parser.add_argument('-D', '--desc', help='Slack description message', default='')
parser.add_argument('-F','--footer', help='Custom footer, default: PandoraFMS', default='PandoraFMS')
parser.add_argument('--api_conf', help='Api configuration parameters in coma separate keypairs. EX "user=admin,pass=pandora,api_pass=1234,api_url=http://test.artica.es/pandora_console/include/api.php"')
parser.add_argument('--module_graph', help='Uses pandora API to generate a module graph and attach it to the alert needs module_id and interval parameters in coma separate keypairs. EX "module_id=55,interval=3600"')
parser.add_argument('--tmp_dir', help='Temporary path to store graph images', default='/tmp')

args = parser.parse_args()
filename = None

# Define a function to handle the SIGINT signal
def sigint_handler(signal, frame):
    print ('\nInterrupted by user')
    sys.exit(0)
signal.signal(signal.SIGINT, sigint_handler)

# Define a function to handle the SIGTERM signal
def sigterm_handler(signum, frame):
    print("Received SIGTERM signal.")
    sys.exit(0)
signal.signal(signal.SIGTERM, sigterm_handler)

#Functions

def parse_dic(cValues):
    """convert coma separate keypairs into a dic. EX "test=5,house=8,market=2" wil return "{'test': '5', 'casa': '8', 'mercado': '2'}" """
    data={}
    try :
        for kv in cValues.split(","):
            k,v = kv.strip().split("=")
            data[k.strip()]=v.strip()
    except Exception as e :
        print(f"Warning, error parsing keypairs values: {e}")
    return data

def compose_message (values, tittle, emoji, message, footer):
    """Format Text"""
    values = parse_dic(values)
    m = f"{emoji} *{tittle}*\n_{message}_\n\n"
    
    for k, v in values.items() :
        m += f"*{k}* : {v}\n"
    return m

def parse_api_conf(cConf):
    """Check api configuration parameters """
    if args.api_conf :
    # Parse Api config
        print ("Api config enable")
        apid = parse_dic(cConf)
        
        if apid.get("user") is None:
            print ("Warning. no user defined in api_conf keypairs, skipping graph generation.")
            return None
        
        if apid.get("pass") is None:
            print ("Warning. no password defined in api_conf keypairs, skipping graph generation.")
            return None

        if apid.get("api_pass") is None:
            print ("Warning. no api pass defined in api_conf keypairs, skipping graph generation.")
            return None

        if apid.get("api_url") is None:
            apid['api_url'] = "http://127.0.0.1/pandora_console/include/api.php" 
            #print(f"api_url: {apid['api_url']}")

        return apid
    else:
        return None

def parse_graph_conf(cGraph):
    """Check module graph parameters """
    if cGraph :
    # Parse Api config
        graphd = parse_dic(cGraph)
        if graphd.get("module_id") is None:
            print ("Warning. no module_id defined in module_graph keypairs, skipping graph generation.")
            return
        
        if graphd.get("interval") is None:
            graphd["interval"] = 3600
        
        return graphd
    else:
        print("Warning. no module_graph keypairs defined, skipping graph generation")
        return None

def get_graph_by_moduleid (baseUrl,pUser, pPass, apiPass, moduleId, graphInterval, sep="url_encode_separator_%7C") : 
    """Call Pandorafms api to get graph"""
    try:
        url = f"{baseUrl}?op=get&op2=module_graph&id={moduleId}&other={graphInterval}%7C1&other_mode={sep}&apipass={apiPass}&api=1&user={pUser}&pass={pPass}"
        graph = requests.get(url)
        if graph.status_code != 200:
            print (f"Error requested api url, status code: {graph.status_code}. skipping graph generation")
            return None
        if graph.text == "auth error":
            print (f"Error requested Pandora api url, status code: {graph.text}. skipping graph generation")
            return None
        if graph.text == "Id does not exist in database.":
            print (f"Error requested Pandora api url, status code: {graph.text}. skipping graph generation")
            return None
        if graph.text == "The user has not enough permissions for perform this action.":
            print (f"Error requested Pandora api url, status code: {graph.text} Skiping graph generation")
            return None
        
    except:
        print("Error requested api url. skipping graph generation")
        return None
    return graph

def send_message(message, channel, client, feddback=None):
    """Send text message as slack bot"""
    try:
        response = client.chat_postMessage(channel=channel, text=message)
        assert response["message"]["text"] == message
        if feddback is not None: print(feddback)
    except SlackApiError as e:
        # You will get a SlackApiError if "ok" is False
        assert e.response["ok"] is False
        assert e.response["error"]  # str like 'invalid_auth', 'channel_not_found'
        print(f"Got an Slack auth error: {e.response['error']}")
        sys.exit()

def send_image(imagepath, channel, client) : 
    """Send file as slack bot"""
    try:
        response = client.files_upload(channels=channel, file=imagepath)
        assert response["file"]  # the uploaded file
    except SlackApiError as e:
        # You will get a SlackApiError if "ok" is False
        assert e.response["ok"] is False
        assert e.response["error"]  # str like 'invalid_auth', 'channel_not_found'
        print(f"File Got an error: {e.response['error']}")

# Main
# Intance the client object
client = WebClient(token=args.token)
# Compose message
messageString = compose_message(args.data, args.tittle, args.emoji, args.desc, args.footer)

# Parse api config
if args.api_conf : 
    api = parse_api_conf(args.api_conf)
    # Parse graph config
    if api is not None:
        graph_cfg = parse_graph_conf(args.module_graph) 
        
        ## Generate graph
        if graph_cfg is not None :
            graph = get_graph_by_moduleid (api["api_url"],api["user"], api["pass"], api["api_pass"], graph_cfg["module_id"], graph_cfg["interval"])
            
            if graph is not None:
                try:
                    filename = f"{args.tmp_dir}/graph_{graph_cfg['module_id']}.{datetime.now().strftime('%s')}.png"
                    with open(filename, "wb") as f:
                        f.write(b64decode(graph.text))
                        f.close
                    print (f"Graph generated on temporary file {filename}")
                except Exception as e :
                    print(f"Error, cant generate graph file: {e}")
                    filename = None
            else: filename = None
                
# Send message
send_message(messageString, args.channel, client, "> Message sent successfully")            
if filename is not None:
    if os.path.isfile(filename): send_image(filename, args.channel, client)
if args.footer: send_message(args.footer, args.channel, client)

try:
    os.remove(filename)
except:
    sys.exit()
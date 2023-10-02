#!/usr/bin/env python
# -*- coding: utf-8 -*-

__author__ = "PandoraFMS Team"
__copyright__ = "Copyright 2023, PandoraFMS"
#__credits__ = ["Rob Knight", "Peter Maxwell", "Gavin Huttley", "Matthew Wakefield"]
__maintainer__ = "Projects/QA department"
__status__ = "Test"
__version__ = "1"

import requests, json, sys, argparse, signal, re,datetime

current_date=datetime.datetime.now()
info= f"""
PandoraFMS ilert integration. 
Version: {__version__}
"""

parser = argparse.ArgumentParser(description= info, formatter_class=argparse.RawTextHelpFormatter)
parser.add_argument('-a', '--api_key', help='Api key from ilert', required=True)
parser.add_argument('-t', '--event_type', help='Type of the created event. Can be "alert" or "resolved".', type=str, required=True)
parser.add_argument('-k', '--event_key', help='Title of the event, used as key', type=str, required=True)
parser.add_argument('-T', '--title', help='Title of the event.', type=str, required=True)
parser.add_argument('-d', '--description', help='Description of the event', type=str, default='')
parser.add_argument('-A', '--agent_name', help='pandorafms agent name', type=str, default='')
parser.add_argument('-p', '--priority', help='priority', type=str, default='')
parser.add_argument('-m', '--module_name', help='priority', type=str, default='')
parser.add_argument('-D', '--module_data', help='priority', type=str, default='')
parser.add_argument('-C', '--created_date', help='event date', type=str, default=current_date)

args = parser.parse_args()

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

# Functions

def post_url(url, data=None, headers={"Accept": "application/json"},):
    try :
        data = requests.post(url, data=data, headers=headers)
        result_data = data.status_code
    except Exception as e :
        print (f"[red]Error[/red] posting data from {url} {e}", file = sys.stderr )
        sys.exit()
    return result_data

if __name__ == "__main__":
    # Prepare data
    url = f"https://api.ilert.com/api/v1/events/pandorafms/{args.api_key}"
    
    payload = {
        "eventType": args.event_type,
        "title": args.title,
        "description": args.description,
        "incidentKey": args.event_key,
        "details": {
            "agentName": args.agent_name,
            "createdAt": str(args.created_date),
            "priority": args.priority,
            "moduleName": args.module_name,
            "moduleData": args.module_data
        }
    }

    response=post_url(url, data=json.dumps(payload))

    if response == 202: print ("Alert has been submitted!")
    print (f"http_code: {response}", file=sys.stderr)


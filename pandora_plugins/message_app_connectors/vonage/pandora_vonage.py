#!/usr/bin/env python
# -*- coding: utf-8 -*-

__author__ = "PandoraFMS Team"
__copyright__ = "Copyright 2023, PandoraFMS"
#__credits__ = ["Rob Knight", "Peter Maxwell", "Gavin Huttley", "Matthew Wakefield"]
__maintainer__ = "Projects/QA department"
__status__ = "Test"
__version__ = "1"

import vonage, json, sys, argparse, signal, re,datetime

current_date=datetime.datetime.now()
info= f"""
PandoraFMS vonage integration. 
Version: {__version__}
"""

parser = argparse.ArgumentParser(description= info, formatter_class=argparse.RawTextHelpFormatter)
parser.add_argument('-a', '--api_key', help='Client key from vonage', required=True)
parser.add_argument('-s', '--secret', help='Secret key from vonage".', type=str, required=True)
parser.add_argument('-m', '--message', help='Title of the event, used as key', type=str, required=True)
parser.add_argument('-n', '--phone_number', help='Phone number to send sms with country code. Ex: "+34555444111,+34777444222" could be a coma separated list of numbers', type=str, required=True)
parser.add_argument('-f', '--from_alias', help='From number/string data', type=str, default="PandoraFMS")
parser.add_argument('-v', '--verbose', help='Debug information', action='store_true')


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

def convet_list_comma (data:str):
    if "," in data:
        result = data.split(",")
    else:
        result = []
        result.append(data)
    return result


if __name__ == "__main__":
    # Prepare data
    client = vonage.Client(key=args.api_key, secret=args.secret)

    try:
        numbers=convet_list_comma(args.phone_number)
    except Exception as e:
        print (f"Error: {e}")
        sys.exit()

    for number in numbers :
        responseData = client.sms.send_message(
            {
                "from": args.from_alias,
                "to": number,
                "text": args.message,
            }
        )

        if responseData["messages"][0]["status"] == "0":
            print("Message sent successfully.")
        else:
            print(f"Message failed with error: {responseData['messages'][0]['error-text']}")

        #debug
        if args.verbose == True: print (f"Debug: {responseData}")
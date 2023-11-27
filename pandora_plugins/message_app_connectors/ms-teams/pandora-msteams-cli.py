#!/usr/bin/env python
# -*- coding: utf-8 -*-
import argparse, pymsteams, signal

parser = argparse.ArgumentParser(description='MS Teams connector')
parser.add_argument('-d', '--data', help='Data in coma separate keypairs. Ex: test=5,house=2', required=True)
parser.add_argument('-u', '--url', help='Teams webhook URL', required=True)
parser.add_argument('-t', '--alert_tittle', help='Alert tittle', default='PandoraFMS alert fired')
parser.add_argument('-D', '--alert_desc', help='Alert description', default='Alert Fired')
parser.add_argument('-m', '--message', help='Alert message', default='')
parser.add_argument('-T','--tittle_color', help='Alert tittle descripcion in HEX EX: 53e514', default="ff0000")
parser.add_argument('--sub_desc', help='Alert sub description', default='Alert Fired')
parser.add_argument('--thumb', help='Custom thumbnail url', default="https://pandorafms.com/images/alerta_roja.png")
parser.add_argument('--button', help='Pandora button Url', default='https://pandorafms.com')
parser.add_argument('--button_desc', help='Pandora button description', default='Open web console')

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

### Functions:
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

def add_embed_itmes(data):
    """iterate dictionary and set webhook fields, one for eacj keypair"""
    for k, v in data.items() :
        myMessageSection.addFact(f"{k}:", v)

##Main

# You must create the connectorcard object with the Microsoft Webhook URL
myTeamsMessage = pymsteams.connectorcard(args.url)

# Set Summary
myTeamsMessage.summary('Pandora FMS')

# Set Alert tittle
myTeamsMessage.title(args.alert_tittle)

# Set link buttom
myTeamsMessage.addLinkButton(args.button_desc, args.button)

# Set message color
myTeamsMessage.color(args.tittle_color)

# create the section
myMessageSection = pymsteams.cardsection()

# Section Title
myMessageSection.title(args.message)

# Activity Elements
myMessageSection.activityTitle(args.alert_desc)
myMessageSection.activitySubtitle(args.sub_desc)
myMessageSection.activityImage(args.thumb)

# Facts are key value pairs displayed in a list.
data = parse_dic(args.data)
add_embed_itmes(data)

# Section Text
# myMessageSection.text("This is my section text")

# Section Images
# myMessageSection.addImage("http://i.imgur.com/c4jt321l.png", ititle="This Is Fine")

# Add your section to the connector card object before sending
myTeamsMessage.addSection(myMessageSection)

# Then send the card
try: 
    myTeamsMessage.send()
except Exception as e :
        exit(f"Error sending to message: {e}")

print (f"Mesage sent succefuly: {myTeamsMessage.last_http_status}")
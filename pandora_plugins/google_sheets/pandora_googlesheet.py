#!/usr/bin/env python
# -*- coding: utf-8 -*-

import gspread
import argparse,json,sys
from oauth2client.service_account import ServiceAccountCredentials
from pprint import pprint
from os import remove
import re

import base64

__author__ = "Alejandro Sánchez Carrion"
__copyright__ = "Copyright 2022, PandoraFMS"
__maintainer__ = "Operations department"
__status__ = "Production"
__version__= '1.0'

info = f"""
Pandora FMS Google Sheets
Version = {__version__}

Manual execution

./pandora_googlesheets --creds_json/creds_base64 <file credentials> --name <name document> --sheet <name-sheet> --cell <Number cell> --row <number-row> --column <number-column>

"""

parser = argparse.ArgumentParser(description= info, formatter_class=argparse.RawTextHelpFormatter)
parser.add_argument('--creds_json', help='To authenticate with a json file.')
parser.add_argument('--creds_base64', help='To authenticate with a file that includes the credentials for base64 authentication.')
parser.add_argument('--name', help='Name of the google sheets document.')
parser.add_argument('--cell', help='To collect the value of a cell.')
parser.add_argument('--row', help='To collect the value of a row.',type=int)
parser.add_argument('--column', help='To collect the value of a column.',type=int)
parser.add_argument('--sheet', help='To indicate the name of the document sheet, put it in quotation marks and count spaces and capital letters.',type=str)
parser.add_argument('--onlydigits', help='To parse the value of the cell if its not a digit',default=0)

args = parser.parse_args()

scope = ["https://spreadsheets.google.com/feeds",'https://www.googleapis.com/auth/spreadsheets',"https://www.googleapis.com/auth/drive.file","https://www.googleapis.com/auth/drive"]


def convert_to_number(s):
    cleaned_value = re.sub("[^0-9]", "", s)
    return int(cleaned_value) if cleaned_value else 0

## authenticate with file json input
if args.creds_json is not None and args.creds_base64 == None:
    creds = ServiceAccountCredentials.from_json_keyfile_name(args.creds_json, scope)
## authenticate with base64 input
elif args.creds_base64 is not None and args.creds_json== None:
    ## base64 to json
    text=base64.b64decode(args.creds_base64).decode('utf-8')
    with open("cred.json", "w") as outfile:
        outfile.write(text)
    creds = ServiceAccountCredentials.from_json_keyfile_name("cred.json", scope)
    remove("cred.json")
else:
    print("You need to use the --creds_json or creds_base 64 parameter to authenticate. You can only select one.")
    sys.exit()

try:
    client = gspread.authorize(creds)
except Exception as e:
    print("Error authenticating with credentials:", e)
    sys.exit()

try:
    sheet = client.open(args.name) # Open the spreadsheet
except gspread.exceptions.SpreadsheetNotFound as e:
    print(f"Error: Spreadsheet '{args.name}' not found.")
    sys.exit()
try:
    worksheet = sheet.worksheet(args.sheet) # Open worksheet
except gspread.exceptions.WorksheetNotFound as e:
    print(f"Error: Worksheet '{args.sheet}' not found.")
    sys.exit()

if args.cell is not None and args.row==None and args.column==None :

    val = worksheet.acell(args.cell).value

    if int(args.onlydigits)==1:

        try:
            val = convert_to_number(val)
        except ValueError as e:
            print(e)
        
    
elif args.row is not None and args.column==None and args.cell == None:

    val = worksheet.row_values(args.row)  # Get a specific row

elif args.column is not None and args.row== None and args.cell == None:

    val = worksheet.col_values(args.column)  # Get a specific column

else:
    print("To search for data in a cell use the --cell parameter, for data in a column --column and in a row --row, only one of these parameters can be used at a time.")
    sys.exit()

print(val)

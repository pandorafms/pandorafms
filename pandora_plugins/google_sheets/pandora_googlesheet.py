import gspread
import argparse
from oauth2client.service_account import ServiceAccountCredentials
from pprint import pprint

__author__ = "Alejandro Sánchez Carrion"
__copyright__ = "Copyright 2022, PandoraFMS"
__maintainer__ = "Operations department"
__status__ = "Production"
__version__= '1.0'

info = f"""
Pandora FMS Google Sheets
Version = {__version__}

Manual execution

python3 pandora_googlesheets.py --cred <file credentials> --row <number-row> --column <number-column>

"""

parser = argparse.ArgumentParser(description= info, formatter_class=argparse.RawTextHelpFormatter)
parser.add_argument('--cred', help='')
parser.add_argument('--name', help='')
parser.add_argument('--row', help='',type=int)
parser.add_argument('--column', help='',type=int)

args = parser.parse_args()

scope = ["https://spreadsheets.google.com/feeds",'https://www.googleapis.com/auth/spreadsheets',"https://www.googleapis.com/auth/drive.file","https://www.googleapis.com/auth/drive"]
creds = ServiceAccountCredentials.from_json_keyfile_name(args.cred, scope)

client = gspread.authorize(creds)

sheet = client.open(args.name).sheet1  # Open the spreadhseet

data = sheet.get_all_records()  # Get a list of all records

if args.row is not None and args.column==None:
    row = sheet.row_values(args.row)  # Get a specific row
    print(row)
elif args.row ==None and args.column is not None:
    col = sheet.col_values(args.column)  # Get a specific column
    print(col)
elif args.row is not None and args.column is not None:
    cell = sheet.cell(args.row,args.column).value  # Get the value of a specific cell
    print(cell)

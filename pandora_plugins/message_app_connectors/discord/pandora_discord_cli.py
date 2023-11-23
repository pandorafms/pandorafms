import requests, argparse, sys, os, signal
from datetime import datetime
from re import search
from base64 import b64decode
from discord_webhook import DiscordWebhook, DiscordEmbed


### Variables and arg parser ###
parser = argparse.ArgumentParser(description='Test parser dic')
parser.add_argument('-d', '--data', help='Data in coma separate keypairs. Ex: test=5,house=2', required=True)
parser.add_argument('-u', '--url', help='Discord webhook URL', required=True)
parser.add_argument('-t', '--alert_tittle', help='Alert tittle', default='PandoraFMS alert fired')
parser.add_argument('-D', '--alert_desc', help='Alert description', default='alert')
parser.add_argument('-m', '--message', help='Discord message', default='')
parser.add_argument('-T','--tittle_color', help='Alert tittle descripcion in HEX EX: 53e514', default="53e514")
parser.add_argument('-A','--author', help='Alert custom author', default='PandoraFMS')
parser.add_argument('-F','--footer', help='Custom footer', default='')
parser.add_argument('--avatar_url', help='Custom avatar URL for the user which send the alert', default='')
parser.add_argument('--author_url', help='Alert custom url author', default='')
parser.add_argument('--author_icon_url', help='Alert custom author icon url ', default='')
parser.add_argument('--thumb', help='Custom thumbnail url', default='')
parser.add_argument('--api_conf', help='Api configuration parameters in coma separate keypairs. EX "user=admin,pass=pandora,api_pass=1234,api_url=http://test.artica.es/pandora_console/include/api.php"')
parser.add_argument('--module_graph', help='Uses pandora API to generate a module graph and attach it to the alert needs module_id and interval parameters in coma separate keypairs. EX "module_id=55,interval=3600"')
parser.add_argument('--tmp_dir', help='Temporary path to store graph images', default='/tmp')


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
        embed.add_embed_field(name=k, value=v)

def parse_api_conf(cConf):
    if args.api_conf :
    # Parse Api config
        print ("Api config enable")
        apid = parse_dic(cConf)
        
        if apid.get("user") is None:
            print ("Error no user defined in api_conf keypairs, skipping graph generation.")
            return
        
        if apid.get("pass") is None:
            print ("Error no password defined in api_conf keypairs, skipping graph generation.")
            return

        if apid.get("api_pass") is None:
            print ("Error no Api pass defined in api_conf keypairs, skipping graph generation.")
            return

        if apid.get("api_url") is None:
            apid['api_url'] = "http://127.0.0.1/pandora_console/include/api.php" 
            #print(f"api_url: {apid['api_url']}")

        return apid
    else:
        return None

def parse_graph_conf(cGraph):
    if not args.api_conf:
        print ("To get graph data api conf shoul be provided please set an api config")
        return
    
    if cGraph :
    # Parse Api config
        graphd = parse_dic(cGraph)
        if graphd.get("module_id") is None:
            print ("error no module_id defined in module_graph keypairs, skipping graph generation.")
            return
        
        if graphd.get("interval") is None:
            graphd["interval"] = 3600
        
        return graphd
    else:
        return None

def get_graph_by_moduleid (baseUrl,pUser, pPass, apiPass, moduleId, graphInterval) : 
    sep="url_encode_separator_%7C"
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

## Main
## Basic message
webhook = DiscordWebhook(url=args.url, content=args.message, avatar_url=args.avatar_url)
# create embed object for webhook
embed = DiscordEmbed(title=args.alert_tittle, description=args.alert_desc, color=int(args.tittle_color, 16))
# set author
embed.set_author(name=args.author, url=args.author_url, icon_url=args.author_icon_url)
# set thumbnail
if args.thumb: embed.set_thumbnail(url=args.thumb)
# set footer
if args.footer : embed.set_footer(text=args.footer)
# set timestamp (default is now)
embed.set_timestamp()
# Parse data keys
data = parse_dic(args.data)
# add fields to embed
add_embed_itmes(data)

# Parse api config
api = parse_api_conf(args.api_conf)
# Parse graph config
graph_cfg = parse_graph_conf(args.module_graph)
  
## Generate module graph

if graph_cfg is not None and api is not None:
    graph = get_graph_by_moduleid (api["api_url"],api["user"], api["pass"], api["api_pass"], graph_cfg["module_id"], graph_cfg["interval"])
    
    if graph is not None:
        try:
            namef =  f"graph_{graph_cfg['module_id']}.{datetime.now().strftime('%s')}.png"
            filename = f"{args.tmp_dir}/{namef}"
            with open(filename, "wb") as f:
                f.write(b64decode(graph.text))
                f.close
            print (f"Graph generated on temporary file {filename}")
        except Exception as e :
            print(f"Error, cant generate graph file: {e}")
            filename = None

        try:
            with open(filename, "rb") as F:
                webhook.add_file(file=F.read(), filename=namef)
                f.close
            embed.set_image(url=f'attachment://{namef}')
        except Exception as e :
            print(f"Error, cant add graph file: {e}")
            filename = None

# add embed object to webhook
webhook.add_embed(embed)

# Execute webhook send
response = webhook.execute()

# clean temp file if exist
try:
    os.remove(filename)
except:
    pass

# print response
print (f"Message sent. status code: {response[0].status_code}") if response[0].status_code == 200 else print (f"Error status code: {response[0].status_code}")

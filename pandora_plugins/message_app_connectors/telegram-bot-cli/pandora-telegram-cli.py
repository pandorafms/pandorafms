import requests, argparse, json, sys, os, signal
from datetime import datetime
from base64 import b64decode

__version__='080621'

### Variables and arg parser ###
parser = argparse.ArgumentParser(description=f'Bot telegram cli, Version: {__version__}')
parser.add_argument('-m', '--message', help='Message to be send', required=True)
parser.add_argument('-t', '--token', help='Bot token', required=True)
parser.add_argument('-c', '--chat_id', help='chat id to send messages', required=True)
parser.add_argument('--api_conf', help='Api configuration parameters in coma separate keypairs. EX "user=admin,pass=pandora,api_pass=1234,api_url=http://test.artica.es/pandora_console/include/api.php"')
parser.add_argument('--module_graph', help='Uses pandora API to generate a module graph and attach it to the alert needs module_id and interval parameters in coma separate keypairs. EX "module_id=55,interval=3600"')
parser.add_argument('--tmp_dir', help='Temporary path to store graph images', default='/tmp')


args = parser.parse_args()

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
def parse_api_conf(cConf):
    """Check apiconfiguration parameters """
    if args.api_conf :
    # Parse Api config
        print ("Api config enable", file=sys.stderr)
        apid = parse_dic(cConf)
        
        if apid.get("user") is None:
            print ("Warning. no user defined in api_conf keypairs, skiping graph generation.")
            return None
        
        if apid.get("pass") is None:
            print ("Warning. no password defined in api_conf keypairs, skiping graph generation.")
            return None

        if apid.get("api_pass") is None:
            print ("Warning. no api pass defined in api_conf keypairs, skiping graph generation.")
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
            print ("Warning. no module_id defined in module_graph keypairs, skiping graph generation.")
            return
        
        if graphd.get("interval") is None:
            graphd["interval"] = 3600
        
        return graphd
    else:
        print("Warning. no module_graph keypairs defined, skiping graph generation")
        return None

def get_graph_by_moduleid (baseUrl,pUser, pPass, apiPass, moduleId, graphInterval, sep="url_encode_separator_%7C") : 
    """Call Pandorafms api to get graph"""

    try:
        url = f"{baseUrl}?op=get&op2=module_graph&id={moduleId}&other={graphInterval}%7C1&other_mode={sep}&apipass={apiPass}&api=1&user={pUser}&pass={pPass}"
        graph = requests.get(url)
        if graph.status_code != 200:
            print (f"Error requested api url, status code: {graph.status_code}. Skiping graph generation")
            return None
        if graph.text == "auth error":
            print (f"Error requested Pandora api url, status code: {graph.text}. Skiping graph generation")
            return None
        if graph.text == "Id does not exist in database.":
            print (f"Error requested Pandora api url, status code: {graph.text}. Skiping graph generation")
            return None
        if graph.text == "The user has not enough permissions for perform this action.":
            print (f"Error requested Pandora api url, status code: {graph.text} Skiping graph generation")
            return None
        
    except:
        print("Error requested api url. Skiping graph generation")
        return None
    return graph

def send(mssg, chatId, token):
    url = f"https://api.telegram.org/bot{token}/sendMessage"
    headers = {'content-type': 'application/json'}
    data = {
        "chat_id": chatId,
        "text": mssg
    }

    try:
        response = requests.get(url, data=json.dumps(data), headers=headers)
        r = response.json()
        print(r)
    except Exception as e :
        r = None
        exit(f"Error requesting telegram api: {e}")


def sendMedia(mssg, chatId, token, filepath):
    url = f"https://api.telegram.org/bot{token}/sendPhoto"
    data = {
        "chat_id": chatId, 
        "caption": mssg
    }
    try:
        with open(filepath, "rb") as photog:
            request = requests.post(url, data=data, files={'photo': (filepath, photog)})         
        r = request.json()
    except Exception as e :
        r = None
        print(f"Error, cant add graph file: {e}")

    if r is not None:
        r = request.json()
        print(r)

# Parse api config
filecap=None

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
                    print (f"Graph generated on temporary file {filename}", file=sys.stderr)
                except Exception as e :
                    print(f"Error, cant generate graph file: {e}", file=sys.stderr)
                    filename = None
            else: filename = None

    if filename is not None:
        filecap=f"graph_{graph_cfg['module_id']}.{datetime.now().strftime('%s')}.png"
        
# Send message
send(mssg=args.message, chatId=args.chat_id, token=args.token)

if filecap is not None:
    sendMedia(mssg='', chatId=args.chat_id, token=args.token, filepath=filename)
    try:
        os.remove(filename)
    except Exception as e:
        exit('Error: {e}')
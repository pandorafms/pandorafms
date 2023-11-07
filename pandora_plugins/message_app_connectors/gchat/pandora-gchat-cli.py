import requests, signal, argparse, sys, os, json

### Variables and arg parser ###
parser = argparse.ArgumentParser(description='Google chat webhook conector')
parser.add_argument(
    '-d', '--data', help='Data in coma separate keypairs. Ex: test=5,house=2', required=True)
parser.add_argument(
    '-u', '--url', help='Google chat webhook URL', required=True)
parser.add_argument('-t', '--alert_title', help='Alert title',
                    default='PandoraFMS alert system')
parser.add_argument('-D', '--alert_desc',
                    help='Alert description', default='Alert Fired')
parser.add_argument('--thumb', help='Custom thumbnail url',
                    default="https://pandorafms.com/images/alerta_roja.png")
parser.add_argument('--btn_desc', help='button description', default=None)
parser.add_argument('--btn_url', help='button url',
                    default="https://pandorafms.com/")


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

# classes


class Message():
    def __init__(self, title, subtitle, imageurl='https://goo.gl/aeDtrS'):
        """ Initialize message object, setting header options"""
        self.dic = {
            'cards': []
        }

        header = {'header': {'title': title,
                             'subtitle': subtitle, 'imageUrl': imageurl}}
        self.dic['cards'].append(header)

        sections = {'sections': []}
        self.dic['cards'].append(sections)

    def add_header(self, title, subtitle, imageurl='https://goo.gl/aeDtrS'):
        """Add header to message object"""
        header = {'header': {'title': title,
                             'subtitle': subtitle, 'imageUrl': imageurl}}
        self.dic['cards'].append(header)

    def add_value(self, keyval):
        """Add key value pairs data to message object, keyval should be a dictionary"""
        m = ''
        arr = []
        for k, v in keyval.items():
            m += f"<b>{k}</b>:  {v} \n"

        arr.append({'textParagraph': {'text': m}})

        widgets = {'widgets': arr}
        self.dic['cards'][1]['sections'].append(widgets)

    def add_buttom(self, desc, url):
        """Add button to message object"""
        btn = [{"textButton": {"text": desc, "onClick": {"openLink": {'url': url}}}}]
        arr = [({'buttons': btn})]

        widgets = {'widgets': arr}
        self.dic['cards'][1]['sections'].append(widgets)

# functions


def parse_dic(cValues):
    """convert coma separate keypairs into a dic. EX "test=5,house=8,market=2" wil return "{'test': '5', 'casa': '8', 'mercado': '2'}" """
    data = {}
    try:
        for kv in cValues.split(","):
            k, v = kv.strip().split("=")
            data[k.strip()] = v.strip()
    except Exception as e:
        print(f"Warning, error parsing keypairs values: {e}")
    return data


def sendMessage(url, message):
    """sends google chat message"""
    message = json.dumps(message)
    try:
        header = {'Content-Type': 'application/json; charset: UTF-8'}
        response = requests.post(url, headers=header, data=message)

        print(f"Message sent successfully: {response.status_code}")
    except:
        print("Error requested api url. skipping graph generation")
        return None
    return response


if __name__ == '__main__':

    # Initializaate message object
    a = Message(args.alert_title, args.alert_desc, args.thumb)
    # Parse data values
    data = parse_dic(args.data)
    # Add datavalues into message object
    a.add_value(data)
    # Chek button parameters and add it to the message object
    if args.btn_desc != None:
        a.add_buttom(args.btn_desc, args.btn_url)

    # Send message
    sendMessage(args.url, a.dic)

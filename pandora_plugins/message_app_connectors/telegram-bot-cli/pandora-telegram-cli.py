import requests, argparse, json, sys, os

### Variables and arg parser ###
parser = argparse.ArgumentParser(description='Bot telegram cli')
parser.add_argument('-m', '--message', help='Message to be send', required=True)
parser.add_argument('-t', '--token', help='Bot token', required=True)
parser.add_argument('-c', '--chat_id', help='chat id to send messages', required=True)


args = parser.parse_args()


def send(mssg, chatId, token):
    url = f"https://api.telegram.org/bot{token}/sendMessage"
    headers = {'content-type': 'application/json'}

    data = {
        "chat_id": chatId,
        "text": mssg
    }

    response = requests.get(url, data=json.dumps(data), headers=headers)

    r = response.json()
    print(r)

send(mssg=args.message, chatId=args.chat_id, token=args.token)

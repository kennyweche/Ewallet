import requests
import sys
import json

class Task:
    # variables
    _data = None
    _ch = None
    _method = None
    _logger = None
    _murl = None

    # constructor
    def __init__(self, _murl, logger, ch, method, data):
        """Pass your data here"""
        self._data = data
        self._ch = ch
        self._method = method
        self._logger = logger
        self._murl = _murl

    def log_message(self, message):
        self._logger.info('%s' % str(message))

    '''
    Process the payment
    '''

    def send_request(self, url, post_data):
        self.log_message("Got request, sending #####")
        content = None
        try:
            post_response = requests.post(url=url, data=post_data)
            content = str(post_response.content)
            self.log_message("Sent request response is %s" % content)
        except:
            _error = str(sys.exc_info()[1])
            self.log_message("Error during processing %s" % _error)
            content = None
        return content

    
    def process(self):
        """Do something with your data, mydata variable """
        self.log_message("Thread received %s" % str(self._data))
	#{"balance":1000,"amount":"200","account":"254711240985","url":"http:\/\/web1\/lotto\/notify.php"}
	_hit_url = self.get_item(self._data, "url");
	#_balance = self.get_item(self._data, "balance")
	#_amount = self.get_item(self._data, "amount")
	#_account = self.get_item(self._data, "account")
	
	#_payload = {
	#    'balance': _balance,
	#    'account': _account,
	#    'amount': _amount
	#}
	#_json_payload = json.dumps(_payload)
	#self.log_message("Payload %s being sent to notify url->%s" % (_json_payload, _hit_url))
        _response = self.send_request(_hit_url, self._data)
        self.log_message("Response from API is %s" % str(_response))
        self._ch.basic_ack(delivery_tag=self._method.delivery_tag)

    '''
    Get item from JSON packet
    '''
    def get_item(self, dataset, key):
        try:
            json_body = json.loads(dataset)
            value = json_body[key]
        except:
            value = None
        return value

    '''
    Get item from JSON packet
    '''
    def get_inner_item(self, dataset, key):
        try:
            value = dataset[key]
        except:
            value = None
        return value


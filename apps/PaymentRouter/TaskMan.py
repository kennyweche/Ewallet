import requests
import sys
import json
import time

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
        _response = self.send_request(self._murl, self._data)
        self.log_message("Response from API is %s" % str(_response))
        if _response is None:
            while True:
                _response = self.send_request(self._murl, self._data)
                _code = str(self.get_item(_response, 'statusCode'))
                if _code == "121" or _code == "130":
                    self._ch.basic_ack(delivery_tag=self._method.delivery_tag)
                    break
                time.sleep(float(5))                                                                                            
            return

        _code = self.get_item(_response, 'statusCode')
	self.log_message("StatusCode : %s" % _code)
	if str(_code) == "121":
	    self.log_message("StatusCode : %s OK #Mark as processed" % _code)
            self._ch.basic_ack(delivery_tag=self._method.delivery_tag)
        elif str(_code) == "130":
            self.log_message("StatusCode : %s OK #Mark as already processed" % _code)
            self._ch.basic_ack(delivery_tag=self._method.delivery_tag)
	else: 
	    self.log_message("Expected PaymentQueued code ## will retry as gateway will handle duplicates")
            time.sleep(float(2))
            _response = self.send_request(self._murl, self._data)
            _code = str(self.get_item(_response, 'statusCode'))
            while _code is not "121" or _code is None:
                _response = self.send_request(self._murl, self._data)
                _code = str(self.get_item(_response, 'statusCode'))
                if _code == "121" or _code == '130':
                    self.log_message("StatusCode : %s OK #Mark as Finally processed" % _code)
                    self._ch.basic_ack(delivery_tag=self._method.delivery_tag)
                    break
                time.sleep(float(3))
        self.log_message("Finished processing thread ##")

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


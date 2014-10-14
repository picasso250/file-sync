import json
import socket
import logging
import struct

def _recv(s):
    l = _read_enough(s, 4)
    if len(l) == 0:
        print('can not be 0 of title length')
        return {}, None
    l, = struct.unpack('i', l)
    if l == 0:
        print('no 0')
        return {}, None
    j = _read_enough(s, l)
    header = json.loads(j.decode())
    data = None
    if 'data-size' in header and header['data-size'] > 0:
        data = _read_enough(s, header['data-size'])
    return header, data

def _read_enough(s, l):
    b = bytes()
    while l != 0:
        logging.debug('recv length %s',l)
        data = s.recv(l)
        b += data
        l -= len(data)
        if len(data) == 0:
            print('empty data')
            break
    return b

def _send(s, header, data = None):
    if data is None:
        size = 0
    else:
        size = len(data)
    print('header', header)
    header['size'] = size
    header['data-size'] = size
    logging.info(header)
    json_ctrl = json.dumps(header);
    l = len(json_ctrl);
    l = struct.pack('i', l);
    s.sendall(l)
    if s.sendall(json_ctrl.encode('utf-8')) is not None:
        print ("Write failed in ")
    if data is not None and s.sendall(data) is not None:
        print ("Write failed in ")


class Protocol(object):
    """docstring for Protocol"""
    def __init__(self, host, port):
        super(Protocol, self).__init__()
        self.socket = self._open_socket(host, port)
        
    def send(self, header, data = None):
        print(header)
        _send(self.socket, header, data)

    def _open_socket(self, host, port):
        logging.info("Connect to %s", str(host)+':'+str(port))
        self.socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        if self.socket is None:
            print("Could not create socket\n"); # 创建一个Socket
            return None
        connection = self.socket.connect((host, port))
        return self.socket

    def _socket_write_enough(self, s, b):
        length = len(b);
        while True:
            sent = s.send(b)
            # Check if the entire message has been send
            if sent < length:
                print('send more')
                # If not sent the entire message.
                # Get the part of the message that has not yet been send as message
                b = b[sent:]
                # Get the length of the not send part
                length -= sent
            else:
                break
        return True

    def recv(self, l = 0):
        return _recv(self.socket)

    def close(self):
        self.socket.close()

class Server(Protocol):
    """docstring for Server"""
    def __init__(self, host, port):
        super(Server, self).__init__()
        self.host = host
        self.port = port
        self.socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        self.socket.bind((self.host, self.port))
        self.socket.listen(1)
    
    def recv(self):
        conn, addr = self.socket.accept()
        print('Connected by', addr)
        return _recv(conn)
        conn.close()

import socketserver

class BaseRequestHandler(socketserver.BaseRequestHandler):
    """
    The RequestHandler class for our server.

    It is instantiated once per connection to the server, and must
    override the handle() method to implement communication to the
    client.
    """

    def recv_request(self):
        return _recv(self.request)

    def sendall_request(self, header, data = None):
        _send(self.request, header, data)

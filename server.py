import socketserver
import json
import struct
import logging

class FileHandler(socketserver.BaseRequestHandler):
    """
    The RequestHandler class for our server.

    It is instantiated once per connection to the server, and must
    override the handle() method to implement communication to the
    client.
    """

    def handle(self):
        # self.request is the TCP socket connected to the client
        head = self.get_request_head()
        cmd = head['cmd']
        if cmd == 'save file':
            i = head['id']
            p = pairs[i]['root_server']+head['filename']
            logging.info("recv file %s", head['filename'])
            self.save_file(p, head['data-size'])
            self.request.sendall('save to {}'.format(p).encode())
            print ("{} send {}, we save it to {}".format(self.client_address[0], head['filename'], p))

    def get_request_head(self):
        hsize = self.request.recv(4)
        l, = struct.unpack('i', hsize)
        j = self.request.recv(l)
        head = json.loads(j.decode())
        return head

    def save_file(self, fname, total):
        if total == 0:
            with open(fname, 'a') as f:
                os.utime(fname, None)
                return
        chunk_size = 1024
        tmp = fname+'.xc.tmp'
        with open(tmp, 'w') as f:
            while total > 0:
                f.write(self.request.recv(chunk_size))
                total -= chunk_size
        os.rename(tmp, fname)

if __name__ == "__main__":

    logging.basicConfig(filename='sync.log')

    config = json.load(open('config.default.json'))
    config.update(json.load(open('config.user.json')))
    pairs = config['pairs']

    # Create the server, binding to localhost on port specified
    host = config['host']
    port = config['port']
    print('listen on {}:{}'.format(host, port))
    server = socketserver.TCPServer((host, port), FileHandler)

    # Activate the server; this will keep running until you
    # interrupt the program with Ctrl-C
    server.serve_forever()

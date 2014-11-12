import SocketServer

class FileHandler(SocketServer.BaseRequestHandler):
    """
    The RequestHandler class for our server.

    It is instantiated once per connection to the server, and must
    override the handle() method to implement communication to the
    client.
    """

    def handle(self):
        # self.request is the TCP socket connected to the client
        hsize = self.request.recv(4)
        l, = struct.unpack('i', hsize)
        j = self.request.recv(l)
        head = json.loads(j)
        


        print "{} wrote:".format(self.client_address[0])
        print self.data
        # just send back the same data, but upper-cased
        self.request.sendall(self.data.upper())

if __name__ == "__main__":
    if len(sys.argv) < 2:
        sys.stderr.write('Usage: sys.argv[0] <port>')
        sys.exit(1)
    HOST, PORT = "localhost", int(sys.argv[1])

    # Create the server, binding to localhost on port specified
    server = SocketServer.TCPServer((HOST, PORT), FileHandler)

    # Activate the server; this will keep running until you
    # interrupt the program with Ctrl-C
    server.serve_forever()

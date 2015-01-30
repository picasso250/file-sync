
void recv_all(int sock, char * s, size_t len)
{
	while (len > 0)
	{
		int i = recv(sock, s, len, 0);
		s += i;
		len -= i;
	}
}
void send_all(int sock, char * s, size_t len)
{
	while (len > 0)
	{
		int i = send(sock, s, len, 0);
		s += i;
		len -= i;
	}
}

#define INT_LEN 4
int recv_int(int sock)
{
	char buf[INT_LEN];
	recv_all(sock, buf, INT_LEN);
	return *((int *) buf);
}
void send_int(int sock, int v)
{
	send_all(sock, (char *)(&v), INT_LEN);
}

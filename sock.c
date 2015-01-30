
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
		printf("send %d\n", len);
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

size_t recv_str(int sock, char * s, int len)
{
	int size = 0;
	while (len > 0)
	{
		int i = recv(sock, s, len, 0);
		size += i;
		if (i > 0 && *(s+i-1) == '\0')
		{
			return size;
		}
		s += i;
		len -= i;
	}
}

#define FILE_BUF_SIZE 256
int send_file(int sock, char * fn)
{
	char buf[FILE_BUF_SIZE];
	int f = open(fn, O_RDONLY);
	if (f < 0)
	{
		return f;
	}
	int len;
	while ((len = read(f, buf, FILE_BUF_SIZE)) > 0)
	{
		printf("send_file, read %d\n", len);
		send_all(sock, buf, len);
	}
	close(f);
	return len;
}
void write_all(int file, char * s, size_t len)
{
	while (len > 0)
	{
		int i = write(file, s, len, 0);
		s += i;
		len -= i;
	}
}
int recv_file(int sock, char * fn, int size)
{
	int f = open(fn, O_WRONLY | O_CREAT);
	if (f < 0)
	{
		return f;
	}
	fcntl(sock, F_SETFL, O_NONBLOCK);
	int len;
	char buf[FILE_BUF_SIZE];
	printf("start recv %d\n", FILE_BUF_SIZE);
	while (size > 0)
	{
		len = recv(sock, buf, FILE_BUF_SIZE, 0);
		if (len == -1)
		{
			if (errno == EAGAIN)
			{
				printf("EAGAIN\n");
				continue;
			}
			return len;
		}
		if (len == 0)
			return len;
		printf("recv_file, read %d\n", len);
		write_all(f, buf, len);
		size -= len;
	}
	printf("end recv\n");
	close(f);
	return len;
}


#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <resolv.h>
#include <sys/types.h>
#include <sys/socket.h>

#define DEST_IP "127.0.0.1"
#define DEST_PORT 8081

#include "hashtable.c"

int main(int argc, char const *argv[])
{
	printf("%s\n", "start");
	hashtable_init();
	hashtable_set("a", 3);
	hashtable_set("b", 4);
	hashtable_set("c", 5);
	hashtable_set("d", 6);
	int a = hashtable_get("a");
	int b = hashtable_get("b");
	int c = hashtable_get("c");
	int d = hashtable_get("d");
	printf("a: %d, b: %d, c: %d, d: %d\n", a, b, c, d);
	return 0;
	int sock;
	sock = socket (PF_INET, SOCK_STREAM, 0);
	if (sock < 0)
    {
      perror ("socket");
      exit (EXIT_FAILURE);
    }
	struct sockaddr_in dest_addr;
	dest_addr.sin_family=AF_INET;/*hostbyteorder*/
	dest_addr.sin_port=htons(DEST_PORT);/*short,network byte order*/
	dest_addr.sin_addr.s_addr=inet_addr(DEST_IP);
	bzero(&(dest_addr.sin_zero),8);/*zero the rest of the struct*/
	/*don'tforgettoerrorchecktheconnect()!*/
	if (connect(sock, (struct sockaddr*)&dest_addr, sizeof(struct sockaddr)) < 0)
	{
		perror ("connect");
		exit (EXIT_FAILURE);
	}
	char buf[50];
	memset(buf, 0, 50);
	recv(sock, buf, 10, 0);
	printf("recieve: %s\n", buf);
	close(sock);
	printf("%s\n", "end");
	return 0;
}

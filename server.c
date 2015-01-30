#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <resolv.h>
#include <sys/types.h>
#include <sys/socket.h>

int
make_socket (uint16_t port)
{
  int sock;
  struct sockaddr_in name;

  /* Create the socket. */
  sock = socket (PF_INET, SOCK_STREAM, 0);
  if (sock < 0)
    {
      perror ("socket");
      exit (EXIT_FAILURE);
    }

  /* Give the socket a name. */
  name.sin_family = AF_INET;
  name.sin_port = htons (port);
  name.sin_addr.s_addr = htonl (0);
  if (bind (sock, (struct sockaddr *) &name, sizeof (name)) < 0)
    {
      perror ("bind");
      exit (EXIT_FAILURE);
    }

  return sock;
}
int main(int argc, char const *argv[])
{
	printf("%s\n", "start");
	int sock;
	sock = make_socket(8081);
	if (listen(sock, 5) < 0)
	{
		perror("listen");
		exit(EXIT_FAILURE);
	}
	struct sockaddr_in name;
	int new_fd;
	int addr_size = sizeof(struct sockaddr_in);
	while (1) {
		new_fd = accept(sock, (struct sockaddr*)&name, &addr_size);
		if (new_fd < 0)
		{
			perror ("accept");
			exit (EXIT_FAILURE);
		}
		char * s = "helloz";
		printf("send: %s\n", s);
		send(new_fd, s, strlen(s), 0);
		close(new_fd);
		break;
	}
	close(sock);
	printf("%s\n", "end");
	return 0;
}

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <dirent.h>
#include <resolv.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/socket.h>
#include <errno.h>
#include <err.h>

#define DEST_IP "127.0.0.1"
#define DEST_PORT 8081

#include "hashtable.c"

enum {
	WALK_OK = 0,
	WALK_BADPATTERN,
	WALK_NAMETOOLONG,
	WALK_BADIO,
};
 
#define WS_NONE		0
#define WS_RECURSIVE	(1 << 0)
#define WS_DEFAULT	WS_RECURSIVE
#define WS_FOLLOWLINK	(1 << 1)	/* follow symlinks */
#define WS_DOTFILES	(1 << 2)	/* per unix convention, .file is hidden */
#define WS_MATCHDIRS	(1 << 3)	/* if pattern is used on dir names too */

int root_len;
char ip[FILENAME_MAX];
char dest[FILENAME_MAX];
int port = 0;

int walk_recur(const char *dname)
{
	struct dirent *dent;
	DIR *dir;
	struct stat st;
	char fn[FILENAME_MAX];
	int res = WALK_OK;
	int len = strlen(dname);
	if (len >= FILENAME_MAX - 1)
		return WALK_NAMETOOLONG;
 
	strcpy(fn, dname);
	fn[len++] = '/';
 
	if (!(dir = opendir(dname))) {
		warn("can't open %s", dname);
		return WALK_BADIO;
	}
 
	errno = 0;
	while ((dent = readdir(dir))) {
		if (dent->d_name[0] == '.')
			continue;
		if (!strcmp(dent->d_name, ".") || !strcmp(dent->d_name, ".."))
			continue;
 		
		strncpy(fn + len, dent->d_name, FILENAME_MAX - len);
		if (lstat(fn, &st) == -1) {
			warn("Can't stat %s", fn);
			res = WALK_BADIO;
			continue;
		}
 
		/* don't follow symlink unless told so */
		if (S_ISLNK(st.st_mode))
			continue;
 
		/* will be false for symlinked dirs */
		if (S_ISDIR(st.st_mode)) {
			/* recursively follow dirs */
			walk_recur(fn);
			continue;
		}

		if (hashtable_get(fn) != st.st_mtime)
		{
			printf("upload from %s to %s%s\n", fn, dest, fn+root_len);
			char *new_fn = strdup(fn);
			hashtable_set(new_fn, st.st_mtime);
		}
	}
 
	if (dir) closedir(dir);
	return res ? res : errno ? WALK_BADIO : WALK_OK;
}

int main(int argc, char const *argv[])
{
	char *usage = "Usage: %s ip:port/dest from\n";
	if (argc < 3)
	{
		printf(usage, argv[0]);
		exit(EXIT_FAILURE);
	}
	printf("%s\n", "start");

	char port_str[10];
	const char *p = argv[1];
	const char *port_begin = NULL;
	for (; *p; ++p)
	{
		if (*p == ':')
		{
			strncpy(ip, argv[1], p - argv[1]);
			port_begin = p+1;
		}
		else if (*p == '/')
		{
			if (port_begin == NULL)
			{
				perror("no :");
				exit(EXIT_FAILURE);
			}
			strncpy(port_str, port_begin, p - port_begin);
			port = atoi(port_str);
			strcpy(dest, p);
			break;
		}
	}
	if (port == 0)
	{
		printf(usage, argv[0]);
		exit(EXIT_FAILURE);
	}
	printf("ip: %s\n", ip);
	printf("port: %d\n", port);
	printf("dest: %s\n", dest);

	root_len = strlen(argv[2]);
	int r = walk_recur(argv[2]);
	// hashtable_print();
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
	dest_addr.sin_port=htons(port);/*short,network byte order*/
	dest_addr.sin_addr.s_addr=inet_addr(ip);
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

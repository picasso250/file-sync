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
#include <fcntl.h>

#define DEST_IP "127.0.0.1"
#define DEST_PORT 8081

#include "hashtable.c"
#include "sock.c"

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
int port = 0;
char dest[FILENAME_MAX];
int ignore_c = 0;
char const * ignore_v[10];

int is_ignore(char * fn)
{
	int i;
	for (i = 0; i < ignore_c; ++i)
	{
		if (strcmp(fn, ignore_v[i]) == 0)
		{
			return 1;
		}
	}
	return 0;
}

#define FILE_BUF_SIZE 256
int send_file(int sock, char * fn, size_t size)
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
		send_all(sock, buf, len);
	}
	close(f);
	return len;
}

void upload(char * fn, char * dest, off_t size)
{
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
	send_all(sock, dest, strlen(dest)); // filename
	int remote_size = recv_int(sock);
	if (remote_size == size)
	{
		return;
	}
	send_int(sock, size);
	if (send_file(sock, fn, size) < 0)
	{
		perror("send file");
		exit(EXIT_FAILURE);
	}
	remote_size = recv_int(sock);
	if (remote_size != size)
	{
		perror("remote_size not equal to local size after upload");
		exit(EXIT_FAILURE);
	}
	close(sock);
	printf("%s\n", "upload end");
	return;
}

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
		if (is_ignore(dent->d_name))
		{
			continue;
		}
 		
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
			char dest_fn[FILENAME_MAX];
			strcpy(dest_fn, dest);
			strncpy(dest_fn+strlen(dest), fn+root_len, strlen(fn+root_len)+1);
			printf("upload from %s to %s\n", fn, dest_fn);
			upload(fn, dest_fn, st.st_size);
			char *new_fn = strdup(fn);
			hashtable_set(new_fn, st.st_mtime);
		}
	}
 
	if (dir) closedir(dir);
	return res ? res : errno ? WALK_BADIO : WALK_OK;
}

int parse_ignore(int argc, char const *argv[]) {
	int i = 2;
	while (i < argc)
	{
		if (strcmp("--ignore", argv[i]) == 0)
		{
			i++;
			if (i < argc)
			{
				printf("we will ignore %s\n", argv[i]);
				ignore_v[ignore_c++] = argv[i];
			} else {
				perror("ignore what?");
				return (EXIT_FAILURE);
			}
		}
		i++;
	}
	return 0;
}
int parse_arg(int argc, char const *argv[]) {
	char *usage = "Usage: %s ip:port/dest from\n";
	if (argc < 3)
	{
		printf(usage, argv[0]);
		return (EXIT_FAILURE);
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
				return (EXIT_FAILURE);
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
		return (EXIT_FAILURE);
	}
	printf("ip: %s\n", ip);
	printf("port: %d\n", port);
	printf("dest: %s\n", dest);

	root_len = strlen(argv[2]);
	return parse_ignore(argc, argv);
}

int main(int argc, char const *argv[])
{
	int ret;
	if ((ret = parse_arg(argc, argv)) != 0)
	{
		exit(ret);
	}

	int r = walk_recur(argv[2]);
	// hashtable_print();
	return 0;
}

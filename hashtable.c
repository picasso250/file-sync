unsigned long
hash(unsigned char *str)
{
    unsigned long hash = 5381;
    int c;

    while (c = *str++)
        hash = ((hash << 5) + hash) + c; /* hash * 33 + c */

    return hash;
}

#define HASH_TABLE_LEN 2
struct hash_list_node {
	char * key;
	int value;
	struct hash_list_node * next;
};
struct hash_list_node * list[HASH_TABLE_LEN];

void hashtable_init()
{
	memset(list, 0, HASH_TABLE_LEN);
}
struct hash_list_node * make_node(char *key, int value)
{
	struct hash_list_node * p = malloc(sizeof(struct hash_list_node));
	p->key = key; // move
	p->value = value;
	p->next = NULL;
	return p;
}
int hashtable_get(char *key)
{
	int pos = hash(key) % HASH_TABLE_LEN;
	if (list[pos])
	{
		struct hash_list_node * node = list[pos];
		while (node && strcmp(key, node->key) != 0)
		{
			node = node->next;
		}
		if (node)
		{
			return node->value;
		}
	}
	return -1;
}
void hashtable_set(char *key, int value)
{
	int pos = hash(key) % HASH_TABLE_LEN;
	if (!list[pos])
	{
		list[pos] = make_node(key, value);
		return;
	}
	struct hash_list_node * p = list[pos];
	while (p)
	{
		if (strcmp(p->key, key) == 0)
		{
			p->value = value; // rewrite
			return;
		}
		if (p->next == NULL)
		{
			p->next = make_node(key, value);
			return;
		}
		p = p->next;
	}
}

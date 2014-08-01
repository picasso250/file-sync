#

# 监视本地文件的改变，并将改变通知服务器

import os
import json
import time
import socket
import struct

def get_config():
    config_file = 'config.default.json'
    if (os.path.isfile(config_file)):
        print(config_file, "not exists\n")
        return None
    
    config = json.load(config_file)
    f = 'config.user.json'
    if os.path.isfile(f):
        config_user = json.load(f)
        config = config.update(config_user)
    return config

def load_modify_time():
    f = 'modify_time';
    if (os.path.isfile(f)):
        return json.load(f)
    return None

def open_socket(host, port):
    print("Connect to"+host+port+ "... \n")
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    if s is None:
        print("Could not create socket\n"); # 创建一个Socket
        return None
    connection = s.connect((host, port))
    if connection is None:
        print("Could not connet server\n");# 连接
        return None
    return s

def send_file_change(host, port, root, filemtime, modify_table, filename, s):
    modify_table[filename] = filemtime;
    print( "time diff modify_table[filename] filemtime\n");
    print( "send file filename\n");
    if s is None:
        s = open_socket(host, port)
    send_relet_file(s, root, filename);
    changed = True
    return (modify_table, s, changed)

def process_file(host, port, root, modify_table, filename, s, changed):
    if filename not in modify_table:
        filemtime = os.path.getmtime(filename);
        modify_table, s, changed = send_file_change(host, port, root, filemtime, modify_table, filename, s);
        return (modify_table, s, changed);
    else:
        filemtime = os.path.getmtime(filename);
        if (modify_table[filename] != filemtime):
            modify_table, s, changed = send_file_change(host, port, root, filemtime, modify_table, filename, s);
        return (modify_table, s, changed);

def socket_write_enough(s, string):
    s.sendall(string)
    return True

def send_end():
    print("send end\n")
    ctrl = {
        'cmd': 'end'
    }
    j = json.loads(ctrl)
    l = len(j)
    print("length of control message", l)
    l = struct.pack('i', l)
    if not socket_write_enough(socket, l):
        print("Write failed\n");
        return None
    if not socket_write_enough(socket, json):
        print("Write failed\n")
        return None

def end_socket(s):
    send_end(s);
    while True:
        buff = s.recv(1024)
        if not buff:
            break;
        print("Response was:", buff, "\n");
    s.close()

def save_modify_time(modify_table):
    f = 'modify_time'
    return json.dump(modify_table, f)

def watch_dir(host, prot, root, ignore):
    s = None
    changed = False

    modify_table = load_modify_time();
    if not os.path.isdir(root):
        print("root not dir\n")
        return None

    queue = [root]

    t = -time.time()
    while len(queue) > 0:
        root_dir = queue.pop(0)
        if not os.path.isdir(root_dir):
            print("root_dir not exists\n")
            return None

        for f in os.listdir(root_dir):
            if (f == '.' or f == '..'):
                continue;
            if f in ignore:
                continue;
            filename = root_dir+'/'+f
            if (os.path.isfile(filename)):
                modify_table, s, changed = process_file(host, port, root, modify_table, filename, s, changed);
            elif (os.path.isdir(filename)):
                    queue.append(filename)
    t += time.time()
    print(" (scan takes " + int(t*1000) + " ms)")
    save_modify_time(modify_table);

    if s is not None:
        end_socket(s);
    return changed;

config = get_config()

if config is None:
    print('config is None')
else:
    host = config['host'];
    port = config['port'];
    root = config['root_client'];

    print( "on root\n")

    ignore = config['ignore'];
    interval = 1;
    sleep = 0;
    while (True):
        changed = watch_dir(host, port, root, ignore);
        if changed is None:
            break
        if (changed):
            sleep = interval;
            print()
        else:
            sleep += 1
        print("\rsleep", sleep, 's')
        time.sleep(interval);

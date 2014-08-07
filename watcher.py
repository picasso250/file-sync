#

# 监视本地文件的改变，并将改变通知服务器

import os
import json
import time
import socket
import struct
import re
from os.path import join

def get_config():
    config_file = os.path.dirname(__file__)+'/config.default.json'
    if not os.path.isfile(config_file):
        print(config_file, "not exists\n")
        return None
    config = json.load(open(config_file))

    f = os.path.dirname(__file__)+'/config.user.json'
    if os.path.isfile(f):
        config_user = json.load(open(f))
        config.update(config_user)

    return config

def load_modify_time():
    f = os.path.dirname(__file__)+'/modify_time'
    if os.path.isfile(f):
        return json.load(open(f))
    print(f, 'not exists when load modify_time')
    return {}

def open_socket(host, port):
    print("Connect to", str(host)+':'+str(port), "... \n")
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    if s is None:
        print("Could not create socket\n"); # 创建一个Socket
        return None
    connection = s.connect((host, port))
    return s

def is_text_file(filename):
    # 现在是根据后缀名来判断。但这样恐怕多有不妥
    return re.search('\.(png|jpg|gif|eot|woff|ttf|gz|tar|bz2|zip|rar|7z)$', filename, re.I) is None

def send_relet_file(s, root, filename):
    if filename.find(root) != 0:
        print( "Error: filename filename, root root not match\n")
        return None
    relat_path = filename[len(root)+1:]

    content = open(filename, 'rb').read(2**20) # less then 1GB

    size = len(content);
    if (size == 0):
        print( "emtpy file\n")
    
    ctrl = {
        'cmd' : 'send file',
        'filename': relat_path,
        'size': size,
    }
    print('ctrl', ctrl)
    json_ctrl = json.dumps(ctrl);
    l = len(json_ctrl);
    print( "length of control message", l)
    l = struct.pack('i', l);
    s.sendall(l)
    if not socket_write_enough(s, json_ctrl.encode('utf-8')):
        print ("Write failed in ")
    if not socket_write_enough(s, content):
        print ("Write failed in ")

def send_file_change(host, port, root, filemtime, modify_table, filename, s):
    modify_table[filename] = filemtime;
    print( "send file", filename);
    if s is None:
        s = open_socket(host, port)
    send_relet_file(s, root, filename)

    buff = s.recv(1024)
    print("Response was:", buff, "\n");

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

def socket_write_enough(s, b):
    length = len(b);
    while True:
        sent = s.send(b)
        # Check if the entire message has been send
        if sent < length:
            print('send more')
            # If not sent the entire message.
            # Get the part of the message that has not yet been send as message
            b = b[sent:]
            # Get the length of the not send part
            length -= sent
        else:
            break
    return True

def send_end(s):
    print("send end\n")
    ctrl = {
        'cmd': 'end'
    }
    j = json.dumps(ctrl)
    l = len(j)
    print("length of control message", l)
    l = struct.pack('i', l)
    if not socket_write_enough(s, l):
        print("Write failed\n");
        return None
    if not socket_write_enough(s, j.encode('utf-8')):
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
    return json.dump(modify_table, open(f, 'w'))

def watch_dir(host, prot, root, ignore):
    s = None
    changed = False

    modify_table = load_modify_time()
    if modify_table is None:
        return None
    if not os.path.isdir(root):
        print("root not dir\n")
        return None

    t = -time.time()

    for r, dirs, files in os.walk(root):
        for name in files:
            modify_table, s, changed = process_file(host, port, root, modify_table, join(r, name), s, changed);
        for ignore_dir in ignore:
            if ignore_dir in dirs:
                dirs.remove(ignore_dir)  # don't visit

    t += time.time()
    print(" (scan takes " + str(int(t*1000)) + " ms)", end='')
    save_modify_time(modify_table)

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

    print( "on", root)

    ignore = config['ignore'];
    interval = 1;
    sleep = 0;
    while (True):
        changed = watch_dir(host, port, root, ignore)
        if changed is None:
            break
        if changed:
            sleep = interval;
            print()
        else:
            sleep += 1
        print("\rsleep", sleep, 's', end='')
        time.sleep(interval);

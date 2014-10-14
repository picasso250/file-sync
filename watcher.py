#coding: utf-8

# 监视本地文件的改变，并将改变通知服务器

import os
import json
import time
import re
import logging
from os.path import join
import protocol

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

def is_text_file(filename):
    # 现在是根据后缀名来判断。但这样恐怕多有不妥
    return re.search('\.(png|jpg|gif|eot|woff|ttf|gz|tar|bz2|zip|rar|7z)$', filename, re.I) is None

def send_relet_file(s, id_, root, filename):
    if filename.find(root) != 0:
        print( "Error: filename filename, root root not match\n")
        return None
    print('upload', filename)
    relat_path = filename[len(root)+1:]

    content = open(filename, 'rb').read(2**20) # less then 1GB
    
    ctrl = {
        'cmd' : 'send file',
        'filename': relat_path,
        'id': id_
    }
    s.send(ctrl, content)

def send_file_change(host, port, id_, root, filemtime, modify_table, filename, s):
    modify_table[filename] = filemtime
    logging.info("send file %s", filename)
    if s is None:
        s = protocol.Protocol(host, port)
    send_relet_file(s, id_, root, filename)

    buff, _ = s.recv(1024)
    logging.info("Response was: %s", str(buff))

    changed = True
    return (modify_table, s, changed)

def process_file(host, port, id_, root, modify_table, filename, s, changed):
    if filename not in modify_table:
        filemtime = os.path.getmtime(filename);
        modify_table, s, changed = send_file_change(host, port, id_, root, filemtime, modify_table, filename, s);
        return (modify_table, s, changed);
    else:
        filemtime = os.path.getmtime(filename);
        if (modify_table[filename] != filemtime):
            modify_table, s, changed = send_file_change(host, port, id_, root, filemtime, modify_table, filename, s);
        return (modify_table, s, changed);

def send_end(s):
    print("send end\n")
    ctrl = {
        'cmd': 'end'
    }
    s.send(ctrl)

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

def watch_dir(host, prot, id_, root, ignore):
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
            if name not in ignore:
                modify_table, s, changed = process_file(host, port, id_, root, modify_table, join(r, name), s, changed);
        for ignore_dir in ignore:
            if ignore_dir in dirs:
                dirs.remove(ignore_dir)  # don't visit

    t += time.time()
    print(root, "=", str(int(t*1000)), end='ms, ')
    save_modify_time(modify_table)

    if s is not None:
        end_socket(s);
    return changed

logging.basicConfig(format='%(asctime)s %(levelname)s %(message)s', datefmt='%Y-%m-%d %H:%M:%S', level=logging.DEBUG, filename='app.log')

config = get_config()

if config is None:
    print('config is None')
else:
    host = config['host']
    port = config['port']
    pairs = config['pairs']

    for cs_pair in pairs:
        root = cs_pair['root_client']
        print( "on", root)

    interval = 1;
    sleep = 0;
    changed = False
    while (True):
        i = 0
        for cs_pair in pairs:
            root = cs_pair['root_client']
            ignore = cs_pair['ignore']
            changed |= watch_dir(host, port, i, root, ignore)
            i += 1
        if changed is None:
            break
        if changed:
            changed = False
            sleep = interval;
            print()
        else:
            sleep += 1
        print("\rsleep", sleep, 's', end=' ')
        time.sleep(interval);

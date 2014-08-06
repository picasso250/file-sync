import os,os.path

path = 'd:/gxt-web-ui'

import os
from os.path import join, getsize
for root, dirs, files in os.walk(path):
    print('root', root)
    for name in files:
        print('\t'+join(root, name))
    for ignore_dir in ['yii', 'res', 'views']:
        if ignore_dir in dirs:
            dirs.remove(ignore_dir)  # don't visit CVS directories

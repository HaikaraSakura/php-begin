#!/bin/bash

# シンボリックリンクを作成
rm -rf /var/www/html
ln -s /var/www/public /var/www/html

# 他のコマンドを実行
exec "$@"

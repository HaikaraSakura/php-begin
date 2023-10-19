# ベースイメージを指定
FROM httpd:2.4

WORKDIR /var/www

COPY ./conf/httpd.conf /usr/local/apache2/conf/

# ドキュメントルートのシンボリックリンクを作成
COPY ./create_symlink.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/create_symlink.sh
ENTRYPOINT ["/usr/local/bin/create_symlink.sh"]

CMD ["httpd-foreground"]

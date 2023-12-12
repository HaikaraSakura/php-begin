# ベースイメージを指定
FROM httpd:2.4

WORKDIR /var/www

COPY ./conf/httpd.conf /usr/local/apache2/conf/httpd.conf

CMD ["httpd-foreground"]

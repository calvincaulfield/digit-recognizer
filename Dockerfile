FROM php:7.2-apache

COPY ./html/ /var/www/html/
COPY ./apache2/ports.conf /etc/apache2/
COPY ./apache2/apache2.conf /etc/apache2/
COPY ./apache2/mysite.conf /etc/apache2/sites-available/

RUN a2ensite mysite
RUN service apache2 restart
RUN chown -R www-data:www-data /var/www/html/audio
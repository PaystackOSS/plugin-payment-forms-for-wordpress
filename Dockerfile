FROM wordpress:php7.2

WORKDIR /var/www/html/

COPY . ./wp-content/plugins/paystack

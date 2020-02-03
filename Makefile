
all:
	mkdir -p tmp/vendor
	mkdir -p tmp/cache
	cp -R ./vendor/rakuten-ws ./tmp/vendor
	cp ./*.php ./tmp/

test:
	./vendor/bin/phpunit

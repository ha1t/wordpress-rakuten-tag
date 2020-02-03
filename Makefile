
all:
	mkdir -p tmp/vendor
	cp -R ./vendor/rakuten-ws ./tmp/vendor
	cp ./*.php ./tmp/

test:
	./vendor/bin/phpunit

clean:
	rm -rf ./tmp


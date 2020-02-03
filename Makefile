
all:
	rm -rf ./tmp
	mkdir -p tmp/vendor
	cp -R ./vendor/rakuten-ws ./tmp/vendor
	cp ./*.php ./tmp/

build:
	mv ./tmp ./wordpress-rakuten-tag
	zip -r wordpress-rakuten-tag.zip ./wordpress-rakuten-tag
	mv ./wordpress-rakuten-tag ./tmp

test:
	./vendor/bin/phpunit

clean:
	rm -rf ./tmp


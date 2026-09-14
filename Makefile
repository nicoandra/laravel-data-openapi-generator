
install:
	docker-compose run -e XEBUG_MODE=off --rm openapi-generator composer install

update:
	docker-compose run -e XEBUG_MODE=off --rm openapi-generator composer update

test:
	docker-compose run -e XEBUG_MODE=off --build --rm -v $(PWD):/var/www/html openapi-generator composer run test

test-coverage:
	docker-compose run --rm -v $(PWD):/var/www/html openapi-generator composer run test-coverage

format:
	docker-compose run --rm -v $(PWD):/var/www/html openapi-generator composer run format

dev:
	docker-compose up --build openapi-generator

install:
	docker-compose run --rm openapi-generator composer install

test:
	docker-compose run --build --rm -v $(PWD):/var/www/html openapi-generator composer run test

test-coverage:
	docker-compose run --rm -v $(PWD):/var/www/html openapi-generator composer run test-coverage

format:
	docker-compose run --rm -v $(PWD):/var/www/html openapi-generator composer run format

dev:
	docker-compose up --build openapi-generator
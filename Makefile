
install:
	docker-compose run --rm openapi-generator composer install

test:
	docker-compose run --build --rm -v $(PWD):/var/www/html openapi-generator composer run test


dev:
	docker-compose up --build openapi-generator
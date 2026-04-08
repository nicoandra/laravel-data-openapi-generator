
install:
	docker-compose run --rm php composer install

test:
	docker-compose run --build --rm -v $(PWD):/var/www/html php composer run test
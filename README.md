# VWA skeleton project

## Installation
- Copy sources to a machine with PHP and Composer
- Copy `/.env.example` file to `/.env`. Insert database credentials into it.
- Make `/cache` folder writeable (`chmod 0777 cache`).
- Make `/logs` folder writable too (`chmod 0777 logs`).
- Install project dependencies using `composer install` command.

## Docker
To run project in [Docker](https://www.docker.com/) type `docker-compose up` command
in projet root folder. Docker should open two ports on your machine:

- http://localhost:8080 for your project
- http://localhost:8081 for Adminer

DB connection inside Docker:

- hostname: postgres
- user: postgres
- pass: docker
- database name: db

### First run:
You have to import DB structure using following command:
`docker-compose exec postgres bash /tmp/docker/import.sh`
export $(cat env/env-${HW_ENV})
source secret/secret

docker-compose "$@"
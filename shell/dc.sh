export $(cat env/env-${HW_ENV})
source secret/secret
export SW_ENV=dev

docker-compose "$@"
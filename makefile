THIS_FILE := $(lastword $(MAKEFILE_LIST))
.PHONY: \
dev

dev:
	docker-compose --env-file .env.dev up

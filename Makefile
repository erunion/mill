.PHONY: docs examples

MILL := ./bin/mill
EXAMPLE_MILL_CONFIG := examples/mill.xml
EXAMPLES_DIR := examples/Showtimes/compiled
EXAMPLES_DIR_PUBLIC := $(EXAMPLES_DIR)/public

code-coverage: ## Run code coverage.
	./vendor/bin/phpunit --coverage-html reports/

phpcs: ## Verify code standards.
	./vendor/bin/phpcs --standard=PSR2 bin/ src/ tests/

phpunit: ## Run unit tests.
	./vendor/bin/phpunit

psalm: ## Run static analysis checks.
	./vendor/bin/psalm

test: phpcs psalm phpunit ## Run all checks and unit tests.
	npm test

examples: ## Compile examples.
	rm -rf $(EXAMPLES_DIR)
	mkdir $(EXAMPLES_DIR)
	mkdir $(EXAMPLES_DIR_PUBLIC)
	make examples-apiblueprint
	make examples-openapi
	make examples-changelogs
	make examples-errors

examples-apiblueprint: ## Compile example API Blueprint definitions.
	$(MILL) compile --config=$(EXAMPLE_MILL_CONFIG) --format=apiblueprint --for_public_consumption=true $(EXAMPLES_DIR_PUBLIC)
	$(MILL) compile --config=$(EXAMPLE_MILL_CONFIG) --format=apiblueprint $(EXAMPLES_DIR)

examples-openapi: ## Compile example OpenAPI definitions.
	$(MILL) compile --config=$(EXAMPLE_MILL_CONFIG) --format=openapi --for_public_consumption=true $(EXAMPLES_DIR_PUBLIC)
	$(MILL) compile --config=$(EXAMPLE_MILL_CONFIG) --format=openapi $(EXAMPLES_DIR)

examples-changelogs: ## Compile example changelogs.
	$(MILL) changelog --config=$(EXAMPLE_MILL_CONFIG) --private=false $(EXAMPLES_DIR)
	@mv $(EXAMPLES_DIR)/changelog.md $(EXAMPLES_DIR)/changelog-public-only-all-vendor-tags.md

	$(MILL) changelog --config=$(EXAMPLE_MILL_CONFIG) --private=false --vendor_tag='tag:BUY_TICKETS' --vendor_tag='tag:FEATURE_FLAG' $(EXAMPLES_DIR)
	@mv $(EXAMPLES_DIR)/changelog.md $(EXAMPLES_DIR)/changelog-public-only-matched-with-tickets-and-feature-vendor-tags.md

	$(MILL) changelog --config=$(EXAMPLE_MILL_CONFIG) --private=false --vendor_tag='tag:DELETE_CONTENT' $(EXAMPLES_DIR)
	@mv $(EXAMPLES_DIR)/changelog.md $(EXAMPLES_DIR)/changelog-public-only-matched-with-delete-vendor-tags.md

	$(MILL) changelog --config=$(EXAMPLE_MILL_CONFIG) $(EXAMPLES_DIR)

examples-errors: ## Compile example error compilations.
	$(MILL) errors --config=$(EXAMPLE_MILL_CONFIG) --private=false $(EXAMPLES_DIR)
	@mv $(EXAMPLES_DIR)/1.0/errors.md $(EXAMPLES_DIR)/1.0/errors-public-only-all-vendor-tags.md
	@mv $(EXAMPLES_DIR)/1.1/errors.md $(EXAMPLES_DIR)/1.1/errors-public-only-all-vendor-tags.md
	@mv $(EXAMPLES_DIR)/1.1.1/errors.md $(EXAMPLES_DIR)/1.1.1/errors-public-only-all-vendor-tags.md
	@mv $(EXAMPLES_DIR)/1.1.3/errors.md $(EXAMPLES_DIR)/1.1.3/errors-public-only-all-vendor-tags.md

	$(MILL) errors --config=$(EXAMPLE_MILL_CONFIG) --private=false --vendor_tag='tag:BUY_TICKETS' --vendor_tag='tag:FEATURE_FLAG' $(EXAMPLES_DIR)
	@mv $(EXAMPLES_DIR)/1.0/errors.md $(EXAMPLES_DIR)/1.0/errors-public-only-unmatched-vendor-tags.md
	@mv $(EXAMPLES_DIR)/1.1/errors.md $(EXAMPLES_DIR)/1.1/errors-public-only-unmatched-vendor-tags.md
	@mv $(EXAMPLES_DIR)/1.1.1/errors.md $(EXAMPLES_DIR)/1.1.1/errors-public-only-unmatched-vendor-tags.md
	@mv $(EXAMPLES_DIR)/1.1.3/errors.md $(EXAMPLES_DIR)/1.1.3/errors-public-only-unmatched-vendor-tags.md

	$(MILL) errors --config=$(EXAMPLE_MILL_CONFIG) --private=false --vendor_tag='tag:DELETE_CONTENT' $(EXAMPLES_DIR)
	@mv $(EXAMPLES_DIR)/1.0/errors.md $(EXAMPLES_DIR)/1.0/errors-public-only-matched-vendor-tags.md
	@mv $(EXAMPLES_DIR)/1.1/errors.md $(EXAMPLES_DIR)/1.1/errors-public-only-matched-vendor-tags.md
	@mv $(EXAMPLES_DIR)/1.1.1/errors.md $(EXAMPLES_DIR)/1.1.1/errors-public-only-matched-vendor-tags.md
	@mv $(EXAMPLES_DIR)/1.1.3/errors.md $(EXAMPLES_DIR)/1.1.3/errors-public-only-matched-vendor-tags.md

	$(MILL) errors --config=$(EXAMPLE_MILL_CONFIG) $(EXAMPLES_DIR)

help: ## Show this help.
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

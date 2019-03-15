MILL := ./bin/mill
RESOURCE_MILL_CONFIG := resources/examples/mill.xml
RESOURCE_DIR := resources/examples/Showtimes/compiled
RESOURCE_DIR_PUBLIC := $(RESOURCE_DIR)/public

build-docs-apiblueprint:
	$(MILL) compile --config=$(RESOURCE_MILL_CONFIG) --format=apiblueprint --for_public_consumption=true $(RESOURCE_DIR_PUBLIC)
	$(MILL) compile --config=$(RESOURCE_MILL_CONFIG) --format=apiblueprint $(RESOURCE_DIR)

build-docs-openapi:
	$(MILL) compile --config=$(RESOURCE_MILL_CONFIG) --format=openapi --for_public_consumption=true $(RESOURCE_DIR_PUBLIC)
	$(MILL) compile --config=$(RESOURCE_MILL_CONFIG) --format=openapi $(RESOURCE_DIR)

build-changelogs:
	$(MILL) changelog --config=$(RESOURCE_MILL_CONFIG) --private=false $(RESOURCE_DIR)
	@mv $(RESOURCE_DIR)/changelog.md $(RESOURCE_DIR)/changelog-public-only-all-vendor-tags.md

	$(MILL) changelog --config=$(RESOURCE_MILL_CONFIG) --private=false --vendor_tag='tag:BUY_TICKETS' --vendor_tag='tag:FEATURE_FLAG' $(RESOURCE_DIR)
	@mv $(RESOURCE_DIR)/changelog.md $(RESOURCE_DIR)/changelog-public-only-matched-with-tickets-and-feature-vendor-tags.md

	$(MILL) changelog --config=$(RESOURCE_MILL_CONFIG) --private=false --vendor_tag='tag:DELETE_CONTENT' $(RESOURCE_DIR)
	@mv $(RESOURCE_DIR)/changelog.md $(RESOURCE_DIR)/changelog-public-only-matched-with-delete-vendor-tags.md

	$(MILL) changelog --config=$(RESOURCE_MILL_CONFIG) $(RESOURCE_DIR)

build-errors:
	$(MILL) errors --config=$(RESOURCE_MILL_CONFIG) --private=false $(RESOURCE_DIR)
	@mv $(RESOURCE_DIR)/1.0/errors.md $(RESOURCE_DIR)/1.0/errors-public-only-all-vendor-tags.md
	@mv $(RESOURCE_DIR)/1.1/errors.md $(RESOURCE_DIR)/1.1/errors-public-only-all-vendor-tags.md
	@mv $(RESOURCE_DIR)/1.1.1/errors.md $(RESOURCE_DIR)/1.1.1/errors-public-only-all-vendor-tags.md
	@mv $(RESOURCE_DIR)/1.1.3/errors.md $(RESOURCE_DIR)/1.1.3/errors-public-only-all-vendor-tags.md

	$(MILL) errors --config=$(RESOURCE_MILL_CONFIG) --private=false --vendor_tag='tag:BUY_TICKETS' --vendor_tag='tag:FEATURE_FLAG' $(RESOURCE_DIR)
	@mv $(RESOURCE_DIR)/1.0/errors.md $(RESOURCE_DIR)/1.0/errors-public-only-unmatched-vendor-tags.md
	@mv $(RESOURCE_DIR)/1.1/errors.md $(RESOURCE_DIR)/1.1/errors-public-only-unmatched-vendor-tags.md
	@mv $(RESOURCE_DIR)/1.1.1/errors.md $(RESOURCE_DIR)/1.1.1/errors-public-only-unmatched-vendor-tags.md
	@mv $(RESOURCE_DIR)/1.1.3/errors.md $(RESOURCE_DIR)/1.1.3/errors-public-only-unmatched-vendor-tags.md

	$(MILL) errors --config=$(RESOURCE_MILL_CONFIG) --private=false --vendor_tag='tag:DELETE_CONTENT' $(RESOURCE_DIR)
	@mv $(RESOURCE_DIR)/1.0/errors.md $(RESOURCE_DIR)/1.0/errors-public-only-matched-vendor-tags.md
	@mv $(RESOURCE_DIR)/1.1/errors.md $(RESOURCE_DIR)/1.1/errors-public-only-matched-vendor-tags.md
	@mv $(RESOURCE_DIR)/1.1.1/errors.md $(RESOURCE_DIR)/1.1.1/errors-public-only-matched-vendor-tags.md
	@mv $(RESOURCE_DIR)/1.1.3/errors.md $(RESOURCE_DIR)/1.1.3/errors-public-only-matched-vendor-tags.md

	$(MILL) errors --config=$(RESOURCE_MILL_CONFIG) $(RESOURCE_DIR)

build-resources:
	rm -rf $(RESOURCE_DIR)
	mkdir $(RESOURCE_DIR)
	mkdir $(RESOURCE_DIR_PUBLIC)
	make build-docs-apiblueprint
	make build-docs-openapi
	make build-changelogs
	make build-errors

code-coverage:
	./vendor/bin/phpunit --coverage-html reports/

docs:
	docsify serve docs/

phpcs:
	./vendor/bin/phpcs --standard=PSR2 bin/ src/ tests/

phpunit:
	./vendor/bin/phpunit

psalm:
	./vendor/bin/psalm

test: phpcs psalm phpunit
	npm test

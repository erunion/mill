# Changelog: Mill unit test API, Showtimes

## 1.1.3
### Added
- PATCH on `/movies/{id}` will now return a `404 Not Found` with a `Error` representation: If the trailer URL could not be validated.
- POST on `/movies` now returns a `201 Created`.

### Removed
- `external_urls.tickets` has been removed from the `Movie` representation.

## 1.1.2
### Changed
- GET on `/movie/{id}` will now return a `application/mill.example.movie` content type.
- GET on `/movies/{id}` will now return a `application/mill.example.movie` content type.
- PATCH on `/movies/{id}` will now return a `application/mill.example.movie` content type.
- GET on `/movies` will now return a `application/mill.example.movie` content type.
- POST on `/movies` will now return a `application/mill.example.movie` content type.
- GET on `/theaters/{id}` will now return a `application/mill.example.theater` content type.
- PATCH on `/theaters/{id}` will now return a `application/mill.example.theater` content type.
- GET on `/theaters` will now return a `application/mill.example.theater` content type.
- POST on `/theaters` will now return a `application/mill.example.theater` content type.

## 1.1.1
### Added
- A `imdb` request parameter was added to PATCH on `/movies/{id}`.

## 1.1
### Added
- `external_urls` has been added to the `Movie` representation.
- `external_urls.imdb` has been added to the `Movie` representation.
- `external_urls.tickets` has been added to the `Movie` representation.
- `external_urls.trailer` has been added to the `Movie` representation.
- PATCH on `/movies/{id}` was added.
- A `imdb` request parameter was added to POST on `/movies`.
- A `trailer` request parameter was added to POST on `/movies`.
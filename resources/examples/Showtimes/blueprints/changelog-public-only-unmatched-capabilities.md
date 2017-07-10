# Changelog: Mill unit test API, Showtimes

## 1.1.3 (2017-05-27)
Changed up the responses for `/movie/{id}`, `/movies/{id}` and `/movies`.

### Reference
#### Added
##### Resources
- The `GET` on `/movies/{id}` can now throw the following errors:
    - `404 Not Found` with a `Error` representation: For no reason.
    - `404 Not Found` with a `Error` representation: For some other reason.
- `PATCH` on `/movies/{id}` now returns a `404 Not Found` with a `Error` representation: If the trailer URL could not be validated.
- `PATCH` on `/movies/{id}` now returns a `202 Accepted` with a `Movie` representation.
- `POST` on `/movies` now returns a `201 Created`.

#### Removed
##### Representations
- `external_urls.tickets` has been removed from the `Movie` representation.

## 1.1.2 (2017-04-01)
### Reference
#### Changed
##### Resources
- `/movies/{id}` now returns a `application/mill.example.movie` Content-Type header on the following HTTP methods:
    - `GET`
    - `PATCH`
- `/movies` now returns a `application/mill.example.movie` Content-Type header on the following HTTP methods:
    - `GET`
    - `POST`
- `/theaters/{id}` now returns a `application/mill.example.theater` Content-Type header on the following HTTP methods:
    - `GET`
    - `PATCH`
- `/theaters` now returns a `application/mill.example.theater` Content-Type header on the following HTTP methods:
    - `GET`
    - `POST`

## 1.1.1 (2017-03-01)
### Reference
#### Added
##### Resources
- A `imdb` request parameter was added to `PATCH` on `/movies/{id}`.

## 1.1 (2017-02-01)
### Reference
#### Added
##### Representations
- The following fields have been added to the `Movie` representation:
    - `external_urls`
    - `external_urls.imdb`
    - `external_urls.tickets`
    - `external_urls.trailer`

##### Resources
- `PATCH` on `/movies/{id}` was added.
- A `page` request parameter was added to `GET` on `/movies`.
- The following parameters have been added to `POST` on `/movies`:
    - `imdb`
    - `trailer`

#### Removed
##### Representations
- `website` has been removed from the `Theater` representation.
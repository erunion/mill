# Changelog: Mill unit test API, Showtimes

## 1.1.3 (2017-05-27)
Changed up the responses for `/movie/{id}`, `/movies/{id}` and `/movies`.

### Reference
#### Added
##### Resources
- The following Movies resources have added:
    - `/movie/{id}` will now throw the following errors on `GET` requests:
        - `404 Not Found` with a `Error` representation: For no reason.
        - `404 Not Found` with a `Error` representation: For some other reason.
    - `/movies/{id}` will now throw the following errors on `GET` requests:
        - `404 Not Found` with a `Error` representation: For no reason.
        - `404 Not Found` with a `Error` representation: For some other reason.
    - On `PATCH` requests to `/movies/{id}`, a `404 Not Found` with a `Error` representation will now be returned: If the trailer URL could not be validated.
    - On `/movies/{id}`, `PATCH` requests now returns a `202 Accepted` with a `Movie` representation.
    - `POST` on `/movies` now returns a `201 Created`.

#### Removed
##### Representations
- `external_urls.tickets` has been removed from the `Movie` representation.

## 1.1.2 (2017-04-01)
### Reference
#### Changed
##### Resources
- The following Movies resources have changed:
    - On `/movie/{id}`, `GET` requests will return a `application/mill.example.movie` Content-Type header.
    - On `/movies/{id}`, `GET` requests will return a `application/mill.example.movie` Content-Type header.
    - On `/movies/{id}`, `PATCH` requests will return a `application/mill.example.movie` Content-Type header.
    - On `/movies`, `GET` requests will return a `application/mill.example.movie` Content-Type header.
    - On `/movies`, `POST` requests will return a `application/mill.example.movie` Content-Type header.
- The following Theaters resources have changed:
    - On `/theaters/{id}`, `GET` requests will return a `application/mill.example.theater` Content-Type header.
    - On `/theaters/{id}`, `PATCH` requests will return a `application/mill.example.theater` Content-Type header.
    - On `/theaters`, `GET` requests will return a `application/mill.example.theater` Content-Type header.
    - On `/theaters`, `POST` requests will return a `application/mill.example.theater` Content-Type header.

## 1.1.1 (2017-03-01)
### Reference
#### Added
##### Resources
- The following Movies resources have added:
    - A `imdb` request parameter was added to `PATCH` on `/movies/{id}`.

## 1.1 (2017-02-01)
### Reference
#### Added
##### Representations
- The `Movie` representation has added the following fields:
    - `external_urls`
    - `external_urls.imdb`
    - `external_urls.tickets`
    - `external_urls.trailer`

##### Resources
- The following Movies resources have added:
    - `/movies/{id}` has been added with support for the following HTTP methods:
        - `PATCH`
        - `DELETE`
    - A `page` request parameter was added to `GET` on `/movies`.
    - The following parameters have been added to `POST` on `/movies`:
        - `imdb`
        - `trailer`

#### Removed
##### Representations
- `website` has been removed from the `Theater` representation.
# Errors: Mill unit test API, Showtimes

## Movies
| Error Code | Path | Method | HTTP Code | Description |
| :--- | :--- | :--- | :--- | :--- |
| 666 | /movies/{id} | PATCH | 403 Forbidden | If the user is not allowed to edit that movie. |
| 1337 | /movies/{id} | PATCH | 403 Forbidden | If something cool happened. |
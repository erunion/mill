# Errors: Mill unit test API, Showtimes

## Movies
| URI | Method | HTTP Code | Error Code | Description |
| :--- | :--- | :--- | :--- | :--- |
| `/movies/{id}` | PATCH | 403 Forbidden | 666 | If the user is not allowed to edit that movie. |
| `/movies/{id}` | PATCH | 403 Forbidden | 1337 | If something cool happened. |
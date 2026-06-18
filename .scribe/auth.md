# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {your-santum-token}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

You can retrieve your token by logging in via <code>POST /api/v1/auth/login</code> and using the <code>token</code> from the response.

# WebHooks

WebHooks are the main method of communication between NinjaForms.com, as a service, and the distributed Ninja Forms plugin.

Every request should be signed using the OAuth client registered by the end user's WordPress installation with NinjaForms.com.

## Terminology

**Ninja Forms** - The singleton instance of the main Ninja Forms plugin class.

**Controller** - The object responsible for processing a WebHook request. See: `includes/WebHooks/Webhook.php`.

**Router** - The object responsible for instantiating a WebHook Controller. See: `includes/WebHooks/Router.php`.

**Response** - The object responsible for returning an HTTP Status Code and JSON data. See: `includes/WebHooks/Response.php`.

## Security

All requests must be signed using a `hash` parameter which is generated using the `payload`, the OAuth Client ID, and the OAuth Client Secret.

The **Router** is the single point of entry for the WebHooks `payload` and `hash`, which is responsible for verifying the `hash` before passing the `payload` to a **Controller**.

## Flow

**Ninja Forms** instantiates the **Router**, specifying the requested WebHook and the available WebHook **Controllers**.

The **Router** verifies the `hash`, instantiates a **Response** and the appropriate **Controller** (with the `payload`).

The **Controller** processes the passed `payload` and manipulates the **Response**.

### Routing

The **Router** accepts a specified `$webhook` (route) name and an array of WebHook Controllers. After instantiating the appropriate controller, the `$hash` is verified against the `$payload` and `$client_secret` before the controller is passed the `$payload` for processing.

### Controller

The **Controller** is the processing object for a WebHook request and should implement the WebHook Interface (See: `includes/Webhooks/Webhook.php`).

Each **Controller** must contain a `process()` method by which the `payload` and a **Response** object are passed.

If the **Controller* requires that processing be run inside of a specific WordPress hook, then the `process()` method should add the hook with a method callback (making the `payload` data accessible as a property of the class).

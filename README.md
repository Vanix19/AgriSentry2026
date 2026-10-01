# AgriSentry

Goat health monitoring with a Laravel web dashboard, an Expo mobile app, and LoRaWAN collar integration.

- [Web application setup](Agrisentry/README.md)
- [Mobile application setup](agrisentry-mobile/README.md)
- [Heltec motion and battery packet integration](Agrisentry/firmware/HELTEC-INTEGRATION.md)

Copy each application's `.env.example` to `.env` and configure your own database and service credentials. Secrets, local databases, uploaded records, and dependencies are excluded from this repository.

GitHub stores the source. The Laravel application requires PHP hosting, a database, and configured background workers; it cannot run on GitHub Pages.

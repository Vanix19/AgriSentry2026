# Third-Party Notices

This project includes or loads the following third-party resources. PHP and
JavaScript packages managed via Composer (`composer.json`/`composer.lock`)
and npm (`package.json`) are not repeated here — their licenses are declared
in their respective package metadata under `vendor/` and `node_modules/`.

| Resource | Author / Source | License | Used in |
|---|---|---|---|
| [QRCode for JavaScript](http://www.d-project.com/) | davidshimjs (see also [jquery-qrcode](http://jeromeetienne.github.com/jquery-qrcode/)) | MIT | Vendored at `public/js/qrcode.min.js`; loaded on `goat-profile.blade.php` and `agrisentry.blade.php` |
| [Chart.js](https://www.chartjs.org/) | Chart.js Contributors | MIT | Loaded via CDN (`cdn.jsdelivr.net/npm/chart.js@4`) on `goat-profile.blade.php` |
| [DM Sans](https://fonts.google.com/specimen/DM+Sans) & [DM Mono](https://fonts.google.com/specimen/DM+Mono) | Colophon Foundry / Google Fonts | SIL Open Font License 1.1 | Loaded via Google Fonts (`fonts.googleapis.com`) on `agrisentry.blade.php`, `admin-users.blade.php`, `role-select.blade.php`, `species-select.blade.php`, `auth/login.blade.php` |
| [Instrument Sans](https://fonts.google.com/specimen/Instrument+Sans) | Rick Banks / Bunny Fonts | SIL Open Font License 1.1 | Loaded via Bunny Fonts (`fonts.bunny.net`) on `welcome.blade.php` |

## License texts

- **MIT License**: permits reuse with attribution and without warranty. Full text: https://opensource.org/license/mit
- **SIL Open Font License 1.1**: permits use, study, modification, and redistribution of the fonts, bundled or unbundled, provided the font names are not sold on their own and derivative fonts are also released under the OFL. Full text: https://openfontlicense.org

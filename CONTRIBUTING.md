# Contributing

Thank you for helping improve the EasySlip SDK for PHP. This guide explains how to set up the project, run the checks and send a change.

## Before you start

- For a bug, open an [issue](https://github.com/EASYSLIP-CO-LTD/sdk-php/issues) with the SDK version, your PHP version and the steps to reproduce it.
- For a new feature or a change to the public API, open an issue first so we can agree on the design.
- For a security problem, follow [SECURITY.md](SECURITY.md) instead of opening an issue.
- Never put a real API key, webhook secret or slip image in an issue, a commit or a test.

## Set up

You need PHP 8.2, 8.3, 8.4 or 8.5 with the `curl`, `fileinfo`, `mbstring` and `xml` extensions, and Composer 2.

```sh
git clone https://github.com/EASYSLIP-CO-LTD/sdk-php.git
cd sdk-php
composer update
```

The repository has no `composer.lock`, because a library's lock file is never used by the projects that install it. `composer update` installs the newest versions your PHP version allows, which is what CI tests.

## Checks

Run these before you open a pull request. CI runs the same commands on every supported PHP version. `composer check` runs all of them except the package check.

| Command | What it checks |
| --- | --- |
| `composer lint` | The syntax of every PHP file on your PHP version. |
| `composer format:check` | Formatting with PHP CS Fixer (PER Coding Style 3.0). `composer format` fixes it. |
| `composer analyse` | Types in `src` and `examples` with PHPStan at the max level. |
| `composer check:readme` | Every PHP code block in `README.md` and `README.th.md` has valid syntax and passes PHPStan at the max level. |
| `composer test` | Unit tests in `tests/Unit` and the conformance tests in `tests/Conformance`, which replay the recorded API responses in `tests/fixtures`. |
| `composer test:coverage` | The same tests with a coverage report (needs the `pcov` or `xdebug` extension). |
| `composer check:package` | Builds the package archive from the committed `HEAD`, installs it in a new project and makes an offline call with Guzzle and Symfony's HTTP client. |

## Integration tests

`composer test:integration` calls the live API, so it needs your own API key. Every test is skipped when no key is set.

| Variable | What it does |
| --- | --- |
| `EASYSLIP_API_KEY` or `EASYSLIP_API_KEY_FILE` | Your API key. |
| `EASYSLIP_V2_BASE_URL`, `EASYSLIP_V1_BASE_URL` | Another API host, if you test against one. |
| `EASYSLIP_TEST_PAYLOADS_FILE` | A file with QR payloads of slips this branch has already verified, one per line. Enables the verification tests. |
| `EASYSLIP_TEST_QR_DIR` | A folder with `qr-0.png` and `qr-1.png` slip images that match those payloads. |
| `EASYSLIP_TEST_CALLBACK_URL` | An https URL that accepts webhooks. Enables the background job tests. |
| `EASYSLIP_TEST_WRITE=1` | Allows the tests to create a test bank account. |

Verifications use your quota. Use slips you are allowed to verify, and never commit them.

## Pull requests

- Keep each pull request to one change, and add or update tests for it.
- Update `README.md` and `README.th.md` when the public API or its behaviour changes, and run `composer check:readme`.
- Write the pull request title as a [Conventional Commit](https://www.conventionalcommits.org/en/v1.0.0/), for example `fix: keep the request id on network errors` or `feat: add bankAccounts->delete()`. Pull requests are squash merged, and the title becomes the commit message.
- `feat` adds a minor version, `fix` and `perf` add a patch version, and a `!` after the type (for example `feat!:`) marks a breaking change.

## Releases

Maintainers release the SDK with [release-please](https://github.com/googleapis/release-please). Everything runs in one workflow, [`.github/workflows/release.yml`](.github/workflows/release.yml), and Packagist reads each new tag through its GitHub hook:

1. Every merge to `main` updates a release pull request with the next version and the changelog. GitHub does not start workflows for a pull request that a workflow updated, so the release job starts CI on the release branch itself, and the checks show on the release pull request. If they are missing, run the `CI` workflow on the `release-please--branches--main` branch from the Actions tab.
2. Merging the release pull request creates the version tag and a draft GitHub Release. The same workflow run then checks that the tag points at the release commit on `main` and matches the release manifest and the User-Agent version, runs every check, and builds the archive.
3. The `publish` job runs in the `release` environment. It waits until Packagist lists the new version for the tagged commit, attaches the archive and its build attestation to the draft release and publishes the release. The `smoke` job then installs that version from Packagist on every supported PHP version.

To check a tag again, for example after a failed publish job, run the `Release` workflow on `main` from the Actions tab and enter the tag, such as `v1.0.0`. The run checks the tag the same way and leaves a published release as it is.

### One-time setup

- Submit `https://github.com/EASYSLIP-CO-LTD/sdk-php` on packagist.org as the `easyslip/sdk` package, and connect Packagist to GitHub so that it updates the package when a tag is pushed. No Packagist token is stored in this repository.
- In the repository settings, create the `release` environment. Limit its deployment branches to `main`, and add required reviewers if a person should approve each publish. While a publish waits for approval, later runs of the `Release` workflow on `main` wait for it.
- Optional: create an `integration` environment with an `EASYSLIP_API_KEY` secret, so that CI runs the integration tests on `main`.

# Authentication

This is the AUTH-01 audit of FinTrack's generated Laravel Vue starter. Fortify handles the browser authentication flows with the `web` guard. The application supplies Inertia pages, registration and password reset actions, and account settings controllers. Registration, password reset, email verification, two-factor authentication (2FA), and passkeys are enabled.

## Routes and PHPUnit coverage

| Flow | Routes and current behavior | Existing test coverage |
| --- | --- | --- |
| Registration | Guests use `GET /register` and `POST /register`. `Register.vue` submits the name, email, password, and password confirmation. Fortify lowercases the email before [`CreateNewUser`](../app/Actions/Fortify/CreateNewUser.php) validates the input and creates the account. It signs in the new user and redirects to `/dashboard`. | [`RegistrationTest`](../tests/Feature/Auth/RegistrationTest.php) checks that the form loads, a valid submission signs in the user, and duplicate addresses are rejected even when the submitted address uses a different case. |
| Password sign-in | Guests use `GET /login` and `POST /login`. `Login.vue` submits the email and password and offers a remember-me option. `POST /login` uses the `login` limiter, which allows five requests per minute per normalized email and IP address. | [`AuthenticationTest`](../tests/Feature/Auth/AuthenticationTest.php) checks sign-in with mixed-case addresses and the same error for an incorrect password or an unknown email. It also checks the 2FA redirect, HTTP 429 on the sixth request, and that requests are processed again after the one-minute window. |
| Sign-out | Authenticated users submit `POST /logout`. Fortify signs out the user, invalidates the session, regenerates the CSRF token, and redirects to the home page. | `AuthenticationTest` checks the home redirect, removal of session data, and redirection to login when the signed-out user opens the dashboard. |
| Password reset | Guests request a link through `GET` and `POST /forgot-password`. `GET /reset-password/{token}` shows the reset form and `POST /reset-password` submits the token, email, and new password. [`ResetUserPassword`](../app/Actions/Fortify/ResetUserPassword.php) validates and saves the password. The reset broker is configured for a 60-minute token lifetime. | [`PasswordResetTest`](../tests/Feature/Auth/PasswordResetTest.php) checks both forms, reset notification dispatch, sign-in with the new password after a valid reset, and rejection of the old password and invalid or expired reset tokens. |
| Email verification | Authenticated users see the prompt at `GET /email/verify`. Verification uses `GET /email/verify/{id}/{hash}` with signed URL validation and throttling. `POST /email/verification-notification` resends the link and is also throttled. | [`EmailVerificationTest`](../tests/Feature/Auth/EmailVerificationTest.php) checks the prompt, successful verification, rejection of wrong or expired links, and repeat visits after verification. [`VerificationNotificationTest`](../tests/Feature/Auth/VerificationNotificationTest.php) checks when the resend action dispatches a verification notification. |
| Dashboard | `GET /dashboard` requires authentication and a verified email. A registration redirect points to this route. An unverified user is sent to the verification prompt when following it. | [`DashboardTest`](../tests/Feature/DashboardTest.php) checks that guests go to login, signed-in users with an unverified email go to the verification prompt, and verified users can open the dashboard. |
| Password confirmation | `GET` and `POST /user/confirm-password` show and submit the confirmation form for authenticated users. `GET /user/confirmed-password-status` reports confirmation status. | [`PasswordConfirmationTest`](../tests/Feature/Auth/PasswordConfirmationTest.php) checks that the page renders and a guest is redirected to login. `SecurityTest` checks the redirect to confirmation when the security page requires it. |
| Two-factor authentication | `GET` and `POST /two-factor-challenge` handle the sign-in challenge. The POST uses a separate limiter. Authenticated users manage setup, confirmation, QR code, secret key, and recovery codes through `/user/*` routes protected by password confirmation. | `AuthenticationTest` checks initiation of the challenge. [`TwoFactorChallengeTest`](../tests/Feature/Auth/TwoFactorChallengeTest.php) checks the page and redirection when there is no pending sign-in. `SecurityTest` checks the security page with 2FA enabled and disabled as a feature. |
| Passkeys | Passkey sign-in is available from `Login.vue` through `/passkeys/login` and `/passkeys/login/options`. Passkey registration and deletion are available from security settings through `/user/passkeys` routes and require password confirmation. `/passkeys/confirm` routes support passkey confirmation for signed-in users. | The reviewed application tests contain no complete passkey sign-in, registration, or deletion scenario. |
| Profile | Authenticated users visit `GET /settings/profile` and update their own name and email through `PATCH /settings/profile`. The request lowercases the email before checking uniqueness. The endpoint also accepts optional `locale` and `timezone` values. The Vue form currently submits name and email. [`ProfileController`](../app/Http/Controllers/Settings/ProfileController.php) updates the current user and clears `email_verified_at` when the email changes. | [`ProfileUpdateTest`](../tests/Feature/Settings/ProfileUpdateTest.php) checks valid profile and preference updates, guest redirection, validation errors for invalid profile data, preservation of protected fields, and case-insensitive email uniqueness. |
| Security and appearance | `GET /settings/security`, `PUT /settings/password`, and `GET /settings/appearance` require authentication and a verified email. The security page uses password confirmation. The password update requires the current password and is throttled. | [`SecurityTest`](../tests/Feature/Settings/SecurityTest.php) checks the security page, password confirmation, storage of the new password hash, and rejection of an incorrect current password. |
| Account deletion | Self-service account deletion is temporarily disabled. The profile page omits the deletion control. [`ProfileDeleteRequest`](../app/Http/Requests/Settings/ProfileDeleteRequest.php) rejects authenticated, verified users with HTTP 403 before password validation or deletion. | `ProfileUpdateTest` checks that a request with the correct password is forbidden, the account still exists, and the user stays signed in. |

The [Fortify provider](../app/Providers/FortifyServiceProvider.php) maps the actions, rate limiters, and Inertia pages in [`resources/js/pages/auth`](../resources/js/pages/auth). The profile, security, and appearance pages live in [`resources/js/pages/settings`](../resources/js/pages/settings). Route middleware is defined by [Fortify configuration](../config/fortify.php), [web routes](../routes/web.php), and [settings routes](../routes/settings.php). The reset token lifetime is defined in [`config/auth.php`](../config/auth.php).

## User model

[`User`](../app/Models/User.php) allows mass assignment of `name`, `email`, `password`, `locale`, and `timezone`. The `hashed` cast hashes passwords on assignment. Serialization omits the password, remember token, 2FA secret, and recovery codes. The model implements Laravel's email verification contract, stores verification state in `email_verified_at`, and supports 2FA and passkeys.

### User preferences

The database supplies both defaults for existing and newly registered users through the AUTH-02 migration.

| Field | Default | Accepted values |
| --- | --- | --- |
| `locale` | `hr-HR` | `hr-HR` and `en-GB` |
| `timezone` | `Europe/Zagreb` | Named time zones accepted by Laravel, such as `Europe/Zagreb` and `UTC` |

[`ProfileUpdateRequest`](../app/Http/Requests/Settings/ProfileUpdateRequest.php) validates each preference when it is supplied. Omitted preferences retain their saved values. Empty, null, and unsupported values are rejected.

These are personal preferences for display. The financial business date uses `Europe/Zagreb`, and posting timestamps are recorded in UTC. See the [architecture notes](ARCHITECTURE.md).

## Verification results

For AUTH-01, the developer supplied the route inventory from `php artisan route:list -v` and the following test results. The [base test case](../tests/TestCase.php) requires the `pgsql` driver and verifies that both the current PostgreSQL database and role are `fintrack_test`.

| Command | Reported result |
| --- | --- |
| `php artisan test` | 40 passed, 138 assertions, 3.61 s |
| `php artisan test --filter=PasswordResetTest` | 5 passed, 10 assertions, 1.47 s |
| `php artisan test --filter=EmailVerificationTest` | 7 passed, 18 assertions, 0.69 s |

## Local authentication checks

Set `MAIL_MAILER=log` in the local environment. Open the app at the scheme, host, and port configured in `APP_URL`. With the default log channel, watch `storage/logs/laravel.log` for the messages.

1. Register a local test account and open the verification link from the log in the same signed-in browser. Confirm that the dashboard opens.
2. Sign out, request a reset through **Forgot password**, and open the reset link from the log. Set a new password and sign in with it.

Run `php artisan test --filter=ProfileUpdateTest` to check profile update and account deletion behavior on the configured PostgreSQL test database.

## Follow-up work

Keep self-service deletion disabled until it can preserve household ownership and financial history.

- **HH-06** will cover a sole household owner. An attempted account deletion must preserve the user and the household ownership record while no other owner exists.
- **LED-08** will cover users referenced by financial history. An attempted account deletion must preserve the user and the linked financial records.

The current 2FA tests stop at the challenge screen. The app test suite has no complete passkey flow.

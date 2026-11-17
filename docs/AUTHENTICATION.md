# Authentication

This is the AUTH-01 audit of FinTrack's generated Laravel Vue starter. Fortify handles the browser authentication flows with the `web` guard. The application supplies Inertia pages, registration and password reset actions, and account settings controllers. Registration, password reset, email verification, two-factor authentication (2FA), and passkeys are enabled.

## Routes and PHPUnit coverage

| Flow | Routes and current behavior | Existing test coverage |
| --- | --- | --- |
| Registration | Guests use `GET /register` and `POST /register`. `Register.vue` submits the name, email, password, and password confirmation. Fortify lowercases the email before [`CreateNewUser`](../app/Actions/Fortify/CreateNewUser.php) validates the input and creates the account. It signs in the new user and redirects to `/dashboard`. | [`RegistrationTest`](../tests/Feature/Auth/RegistrationTest.php) checks that the form loads and a valid submission signs in the user and redirects. |
| Password sign-in | Guests use `GET /login` and `POST /login`. `Login.vue` submits the email and password and offers a remember-me option. `POST /login` uses the `login` limiter, which allows five requests per minute per normalized email and IP address. | [`AuthenticationTest`](../tests/Feature/Auth/AuthenticationTest.php) checks the form, successful sign-in, rejection of an incorrect password, redirection to a 2FA challenge, and HTTP 429 when the test preloads the limiter to its threshold. |
| Sign-out | Authenticated users submit `POST /logout`. Fortify signs out the user, invalidates the session, regenerates the CSRF token, and redirects to the home page. | `AuthenticationTest` checks the home redirect and that the user is a guest after the request. |
| Password reset | Guests request a link through `GET` and `POST /forgot-password`. `GET /reset-password/{token}` shows the reset form and `POST /reset-password` submits the token, email, and new password. [`ResetUserPassword`](../app/Actions/Fortify/ResetUserPassword.php) validates and saves the password. The reset broker is configured for a 60-minute token lifetime. | [`PasswordResetTest`](../tests/Feature/Auth/PasswordResetTest.php) checks both forms, the password reset notification with `Notification::fake()`, a successful response for a valid token, and a validation error for an invalid token. |
| Email verification | Authenticated users see the prompt at `GET /email/verify`. Verification uses `GET /email/verify/{id}/{hash}` with signed URL validation and throttling. `POST /email/verification-notification` resends the link and is also throttled. | [`EmailVerificationTest`](../tests/Feature/Auth/EmailVerificationTest.php) checks the prompt, redirection from a verified-only page, successful verification, rejection of a wrong hash or user ID, and repeat visits after verification. [`VerificationNotificationTest`](../tests/Feature/Auth/VerificationNotificationTest.php) checks that the resend action requests a notification for an unverified user and skips it for a verified user. |
| Dashboard | `GET /dashboard` requires authentication and a verified email. A registration redirect points to this route. An unverified user is sent to the verification prompt when following it. | [`DashboardTest`](../tests/Feature/DashboardTest.php) checks the guest redirect to login and access for a verified user. `EmailVerificationTest` checks the unverified redirect using `/settings/appearance`, which has the same `verified` middleware. |
| Password confirmation | `GET` and `POST /user/confirm-password` show and submit the confirmation form for authenticated users. `GET /user/confirmed-password-status` reports confirmation status. | [`PasswordConfirmationTest`](../tests/Feature/Auth/PasswordConfirmationTest.php) checks that the page renders and a guest is redirected to login. `SecurityTest` checks the redirect to confirmation when the security page requires it. |
| Two-factor authentication | `GET` and `POST /two-factor-challenge` handle the sign-in challenge. The POST uses a separate limiter. Authenticated users manage setup, confirmation, QR code, secret key, and recovery codes through `/user/*` routes protected by password confirmation. | `AuthenticationTest` checks initiation of the challenge. [`TwoFactorChallengeTest`](../tests/Feature/Auth/TwoFactorChallengeTest.php) checks the page and redirection when there is no pending sign-in. `SecurityTest` checks the security page with 2FA enabled and disabled as a feature. |
| Passkeys | Passkey sign-in is available from `Login.vue` through `/passkeys/login` and `/passkeys/login/options`. Passkey registration and deletion are available from security settings through `/user/passkeys` routes and require password confirmation. `/passkeys/confirm` routes support passkey confirmation for signed-in users. | The reviewed application tests contain no complete passkey sign-in, registration, or deletion scenario. |
| Profile | Authenticated users visit `GET /settings/profile` and update their own name and email through `PATCH /settings/profile`. The endpoint also accepts optional `locale` and `timezone` values. The Vue form currently submits name and email. [`ProfileController`](../app/Http/Controllers/Settings/ProfileController.php) updates the current user and clears `email_verified_at` when the email changes. | [`ProfileUpdateTest`](../tests/Feature/Settings/ProfileUpdateTest.php) checks the page, changes to name and email, and preservation of verification when the email stays the same. It also checks valid preference updates, rejection of unsupported or null values, and protection of the password and verification timestamp. |
| Security and appearance | `GET /settings/security`, `PUT /settings/password`, and `GET /settings/appearance` require authentication and a verified email. The security page uses password confirmation. The password update requires the current password and is throttled. | [`SecurityTest`](../tests/Feature/Settings/SecurityTest.php) checks the security page, password confirmation, storage of the new password hash, and rejection of an incorrect current password. |
| Account deletion | `DELETE /settings/profile` requires authentication, a verified email, and the current password. `ProfileController` signs out and deletes the current user, invalidates the session, and regenerates the CSRF token. | `ProfileUpdateTest` checks that a correct password deletes the database row, signs out the user, and redirects to the home page. An incorrect password returns a validation error to the profile page and leaves the user in the database. |

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

## Follow-up work

- In **AUTH-03**, exercise duplicate registration with the same email and case variants. Verify that real sign-in requests reach the throttle limit and define the expected behavior for invalid credentials.
- In **AUTH-04**, test expired password reset tokens and expired verification links. After a reset, verify that the new password works and the old one fails. Check the local mail output for reset and verification links. Verify session invalidation on logout and direct access to protected routes. The current 2FA tests stop at the challenge screen and the app test suite has no complete passkey flow.
- In **AUTH-05**, cover unauthorized and invalid profile updates. Define the initial rule for account deletion so it cannot silently remove financial history or leave a household without an owner. Add domain-specific regression tests as the household and financial models are introduced.
